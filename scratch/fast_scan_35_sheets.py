import os
import zipfile
import xml.etree.ElementTree as ET
import json

base_dir = r"public/Sample Data/IMUT ALL UNIT (35)"
files = sorted([f for f in os.listdir(base_dir) if f.endswith('.xlsx')])

print(f"Total files: {len(files)}")
summary = {}

for idx, fname in enumerate(files, 1):
    fpath = os.path.join(base_dir, fname)
    sheets = []
    try:
        with zipfile.ZipFile(fpath, 'r') as z:
            wb_xml = z.read('xl/workbook.xml')
            root = ET.fromstring(wb_xml)
            for elem in root.iter('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}sheet'):
                sheets.append(elem.attrib.get('name'))
        summary[fname] = sheets
        print(f"[{idx:2d}/35] {fname} ({len(sheets)} sheets): {sheets}")
    except Exception as e:
        print(f"[{idx:2d}/35] {fname} ERROR: {e}")

with open("scratch/imut_all_35_sheets.json", "w") as f:
    json.dump(summary, f, indent=2)

print("\nDone! Saved to scratch/imut_all_35_sheets.json")
