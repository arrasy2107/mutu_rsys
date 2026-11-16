import openpyxl
import json
import datetime

wb = openpyxl.load_workbook('public/Sample Data/FARMASI/Form Pengumpul Data.xlsx', data_only=True)
ws = wb['FORM PENGUMPUL DATA']

print("MAX ROW:", ws.max_row, "MAX COL:", ws.max_column)

# Let's inspect Col 1-32: KETEPATAN WAKTU TUNGGU PELAYANAN FARMASI
print("\n=== SCANNING COL 1-32 (WAKTU TUNGGU FARMASI) ===")
for r in range(1, 40):
    vals = [ws.cell(r, c).value for c in range(1, 15)]
    if any(vals):
        # convert dates to str
        s_vals = [v.strftime('%Y-%m-%d') if isinstance(v, datetime.datetime) else str(v) if v is not None else '' for v in vals]
        print(f"R{r}:", " | ".join(s_vals[:10]))

# Check if there are further blocks down the rows in Col 1-15 (e.g., month 2, month 3, etc.)
date_rows = []
for r in range(1, ws.max_row+1):
    c1 = ws.cell(r, 1).value
    c2 = ws.cell(r, 2).value
    if isinstance(c1, datetime.datetime) or (isinstance(c1, str) and any(m in c1.lower() for m in ['januari', 'februari', 'maret', 'april', 'mei', 'juni', 'juli', 'agustus', 'september', 'oktober', 'november', 'desember', '2026'])):
        date_rows.append((r, c1))
    elif isinstance(c2, datetime.datetime):
        date_rows.append((r, c2))

print(f"\nDate / Month rows found in Col 1-2 (Total {len(date_rows)}):")
for dr in date_rows[:20]:
    print(dr)

# Let's inspect Col 33-42: KETEPATAN PENGISIAN ATURAN PAKAI OBAT DI EMR OLEH MEDIS
print("\n=== SCANNING COL 33-42 (ATURAN PAKAI EMR) ===")
for r in range(1, 30):
    vals = [ws.cell(r, c).value for c in range(33, 43)]
    if any(vals):
        s_vals = [str(v) if v is not None else '' for v in vals]
        print(f"R{r}:", " | ".join(s_vals))

# Let's inspect Col 43-51: KEPATUHAN PENGGUNAAN FORMULARIUM NASIONAL
print("\n=== SCANNING COL 43-51 (FORNAS) ===")
for r in range(1, 30):
    vals = [ws.cell(r, c).value for c in range(43, 52)]
    if any(vals):
        s_vals = [str(v) if v is not None else '' for v in vals]
        print(f"R{r}:", " | ".join(s_vals))
