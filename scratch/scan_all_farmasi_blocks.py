import openpyxl

wb = openpyxl.load_workbook('public/Sample Data/FARMASI/Form Pengumpul Data.xlsx', data_only=True)
ws = wb['FORM PENGUMPUL DATA']

# Search for all month headers and TOTAL rows in Col 1
print("=== MONTHS IN COL 1 (WAKTU TUNGGU FARMASI) ===")
for r in range(1, ws.max_row+1):
    c1 = str(ws.cell(r, 1).value or '').strip()
    if any(m in c1.lower() for m in ['januari', 'februari', 'maret', 'april', 'mei', 'juni', 'juli', 'agustus', 'september', 'oktober', 'november', 'desember', '2026']):
        print(f"Row {r}: {c1}")
    elif c1.upper() == 'TOTAL':
        c5 = ws.cell(r, 5).value
        c7 = ws.cell(r, 7).value
        print(f"  -> Total Row {r}: Sampel(D)={c5}, Sesuai(N)={c7}")

# Search for Col 33 (ATURAN PAKAI EMR) - how many doctors/rows
print("\n=== SCANNING COL 33 (ATURAN PAKAI EMR) ===")
for r in range(1, min(70, ws.max_row+1)):
    no = ws.cell(r, 33).value
    dr = ws.cell(r, 34).value
    sampel = ws.cell(r, 35).value
    n_pasien = ws.cell(r, 36).value
    tgl_lahir = ws.cell(r, 37).value
    no_rm = ws.cell(r, 38).value
    nm_obat = ws.cell(r, 39).value
    dosis = ws.cell(r, 40).value
    aturan = ws.cell(r, 41).value
    if dr:
        print(f"Row {r} | No: {no} | Dr: {dr} | Sampel: {sampel} | Px: {n_pasien} | Tgl: {tgl_lahir} | RM: {no_rm} | Obat: {nm_obat} | Dosis: {dosis} | Aturan: {aturan}")

# Search for Col 43 (FORNAS)
print("\n=== SCANNING COL 43 (FORNAS) ===")
for r in range(1, 25):
    vals = [ws.cell(r, c).value for c in range(43, 50)]
    if any(vals):
        print(f"Row {r}:", [v for v in vals if v is not None])
