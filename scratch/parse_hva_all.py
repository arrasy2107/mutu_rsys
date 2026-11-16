import glob
import os
import json
import re
import xlrd

with open('scratch/db_units.json', 'r', encoding='utf-8') as f:
    units = json.load(f)

files = glob.glob('public/Sample Data/IV. HVA/ALL UNIT (35)/*.xls')

def get_unit_for_file(filepath):
    fn_raw = os.path.basename(filepath)
    fn = fn_raw.replace(' 2026.xls', '').replace('.xls', '').lower().strip()
    fn_clean = re.sub(r'[^a-z0-9]', '', fn)
    
    if fn_clean == 'it':
        return next(u for u in units if u['kode'] == 'IT')
    if fn_clean in ['intensive', 'icu']:
        return next(u for u in units if u['kode'] == 'ICU')
    if fn_clean == 'irj':
        return next(u for u in units if u['kode'] == 'IRJ')
    if fn_clean == 'pemasaran':
        return next(u for u in units if u['kode'] == 'MARKETING')
    if fn_clean in ['pemeliharaansaranamedis', 'ipsmedis']:
        return next(u for u in units if u['kode'] == 'IPS-M')
    if fn_clean in ['pemeliharaansaranaumum', 'ipsnonmedis']:
        return next(u for u in units if u['kode'] == 'IPS-U')
    if 'keamanan' in fn_clean:
        return next(u for u in units if u['kode'] == 'KEAMANAN')
    if 'kepegawaian' in fn_clean or fn_clean == 'psdm':
        return next(u for u in units if u['kode'] == 'PSDM')
    if 'cuci' in fn_clean:
        return next(u for u in units if u['kode'] == 'CUCI-JAHIT')
    if 'gudangumum' in fn_clean:
        return next(u for u in units if u['kode'] == 'GUDANG-UMUM')
    if fn_clean == 'farmasi':
        return next(u for u in units if u['kode'] == 'FARMASI-RJ')
    if 'pastoral' in fn_clean:
        return next(u for u in units if u['kode'] == 'PASTORAL')
    if 'homecare' in fn_clean:
        return next(u for u in units if u['kode'] == 'HOMECARE')
    if 'customercare' in fn_clean:
        return next(u for u in units if u['kode'] == 'CUSTOMER-CARE')
    if 'rehabilitasi' in fn_clean:
        return next(u for u in units if u['kode'] == 'REHAB-MED')
    if 'rekammedis' in fn_clean:
        return next(u for u in units if u['kode'] == 'REKAM-MED')
    if 'herlina' in fn_clean:
        return next(u for u in units if u['kode'] == 'IRNA-HERLINA')
    if 'sudirman' in fn_clean:
        return next(u for u in units if u['kode'] == 'IRNA-SUD')
    if 'thamrin' in fn_clean:
        return next(u for u in units if u['kode'] == 'IRNA-TH')
    if 'dewisartika' in fn_clean:
        return next(u for u in units if u['kode'] == 'IRNA-DS')
    if 'adeirma' in fn_clean:
        return next(u for u in units if u['kode'] == 'IRNA-AI')
    if 'laruffa' in fn_clean:
        return next(u for u in units if u['kode'] == 'IRNA-LA')
    if 'laboratorium' in fn_clean:
        return next(u for u in units if u['kode'] == 'LABOR')

    for u in units:
        un = re.sub(r'[^a-z0-9]', '', u['nama'].lower())
        uk = re.sub(r'[^a-z0-9]', '', u['kode'].lower())
        if fn_clean == uk or fn_clean == un:
            return u
            
    for u in units:
        un = re.sub(r'[^a-z0-9]', '', u['nama'].lower())
        if fn_clean in un or un in fn_clean:
            return u
            
    return None

sheets_map = [
    ('Natural Hazards', 'natural'),
    ('Technological Hazards', 'technological'),
    ('Human Hazards', 'human'),
    ('Hazardous Materials', 'hazardous_materials')
]

