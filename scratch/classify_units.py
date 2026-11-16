import json
import re

with open(r"c:\laragon\www\mutu_rsys\scratch\full_form_scan_result.json", "r", encoding="utf-8") as f:
    data = json.load(f)

classification = {
    "daily_grid": [],       # Kalender harian 1-31 (Numerator, Denominator per hari)
    "patient_register": [], # Log transaksi / pasien (No, Tanggal, No RM, Nama Pasien, Checklist Indikator, dsb)
    "entity_matrix": [],    # Berdasarkan Entitas (Dokter, Nama Barang, Parameter, Lokasi/Lantai)
    "monthly_summary": [],  # Rekap bulanan langsung (Bulan Jan-Des, Event/Audit periodik)
    "other": []
}

details = []

for fname, content in data.items():
    unit_name = fname.replace(".xlsx", "")
    sheets = content.get("sheet_names", [])
    sheets_data = content.get("sheets_data", {})

    # Detect form sheets
    form_sheets = [s for s in sheets if any(k in s.lower() for k in ["form", "pengumpul", "tally", "checklist", "harian", "data"])]
    if not form_sheets:
        form_sheets = sheets[:2] # fallback to first sheet

    sheet_archetypes = []
    
    for sname in form_sheets:
        rows = sheets_data.get(sname, [])
        all_text = " ".join([c[1] for r in rows for c in r[1]]).lower()
        
        # Check patterns
        has_rm = any(term in all_text for term in ["no rm", "no. rm", "rekam medik", "nama pasien", "pasien", "sbar", "tbak", "gelang", "identifikasi"])
        has_dates_1_31 = any(f"tgl {i}" in all_text or f"tanggal {i}" in all_text for i in range(1, 15)) or ("tanggal" in all_text and any(str(i) in all_text for i in range(1, 31)))
        has_monthly_only = any(m in all_text for m in ["januari", "februari", "maret"]) and not has_dates_1_31 and not has_rm
        has_doctor_or_item = any(term in all_text for term in ["nama dokter", "spesialis", "nama barang", "parameter", "limbah", "ipal", "ruangan", "inspeksi"])
        
        # Check first column labels
        sample_headers = []
        for r_idx, cells in rows[:12]:
            cell_vals = [c[1] for c in cells]
            if len(cell_vals) >= 3:
                sample_headers.append(" | ".join(cell_vals[:6]))
        
        arch = "unknown"
        if has_rm:
            arch = "patient_register"
        elif has_doctor_or_item:
            arch = "entity_matrix"
        elif has_dates_1_31 or "hari ke" in all_text or "tgl" in all_text:
            arch = "daily_grid"
        elif has_monthly_only or "triwulan" in all_text or "rekapitulasi" in all_text:
            arch = "monthly_summary"
        else:
            arch = "daily_grid" # default hospital tally standard
            
        sheet_archetypes.append({
            "sheet": sname,
            "archetype": arch,
            "sample_headers": sample_headers[:4]
        })

    # Primary unit archetype
    primary = sheet_archetypes[0]["archetype"] if sheet_archetypes else "daily_grid"
    # if any sheet is patient_register, it's strong register pattern
    if any(sa["archetype"] == "patient_register" for sa in sheet_archetypes):
        primary = "patient_register"
    elif any(sa["archetype"] == "entity_matrix" for sa in sheet_archetypes):
        primary = "entity_matrix"
    elif any(sa["archetype"] == "monthly_summary" for sa in sheet_archetypes) and not any(sa["archetype"] == "daily_grid" for sa in sheet_archetypes):
        primary = "monthly_summary"
        
    classification[primary].append(unit_name)
    details.append({
        "unit": unit_name,
        "sheets": sheets,
        "primary_archetype": primary,
        "sheet_analysis": sheet_archetypes
    })

print("=== CLASSIFICATION SUMMARY ===")
for k, v in classification.items():
    print(f"\n[{k.upper()}] ({len(v)} units):")
    print(", ".join(sorted(v)))

with open(r"c:\laragon\www\mutu_rsys\scratch\unit_classification_details.json", "w", encoding="utf-8") as out:
    json.dump({"summary": classification, "details": details}, out, indent=2, ensure_ascii=False)
