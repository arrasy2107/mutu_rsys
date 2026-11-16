import openpyxl
import datetime

wb = openpyxl.load_workbook('public/Sample Data/FARMASI/Form Pengumpul Data.xlsx', data_only=True)
ws = wb['FORM PENGUMPUL DATA']

print("=== INSPECTING COL 1-10 IN DETAIL ===")
current_month = "Initial"
for r in range(1, ws.max_row+1):
    c1 = ws.cell(r, 1).value
    c2 = ws.cell(r, 2).value
    c5 = ws.cell(r, 5).value
    c7 = ws.cell(r, 7).value
    if c1 and ('2026' in str(c1) or any(m in str(c1).lower() for m in ['januari', 'februari', 'maret', 'april', 'mei', 'juni', 'juli', 'agustus', 'september'])):
        print(f"\n[ROW {r}] HEADER: {c1} | col2: {c2}")
    elif c1 and 'TOTAL' in str(c1).upper():
        print(f"  [ROW {r}] TOTAL: Sampel={c5}, Sesuai={c7}")
    elif isinstance(c1, datetime.datetime) or (isinstance(c2, datetime.datetime)):
        d_val = c1 if isinstance(c1, datetime.datetime) else c2
        print(f"  Daily Date Row {r}: {d_val.strftime('%Y-%m-%d')} | Col3={ws.cell(r,3).value} | Col5={c5} | Col7={c7}")