canonical_events = {
    # Natural
    'angin puting beliung': 'Angin Puting Beliung',
    'tornado': 'Tornado',
    'hujan badai': 'Hujan Badai',
    'salju': 'Salju',
    'badai salju': 'Badai Salju',
    'hujan es': 'Hujan Es',
    'gempa bumi': 'Gempa Bumi',
    'tsunami': 'Tsunami',
    'temperatur ekstrim': 'Temperatur Ekstrim',
    'musim kemarau': 'Musim Kemarau',
    'banjir, eksternal': 'Banjir Eksternal',
    'banjir eksternal': 'Banjir Eksternal',
    'gunung meletus': 'Gunung Meletus',
    
    # Technological
    'kegagalan listrik': 'Kegagalan Listrik',
    'kegagalan genset': 'Kegagalan Genset',
    'kegagalan pengangkutan': 'Kegagalan Pengangkutan',
    'kekurangan bahan bakar': 'Kekurangan Bahan Bakar',
    'kegagalan gas alam': 'Kegagalan Gas Alam',
    'kegagalan air': 'Kegagalan Air',
    'kegagalan selokan air kotor': 'Kegagalan Selokan Air Kotor',
    'kegagalan mesin uap': 'Kegagalan Mesin Uap',
    'kegagalan alarm kebakaran': 'Kegagalan Alarm Kebakaran',
    'kegagalan komunikasi': 'Kegagalan Komunikasi',
    'kegagalan gas medis': 'Kegagalan Gas Medis',
    'kegagalan vakum medis': 'Kegagalan Vakum Medis',
    'kegagalan sistem informasi': 'Kegagalan Sistem Informasi',
    'api, internal': 'Api Internal',
    'api internal': 'Api Internal',
    'banjir, internal': 'Banjir Internal',
    'banjir internal': 'Banjir Internal',
    'paparan hazmat , internal': 'Paparan Hazmat Internal',
    'paparan hazmat internal': 'Paparan Hazmat Internal',
    'kekurangan pasokan': 'Kekurangan Pasokan',
    'kerusakan struktur': 'Kerusakan Struktur',
    
    # Human
    'insiden korban kecelakaan besar-besaran (trauma)': 'Insiden Korban Massal (Trauma)',
    'insiden korban kecelakaan besar-besaran (medis/infeksius)': 'Insiden Korban Massal (Medis/Infeksius)',
    'terorisme, biologis': 'Terorisme Biologis',
    'terorisme biologis': 'Terorisme Biologis',
    'situasi vip': 'Situasi VIP',
    'penculikan bayi': 'Penculikan Bayi',
    'situasi penyanderaan': 'Situasi Penyanderaan',
    'gangguan sipil': 'Gangguan Sipil',
    'aksi buruh': 'Aksi Buruh',
    'admisi forensik': 'Admisi Forensik',
    'ancaman bom': 'Ancaman Bom',
    
    # Hazardous Materials
    'insiden kecelakaan hazmat besar-besaran (dari riwayat kejadian di rs dengan >= 5 korban)': 'Insiden Hazmat Massal (>= 5 Korban)',
    'insiden kecelakaan hazmat kecil (dari riwayat kejadian di rs dengan < 5 korban)': 'Insiden Hazmat Kecil (< 5 Korban)',
    'paparan kimiawi, eksternal': 'Paparan Kimiawi Eksternal',
    'paparan kimiawi eksternal': 'Paparan Kimiawi Eksternal',
    'tumpahan internal ukuran kecil-menengah': 'Tumpahan Internal Kecil-Menengah',
    'tumpahan internal luas': 'Tumpahan Internal Luas',
    'terorisme, kimiawi': 'Terorisme Kimiawi',
    'terorisme kimiawi': 'Terorisme Kimiawi',
    'paparan radiologi,  internal': 'Paparan Radiologi Internal',
    'paparan radiologi, internal': 'Paparan Radiologi Internal',
    'paparan radiologi internal': 'Paparan Radiologi Internal',
    'paparan radiologi, eksternal': 'Paparan Radiologi Eksternal',
    'paparan radiologi eksternal': 'Paparan Radiologi Eksternal',
    'terorisme, radiologis': 'Terorisme Radiologis',
    'terorisme radiologis': 'Terorisme Radiologis',
}

