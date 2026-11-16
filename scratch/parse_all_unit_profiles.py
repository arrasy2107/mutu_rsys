import zipfile
import xml.etree.ElementTree as ET
import os
import json
import re

folder = r"C:\laragon\www\mutu_rsys\public\Sample Data\Form Pengumpul Data"
files = [f for f in sorted(os.listdir(folder)) if f.endswith(".xlsx") and not f.startswith("~$")]

def parse_profil(z, ss):
    wb_tree = ET.fromstring(z.read('xl/workbook.xml'))
    sheets_elem = wb_tree.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}sheets')
    r_id = None
    for s in sheets_elem.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}sheet'):
        if 'profil' in s.get('name').lower():
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
    
    rows = []
    for row in sheet_data.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}row'):
        row_dict = {}
        for c in row.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}c'):
            r_coord = c.get('r')
            col_letter = re.sub(r'[0-9]', '', r_coord)
            t = c.get('t')
            v_elem = c.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}v')
            val = ""
            if v_elem is not None and v_elem.text:
                if t == 's':
                    val = ss[int(v_elem.text)] if int(v_elem.text) < len(ss) else ""
                else:
                    val = v_elem.text
            if val.strip():
                row_dict[col_letter] = val.strip()
        if row_dict:
            rows.append(row_dict)
            
    # Parse blocks of indicators
    indicators = []
    current_ind = None
    
    for r in rows:
        a_val = r.get('A', '')
        b_val = r.get('B', '') or r.get('C', '') or r.get('D', '')
        
        a_low = a_val.lower()
        if 'jenis' in a_low and ('.' in a_val or ':' in a_val or '1' in a_val or '2' in a_val or '3' in a_val):
            if current_ind and current_ind.get('judul'):
                indicators.append(current_ind)
            current_ind = {
                'kode': b_val or 'IMP-U1',
                'judul': '',
                'numerator': '',
                'denominator': '',
                'target': '100%',
                'formula': '(Numerator / Denominator) * 100%',
                'instrumen': ''
            }
        elif current_ind is not None:
            if 'judul' in a_low:
                current_ind['judul'] = b_val
            elif 'numerator' in a_low or 'pembilang' in a_low:
                current_ind['numerator'] = b_val
            elif 'denominator' in a_low or 'penyebut' in a_low:
                current_ind['denominator'] = b_val
            elif 'target' in a_low or 'standar' in a_low:
                current_ind['target'] = b_val
            elif 'formula' in a_low:
                current_ind['formula'] = b_val
            elif 'instrumen' in a_low:
                current_ind['instrumen'] = b_val
                
    if current_ind and current_ind.get('judul'):
        indicators.append(current_ind)
        
    return indicators

unit_indicators_map = {}

for fname in files:
    base = fname.replace(".xlsx", "")
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
        inds = parse_profil(z, ss)
        unit_indicators_map[base] = inds

with open(r"c:\laragon\www\mutu_rsys\scratch\all_unit_profiles_parsed.json", "w", encoding="utf-8") as f:
    json.dump(unit_indicators_map, f, indent=2, ensure_ascii=False)

print(f"Parsed profiles for {len(unit_indicators_map)} units!")
for u, inds in unit_indicators_map.items():
    print(f"[{u}] Found {len(inds)} indicators")
