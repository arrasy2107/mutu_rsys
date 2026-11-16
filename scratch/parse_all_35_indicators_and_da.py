import os
import openpyxl
import json
import re

base_dir = r"public/Sample Data/IMUT ALL UNIT (35)"
files = sorted([f for f in os.listdir(base_dir) if f.endswith('.xlsx')])

unit_mapping = {
    "ADE IRMA.xlsx": {"id": 46, "kode": "IRNA-AI", "nama": "Ade Irma"},
    "AKRI.xlsx": {"id": 25, "kode": "AKRI", "nama": "AKRI"},
    "AKUTANSI.xlsx": {"id": 21, "kode": "AKUNTANSI", "nama": "Akuntansi"},
    "BENDAHARA.xlsx": {"id": 23, "kode": "BENDAHARA", "nama": "Bendahara"},
    "CASEMIX.xlsx": {"id": 24, "kode": "CASEMIX", "nama": "Casemix"},
    "CUCI JAHIT.xlsx": {"id": 28, "kode": "CUCI-JAHIT", "nama": "Cuci Jahit"},
    "CUSTOMER CARE.xlsx": {"id": 31, "kode": "CUSTOMER-CARE", "nama": "Customer Care"},
    "DEWI SARTIKA.xlsx": {"id": 45, "kode": "IRNA-DS", "nama": "Dewi Sartika"},
    "DIKLIT.xlsx": {"id": 36, "kode": "DIKLIT", "nama": "Diklit"},
    "FARMASI.xlsx": {"id": 12, "kode": "FARMASI-RJ", "nama": "Instalasi Farmasi Rawat Jalan"},
    "GIZI.xlsx": {"id": 15, "kode": "GIZI", "nama": "Instalasi Gizi"},
    "GUDANG UMUM.xlsx": {"id": 27, "kode": "GUDANG-UMUM", "nama": "Gudang Umum"},
    "HERLINA.xlsx": {"id": 39, "kode": "IRNA-HERLINA", "nama": "Herlina"},
    "HOME CARE.xlsx": {"id": 33, "kode": "HOMECARE", "nama": "Home Care"},
    "IBS.xlsx": {"id": 19, "kode": "IBS", "nama": "Instalasi Bedah Sentral"},
    "IGD.xlsx": {"id": 1, "kode": "IGD", "nama": "Instalasi Gawat Darurat"},
    "INTENSIVE CARE.xlsx": {"id": 2, "kode": "ICU", "nama": "Intensive Care Unit"},
    "IRJ.xlsx": {"id": 18, "kode": "IRJ", "nama": "Instalasi Rawat Jalan"},
    "IT.xlsx": {"id": 37, "kode": "IT", "nama": "IT"},
    "K3LH.xlsx": {"id": 42, "kode": "K3LH", "nama": "K3LH"},
    "KEAMANAN & TRANSPORTASI.xlsx": {"id": 34, "kode": "KEAMANAN", "nama": "Keamanan & Transportasi"},
    "KEPEGAWAIAN.xlsx": {"id": 35, "kode": "PSDM", "nama": "Kepegawaian"},
    "LA RUFFA.xlsx": {"id": 47, "kode": "IRNA-LA", "nama": "La Ruffa"},
    "LABORATORIUM.xlsx": {"id": 13, "kode": "LABOR", "nama": "Laboratorium"},
    "LOGISTIK.xlsx": {"id": 26, "kode": "LOGISTIK", "nama": "Logistik"},
    "PASTORAL CARE.xlsx": {"id": 32, "kode": "PASTORAL", "nama": "Pastoral Care"},
    "PEMASARAN.xlsx": {"id": 22, "kode": "MARKETING", "nama": "Pemasaran"},
    "PEMELIHARAAN SARANA MEDIS.xlsx": {"id": 30, "kode": "IPS-M", "nama": "Pemeliharaan Sarana Medis"},
    "PEMELIHARAAN SARANA UMUM.xlsx": {"id": 29, "kode": "IPS-U", "nama": "Pemeliharaan Sarana Umum"},
    "RADIOLOGI.xlsx": {"id": 14, "kode": "RADIOLOGI", "nama": "Instalasi Radiologi"},
    "REHABILITASI MEDIK.xlsx": {"id": 20, "kode": "REHAB-MED", "nama": "Rehabilitasi Medik"},
    "REKAM MEDIS.xlsx": {"id": 17, "kode": "REKAM-MED", "nama": "Rekam Medis"},
    "SUDIRMAN.xlsx": {"id": 43, "kode": "IRNA-SUD", "nama": "Sudirman"},
    "THAMRIN.xlsx": {"id": 44, "kode": "IRNA-TH", "nama": "Thamrin"},
    "TU.xlsx": {"id": 38, "kode": "TU", "nama": "TU"},
}

all_parsed_data = {}
total_indicators_found = 0