default_rtl = {
    'Gempa Bumi': {
        'rtl': 'Pelaksanaan simulasi evakuasi gempa berkala (Disaster Drill), inspeksi struktural berkala oleh K3RS, pemasangan pengaman perabot berat dan jalur evakuasi aman.',
        'pj': 'Komite K3RS & MFK',
        'target': 'Triwulan II 2026',
        'status': 'proses'
    },
    'Tsunami': {
        'rtl': 'Pemetaan dan sosialisasi rute evakuasi vertikal (Lt. 3 ke atas), integrasi sirine peringatan dini tsunami dengan BPBD dan BMKG.',
        'pj': 'Tim Tanggap Darurat & K3RS',
        'target': 'Semester I 2026',
        'status': 'proses'
    },
    'Hujan Badai': {
        'rtl': 'Pembersihan rutin berkala talang dan saluran drainase utama, pemangkasan dahan pohon rawan tumbang, penyiapan pompa portabel darurat.',
        'pj': 'Instalasi Sarana Umum (IPS-U)',
        'target': 'Rutin Bulanan 2026',
        'status': 'proses'
    },
    'Kegagalan Listrik': {
        'rtl': 'Uji otomatisasi transfer switch genset berkala (< 10 detik), pemeliharaan preventif UPS ruang kritis (OK/ICU/NICU), ketersediaan stok BBM solar 3x24 jam.',
        'pj': 'Instalasi Pemeliharaan Sarana (IPS-U & Medis)',
        'target': 'Rutin Bulanan 2026',
        'status': 'proses'
    },
    'Kegagalan Air': {
        'rtl': 'Pemeliharaan ground tank kapasitas 2x24 jam kebutuhan RS, MoU pengiriman darurat tangki air bersih dengan PDAM dan pihak ketiga.',
        'pj': 'Instalasi Sanitasi & IPS-U',
        'target': 'Triwulan I 2026',
        'status': 'selesai'
    },
    'Kegagalan Genset': {
        'rtl': 'Preventive maintenance mingguan genset utama dan cadangan, kontrak service resmi vendor (ATPM), kalibrasi governor dan baterai starter.',
        'pj': 'Kepala IPS-U',
        'target': 'Mingguan 2026',
        'status': 'proses'
    },
    'Kegagalan Komunikasi': {
        'rtl': 'Penyediaan dan pengecekan baterai radio HT darurat di seluruh pos perawat, penyiapan direct line GSM cadangan bebas PABX.',
        'pj': 'Instalasi IT & Keamanan',
        'target': 'Triwulan II 2026',
        'status': 'proses'
    },
    'Banjir Eksternal': {
        'rtl': 'Peninggian tanggul pintu masuk basement, kesiapan 4 unit pompa celup otomatis, penyiapan kantong pasir (sandbags) siap pasang.',
        'pj': 'K3RS & IPS-U',
        'target': 'Triwulan I 2026',
        'status': 'selesai'
    },
    'Ancaman Bom': {
        'rtl': 'Penyusunan SPO Code Black (Ancaman Bom), pelatihan penanganan paket mencurigakan bagi petugas keamanan, koordinasi nomor hotline Gegana Polri.',
        'pj': 'Kepala Keamanan & K3RS',
        'target': 'Triwulan III 2026',
        'status': 'belum_mulai'
    },
    'Kegagalan Sistem Informasi': {
        'rtl': 'Implementasi DRP (Disaster Recovery Plan), replikasi backup database SIMRS harian ke secondary off-site server, SOP layanan manual billing & MR darurat.',
        'pj': 'Kepala Instalasi IT',
        'target': 'Triwulan II 2026',
        'status': 'proses'
    },
    'Api Internal': {
        'rtl': 'Inspeksi berkala APAR & Hydrant bulanan, sertifikasi petugas Code Red di tiap shift unit kerja, uji alarm kebakaran terintegrasi.',
        'pj': 'Komite K3RS',
        'target': 'Bulanan 2026',
        'status': 'proses'
    },
    'Terorisme Biologis': {
        'rtl': 'Penyediaan ruang isolasi bertekanan negatif, stok APD level 3 siap pakai, pelatihan tim pencegahan & pengendalian infeksi (PPI).',
        'pj': 'Komite PPI & IGD',
        'target': 'Semester II 2026',
        'status': 'belum_mulai'
    },
    'Insiden Korban Massal (Trauma)': {
        'rtl': 'Penyusunan Disaster Plan Hospital Incident Command System (HICS), penyiapan triase darurat terpadu di lobi/tenda lapangan, simulasi Code Brown.',
        'pj': 'Kepala IGD & Tim HICS',
        'target': 'Semester I 2026',
        'status': 'proses'
    },
    'Tumpahan Internal Kecil-Menengah': {
        'rtl': 'Penyediaan Spill Kit lengkap di seluruh unit pengguna B3, pelatihan rutin SPO penanganan tumpahan kimia/merkuri bagi petugas farmasi/laboratorium/cleaning.',
        'pj': 'Tim K3LH & PPI',
        'target': 'Triwulan I 2026',
        'status': 'selesai'
    },
    'Insiden Korban Massal (Medis/Infeksius)': {
        'rtl': 'Protokol kesiapsiagaan lonjakan pandemi/wabah, aktivasi bangsal isolasi darurat, stok obat dan desinfektan penanganan wabah.',
        'pj': 'Komite PPI & Pelayanan Medis',
        'target': 'Triwulan II 2026',
        'status': 'proses'
    }
}

