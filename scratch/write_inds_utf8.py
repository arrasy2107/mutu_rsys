import json

with open("scratch/parsed_35_units_imut_data.json", "r", encoding="utf-8") as f:
    data = json.load(f)

with open("scratch/all_extracted_inds_utf8.txt", "w", encoding="utf-8") as out:
    for fname, u in data.items():
        out.write(f"\nUNIT: {u['unit_nama']} ({fname}) - {len(u['indicators'])} indicators:\n")
        for ind in u["indicators"]:
            out.write(f"  - [{ind['target_operator']} {ind['target_capaian']}%] {ind['judul']}\n")
            out.write(f"    Months: {ind['months_count']} months recorded.\n")

print("Wrote UTF-8 output.")
