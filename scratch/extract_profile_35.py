import os
import zipfile
import xml.etree.ElementTree as ET
import json

folder = r"C:\laragon\www\mutu_rsys\public\Sample Data\Form Pengumpul Data"
files = [f for f in sorted(os.listdir(folder)) if f.endswith(".xlsx") and not f.startswith("~$")]

def get_sheet_cells(z, target_sheet_name, ss):
    # find sheet
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

results = []

for fname in files:
    fpath = os.path.join(folder, fname)
    with zipfile.ZipFile(fpath, 'r') as z:
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

        wb_tree = ET.fromstring(z.read('xl/workbook.xml'))
        sheets = [s.get('name') for s in wb_tree.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}sheets').findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}sheet')]
        
        # Check PROFIL
        profil_sheet = next((s for s in sheets if 'profil' in s.lower()), None)
        judul = ""
        num = ""
        den = ""
        target = ""
        formula = ""
        
        if profil_sheet:
            p_rows = get_sheet_cells(z, profil_sheet, ss)
            for r in p_rows:
                row_str = " ".join([c[1] for c in r])
                for idx, c in enumerate(r):
                    txt = c[1].strip().lower()
                    if 'judul' in txt or 'nama indikator' in txt:
                        if idx + 1 < len(r) and r[idx+1][1]:
                            judul = r[idx+1][1]
                    elif 'pembilang' in txt or 'numerator' in txt:
                        if idx + 1 < len(r) and r[idx+1][1]:
                            num = r[idx+1][1]
                    elif 'penyebut' in txt or 'denominator' in txt:
                        if idx + 1 < len(r) and r[idx+1][1]:
                            den = r[idx+1][1]
                    elif 'target' in txt or 'standar' in txt:
                        if idx + 1 < len(r) and r[idx+1][1]:
                            target = r[idx+1][1]
                            
        # If still empty, check form pengumpul data headers
        form_sheet = next((s for s in sheets if 'form' in s.lower() or 'tally' in s.lower()), sheets[0])
        f_rows = get_sheet_cells(z, form_sheet, ss)
        f_header = []
        for r in f_rows[:6]:
            row_str = " | ".join([c[1] for c in r if c[1]])
            if row_str:
                f_header.append(row_str)

        results.append({
            'file': fname,
            'unit': fname.replace(".xlsx", ""),
            'profil_found': bool(profil_sheet),
            'judul': judul,
            'num': num,
            'den': den,
            'target': target,
            'form_sheet': form_sheet,
            'header_preview': f_header[:3]
        })

with open(r"c:\laragon\www\mutu_rsys\scratch\profile_summary_35.json", "w", encoding="utf-8") as f:
    json.dump(results, f, indent=2, ensure_ascii=False)

print("Saved profile summary for 35 units!")
