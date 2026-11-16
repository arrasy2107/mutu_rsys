import os
import openpyxl
import json

base_dir = r"public/Sample Data/IMUT ALL UNIT (35)"
files = sorted(os.listdir(base_dir))

print(f"Total files in '{base_dir}': {len(files)}")

summary = {}
for idx, fname in enumerate(files, 1):
    fpath = os.path.join(base_dir, fname)
    if not fname.endswith('.xlsx'):
        continue
    try:
        wb = openpyxl.load_workbook(fpath, read_only=True)
        sheets = wb.sheetnames
        wb.close()
        summary[fname] = sheets
        print(f"[{idx:2d}/35] {fname} ({len(sheets)} sheets): {sheets}")
    except Exception as e:
        print(f"[{idx:2d}/35] {fname} ERROR: {e}")

with open("scratch/imut_all_35_sheets.json", "w") as f:
    json.dump(summary, f, indent=2)

print("\nSummary saved to scratch/imut_all_35_sheets.json")
