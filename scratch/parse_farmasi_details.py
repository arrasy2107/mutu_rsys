import openpyxl
import datetime

wb = openpyxl.load_workbook('public/Sample Data/FARMASI/Form Pengumpul Data.xlsx', data_only=True)
ws = wb['FORM PENGUMPUL DATA']

print("=== CHECKING SECTIONS IN FORM PENGUMPUL DATA ===")
# Check rows 1 to 100 for col 1
for r in range(1, 100):
    val = ws.cell(r, 1).value
    val2 = ws.cell(r, 2).value
    val3 = ws.cell(r, 3).value
    val5 = ws.cell(r, 5).value
    val7 = ws.cell(r, 7).value
    if val or val2 or val5 or val7:
        print(f"R{r:2d} | Col1: {val} | Col2: {val2} | Col3: {val3} | Col5: {val5} | Col7: {val7}")
