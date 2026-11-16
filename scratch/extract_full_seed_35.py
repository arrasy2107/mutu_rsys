import os
import zipfile
import xml.etree.ElementTree as ET
import json
import re

folder = r"C:\laragon\www\mutu_rsys\public\Sample Data\Form Pengumpul Data"
files = [f for f in sorted(os.listdir(folder)) if f.endswith(".xlsx") and not f.startswith("~$")]

def parse_shared_strings(z):
    ss = []
    if 'xl/sharedStrings.xml' in z.namelist():
        tree = ET.fromstring(z.read('xl/sharedStrings.xml'))
        for si in tree.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}si'):
            t = si.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}t')
            if t is not None and t.text:
                ss.append(t.text)
            else:
                parts = [r.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}t').text 
                         for r in si.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}r') 
                         if r.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}t') is not None 
                         and r.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}t').text]
                ss.append("".join(parts))
    return ss

def get_sheet_cells_raw(z, target_sheet_name, ss):
    wb_tree = ET.fromstring(z.read('xl/workbook.xml'))
    sheets_elem = wb_tree.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}sheets')
    r_id = None
    for s in sheets_elem.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}sheet'):
        if s.get('name').strip().lower() == target_sheet_name.strip().lower():
            r_id = s.get('{http://schemas.openxmlformats.org/officeDocument/2006/relationships}id')
            break
    if not r_id:
        return []

    rel_xml = ET.fromstring(z.read('xl/_rels/workbook.xml.rels'))
    rels = {r.get('Id'): r.get('Target') for r in rel_xml.findall('{http://schemas.openxmlformats.org/package/2006/relationships}Relationship')}
    target = rels.get(r_id)
    sheet_path = 'xl/' + target if not target.startswith('xl/') else target
    sheet_path = sheet_path.replace('//', '/')
    if sheet_path not in z.namelist():
        return []

    sheet_tree = ET.fromstring(z.read(sheet_path))
    sheet_data = sheet_tree.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}sheetData')
    if sheet_data is None:
        return []

    rows = []
    for row in sheet_data.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}row'):
        row_cells = []
        for c in row.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}c'):
            r_coord = c.get('r')
            t = c.get('t')
            v_elem = c.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}v')
            val = ""
            if v_elem is not None and v_elem.text:
                if t == 's':
                    val = ss[int(v_elem.text)] if int(v_elem.text) < len(ss) else ""
                else:
                    val = v_elem.text
            row_cells.append((r_coord, val))
        if any(c[1] for c in row_cells):
            rows.append(row_cells)
    return rows

seed_data = {}

for fname in files:
    base = fname.replace(".xlsx", "")
    fpath = os.path.join(folder, fname)
    with zipfile.ZipFile(fpath, 'r') as z:
        ss = parse_shared_strings(z)
        wb_tree = ET.fromstring(z.read('xl/workbook.xml'))
        sheets = [s.get('name') for s in wb_tree.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}sheets').findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}sheet')]
        
        # PROFIL sheet inspection
        p_sheet = next((s for s in sheets if 'profil' in s.lower()), None)
        judul = ""
        num = ""
        den = ""
        target = "100%"
        operator = ">="
        formula = "(Numerator / Denominator) * 100%"
        
        if p_sheet:
            p_cells = get_sheet_cells_raw(z, p_sheet, ss)
            for r in p_cells:
                r_text = [c[1].strip() for c in r if c[1].strip()]
                for idx, txt in enumerate(r_text):
                    t_low = txt.lower()
                    if ('judul' in t_low or 'nama indikator' in t_low) and not judul:
                        if idx + 1 < len(r_text):
                            judul = r_text[idx+1]
                    elif ('pembilang' in t_low or 'numerator' in t_low) and not num:
                        if idx + 1 < len(r_text):
                            num = r_text[idx+1]
                    elif ('penyebut' in t_low or 'denominator' in t_low) and not den:
                        if idx + 1 < len(r_text):
                            den = r_text[idx+1]
                    elif ('target' in t_low or 'standar' in t_low) and not target or target == "100%":
                        if idx + 1 < len(r_text):
                            target = r_text[idx+1]

        # Form sheet inspection
        form_sheet = next((s for s in sheets if 'form' in s.lower() or 'tally' in s.lower() or 'kunjungan' in s.lower()), sheets[0])
        f_cells = get_sheet_cells_raw(z, form_sheet, ss)
        
        # Extract headers from form sheet
        all_text = " ".join([c[1] for r in f_cells[:15] for c in r]).lower()
        
        # Classify archetype
        arch = "daily_grid"
        has_rm = any(term in all_text for term in ["no rm", "no. rm", "rekam medik", "nama pasien", "sbar", "tbak", "gelang", "identifikasi"])
        has_doctor_or_item = any(term in all_text for term in ["nama dokter", "nama barang", "parameter", "limbah", "ipal"])
        has_monthly_only = ("bulan" in all_text or "januari" in all_text) and not any(f"tgl {i}" in all_text for i in range(2, 28)) and not has_rm

        if has_rm:
            arch = "dynamic_rows"
        elif has_doctor_or_item:
            arch = "fixed_rows"
        elif has_monthly_only or base in ['CASEMIX', 'DIKLIT', 'KEPEGAWAIAN', 'PEMELIHARAAN SARANA UMUM', 'TU', 'BENDAHARA', 'GUDANG UMUM']:
            arch = "monthly_summary"
        else:
            arch = "daily_grid"

        # Headers preview
        headers = []
        for r in f_cells[:8]:
            row_items = [c[1].strip() for c in r if c[1].strip()]
            if len(row_items) >= 2:
                headers.append(row_items)

        seed_data[base] = {
            'file': fname,
            'archetype': arch,
            'judul': judul,
            'numerator': num,
            'denominator': den,
            'target': target,
            'headers_sample': headers[:3]
        }

with open(r"c:\laragon\www\mutu_rsys\scratch\seed_ready_35.json", "w", encoding="utf-8") as f:
    json.dump(seed_data, f, indent=2, ensure_ascii=False)

print("Extraction complete, results written to scratch/seed_ready_35.json")