def normalize_event_name(raw_name):
    clean = re.sub(r'\s+', ' ', raw_name.strip().lower())
    if clean in canonical_events:
        return canonical_events[clean]
    if re.match(r'^\d+(\.\d+)?$', clean) or clean.startswith('*') or 'ancaman' in clean:
        return None
    return raw_name.strip().title()

def to_int(val):
    try:
        f = float(val)
        return int(round(f))
    except:
        return 0

unit_assessments = []

for filepath in sorted(files):
    unit = get_unit_for_file(filepath)
    if not unit:
        continue
    
    wb = xlrd.open_workbook(filepath)
    
    for sname, cat_key in sheets_map:
        if sname not in wb.sheet_names():
            continue
        sheet = wb.sheet_by_name(sname)
        
        order = 0
        for r in range(6, sheet.nrows):
            c0 = str(sheet.cell_value(r, 0)).strip()
            if not c0:
                continue
            
            event_name = normalize_event_name(c0)
            if not event_name:
                continue
            
            p = to_int(sheet.cell_value(r, 1))
            h = to_int(sheet.cell_value(r, 2))
            pr = to_int(sheet.cell_value(r, 3))
            b = to_int(sheet.cell_value(r, 4))
            pp = to_int(sheet.cell_value(r, 5))
            ri = to_int(sheet.cell_value(r, 6))
            re_val = to_int(sheet.cell_value(r, 7))
            
            severity = h + pr + b + pp + ri + re_val
            risk_pct = round((p / 3.0) * (severity / 18.0) * 100.0, 2) if p > 0 else 0.0
            
            rtl_info = default_rtl.get(event_name, {})
            
            order += 1
            unit_assessments.append({
                'tahun': 2026,
                'ruang_lingkup': 'unit',
                'unit_id': unit['id'],
                'unit_kode': unit['kode'],
                'unit_nama': unit['nama'],
                'kategori': cat_key,
                'nama_kejadian': event_name,
                'urutan': order,
                'probabilitas': p,
                'dampak_manusia': h,
                'dampak_properti': pr,
                'dampak_bisnis': b,
                'kesiapan': pp,
                'respon_internal': ri,
                'respon_eksternal': re_val,
                'skor_keparahan': severity,
                'skor_risiko_relatif': risk_pct,
                'rencana_tindak_lanjut': rtl_info.get('rtl') if risk_pct > 15 else None,
                'pj_mitigasi': f"{unit['nama']} & {rtl_info.get('pj')}" if risk_pct > 15 and rtl_info.get('pj') else (unit['nama'] if risk_pct > 15 else None),
                'target_waktu': rtl_info.get('target') if risk_pct > 15 else None,
                'status_mitigasi': rtl_info.get('status', 'belum_mulai') if risk_pct > 15 else 'belum_mulai',
            })

# Unit rankings
by_unit = {}
for item in unit_assessments:
    uid = item['unit_id']
    if uid not in by_unit:
        by_unit[uid] = []
    by_unit[uid].append(item)