for idx, fname in enumerate(files, 1):
    fpath = os.path.join(base_dir, fname)
    u_info = unit_mapping.get(fname, {"id": None, "kode": fname, "nama": fname})
    print(f"[{idx:2d}/35] Parsing {fname} (Unit: {u_info['nama']})...", flush=True)
    
    try:
        wb = openpyxl.load_workbook(fpath, read_only=True, data_only=True)
    except Exception as e:
        print(f"  Error opening {fname}: {e}")
        continue

    da_sheet_name = None
    for s in wb.sheetnames:
        if 'DATA' in s.upper() and 'ANALISA' in s.upper():
            da_sheet_name = s
            break
            
    unit_entry = {
        "file": fname,
        "unit_id": u_info["id"],
        "unit_kode": u_info["kode"],
        "unit_nama": u_info["nama"],
        "indicators": [],
    }

    if da_sheet_name:
        ws = wb[da_sheet_name]
        rows = []
        for r_idx, row in enumerate(ws.iter_rows(values_only=True)):
            if r_idx > 35:
                break
            rows.append(row)

        bulan_row_idx = None
        for r_i, r_vals in enumerate(rows):
            col0 = str(r_vals[0] or '').strip().upper()
            if col0 == 'BULAN':
                bulan_row_idx = r_i
                break

        if bulan_row_idx is not None and bulan_row_idx < len(rows):
            # Title row is usually bulan_row_idx - 1 (e.g. Row 3, with Bulan on Row 4)
            # Or if bulan is on the same row, then bulan_row_idx
            title_row_idx = bulan_row_idx - 1 if bulan_row_idx > 0 and any(rows[bulan_row_idx - 1][1:]) else bulan_row_idx
            row_titles = rows[title_row_idx]
            row_sub = rows[bulan_row_idx] if title_row_idx != bulan_row_idx else (rows[bulan_row_idx + 1] if bulan_row_idx + 1 < len(rows) else [])
            row_std = rows[bulan_row_idx + 1] if title_row_idx != bulan_row_idx else (rows[bulan_row_idx + 2] if bulan_row_idx + 2 < len(rows) else [])

            # Extract indicator blocks
            col_idx = 1
            while col_idx < len(row_titles):
                val = row_titles[col_idx]
                val_str = str(val or '').strip()
                if val_str and val_str.upper() not in ['BULAN', 'HASIL', 'STANDAR', 'NUMERATOR', 'DENOMINATOR', 'CAPAIAN', 'RTL', 'PELAKSANAAN DAN EVALUASI']:
                    ind_title = val_str
                    num_col = None
                    den_col = None
                    hasil_col = None
                    std_val = None

                    # Search within next 6 columns for num, den, hasil
                    for offset in range(0, 6):
                        c = col_idx + offset
                        if c < len(row_sub):
                            sub_text = str(row_sub[c] or '').strip().lower()
                            if 'num' in sub_text:
                                num_col = c
                            elif 'den' in sub_text:
                                den_col = c
                            elif 'hasil' in sub_text or 'capaian' in sub_text or '%' in sub_text:
                                if hasil_col is None:
                                    hasil_col = c
                        if c < len(row_std) and row_std[c] is not None:
                            s_txt = str(row_std[c]).strip()
                            if s_txt and s_txt != 'None' and not std_val and not s_txt.isdigit() and len(s_txt) < 20:
                                std_val = s_txt

                    # Monthly values
                    month_data = {}
                    month_names = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']
                    
                    data_start_row = bulan_row_idx + (2 if title_row_idx != bulan_row_idx else 3)
                    for m_idx in range(12):
                        row_m_idx = data_start_row + m_idx
                        if row_m_idx < len(rows):
                            r_vals = rows[row_m_idx]
                            m_name = month_names[m_idx]
                            num_val = r_vals[num_col] if num_col is not None and num_col < len(r_vals) else None
                            den_val = r_vals[den_col] if den_col is not None and den_col < len(r_vals) else None
                            has_val = r_vals[hasil_col] if hasil_col is not None and hasil_col < len(r_vals) else None
                            
                            def to_num(x):
                                if x is None or str(x).strip() in ['', '#DIV/0!', '-', 'None']:
                                    return None
                                try:
                                    return float(x)
                                except:
                                    return None

                            n_clean = to_num(num_val)
                            d_clean = to_num(den_val)
                            h_clean = to_num(has_val)

                            if n_clean is not None or d_clean is not None or h_clean is not None:
                                month_data[m_idx + 1] = {
                                    "bulan": m_idx + 1,
                                    "bulan_nama": m_name,
                                    "numerator": n_clean or 0,
                                    "denominator": d_clean or 0,
                                    "capaian": round(h_clean, 2) if h_clean is not None else (round((n_clean / d_clean * 100), 2) if n_clean and d_clean else 0),
                                }

                    target_num = 100.0
                    target_op = '>='
                    if std_val:
                        m_target = re.search(r'(\d+(?:\.\d+)?)', str(std_val))
                        if m_target:
                            target_num = float(m_target.group(1))
                        if '<=' in str(std_val) or '≤' in str(std_val) or '<' in str(std_val):
                            target_op = '<='
                        elif ('=' in str(std_val)) and not ('>=' in str(std_val) or '<=' in str(std_val) or '≥' in str(std_val) or '≤' in str(std_val)):
                            target_op = '='

                    ind_info = {
                        "judul": ind_title,
                        "target_raw": std_val,
                        "target_capaian": target_num,
                        "target_operator": target_op,
                        "months_count": len(month_data),
                        "monthly_data": month_data,
                    }
                    unit_entry["indicators"].append(ind_info)
                    total_indicators_found += 1

                    col_idx += 4
                else:
                    col_idx += 1

    wb.close()
    all_parsed_data[fname] = unit_entry
    print(f"  -> Found {len(unit_entry['indicators'])} indicators with data.")

output_file = "scratch/parsed_35_units_imut_data.json"
with open(output_file, "w", encoding="utf-8") as f:
    json.dump(all_parsed_data, f, ensure_ascii=False, indent=2)

print(f"\n==================================================")
print(f"SUCCESS! Parsed {len(all_parsed_data)} units, {total_indicators_found} total indicators.")
print(f"Results saved to {output_file}")
