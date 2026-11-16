import openpyxl

wb = openpyxl.load_workbook('public/Sample Data/IMUT ALL UNIT (35)/RADIOLOGI.xlsx', read_only=True, data_only=True)
ws = wb['DATA & ANALISA']
for r_i, row in enumerate(ws.iter_rows(values_only=True)):
    if r_i > 6:
        break
    vals = [str(c or '').strip() for c in row[:25]]
    non_empty = [(idx, v) for idx, v in enumerate(vals) if v]
    print(f"R{r_i+1:2d}:", non_empty)