for uid, items in by_unit.items():
    sorted_items = sorted(items, key=lambda x: x['skor_risiko_relatif'], reverse=True)
    rank = 1
    for it in sorted_items:
        if it['skor_risiko_relatif'] > 0:
            it['peringkat_prioritas'] = rank
            rank += 1
        else:
            it['peringkat_prioritas'] = None

# Hospital Aggregation (RSYS)
rs_agg = {}
for item in unit_assessments:
    key = (item['kategori'], item['nama_kejadian'])
    if key not in rs_agg:
        rs_agg[key] = {
            'kategori': item['kategori'],
            'nama_kejadian': item['nama_kejadian'],
            'p_list': [],
            'h_list': [],
            'pr_list': [],
            'b_list': [],
            'pp_list': [],
            'ri_list': [],
            're_list': [],
            'risk_list': [],
            'units_positive': 0,
        }
    rs_agg[key]['p_list'].append(item['probabilitas'])
    rs_agg[key]['h_list'].append(item['dampak_manusia'])
    rs_agg[key]['pr_list'].append(item['dampak_properti'])
    rs_agg[key]['b_list'].append(item['dampak_bisnis'])
    rs_agg[key]['pp_list'].append(item['kesiapan'])
    rs_agg[key]['ri_list'].append(item['respon_internal'])
    rs_agg[key]['re_list'].append(item['respon_eksternal'])
    rs_agg[key]['risk_list'].append(item['skor_risiko_relatif'])
    if item['skor_risiko_relatif'] > 0:
        rs_agg[key]['units_positive'] += 1

rs_assessments = []
for idx, (key, val) in enumerate(rs_agg.items()):
    n = len(val['p_list'])
    p_avg = sum(val['p_list']) / n
    h_avg = sum(val['h_list']) / n
    pr_avg = sum(val['pr_list']) / n
    b_avg = sum(val['b_list']) / n
    pp_avg = sum(val['pp_list']) / n
    ri_avg = sum(val['ri_list']) / n
    re_avg = sum(val['re_list']) / n
    risk_avg = round(sum(val['risk_list']) / n, 2)
    
    p_int = int(round(p_avg))
    h_int = int(round(h_avg))
    pr_int = int(round(pr_avg))
    b_int = int(round(b_avg))
    pp_int = int(round(pp_avg))
    ri_int = int(round(ri_avg))
    re_int = int(round(re_avg))
    sev_int = h_int + pr_int + b_int + pp_int + ri_int + re_int
    
    rtl_info = default_rtl.get(val['nama_kejadian'], {})
    
    rs_assessments.append({
        'tahun': 2026,
        'ruang_lingkup': 'rs',
        'unit_id': None,
        'unit_kode': 'RS',
        'unit_nama': 'Tingkat Rumah Sakit (RSYS)',
        'kategori': val['kategori'],
        'nama_kejadian': val['nama_kejadian'],
        'urutan': idx + 1,
        'probabilitas': p_int,
        'dampak_manusia': h_int,
        'dampak_properti': pr_int,
        'dampak_bisnis': b_int,
        'kesiapan': pp_int,
        'respon_internal': ri_int,
        'respon_eksternal': re_int,
        'skor_keparahan': sev_int,
        'skor_risiko_relatif': risk_avg,
        'units_count': val['units_positive'],
        'rencana_tindak_lanjut': rtl_info.get('rtl'),
        'pj_mitigasi': rtl_info.get('pj'),
        'target_waktu': rtl_info.get('target'),
        'status_mitigasi': rtl_info.get('status', 'belum_mulai'),
    })

rs_sorted = sorted(rs_assessments, key=lambda x: x['skor_risiko_relatif'], reverse=True)
rank = 1
for item in rs_sorted:
    if item['skor_risiko_relatif'] > 0:
        item['peringkat_prioritas'] = rank
        rank += 1
    else:
        item['peringkat_prioritas'] = None

output = {
    'unit_assessments': unit_assessments,
    'rs_assessments': rs_assessments,
    'total_unit_records': len(unit_assessments),
    'total_rs_records': len(rs_assessments),
}

with open('scratch/hva_parsed_data.json', 'w', encoding='utf-8') as f:
    json.dump(output, f, indent=2, ensure_ascii=False)

print(f"Total Unit Records: {len(unit_assessments)}")
print(f"Total Hospital Records: {len(rs_assessments)}")

