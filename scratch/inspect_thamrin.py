import zipfile
import xml.etree.ElementTree as ET
import os

folder = r"C:\laragon\www\mutu_rsys\public\Sample Data\Form Pengumpul Data"
fpath = os.path.join(folder, "THAMRIN.xlsx")

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
    sheets = wb_tree.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}sheets')
    first_sheet = sheets.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}sheet')[0]
    r_id = first_sheet.get('{http://schemas.openxmlformats.org/officeDocument/2006/relationships}id')
    
    rel_xml = ET.fromstring(z.read('xl/_rels/workbook.xml.rels'))
    rels = {r.get('Id'): r.get('Target') for r in rel_xml.findall('{http://schemas.openxmlformats.org/package/2006/relationships}Relationship')}
    target = rels.get(r_id)
    sheet_path = 'xl/' + target if not target.startswith('xl/') else target
    sheet_path = sheet_path.replace('//', '/')
    
    sheet_tree = ET.fromstring(z.read(sheet_path))
    sheet_data = sheet_tree.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}sheetData')
    
    with open(r"c:\laragon\www\mutu_rsys\scratch\thamrin_header.txt", "w", encoding="utf-8") as out:
        for row in sheet_data.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}row')[:10]:
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
                if val.strip():
                    row_cells.append(f"{r_coord}: {val.strip()}")
            if row_cells:
                out.write(" | ".join(row_cells) + "\n")

print("Thamrin headers written to thamrin_header.txt")
