<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekapitulasi Pemilihan Indikator Mutu - {{ $selection->unit ? $selection->unit->nama : 'Rumah Sakit' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page {
            size: A4 landscape;
            margin: 6mm 8mm 4mm 8mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 7.5px;
            color: #1e293b;
            line-height: 1.15;
        }

        .header-kop {
            border-bottom: 1.5px solid #1e3a8a;
            padding-bottom: 3px;
            margin-bottom: 4px;
        }
        .header-kop table { width: 100%; border-collapse: collapse; }
        .header-kop td { vertical-align: middle; border: none; padding: 0; }
        .kop-logo { width: 45px; text-align: left; }
        .kop-logo img { max-width: 38px; max-height: 38px; }
        .kop-text { text-align: center; }
        .kop-text h2 { font-size: 10.5px; font-weight: bold; color: #1e3a8a; text-transform: uppercase; margin: 0; }
        .kop-text h3 { font-size: 8px; font-weight: bold; color: #0f172a; margin-top: 1px; }
        .kop-text p { font-size: 6px; color: #64748b; margin-top: 1px; }

        .doc-title-box {
            text-align: center;
            margin-bottom: 3px;
        }
        .doc-title-box h3 {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f172a;
            margin: 0;
        }
        .doc-title-box p {
            font-size: 7.5px;
            font-weight: bold;
            color: #2563eb;
            margin-top: 1px;
        }

        .meta-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 3px solid #2563eb;
            padding: 3px 6px;
            margin-bottom: 3px;
        }
        .meta-table { width: 100%; border-collapse: collapse; }
        .meta-table td { font-size: 6.8px; padding: 1px 3px; vertical-align: top; border: none; line-height: 1.15; }
        .meta-label { width: 14%; color: #475569; font-weight: bold; }
        .meta-val { width: 36%; color: #0f172a; }

        .section-header {
            background-color: #1e3a8a;
            color: #ffffff;
            font-size: 7.2px;
            font-weight: bold;
            padding: 2px 5px;
            margin-top: 3px;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 6.7px;
            margin-bottom: 3px;
        }
        table.data-table th, table.data-table td {
            border: 0.8px solid #cbd5e1;
            padding: 1.5px 3px;
            line-height: 1.15;
        }
        table.data-table thead th {
            background-color: #f1f5f9;
            color: #1e293b;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
        }
        table.data-table thead tr.sub-head th {
            font-size: 6.3px;
            background-color: #f8fafc;
        }
        table.data-table tbody td {
            vertical-align: middle;
        }
        table.data-table tbody tr:nth-child(even) {
            background-color: #fbfcfe;
        }

        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }

        .badge-ditetapkan {
            background-color: #dcfce7;
            color: #15803d;
            font-weight: bold;
            padding: 1px 3px;
            border-radius: 2px;
            display: inline-block;
            font-size: 6px;
            border: 0.8px solid #bbf7d0;
        }
        .badge-tidak {
            background-color: #f1f5f9;
            color: #64748b;
            padding: 1px 3px;
            border-radius: 2px;
            display: inline-block;
            font-size: 6px;
            border: 0.8px solid #e2e8f0;
        }

        .conclusion-box {
            border: 0.8px solid #cbd5e1;
            padding: 3px 6px;
            background-color: #ffffff;
            margin-top: 3px;
            margin-bottom: 4px;
        }
        .conclusion-title {
            font-size: 7.2px;
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 2px;
            text-transform: uppercase;
            border-bottom: 0.8px solid #e2e8f0;
            padding-bottom: 1.5px;
        }
        .conclusion-item {
            margin-bottom: 2px;
        }
        .conclusion-label {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 2px;
            font-weight: bold;
            font-size: 6.3px;
            margin-bottom: 1px;
        }
        .label-imp { background-color: #dbeafe; color: #1e40af; border: 0.8px solid #bfdbfe; }
        .label-imu { background-color: #f1f5f9; color: #334155; border: 0.8px solid #cbd5e1; }

        .signatures {
            width: 100%;
            margin-top: 3px;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        .signatures td {
            width: 50%;
            vertical-align: top;
            text-align: center;
            border: none;
            padding: 1px 15px;
        }
        .sig-space {
            height: 22px;
        }
        .sig-name {
            font-size: 7px;
            font-weight: bold;
            text-decoration: underline;
            color: #0f172a;
        }
        .sig-role {
            font-size: 6.3px;
            color: #475569;
        }

        .footer-note {
            margin-top: 2px;
            font-size: 5.8px;
            color: #94a3b8;
            border-top: 0.8px solid #e2e8f0;
            padding-top: 2px;
            text-align: right;
        }
    </style>
</head>
<body>

    <!-- KOP RESMI RSYS -->
    <div class="header-kop">
        <table>
            <tr>
                <td class="kop-logo">
                    @php
                        $logoPath = public_path('images/logo_rsys.png');
                    @endphp
                    @if(file_exists($logoPath))
                        <img src="{{ $logoPath }}" alt="Logo RS">
                    @endif
                </td>
                <td class="kop-text">
                    <h2>RUMAH SAKIT YOS SUDARSO PADANG</h2>
                    <h3>KOMITE MUTU & KESELAMATAN PASIEN</h3>
                    <p>Jl. Situjuh No. 1, Padang, Sumatera Barat | Telp: (0751) 33230 | Sistem Indikator Mutu RS (MUTU RSYS)</p>
                </td>
                <td style="width: 45px;"></td>
            </tr>
        </table>
    </div>

    <!-- JUDUL DOKUMEN & METADATA -->
    <div class="doc-title-box">
        <h3>BERITA ACARA & REKAPITULASI PEMILIHAN INDIKATOR MUTU</h3>
        <p>
            {{ $selection->tipe === 'rs' ? 'TINGKAT RUMAH SAKIT (IMP-RS) TAHUN ' . \Carbon\Carbon::parse($selection->tanggal)->year : 'TINGKAT UNIT: ' . strtoupper($selection->unit?->nama ?? 'SEMUA UNIT') . ' TAHUN ' . \Carbon\Carbon::parse($selection->tanggal)->year }}
        </p>
    </div>

    <div class="meta-box">
        <table class="meta-table">
            <tr>
                <td class="meta-label">Unit / Pelaksana</td>
                <td class="meta-val">: {{ $selection->unit ? $selection->unit->nama : 'Tingkat Rumah Sakit (Komite Mutu / Direksi)' }}</td>
                <td class="meta-label">Hari / Tanggal</td>
                <td class="meta-val">: {{ \Carbon\Carbon::parse($selection->tanggal)->translatedFormat('l, d F Y') }}</td>
            </tr>
            <tr>
                <td class="meta-label">Jam Rapat</td>
                <td class="meta-val">: {{ $selection->jam ?: '-' }} WIB</td>
                <td class="meta-label">Tempat Rapat</td>
                <td class="meta-val">: {{ $selection->tempat ?: 'Ruang Rapat Unit Kerja' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Pemimpin Rapat</td>
                <td class="meta-val">: {{ $selection->pemimpinRapat?->name ?? $selection->pemimpin_rapat_nama ?? '-' }}</td>
                <td class="meta-label">Status Sesi</td>
                <td class="meta-val">: <strong>{{ $selection->status === 'final' ? 'DITETAPKAN (FINAL)' : 'DRAFT' }}</strong></td>
            </tr>
        </table>
    </div>

    <!-- I. SKRINING ALGORITMA PEMILIHAN -->
    <div class="section-header">
        I. Algoritma Pemilihan Indikator Mutu {{ $selection->tipe === 'rs' ? 'Prioritas Rumah Sakit (IMP-RS)' : 'Tingkat Unit' }}
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 18px;">No</th>
                <th rowspan="2" style="width: 180px;">Usulan Indikator Mutu</th>
                <th rowspan="2" style="width: 150px;">Data Pendukung (Masalah)</th>
                <th colspan="5">Tahap 1: Kriteria Kelayakan</th>
                <th colspan="2">Tahap 2: Kendali</th>
                <th rowspan="2" style="width: 42px;">Tahap 3<br>Dapat Diukur</th>
                <th rowspan="2" style="width: 58px;">Penetapan</th>
            </tr>
            <tr class="sub-head">
                <th style="width: 32px;">Visi Misi</th>
                <th style="width: 38px;">Problem Prone</th>
                <th style="width: 38px;">High Volume</th>
                <th style="width: 38px;">High Risk/Cost</th>
                <th style="width: 38px;">Sesuai Aturan</th>
                <th style="width: 42px;">{{ $selection->tipe === 'rs' ? 'Kendali RS' : 'Kendali Unit' }}</th>
                <th style="width: 42px;">{{ $selection->tipe === 'rs' ? 'Regulasi RS' : 'Kendali RS' }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($selection->items as $idx => $item)
            <tr>
                <td class="text-center font-bold">{{ $idx + 1 }}</td>
                <td class="font-bold">{{ $item->usulan_imut }}</td>
                <td>{{ $item->data_pendukung ?: '-' }}</td>
                <td class="text-center font-bold" style="color: {{ $item->ssi_visi_misi ? '#2563eb' : '#94a3b8' }};">{{ $item->ssi_visi_misi ? '✓' : '-' }}</td>
                <td class="text-center font-bold" style="color: {{ $item->problem_prone ? '#2563eb' : '#94a3b8' }};">{{ $item->problem_prone ? '✓' : '-' }}</td>
                <td class="text-center font-bold" style="color: {{ $item->high_volume ? '#2563eb' : '#94a3b8' }};">{{ $item->high_volume ? '✓' : '-' }}</td>
                <td class="text-center font-bold" style="color: {{ $item->high_risk_cost ? '#2563eb' : '#94a3b8' }};">{{ $item->high_risk_cost ? '✓' : '-' }}</td>
                <td class="text-center font-bold" style="color: {{ $item->ssi_aturan ? '#2563eb' : '#94a3b8' }};">{{ $item->ssi_aturan ? '✓' : '-' }}</td>
                <td class="text-center font-bold" style="color: {{ $item->dpt_dikendalikan_unit ? '#2563eb' : '#94a3b8' }};">{{ $item->dpt_dikendalikan_unit ? '✓' : '-' }}</td>
                <td class="text-center font-bold" style="color: {{ $item->tdk_dpt_dikendalikan_rs ? '#2563eb' : '#94a3b8' }};">{{ $item->tdk_dpt_dikendalikan_rs ? '✓' : '-' }}</td>
                <td class="text-center font-bold" style="color: {{ $item->dpt_diukur ? '#2563eb' : '#94a3b8' }};">{{ $item->dpt_diukur ? '✓' : '-' }}</td>
                <td class="text-center">
                    @if($item->is_ditetapkan)
                        <span class="badge-ditetapkan">DITETAPKAN</span>
                    @else
                        <span class="badge-tidak">TIDAK</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="12" class="text-center" style="padding: 6px; color: #94a3b8;">Belum ada usulan indikator mutu</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- II. MATRIKS SKORING PRIORITAS -->
    <div class="section-header">
        II. Matriks Skoring Prioritas Indikator Mutu (Skala 1 - 5)
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 18px;">No</th>
                <th>Topik Usulan Indikator Mutu</th>
                <th style="width: 44px;">K1: Masalah</th>
                <th style="width: 44px;">K2: High Vol</th>
                <th style="width: 44px;">K3: High Pros</th>
                <th style="width: 46px;">K4: Ketdkpuas</th>
                <th style="width: 44px;">K5: Mudah Ukur</th>
                <th style="width: 44px;">K6: Regulasi</th>
                <th style="width: 44px;">K7: Renstra</th>
                <th style="width: 46px;">K8: Patient Exp</th>
                <th style="width: 48px; background-color: #e2e8f0;">Total Skor</th>
                <th style="width: 60px;">Peringkat</th>
            </tr>
        </thead>
        <tbody>
            @php
                $sortedItems = $selection->items->sortByDesc('total_skor')->values();
            @endphp
            @forelse($sortedItems as $idx => $item)
            <tr>
                <td class="text-center font-bold">{{ $idx + 1 }}</td>
                <td class="font-bold">{{ $item->usulan_imut }}</td>
                <td class="text-center">{{ $item->skor_masalah_banyak }}</td>
                <td class="text-center">{{ $item->skor_high_volume }}</td>
                <td class="text-center">{{ $item->skor_high_proses }}</td>
                <td class="text-center">{{ $item->skor_ketidakpuasan }}</td>
                <td class="text-center">{{ $item->skor_kemudahan_ukur }}</td>
                <td class="text-center">{{ $item->skor_ketentuan_pemerintah }}</td>
                <td class="text-center">{{ $item->skor_sesuai_renstra }}</td>
                <td class="text-center">{{ $item->skor_patient_experience }}</td>
                <td class="text-center font-bold" style="background-color: #f1f5f9; color: #1e3a8a;">
                    {{ $item->total_skor }}
                </td>
                <td class="text-center">
                    @if($idx === 0)
                        <span style="font-weight: bold; color: #1e40af;">Prioritas 1 (IMP)</span>
                    @else
                        <span>Prioritas {{ $idx + 1 }}</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="12" class="text-center" style="padding: 6px; color: #94a3b8;">Belum ada data skoring</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- III. KESIMPULAN HASIL PENETAPAN -->
    <div class="conclusion-box">
        <div class="conclusion-title">III. Kesimpulan Hasil Penetapan</div>
        
        @php
            $impItem = $selection->items->firstWhere('is_imp', true) ?? $sortedItems->first();
            // Menampilkan seluruh usulan selain IMP sebagai IMU sesuai tampilan aplikasi
            $imuItems = $selection->items->filter(fn($it) => $it->id !== $impItem?->id)->values();
        @endphp

        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 58%; vertical-align: top; padding-right: 8px;">
                    <div class="conclusion-item">
                        <span class="conclusion-label label-imp">
                            1. INDIKATOR MUTU PRIORITAS ({{ $selection->tipe === 'rs' ? 'IMP-RS' : 'IMP-U' }})
                        </span>
                        <div style="font-size: 7.5px; font-weight: bold; color: #0f172a; margin-top: 1px;">
                            {{ $impItem?->usulan_imut ?? '(Belum Ditetapkan)' }}
                            <span style="font-size: 6.8px; color: #2563eb; font-weight: normal; margin-left: 4px;">
                                (Total Skor: <strong>{{ $impItem?->total_skor ?? 0 }}</strong>)
                            </span>
                        </div>
                    </div>

                    <div class="conclusion-item" style="margin-top: 3px;">
                        <span class="conclusion-label label-imu">
                            2. INDIKATOR MUTU UNIT (IMU / PENDUKUNG)
                        </span>
                        @if($imuItems->count() > 0)
                            <table style="width: 100%; border-collapse: collapse; margin-top: 1px;">
                                @foreach($imuItems as $idx => $imu)
                                    <tr>
                                        <td style="width: 12px; vertical-align: top; font-size: 6.8px; color: #475569; padding: 0.5px 0;">
                                            {{ $idx + 1 }}.
                                        </td>
                                        <td style="font-size: 6.8px; color: #334155; padding: 0.5px 2px;">
                                            <strong>{{ $imu->usulan_imut }}</strong>
                                            <span style="color: #64748b;">(Skor: {{ $imu->total_skor }})</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        @else
                            <p style="font-size: 6.5px; color: #94a3b8; font-style: italic; margin-top: 1px;">
                                - Tidak ada usulan IMU pendukung lainnya -
                            </p>
                        @endif
                    </div>
                </td>
                <td style="width: 42%; vertical-align: top; padding-left: 8px; border-left: 0.8px solid #e2e8f0;">
                    <strong style="font-size: 7px; color: #334155;">Catatan / Notulen Rapat:</strong>
                    <p style="font-size: 6.5px; color: #475569; margin-top: 2px; line-height: 1.2; font-style: {{ $selection->notulen ? 'normal' : 'italic' }};">
                        {{ $selection->notulen ?: 'Tidak ada catatan tambahan dalam notulen rapat pemilihan indikator mutu ini.' }}
                    </p>
                </td>
            </tr>
        </table>
    </div>

    <!-- TANDA TANGAN -->
    <table class="signatures">
        <tr>
            <td>
                <p class="sig-role">Pemimpin Sesi Pemilihan,</p>
                <div class="sig-space"></div>
                <p class="sig-name">{{ $selection->pemimpinRapat?->name ?? $selection->pemimpin_rapat_nama ?? '(................................................)' }}</p>
                <p class="sig-role">{{ $selection->pemimpinRapat ? ($selection->pemimpinRapat->role === 'kepala_unit' ? 'Kepala Unit' : 'PIC Unit') : 'Pemimpin Rapat' }}</p>
            </td>
            <td>
                <p class="sig-role">
                    {{ $selection->tipe === 'rs' ? 'Ketua Komite Mutu RSYS,' : 'Kepala Unit / Penanggung Jawab Mutu,' }}
                </p>
                <div class="sig-space"></div>
                <p class="sig-name">{{ $selection->createdBy?->name ?? '(................................................)' }}</p>
                <p class="sig-role">{{ $selection->tipe === 'rs' ? 'Komite Mutu & Keselamatan Pasien' : ($selection->unit?->nama ?? 'Unit Kerja') }}</p>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Dicetak melalui Sistem Indikator Mutu RS (MUTU RSYS) pada {{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i:s') }} WIB
    </div>

</body>
</html>
