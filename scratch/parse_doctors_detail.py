import openpyxl

wb = openpyxl.load_workbook('public/Sample Data/FARMASI/Form Pengumpul Data.xlsx', data_only=True)
ws = wb['FORM PENGUMPUL DATA']

print("Row 38-42 across cols 30-45:")
for r in range(38, 43):
    vals = [(c, ws.cell(r, c).value) for c in range(30, 45) if ws.cell(r, c).value is not None]
    print(f"R{r}:", vals)

# Check sum of samples and aturan in row 42-78
total_sample = 0
total_aturan = 0
for r in range(42, 80):
    dr = ws.cell(r, 34).value
    s = ws.cell(r, 35).value
    a = ws.cell(r, 41).value
    if dr and isinstance(s, (int, float)):
        total_sample += s
        if isinstance(a, (int, float)):
            total_aturan += a
        print(f"R{r} {dr}: sample={s}, aturan={a}")
print(f"SUM SAMPLE: {total_sample}, SUM ATURAN: {total_aturan}")

# Check if there are other doctor tables down to row 752 in col 33-35
print("\nChecking further down in Col 33-35:")
for r in range(80, ws.max_row+1):
    val = ws.cell(r, 34).value
    if val and ('dr.' in str(val).lower() or 'poli' in str(val).lower() or 'dokter' in str(val).lower()):
        print(f"R{r}: {val} | col35={ws.cell(r, 35).value} | col41={ws.cell(r, 41).value}")
