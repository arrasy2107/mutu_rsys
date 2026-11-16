import openpyxl
import os

mr_rs_path = r"C:\laragon\www\mutu_rsys\public\Sample Data\III. Manajemen Resiko\MR RSYS 2026.xlsx"
igd_path = r"C:\laragon\www\mutu_rsys\public\Sample Data\III. Manajemen Resiko\ALL UNIT (35)\IGD 2026.xlsx"

def dump_sheet_preview(file_path, sheet_name, max_rows=15, max_cols=15):
    print(f"\n==================================================")
    print(f"FILE: {os.path.basename(file_path)} | SHEET: {sheet_name}")
    print(f"==================================================")
    wb = openpyxl.load_workbook(file_path, data_only=True)
    if sheet_name not in wb.sheetnames:
        print(f"Sheet '{sheet_name}' not found!")
        wb.close()
        return
    ws = wb[sheet_name]
    for r in range(1, max_rows + 1):
        row_vals = [ws.cell(r, c).value for c in range(1, max_cols + 1)]
        # Filter trailing None
        while row_vals and row_vals[-1] is None:
            row_vals.pop()
        if any(row_vals):
            print(f"Row {r:2d}: {row_vals}")
    wb.close()

# Preview RS sheets
for s in ['DAFTAR RISIKO RS', 'IDENTIFIKASI RISIKO', 'ANALISIS RISIKO', 'PROFIL', 'PENGENDALIAN', 'RENC PENANGANAN', 'PEMANTAUAN', 'LAPORAN']:
    dump_sheet_preview(mr_rs_path, s, max_rows=12, max_cols=14)

# Preview IGD sheet
for s in ['IDENTIFIKASI RISIKO', 'ANALISIS RISIKO', 'PROFIL', 'PENGENDALIAN', 'RENC PENANGANAN', 'PEMANTAUAN']:
    dump_sheet_preview(igd_path, s, max_rows=10, max_cols=12)

