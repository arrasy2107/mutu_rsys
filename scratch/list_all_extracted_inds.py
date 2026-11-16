import json

with open("scratch/parsed_35_units_imut_data.json", "r", encoding="utf-8") as f:
    data = json.load(f)

for fname, u in data.items():
    print(f"\nUNIT: {u['unit_nama']} ({fname}) - {len(u['indicators'])} indicators:")
    for ind in u["indicators"]:
        print(f"  - [{ind['target_operator']} {ind['target_capaian']}%] {ind['judul'][:60]}")
