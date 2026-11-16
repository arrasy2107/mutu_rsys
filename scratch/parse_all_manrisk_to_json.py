import openpyxl, glob, os, json, re

# Maps unit file to database unit kode
UNIT_FILE_MAP = {
    'ADE IRMA 2026.xlsx': 'IRNA-AI',
    'AKRI 2026.xlsx': 'AKRI',
    'AKUNTANSI 2026.xlsx': 'AKUNTANSI',
    'BENDAHARA 2026.xlsx': 'BENDAHARA',
    'CASEMIX 2026.xlsx': 'CASEMIX',
    'CUCI JAHIT.xlsx': 'CUCI-JAHIT',
    'CUSTOMER CARE 2026.xlsx': 'CUSTOMER-CARE',
    'DEWI SARTIKA 2026.xlsx': 'IRNA-DS',
    'DIKLIT 2026.xlsx': 'DIKLIT',
    'FARMASI 2026.xlsx': 'FARMASI-RJ',
    'GIZI 2026.xlsx': 'GIZI',
    'GUDANG UMUM 2026.xlsx': 'GUDANG-UMUM',
    'HERLINA 2026.xlsx': 'IRNA-HERLINA',
    'HOME CARE 2026.xlsx': 'HOMECARE',
    'IBS 2026.xlsx': 'IBS',
    'IGD 2026.xlsx': 'IGD',
    'INTENSIVE 2026.xlsx': 'ICU',
    'IRJ 2026.xlsx': 'IRJ',
    'IT 2026.xlsx': 'IT',
    'K3LH 2026.xlsx': 'K3LH',
    'KEAMANAN TRANSPORTASI 2026.xlsx': 'KEAMANAN',
    'KEPEGAWAIAN 2026.xlsx': 'PSDM',
    'LA RUFFA 2026.xlsx': 'IRNA-LA',
    'LABORATORIUM 2026.xlsx': 'LABOR',
    'LOGISTIK 2026.xlsx': 'LOGISTIK',
    'PASTORAL CARE 2026.xlsx': 'PASTORAL',
    'PEMASARAN 2026.xlsx': 'MARKETING',
    'PEMELIHARAAN SARANA MEDIS.xlsx': 'IPS-M',
    'PEMELIHARAAN SARANA UMUM.xlsx': 'IPS-U',
    'RADIOLOGI 2026.xlsx': 'RADIOLOGI',
    'REHABILITASI MEDIK 2026.xlsx': 'REHAB-MED',
    'REKAM MEDIS 2026.xlsx': 'REKAM-MED',
    'SUDIRMAN 2026.xlsx': 'IRNA-SUD',
    'THAMRIN 2026.xlsx': 'IRNA-TH',
    'TU 2026.xlsx': 'TU'
}

OWNER_MAP = {
    'AI': 'IRNA-AI',
    'AKRI': 'AKRI',
    'Akuntansi': 'AKUNTANSI',
    'Bendahara': 'BENDAHARA',
    'Casemix & Coder': 'CASEMIX',
    'Cuci dan Jahit': 'CUCI-JAHIT',
    'Customer Care': 'CUSTOMER-CARE',
    'Dewi Sartika': 'IRNA-DS',
    'Diklat': 'DIKLIT',
    'Gudang Umum': 'GUDANG-UMUM',
    'Herlina': 'IRNA-HERLINA',
    'Home Care': 'HOMECARE',
    'IBS': 'IBS',
    'Instalasi Laboratorium': 'LABOR',
    'Instalasi Radiologi': 'RADIOLOGI',
    'Instalasi Rehabilitasi Medik': 'REHAB-MED',
    'Instalasi Rekam Medis': 'REKAM-MED',
    'Intensive': 'ICU',
    'K3LH': 'K3LH',
    'Kepegawaian': 'PSDM',
    'La Ruffa': 'IRNA-LA',
    'Logistik': 'LOGISTIK',
    'P.S. medis': 'IPS-M',
    'PJ Farmasi Rawat Jalan': 'FARMASI-RJ',
    'PJ pengadaan, pengolahan dan pendistribusian bahan makanan': 'GIZI',
    'PSU': 'IPS-U',
    'Pastoral Care': 'PASTORAL',
    'Pemasaran dan Penanganan Komplain': 'MARKETING',
    'Sudirman': 'IRNA-SUD',
    'TIM IT': 'IT',
    'Tata Usaha dan Humas': 'TU',
    'Thamrin': 'IRNA-TH',
    'Tim Bencana': 'K3LH',
    'Unit Keamanan dan Transportasi': 'KEAMANAN',
    'Unit Sterilisasi': 'IBS',
    'IRJ': 'IRJ',
    'IGD': 'IGD',
}

