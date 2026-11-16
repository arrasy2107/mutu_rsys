import openpyxl

mr_rs_path = r"C:\laragon\www\mutu_rsys\public\Sample Data\III. Manajemen Resiko\MR RSYS 2026.xlsx"
wb = openpyxl.load_workbook(mr_rs_path, data_only=True)

print("=== PANDUAN SHEET CONTENT ===")
ws_p = wb['PANDUAN']
for r in range(1, 45):
    vals = [ws_p.cell(r, c).value for c in range(1, 15)]
    while vals and vals[-1] is None:
        vals.pop()
    if any(vals):
        print(f"R{r:2d}: {vals}")

print("\n=== JUKNIS SHEET CONTENT ===")
ws_j = wb['JUKNIS']
for r in range(1, 15):
    vals = [ws_j.cell(r, c).value for c in range(1, 30)]
    while vals and vals[-1] is None:
        vals.pop()
    if any(vals):
        print(f"R{r:2d}: {vals[:8]}")

wb.close()

