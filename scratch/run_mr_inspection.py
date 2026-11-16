import openpyxl
import os
import glob

mr_rs_path = r"C:\laragon\www\mutu_rsys\public\Sample Data\III. Manajemen Resiko\MR RSYS 2026.xlsx"
igd_path = r"C:\laragon\www\mutu_rsys\public\Sample Data\III. Manajemen Resiko\ALL UNIT (35)\IGD 2026.xlsx"
out_path = r"C:\laragon\www\mutu_rsys\scratch\mr_inspection_output.txt"

def inspect_file(wb_path, out_f):
    out_f.write(f"\n======================================================================\n")
    out_f.write(f"FILE: {os.path.basename(wb_path)}\n")
    out_f.write(f"======================================================================\n")
    wb = openpyxl.load_workbook(wb_path, data_only=True)
    out_f.write(f"Sheets: {wb.sheetnames}\n\n")

    for s_name in wb.sheetnames:
        ws = wb[s_name]
        out_f.write(f"--- SHEET: {s_name} (max_row={ws.max_row}, max_column={ws.max_column}) ---\n")
        # Print top rows
        for r in range(1, min(ws.max_row + 1, 15)):
            vals = [ws.cell(r, c).value for c in range(1, min(ws.max_column + 1, 20))]
            while vals and vals[-1] is None:
                vals.pop()
            if any(vals):
                out_f.write(f"Row {r:2d}: {vals}\n")
        out_f.write("\n")
    wb.close()

with open(out_path, "w", encoding="utf-8") as out_f:
    inspect_file(mr_rs_path, out_f)
    inspect_file(igd_path, out_f)

print("Inspection completed. Output saved to:", out_path)