# 8 KARS Priorities keywords
KARS_PRIORITIES = {
    'TRIASE': 'Risiko Klinis',
    'CT SCAN': 'Risiko Keuangan',
    'NOMOR REKAM MEDIS': 'Risiko Operasional',
    'AIR LIMBAH': 'Risiko Kepatuhan',
    'DATA PASIEN RUMAH SAKIT': 'Risiko Reputasi',
    'LAPORAN ANESTESI': 'Risiko Legal',
    'MESIN STERILISASI': 'Risiko Kebijakan',
    'KODING RAWAT JALAN': 'Risiko Fraud',
}

def clean_val(v):
    if v is None:
        return None
    if isinstance(v, (int, float)):
        return v
    s = str(v).strip()
    return s if s else None

def parse_row(ws, r):
    c1 = clean_val(ws.cell(r, 1).value)
    c2 = clean_val(ws.cell(r, 2).value)
    c7 = clean_val(ws.cell(r, 7).value)
    
    if not (c2 or c7):
        return None
        
    # Check if this is a header row
    if str(c1) == 'No' or str(c2) == 'Kegiatan' or str(c1) == '1' and str(c2) == '2':
        return None

    # Pengendalian desain
    des_ada = clean_val(ws.cell(r, 12).value)
    des_tdk = clean_val(ws.cell(r, 13).value)
    desain = 'ada' if des_ada else ('tidak' if des_tdk else None)
    
    # Pengendalian efektivitas
    ef_tdk = clean_val(ws.cell(r, 14).value)
    ef_krg = clean_val(ws.cell(r, 15).value)
    ef_efk = clean_val(ws.cell(r, 16).value)
    efektifitas = 'efektif' if ef_efk else ('kurang_efektif' if ef_krg else ('tidak_efektif' if ef_tdk else 'kurang_efektif'))
    
    p = clean_val(ws.cell(r, 17).value) or 1
    d = clean_val(ws.cell(r, 18).value) or 1
    b = clean_val(ws.cell(r, 19).value) or 1.0
    
    try: p = int(p)
    except: p = 1
    try: d = int(d)
    except: d = 1
    try: b = float(b)
    except: b = 1.0
    
    skor = clean_val(ws.cell(r, 20).value)
    try:
        skor = float(skor)
    except:
        skor = round(p * d * b, 2)
        
    tingkat = clean_val(ws.cell(r, 21).value)
    if not tingkat:
        if skor > 19: tingkat = 'Sangat Tinggi'
        elif skor > 14: tingkat = 'Tinggi'
        elif skor > 9: tingkat = 'Sedang'
        elif skor > 4: tingkat = 'Rendah'
        else: tingkat = 'Sangat Rendah'
        
    peringkat = clean_val(ws.cell(r, 22).value)
    try: peringkat = int(peringkat)
    except: peringkat = None
    
    owner = clean_val(ws.cell(r, 23).value)
    
    # Residu
    res_p = clean_val(ws.cell(r, 37).value)
    res_d = clean_val(ws.cell(r, 38).value)
    res_b = clean_val(ws.cell(r, 39).value)
    try: res_p = int(res_p)
    except: res_p = None
    try: res_d = int(res_d)
    except: res_d = None
    try: res_b = float(res_b)
    except: res_b = None
    
    res_skor = clean_val(ws.cell(r, 40).value)
    try: res_skor = float(res_skor)
    except: res_skor = (res_p * res_d * res_b) if (res_p and res_d and res_b) else None
    
    penurunan = clean_val(ws.cell(r, 41).value)
    try:
        penurunan = float(penurunan)
        if penurunan < 1.0: # fraction like 0.57
            penurunan = round(penurunan * 100, 2)
    except:
        penurunan = None

    data = {
        'nomor_urut': c1,
        'kegiatan': c2,
        'tujuan_kegiatan': clean_val(ws.cell(r, 3).value),
        'kode_risiko': clean_val(ws.cell(r, 4).value) or 'RO',
        'kategori_risiko': clean_val(ws.cell(r, 5).value) or 'Risiko Operasional',
        'ruang_lingkup': clean_val(ws.cell(r, 6).value) or 'Pelayanan',
        'pernyataan_risiko': c7 or c2,
        'penyebab': clean_val(ws.cell(r, 8).value),
        'kontrolabilitas': clean_val(ws.cell(r, 9).value) or 'C',
        'dampak': clean_val(ws.cell(r, 10).value),
        'pengendalian_ada': clean_val(ws.cell(r, 11).value),
        'pengendalian_desain': desain,
        'pengendalian_efektifitas_text': efektifitas,
        'efektivitas_pengendalian': efektifitas if efektifitas in ['efektif', 'cukup', 'kurang'] else ('efektif' if efektifitas=='efektif' else ('kurang' if efektifitas=='tidak_efektif' else 'cukup')),
        'inherent_probabilitas': p,
        'inherent_dampak': d,
        'bobot': b,
        'skoring_akhir': skor,
        'inherent_skor': min(25, max(1, int(round(p * d)))),
        'inherent_band': 'ekstrem' if skor > 19 else ('tinggi' if skor > 14 else ('sedang' if skor > 9 else 'rendah')),
        'tingkat_risiko_label': tingkat,
        'peringkat_risiko': peringkat,
        'pemilik_risiko': owner,
        'alternatif_penanganan': clean_val(ws.cell(r, 24).value),
        'pengendalian_harus_ada': clean_val(ws.cell(r, 25).value),
        'rencana_kegiatan': clean_val(ws.cell(r, 26).value),
        'rencana_penanganan': clean_val(ws.cell(r, 26).value) or clean_val(ws.cell(r, 25).value),
        'waktu_jadwal': str(clean_val(ws.cell(r, 27).value) or ''),
        'jenis_alternatif': clean_val(ws.cell(r, 28).value),
        'penanggung_jawab': clean_val(ws.cell(r, 29).value),
        'pemantauan_rencana': clean_val(ws.cell(r, 30).value),
        'pemantauan_realisasi': clean_val(ws.cell(r, 31).value),
        'pemantauan_belum_tertangani': clean_val(ws.cell(r, 32).value),
        'pemantauan_usulan_perbaikan': clean_val(ws.cell(r, 33).value),
        'waktu_pemantauan_rencana': str(clean_val(ws.cell(r, 34).value) or ''),
        'waktu_pemantauan_realisasi': str(clean_val(ws.cell(r, 35).value) or ''),
        'pj_pemantauan': clean_val(ws.cell(r, 36).value),
        'residual_probabilitas': res_p,
        'residual_dampak': res_d,
        'residual_bobot': res_b,
        'residual_skor': res_skor,
        'residual_band': ('ekstrem' if res_skor > 19 else ('tinggi' if res_skor > 14 else ('sedang' if res_skor > 9 else 'rendah'))) if res_skor else None,
        'penurunan_risiko_persen': penurunan,
        'pelaporan_output': clean_val(ws.cell(r, 42).value),
        'pelaporan_target': clean_val(ws.cell(r, 43).value),
        'pelaporan_realisasi': clean_val(ws.cell(r, 44).value),
        'waktu_implementasi': str(clean_val(ws.cell(r, 45).value) or ''),
        'status_tren': clean_val(ws.cell(r, 46).value),
        'level_risiko_evaluasi': clean_val(ws.cell(r, 47).value),
        'is_rs_priority': False,
        'kategori_kars': None,
        'nomor_prioritas_rs': None,
    }
    return data

