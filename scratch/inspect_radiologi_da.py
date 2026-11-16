import openpyxl

wb = openpyxl.load_workbook('public/Sample Data/IMUT ALL UNIT (35)/RADIOLOGI.xlsx', read_only=True, data_only=True)
ws = wb['DATA & ANALISA']
for r_i, row in enumerate(ws.iter_rows(values_only=True)):
    if r_i > 10:
        break
    vals = [str(c or '').strip() for c in row[:12]]
    if any(vals):
        print(f"R{r_i+1:2d}:", " | ".join(vals[:6]))
