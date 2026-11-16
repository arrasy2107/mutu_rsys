import openpyxl
import os
import glob

mr_rs_path = r"C:\laragon\www\mutu_rsys\public\Sample Data\III. Manajemen Resiko\MR RSYS 2026.xlsx"
units_dir = r"C:\laragon\www\mutu_rsys\public\Sample Data\III. Manajemen Resiko\ALL UNIT (35)"

print("=== INSPECT MR RSYS 2026.xlsx ===")
if os.path.exists(mr_rs_path):
    wb = openpyxl.load_workbook(mr_rs_path, read_only=True, data_only=True)
    print("Sheets in MR RSYS 2026.xlsx:", wb.sheetnames)
    wb.close()
else:
    print("File not found:", mr_rs_path)

print("\n=== INSPECT SAMPLE UNIT FILES ===")
unit_files = glob.glob(os.path.join(units_dir, "*.xlsx"))
print(f"Total unit files found: {len(unit_files)}")

for f in unit_files[:3]:
    basename = os.path.basename(f)
    try:
        wb = openpyxl.load_workbook(f, read_only=True, data_only=True)
        print(f"File: {basename} -> Sheets: {wb.sheetnames}")
        wb.close()
    except Exception as e:
        print(f"Error reading {basename}: {e}")

