import os
import openpyxl
import json
import re

base_dir = r"public/Sample Data/IMUT ALL UNIT (35)"
with open("scratch/parsed_35_units_imut_data.json", "r", encoding="utf-8") as f:
    units_data = json.load(f)

print(f"Loaded {len(units_data)} units from parsed_35_units_imut_data.json")

for fname, u_info in units_data.items():
    fpath = os.path.join(base_dir, fname)
    try:
        wb = openpyxl.load_workbook(fpath, read_only=True, data_only=True)
    except Exception as e:
        print(f"Error {fname}: {e}")
        continue

    prof_sheet = None
    for s in wb.sheetnames:
        if 'PROFIL' in s.upper():
            prof_sheet = s
            break

    if not prof_sheet:
        wb.close()
        continue

    ws = wb[prof_sheet]
    rows = []
    for r_idx, r in enumerate(ws.iter_rows(values_only=True)):
        if r_idx > 120:
            break
        rows.append(r)
    wb.close()

    # Search for blocks in PROFIL sheet
    # A block typically has 'Judul' or 'Defenisi Operasional' or 'Numerator'
    # We can search by matching indicator titles in u_info["indicators"]
    for ind in u_info["indicators"]:
        ind_title = ind["judul"].strip().lower()
        # Find row where title appears or closest match
        matched_row = None
        for r_i, r_vals in enumerate(rows):
            line_str = " ".join([str(c or '').strip().lower() for c in r_vals[:6]])
            # Check match
            words = [w for w in re.split(r'\W+', ind_title) if len(w) > 3]
            if len(words) > 0 and sum(1 for w in words if w in line_str) >= min(3, len(words)):
                matched_row = r_i
                break

        # Defaults
        ind["definisi_operasional"] = ""
        ind["numerator_keterangan"] = ""
        ind["denominator_keterangan"] = ""
        ind["satuan"] = "%"
        ind["kategori_jenis"] = "IMU"

        if matched_row is not None:
            # Look up around matched_row (from matched_row - 5 to matched_row + 25)
            start_r = max(0, matched_row - 5)
            end_r = min(len(rows), matched_row + 25)
            for r_k in range(start_r, end_r):
                r_line = rows[r_k]
                col0 = str(r_line[0] or '').strip().lower()
                col1 = str(r_line[1] or '').strip() if len(r_line) > 1 else ''

                if 'defenisi' in col0 or 'definisi' in col0:
                    ind["definisi_operasional"] = col1
                elif 'numerator' in col0:
                    ind["numerator_keterangan"] = col1
                elif 'denominator' in col0:
                    ind["denominator_keterangan"] = col1
                elif 'satuan' in col0:
                    ind["satuan"] = col1 if col1 else '%'
                elif 'jenis' in col0 and ('inm' in col1.lower() or 'imp' in col1.lower() or 'imu' in col1.lower()):
                    if 'inm' in col1.lower(): ind["kategori_jenis"] = "INM"
                    elif 'imp-rs' in col1.lower(): ind["kategori_jenis"] = "IMP-RS"
                    elif 'imp-u' in col1.lower(): ind["kategori_jenis"] = "IMP-U"
                    else: ind["kategori_jenis"] = "IMU"

print("Done extracting profile details for all 97 indicators.")

with open("scratch/parsed_35_units_imut_data_with_profiles.json", "w", encoding="utf-8") as f:
    json.dump(units_data, f, ensure_ascii=False, indent=2)

print("Saved to scratch/parsed_35_units_imut_data_with_profiles.json")
