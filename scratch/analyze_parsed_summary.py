import json

with open("scratch/parsed_35_units_imut_data.json", "r", encoding="utf-8") as f:
    data = json.load(f)

print(f"{'No':2s} | {'Unit Name':28s} | {'File Name':30s} | {'Inds':4s} | Sample Indicator")
print("-" * 90)
zero_units = []
for idx, (fname, u) in enumerate(data.items(), 1):
    cnt = len(u["indicators"])
    sample = u["indicators"][0]["judul"][:35] if cnt > 0 else "(NO INDICATORS FOUND!)"
    if cnt == 0:
        zero_units.append(fname)
    print(f"{idx:2d} | {u['unit_nama']:28s} | {fname:30s} | {cnt:4d} | {sample}")

print(f"\nTotal Units: {len(data)}, Total Zero Units: {len(zero_units)}: {zero_units}")
