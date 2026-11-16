import json

with open(r"c:\laragon\www\mutu_rsys\scratch\full_form_scan_result.json", "r", encoding="utf-8") as f:
    data = json.load(f)

with open(r"c:\laragon\www\mutu_rsys\scratch\detailed_headers_all.txt", "w", encoding="utf-8") as out:
    for fname, content in sorted(data.items()):
        unit = fname.replace(".xlsx", "")
        sheets = content.get("sheet_names", [])
        out.write(f"\n==========================================\n")
        out.write(f"UNIT: {unit} (Sheets: {sheets})\n")
        out.write(f"==========================================\n")
        sheets_data = content.get("sheets_data", {})
        for sname, rows in sheets_data.items():
            out.write(f"--- Sheet: {sname} (Rows found: {len(rows)}) ---\n")
            for r_idx, cells in rows[:10]:
                texts = [c[1] for c in cells if c[1]]
                if texts:
                    out.write(f"  Row {r_idx}: " + " | ".join(texts[:10]) + "\n")

print("Written detailed_headers_all.txt successfully.")
