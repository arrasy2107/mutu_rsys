import openpyxl
import datetime

path = 'public/Sample Data/FARMASI/Data dan Analisa.xlsx'
wb = openpyxl.load_workbook(path, data_only=True)
print("=== FILE:", path)
print("Sheets:", wb.sheetnames)

for sname in wb.sheetnames:
    ws = wb[sname]
    print(f"\n--- SHEET: {sname} (max_row={ws.max_row}, max_col={ws.max_column}) ---")
    for r in range(1, min(30, ws.max_row+1)):
        vals = [ws.cell(r, c).value for c in range(1, min(15, ws.max_column+1))]
        if any(vals):
            s_vals = [v.strftime('%Y-%m-%d') if isinstance(v, datetime.datetime) else str(v) if v is not None else '' for v in vals]
            print(f"R{r}:", " | ".join(s_vals[:10]))
