import os
import zipfile
import xml.etree.ElementTree as ET
import json

folder = r"C:\laragon\www\mutu_rsys\public\Sample Data\Form Pengumpul Data"
files = [f for f in os.listdir(folder) if f.endswith(".xlsx") and not f.startswith("~$")]

def get_sheet_names_and_data(file_path):
    results = {}
    try:
        with zipfile.ZipFile(file_path, 'r') as z:
            # Shared strings
            shared_strings = []
            if 'xl/sharedStrings.xml' in z.namelist():
                tree = ET.fromstring(z.read('xl/sharedStrings.xml'))
                for si in tree.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}si'):
                    t = si.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}t')
                    if t is not None and t.text:
                        shared_strings.append(t.text)
                    else:
                        # might have r/t
                        text_parts = [r_elem.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}t').text 
                                      for r_elem in si.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}r') 
                                      if r_elem.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}t') is not None 
                                      and r_elem.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}t').text]
                        shared_strings.append("".join(text_parts))

            # Workbook sheets
            wb_xml = ET.fromstring(z.read('xl/workbook.xml'))
            sheets_elem = wb_xml.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}sheets')
            sheets = []
            for s in sheets_elem.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}sheet'):
                sheets.append((s.get('name'), s.get('{http://schemas.openxmlformats.org/officeDocument/2006/relationships}id')))

            # Get sheet relations
            rel_xml = ET.fromstring(z.read('xl/_rels/workbook.xml.rels'))
            rels = {r.get('Id'): r.get('Target') for r in rel_xml.findall('{http://schemas.openxmlformats.org/package/2006/relationships}Relationship')}

            results['sheet_names'] = [s[0] for s in sheets]
            results['sheets_data'] = {}

            # Analyze relevant sheets (Form Pengumpul Data, or sheets if few)
            for name, r_id in sheets:
                target = rels.get(r_id)
                if not target:
                    continue
                sheet_path = 'xl/' + target if not target.startswith('xl/') else target
                sheet_path = sheet_path.replace('//', '/')
                if sheet_path not in z.namelist():
                    continue

                sheet_tree = ET.fromstring(z.read(sheet_path))
                sheet_data = sheet_tree.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}sheetData')
                if sheet_data is None:
                    continue

                rows_summary = []
                row_count = 0
                for row in sheet_data.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}row'):
                    r_idx = row.get('r')
                    cells = []
                    for c in row.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}c'):
                        ref = c.get('r')
                        t = c.get('t')
                        v = c.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}v')
                        val_str = ""
                        if v is not None and v.text is not None:
                            val = v.text
                            if t == 's' and int(val) < len(shared_strings):
                                val_str = shared_strings[int(val)]
                            else:
                                val_str = val
                        elif c.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}is') is not None:
                            is_elem = c.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}is')
                            t_elem = is_elem.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}t')
                            if t_elem is not None:
                                val_str = t_elem.text or ""
                        if val_str:
                            cells.append((ref, val_str.strip()))
                    if cells:
                        rows_summary.append((r_idx, cells))
                        row_count += 1
                        if row_count >= 25: # sample first 25 rows
                            break

                results['sheets_data'][name] = rows_summary

    except Exception as e:
        results['error'] = str(e)

    return results

all_analysis = {}
for fname in sorted(files):
    fpath = os.path.join(folder, fname)
    all_analysis[fname] = get_sheet_names_and_data(fpath)

with open(r"c:\laragon\www\mutu_rsys\scratch\full_form_scan_result.json", "w", encoding="utf-8") as out:
    json.dump(all_analysis, out, indent=2, ensure_ascii=False)

print(f"Scan completed for {len(files)} files.")