# Master RS
master_path = r'C:\laragon\www\mutu_rsys\public\Sample Data\III. Manajemen Resiko\MR RSYS 2026.xlsx'
wb_master = openpyxl.load_workbook(master_path, data_only=True)

# 1. Parse PROSES MR in master
pmr_master = wb_master['PROSES MR']
all_risks = []
master_kegiatan_set = set()

for r in range(9, pmr_master.max_row + 1):
    item = parse_row(pmr_master, r)
    if item:
        # map unit kode from owner
        owner = item['pemilik_risiko']
        unit_kode = OWNER_MAP.get(owner, 'IRJ') # fallback
        item['unit_kode'] = unit_kode
        item['source'] = 'MASTER_PROSES_MR'
        all_risks.append(item)
        if item['kegiatan']:
            master_kegiatan_set.add(item['kegiatan'].lower().strip())

print(f'Parsed from Master PROSES MR: {len(all_risks)} items')

# 2. Check DAFTAR RISIKO RS for priorities
rs_ws = wb_master['DAFTAR RISIKO RS']
rs_priorities = []
for r in range(9, rs_ws.max_row + 1):
    c1 = clean_val(rs_ws.cell(r, 1).value)
    c2 = clean_val(rs_ws.cell(r, 2).value)
    if c1 and c2:
        rs_priorities.append({
            'no': str(c1).replace('\n', ' '),
            'kegiatan': c2,
            'owner': clean_val(rs_ws.cell(r, 23).value)
        })

