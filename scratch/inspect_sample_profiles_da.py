import openpyxl
import os
import sys

sample_files = ["ADE IRMA.xlsx", "CASEMIX.xlsx", "IGD.xlsx", "LABORATORIUM.xlsx", "K3LH.xlsx"]
base_dir = r"public/Sample Data/IMUT ALL UNIT (35)"

for fname in sample_files:
    fpath = os.path.join(base_dir, fname)
    print(f"\n==================================================", flush=True)
    print(f"FILE: {fname}", flush=True)
    wb = openpyxl.load_workbook(fpath, read_only=True, data_only=True)
    
    # 1. Inspect Sheet PROFIL
    prof_sheet = None
    for s in wb.sheetnames:
        if 'PROFIL' in s.upper():
            prof_sheet = s
            break
    if prof_sheet:
        ws = wb[prof_sheet]
        print(f"--- SHEET PROFIL ({prof_sheet}) ---", flush=True)
        r_count = 0
        for row in ws.iter_rows(values_only=True):
            r_count += 1
            if r_count > 25:
                break
            vals = [str(c or '').strip() for c in row[:8]]
            if any(vals):
                print(f"R{r_count:2d}:", " | ".join(vals[:6]), flush=True)
    
    # 2. Inspect Sheet DATA & ANALISA
    da_sheet = None
    for s in wb.sheetnames:
        if 'DATA' in s.upper() and 'ANALISA' in s.upper():
            da_sheet = s
            break
    if da_sheet:
        ws = wb[da_sheet]
        print(f"\n--- SHEET DATA & ANALISA ({da_sheet}) ---", flush=True)
        r_count = 0
        for row in ws.iter_rows(values_only=True):
            r_count += 1
            if r_count > 18:
                break
            vals = [str(c or '').strip() for c in row[:15]]
            if any(vals):
                print(f"R{r_count:2d}:", " | ".join(vals[:10]), flush=True)
    wb.close()
