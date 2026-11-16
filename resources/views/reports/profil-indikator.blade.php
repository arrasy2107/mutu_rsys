<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil Indikator Mutu - RS Yos Sudarso Padang</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page {
            size: A4 portrait;
            margin: 12mm 15mm 12mm 15mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8.5px;
            color: #1e293b;
            line-height: 1.35;
        }

        .page-container {
            width: 100%;
        }
        .page-break {
            page-break-after: always;
        }

        .header-kop {
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .header-kop table { width: 100%; border-collapse: collapse; }
        .header-kop td { vertical-align: middle; border: none; padding: 0; }
        .kop-logo { width: 55px; text-align: left; }
        .kop-logo img { max-width: 48px; max-height: 48px; }
        .kop-text { text-align: center; }
        .kop-text h2 { font-size: 13px; font-weight: bold; color: #1e3a8a; text-transform: uppercase; margin: 0; letter-spacing: 0.5px; }
        .kop-text h3 { font-size: 10px; font-weight: bold; color: #0f172a; margin-top: 2px; }
        .kop-text p { font-size: 7px; color: #64748b; margin-top: 2px; }

        .doc-title-box {
            text-align: center;
            margin-bottom: 10px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #1e3a8a;
            padding: 6px 10px;
        }
        .doc-title-box h3 {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f172a;
            margin: 0;
        }
        .doc-title-box p {
            font-size: 8.5px;
            font-weight: bold;
            color: #2563eb;
            margin-top: 2px;
        }

        .kamus-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        .kamus-table th, .kamus-table td {
            border: 0.8px solid #cbd5e1;
            padding: 4px 6px;
            vertical-align: top;
            font-size: 8px;
        }
        .kamus-label {
            width: 32%;
            background-color: #f1f5f9;
            color: #334155;
            font-weight: bold;
        }
        .kamus-value {
            width: 68%;
            color: #0f172a;
        }

        .section-separator {
            background-color: #1e3a8a;
            color: #ffffff;
            font-weight: bold;
            font-size: 8.5px;
            padding: 3px 6px;
            text-transform: uppercase;
        }

        .badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: bold;
            margin-right: 3px;
        }
        .badge-imn { background-color: #fee2e2; color: #b91c1c; border: 0.5px solid #f87171; }
        .badge-imp-rs { background-color: #fef3c7; color: #b45309; border: 0.5px solid #fbbf24; }
        .badge-imp-u { background-color: #d1fae5; color: #047857; border: 0.5px solid #34d399; }
        .badge-imu { background-color: #ccfbf1; color: #0f766e; border: 0.5px solid #2dd4bf; }
        .badge-spm { background-color: #e0f2fe; color: #0369a1; border: 0.5px solid #38bdf8; }

        .target-box {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            color: #1d4ed8;
            font-size: 9px;
            display: inline-block;
        }

        .footer-note {
            margin-top: 10px;
            border-top: 0.5px solid #e2e8f0;
            padding-top: 3px;
            font-size: 7px;
            color: #94a3b8;
            text-align: right;
            font-style: italic;
        }
    </style>
</head>
<body>

@php
    $totalCount = $indicators->count();
@endphp

@foreach($indicators as $idx => $ind)
    <div class="page-container {{ $idx < $totalCount - 1 ? 'page-break' : '' }}">
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
                    <td style="width: 55px;"></td>
                </tr>
            </table>
        </div>

        <!-- JUDUL DOKUMEN -->
        <div class="doc-title-box">
            <h3>KAMUS / PROFIL INDIKATOR MUTU</h3>
            <p>{{ $ind->kode }} - {{ $ind->judul }}</p>
        </div>

        <!-- TABEL PROFIL LENGKAP -->
        <table class="kamus-table">
            <tr>
                <td colspan="2" class="section-separator">A. IDENTITAS INDIKATOR MUTU</td>
            </tr>
            <tr>
                <td class="kamus-label">1. Judul Indikator</td>
                <td class="kamus-value"><strong>{{ $ind->judul }}</strong></td>
            </tr>
            <tr>
                <td class="kamus-label">2. Kode Indikator</td>
                <td class="kamus-value"><strong>{{ $ind->kode }}</strong></td>
            </tr>
            <tr>
                <td class="kamus-label">3. Unit Kerja Terkait</td>
                <td class="kamus-value">{{ $ind->unit ? $ind->unit->nama : 'Tingkat Rumah Sakit (Semua Unit Kerja)' }}</td>
            </tr>
            <tr>
                <td class="kamus-label">4. Jenis Indikator Mutu</td>
                <td class="kamus-value">
                    @if(is_array($ind->jenis))
                        @foreach($ind->jenis as $j)
                            <span class="badge {{ $j === 'IMN' ? 'badge-imn' : ($j === 'IMP-RS' ? 'badge-imp-rs' : ($j === 'IMP-U' ? 'badge-imp-u' : ($j === 'IMU' ? 'badge-imu' : 'badge-spm'))) }}">
                                {{ $j }}
                            </span>
                        @endforeach
                    @else
                        {{ $ind->jenis ?: '-' }}
                    @endif
                </td>
            </tr>
            <tr>
                <td class="kamus-label">5. Tipe / Jenis Indikator</td>
                <td class="kamus-value">
                    {{ is_array($ind->jenis_indikator) ? implode(', ', $ind->jenis_indikator) : ($ind->jenis_indikator ?: '-') }}
                </td>
            </tr>
            <tr>
                <td class="kamus-label">6. Dimensi Mutu</td>
                <td class="kamus-value">
                    {{ is_array($ind->dimensi_mutu) ? implode(', ', $ind->dimensi_mutu) : ($ind->dimensi_mutu ?: '-') }}
                </td>
            </tr>
            <tr>
                <td class="kamus-label">7. Dasar Pemikiran</td>
                <td class="kamus-value">{{ $ind->dasar_pemikiran ?: '-' }}</td>
            </tr>
            <tr>
                <td class="kamus-label">8. Tujuan Pengukuran</td>
                <td class="kamus-value">{{ $ind->tujuan ?: '-' }}</td>
            </tr>
            <tr>
                <td class="kamus-label">9. Definisi Operasional</td>
                <td class="kamus-value">{{ $ind->definisi_operasional ?: '-' }}</td>
            </tr>
            <tr>
                <td class="kamus-label">10. Kriteria Inklusi</td>
                <td class="kamus-value">{{ $ind->kriteria_inklusi ?: '-' }}</td>
            </tr>
            <tr>
                <td class="kamus-label">11. Kriteria Eksklusi</td>
                <td class="kamus-value">{{ $ind->kriteria_eksklusi ?: '-' }}</td>
            </tr>

            <tr>
                <td colspan="2" class="section-separator">B. METODE PENGUKURAN & TARGET CAPAIAN</td>
            </tr>
            <tr>
                <td class="kamus-label">12. Numerator (Pembilang)</td>
                <td class="kamus-value">{{ $ind->numerator_keterangan ?: '-' }}</td>
            </tr>
            <tr>
                <td class="kamus-label">13. Denominator (Penyebut)</td>
                <td class="kamus-value">{{ $ind->denominator_keterangan ?: '-' }}</td>
            </tr>
            <tr>
                <td class="kamus-label">14. Formula Perhitungan</td>
                <td class="kamus-value" style="font-family: monospace; font-size: 8.5px; color: #1e3a8a;">
                    {{ $ind->formula ?: '-' }}
                </td>
            </tr>
            <tr>
                <td class="kamus-label">15. Satuan Pengukuran</td>
                <td class="kamus-value">{{ $ind->satuan_pengukuran ?: 'Persentase (%)' }}</td>
            </tr>
            <tr>
                <td class="kamus-label">16. Target Capaian</td>
                <td class="kamus-value">
                    <span class="target-box">
                        {{ $ind->target_operator === '>=' ? '≥' : ($ind->target_operator === '<=' ? '≤' : ($ind->target_operator === '>' ? '>' : ($ind->target_operator === '<' ? '<' : '='))) }}
                        {{ $ind->target_capaian }} {{ $ind->satuan_pengukuran === 'Persentase' ? '%' : ($ind->satuan_pengukuran === 'Permil' ? '‰' : ($ind->satuan_pengukuran ? "({$ind->satuan_pengukuran})" : '')) }}
                    </span>
                </td>
            </tr>

            <tr>
                <td colspan="2" class="section-separator">C. METODOLOGI & MANAJEMEN DATA</td>
            </tr>
            <tr>
                <td class="kamus-label">17. Periode Pengumpulan Data</td>
                <td class="kamus-value"><strong>{{ $ind->frekuensi ?: 'Bulanan' }}</strong></td>
            </tr>
            <tr>
                <td class="kamus-label">18. Sumber Data</td>
                <td class="kamus-value">{{ $ind->sumber_data ?: '-' }}</td>
            </tr>
            <tr>
                <td class="kamus-label">19. Metode Pengumpulan Data</td>
                <td class="kamus-value">{{ $ind->metode_pengumpulan ?: 'Retrospektif / Konkuren' }}</td>
            </tr>
            <tr>
                <td class="kamus-label">20. Instrumen Pengambilan Data</td>
                <td class="kamus-value">{{ $ind->instrumen_pengambilan ?: 'Formulir Pengumpul Data / SIMRS' }}</td>
            </tr>
            <tr>
                <td class="kamus-label">21. Besar Sampel & Cara Pengambilan</td>
                <td class="kamus-value">
                    Besar Sampel: {{ $ind->besar_sampel ?: 'Total Populasi' }} | 
                    Cara: {{ $ind->cara_pengambilan_sampel ?: 'Sampling Acak / Sensus Harian' }}
                </td>
            </tr>
            <tr>
                <td class="kamus-label">22. Periode Analisis & Pelaporan</td>
                <td class="kamus-value">{{ $ind->periode_analisis ?: 'Bulanan dan Triwulanan' }}</td>
            </tr>
            <tr>
                <td class="kamus-label">23. Penanggung Jawab Data</td>
                <td class="kamus-value">{{ $ind->penanggung_jawab ?: ($ind->unit ? 'Kepala / PJ ' . $ind->unit->nama : 'Komite Mutu RS') }}</td>
            </tr>
            <tr>
                <td class="kamus-label">24. Pengumpul Data</td>
                <td class="kamus-value">{{ $ind->pengumpul_data ?: 'PIC Pengumpul Data Unit Terkait' }}</td>
            </tr>
            <tr>
                <td class="kamus-label">25. Validator Data</td>
                <td class="kamus-value">{{ $ind->validator ?: 'PJ Mutu / Validator Unit' }}</td>
            </tr>
            <tr>
                <td class="kamus-label">26. Rencana Publikasi Data</td>
                <td class="kamus-value">{{ $ind->publikasi_data ?: 'Internal Rumah Sakit (Laporan Eksekutif Mutu)' }}</td>
            </tr>
        </table>

        <!-- FOOTER -->
        <div class="footer-note">
            Profil Indikator Mutu &bull; Standar Akreditasi Rumah Sakit (KARS / PMKP) &bull; Dicetak pada {{ \Carbon\Carbon::now()->translatedFormat('d F Y H:i:s') }} WIB
        </div>
    </div>
@endforeach

</body>
</html>