print(f'Parsed from DAFTAR RISIKO RS: {len(rs_priorities)} priorities')

# Mark priorities in all_risks
for item in all_risks:
    keg = (item['kegiatan'] or '').lower().strip()
    for prio in rs_priorities:
        pkeg = prio['kegiatan'].lower().strip()
        if pkeg in keg or keg in pkeg or (item['pemilik_risiko'] == prio['owner'] and len(keg) > 5 and pkeg[:10] in keg):
            item['is_rs_priority'] = True
            item['nomor_prioritas_rs'] = prio['no']
            break
            
    # Check 8 KARS priorities
    upper_keg = (item['kegiatan'] or '').upper()
    for kw, kars_cat in KARS_PRIORITIES.items():
        if kw in upper_keg:
            item['is_rs_priority'] = True
            item['kategori_kars'] = kars_cat
            break

# 3. Parse 35 Unit Files
unit_files = sorted(glob.glob(r'C:\laragon\www\mutu_rsys\public\Sample Data\III. Manajemen Resiko\ALL UNIT (35)\*.xlsx'))
added_from_units = 0

for uf in unit_files:
    fname = os.path.basename(uf)
    unit_kode = UNIT_FILE_MAP[fname]
    wb_u = openpyxl.load_workbook(uf, data_only=True)
    pmr_names = [s for s in wb_u.sheetnames if 'PROSES MR' in s.upper()]
    if not pmr_names:
        continue
    ws_u = wb_u[pmr_names[0]]
    for r in range(8, ws_u.max_row + 1):
        item = parse_row(ws_u, r)
        if item and item['kegiatan']:
            keg_key = item['kegiatan'].lower().strip()
            # If not already present in master
            if keg_key not in master_kegiatan_set:
                item['unit_kode'] = unit_kode
                item['source'] = 'UNIT_FILE_' + fname
                master_kegiatan_set.add(keg_key)
                all_risks.append(item)
                added_from_units += 1

print(f'Added from 35 unit files: {added_from_units} unique unit risks')
print(f'Total combined risks to insert: {len(all_risks)}')

# Output to JSON
out_path = r'C:\laragon\www\mutu_rsys\scratch\manrisk_parsed_data.json'
with open(out_path, 'w', encoding='utf-8') as f:
    json.dump(all_risks, f, ensure_ascii=False, indent=2)

print(f'Successfully wrote JSON to {out_path}')

