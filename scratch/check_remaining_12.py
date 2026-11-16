import json

with open(r"c:\laragon\www\mutu_rsys\scratch\all_unit_profiles_parsed.json", "r", encoding="utf-8") as f:
    data = json.load(f)

units_to_check = [
    'DEWI SARTIKA', 'INTENSIVE CARE', 'LA RUFFA', 'PASTORAL CARE', 
    'REHABILITASI MEDIK', 'HOME CARE', 'IT', 'KEAMANAN & TRANSPORTASI', 
    'LOGISTIK', 'K3LH', 'IGD', 'RADIOLOGI'
]

for u in units_to_check:
    inds = data.get(u, [])
    print(f"\n=== {u} ({len(inds)} indicators) ===")
    for idx, ind in enumerate(inds):
        print(f"  {idx+1}. [{ind.get('kode')}] {ind.get('judul')}")
        print(f"     N: {ind.get('numerator')[:80]}...")
        print(f"     D: {ind.get('denominator')[:80]}...")
        print(f"     Target: {ind.get('target')}")
