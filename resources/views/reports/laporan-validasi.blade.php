<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Validasi Data Mutu - RS Yos Sudarso Padang</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page {
            size: A4 portrait;
            margin: 10mm 12mm 10mm 12mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8px;
            color: #1e293b;
            line-height: 1.3;
        }

        .page-container {
            width: 100%;
        }
        .page-break {
            page-break-after: always;
        }

        /* ─── KOP SURAT RESMI RSYS ─── */
        .header-kop {
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 5px;
            margin-bottom: 8px;
        }
        .header-kop table { width: 100%; border-collapse: collapse; }
        .header-kop td { vertical-align: middle; border: none; padding: 0; }
        .kop-logo { width: 50px; text-align: left; }
        .kop-logo img { max-width: 44px; max-height: 44px; }
        .kop-text { text-align: center; }
        .kop-text h2 { font-size: 12px; font-weight: bold; color: #1e3a8a; text-transform: uppercase; margin: 0; letter-spacing: 0.5px; }
        .kop-text h3 { font-size: 9.5px; font-weight: bold; color: #0f172a; margin-top: 1px; }
        .kop-text p { font-size: 7px; color: #64748b; margin-top: 2px; }

        /* ─── JUDUL DOKUMEN ─── */
        .doc-title-box {
            text-align: center;
            margin-bottom: 8px;
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-left: 4px solid #1e3a8a;
            padding: 5px 8px;
        }
        .doc-title-box h3 {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f172a;
            margin: 0;
            letter-spacing: 0.3px;
        }
        .doc-title-box p {
            font-size: 8px;
            font-weight: bold;
            color: #2563eb;
            margin-top: 1px;
        }

        /* ─── META BOX ─── */
        .meta-box {
            background-color: #f1f5f9;
            border: 0.8px solid #cbd5e1;
            padding: 4px 8px;
            margin-bottom: 8px;
        }
        .meta-table { width: 100%; border-collapse: collapse; }
        .meta-table td { font-size: 7.5px; padding: 1.5px 3px; vertical-align: top; border: none; }
        .meta-label { width: 16%; color: #475569; font-weight: bold; }
        .meta-val { width: 34%; color: #0f172a; }

        /* ─── STRIP INDIKATOR HEADER ─── */
        .indicator-header {
            background-color: #1e293b;
            color: #ffffff;
            padding: 4px 8px;
            margin-bottom: 0;
            border-top-left-radius: 3px;
            border-top-right-radius: 3px;
        }
        .indicator-header table { width: 100%; border-collapse: collapse; }
        .indicator-header td { vertical-align: middle; border: none; padding: 0; }
        .ind-title { font-size: 8.5px; font-weight: bold; color: #ffffff; }
        .ind-status { text-align: right; font-size: 7.5px; font-weight: bold; }

        /* ─── BADGES ─── */
        .badge {
            display: inline-block;
            padding: 1.5px 5px;
            border-radius: 2px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-valid { background-color: #dcfce7; color: #15803d; border: 0.5px solid #86efac; }
        .badge-invalid { background-color: #fee2e2; color: #b91c1c; border: 0.5px solid #fca5a5; }
        .badge-pending { background-color: #fef3c7; color: #b45309; border: 0.5px solid #fde047; }
        .badge-impu { background-color: #fef9c3; color: #854d0e; border: 0.5px solid #facc15; }

        /* ─── TABEL 12 BARIS STANDAR RS ─── */
        .val-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        .val-table th, .val-table td {
            border: 0.8px solid #94a3b8;
            padding: 3.5px 6px;
            vertical-align: top;
            font-size: 7.5px;
        }
        .val-label {
            width: 32%;
            background-color: #f8fafc;
            color: #1e293b;
            font-weight: bold;
        }
        .val-value {
            width: 68%;
            color: #0f172a;
        }

        .highlight-agree {
            background-color: #f0fdf4;
            font-weight: bold;
            color: #166534;
        }
        .highlight-disagree {
            background-color: #fef2f2;
            font-weight: bold;
            color: #991b1b;
        }

        /* ─── CATATAN LAMPIRAN ─── */
        .note-lampiran {
            font-size: 6.8px;
            color: #64748b;
            font-style: italic;
            margin-top: 2px;
            margin-bottom: 10px;
        }

        /* ─── TANDA TANGAN RESMI ─── */
        .sign-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            page-break-inside: avoid;
        }
        .sign-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            border: none;
            padding: 2px 10px;
            font-size: 7.5px;
        }
        .sign-title { font-size: 7.5px; color: #475569; }
        .sign-role { font-size: 8px; font-weight: bold; color: #0f172a; margin-top: 1px; }
        .sign-space { height: 38px; }
        .sign-name {
            font-size: 8px;
            font-weight: bold;
            color: #0f172a;
            border-bottom: 0.8px solid #0f172a;
            display: inline-block;
            padding-bottom: 1px;
            min-width: 130px;
        }
        .sign-sub { font-size: 6.8px; color: #64748b; margin-top: 1px; }

        .empty-state {
            text-align: center;
            padding: 25px 10px;
            color: #64748b;
            font-style: italic;
            font-size: 9px;
            border: 1px dashed #cbd5e1;
            background-color: #f8fafc;
        }

        .footer-page {
            position: fixed;
            bottom: -5px;
            left: 0;
            right: 0;
            border-top: 0.5px solid #e2e8f0;
            padding-top: 3px;
            font-size: 6.5px;
            color: #94a3b8;
        }
        .footer-page table { width: 100%; border-collapse: collapse; }
        .footer-page td { border: none; padding: 0; }
    </style>
</head>
<body>

    @php
        $bulanNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $totalEntries = $entries->count();
    @endphp

    @if($entries->isEmpty())
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
                    <td style="width: 50px;"></td>
                </tr>
            </table>
        </div>

        <div class="doc-title-box">
            <h3>LAPORAN REKAPITULASI VALIDASI DATA MUTU</h3>
            <p>{{ $periodLabel }}</p>
        </div>

        <div class="meta-box">
            <table class="meta-table">
                <tr>
                    <td class="meta-label">Unit Kerja:</td>
                    <td class="meta-val"><strong>{{ $unit->nama }} ({{ $unit->kode }})</strong></td>
                    <td class="meta-label">Periode:</td>
                    <td class="meta-val">{{ $periodLabel }}</td>
                </tr>
            </table>
        </div>

        <div class="empty-state">
            Tidak ada catatan data validasi yang sesuai dengan kriteria filter yang dipilih.
        </div>
    @else
        @foreach($entries as $idx => $dataEntry)
            @php
                $v = $dataEntry->latestValidation;
                $eBulan = $dataEntry->period?->bulan ?? 1;
                $eBulanName = $bulanNames[$eBulan] ?? "Bulan {$eBulan}";
                $eTahun = $dataEntry->period?->tahun ?? $tahun;

                $valCap = $v?->validator_capaian ?? $dataEntry->capaian;
                $valNum = $v?->validator_numerator ?? $dataEntry->numerator;
                $valDen = $v?->validator_denominator ?? $dataEntry->denominator;
                $agreeRate = $v?->tingkat_kesesuaian ?? 100;
                $minCap = min($dataEntry->capaian, $valCap);
                $maxCap = max($dataEntry->capaian, $valCap);
                $isValid = ($agreeRate >= 90) && ($dataEntry->status === 'approved' || ($v && $v->is_valid));
                $isPending = $dataEntry->status === 'submitted';

                $indJenis = (array)($dataEntry->indicator?->jenis ?? []);
                $isImpu = str_contains(strtoupper($dataEntry->indicator?->kode ?? ''), 'IMP-U') || 
                          collect($indJenis)->contains(fn($j) => is_string($j) && (str_contains(strtoupper($j), 'IMP-U') || str_contains(strtolower($j), 'prioritas unit')));
            @endphp

            <div class="page-container {{ $idx < $totalEntries - 1 ? 'page-break' : '' }}">
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
                            <td style="width: 50px;"></td>
                        </tr>
                    </table>
                </div>

                <!-- JUDUL DOKUMEN -->
                <div class="doc-title-box">
                    <h3>LAPORAN VALIDASI DATA INDIKATOR MUTU</h3>
                    <p>Standar Akreditasi Rumah Sakit (Kemenkes / SNARS)</p>
                </div>

                <!-- META INFO -->
                <div class="meta-box">
                    <table class="meta-table">
                        <tr>
                            <td class="meta-label">Unit Kerja:</td>
                            <td class="meta-val"><strong>{{ $unit->nama }} ({{ $unit->kode }})</strong></td>
                            <td class="meta-label">Periode Data:</td>
                            <td class="meta-val"><strong>{{ $eBulanName }} {{ $eTahun }}</strong></td>
                        </tr>
                        <tr>
                            <td class="meta-label">Indikator Mutu:</td>
                            <td class="meta-val">[{{ $dataEntry->indicator?->kode }}] {{ $dataEntry->indicator?->judul }}</td>
                            <td class="meta-label">Target Capaian:</td>
                            <td class="meta-val">{{ $dataEntry->indicator?->target_operator }} {{ $dataEntry->indicator?->target_capaian }}%</td>
                        </tr>
                    </table>
                </div>

                <!-- HEADER STRIP INDIKATOR -->
                <div class="indicator-header">
                    <table>
                        <tr>
                            <td class="ind-title">
                                #{{ $idx + 1 }} • [{{ $dataEntry->indicator?->kode }}] {{ $dataEntry->indicator?->judul }}
                                @if($isImpu)
                                    &nbsp;<span class="badge badge-impu">IMP-U • Prioritas Unit</span>
                                @endif
                            </td>
                            <td class="ind-status">
                                @if($isPending)
                                    <span class="badge badge-pending">Menunggu Pengesahan</span>
                                @elseif($isValid)
                                    <span class="badge badge-valid">VALID (Kesesuaian {{ $agreeRate }}% ≥ 90%)</span>
                                @else
                                    <span class="badge badge-invalid">TIDAK VALID (Kesesuaian {{ $agreeRate }}% &lt; 90%)</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- TABEL 12 BARIS STANDAR RS -->
                <table class="val-table">
                    <tr>
                        <td class="val-label">1. Judul Indikator</td>
                        <td class="val-value"><strong>{{ $dataEntry->indicator?->judul }}</strong></td>
                    </tr>
                    <tr>
                        <td class="val-label">2. Numerator</td>
                        <td class="val-value">{{ $dataEntry->indicator?->numerator_keterangan ?: 'Sesuai dengan keterangan numerator pada profil indikator mutu' }}</td>
                    </tr>
                    <tr>
                        <td class="val-label">3. Denominator</td>
                        <td class="val-value">{{ $dataEntry->indicator?->denominator_keterangan ?: 'Sesuai dengan keterangan denominator pada profil indikator mutu' }}</td>
                    </tr>
                    <tr>
                        <td class="val-label">4. Sumber Data</td>
                        <td class="val-value">{{ $dataEntry->indicator?->sumber_data ?: 'Data Rekam Medis / EMR Unit' }}</td>
                    </tr>
                    <tr>
                        <td class="val-label">5. Capaian Indikator (Unit)</td>
                        <td class="val-value">
                            <strong>{{ $dataEntry->capaian }}%</strong> &nbsp;&nbsp; 
                            <span style="color: #64748b;">({{ number_format($dataEntry->numerator, 0, ',', '.') }} / {{ number_format($dataEntry->denominator, 0, ',', '.') }} × 100%)</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="val-label">6. Jumlah Sampel Validasi</td>
                        <td class="val-value"><strong>{{ $v?->jumlah_sampel_validasi ?: number_format($valDen, 0, ',', '.') }}</strong> Sampel Uji Petik</td>
                    </tr>
                    <tr>
                        <td class="val-label">7. Justifikasi Perlu Validasi</td>
                        <td class="val-value">{{ $v?->justifikasi_perlu_validasi ?: 'Wajib validasi awal tahun (Imut Prioritas Unit)' }}</td>
                    </tr>
                    <tr>
                        <td class="val-label">8. Metode Validasi</td>
                        <td class="val-value">{{ $v?->metode_validasi ?: 'Measurement Result Agreement / Kesepakatan pengukuran berdasarkan hasil' }}</td>
                    </tr>
                    <tr>
                        <td class="val-label">9. Hasil Validasi (Uji Petik)</td>
                        <td class="val-value">
                            <strong>{{ $valCap }}%</strong> &nbsp;&nbsp;
                            <span style="color: #64748b;">({{ number_format($valNum, 0, ',', '.') }} / {{ number_format($valDen, 0, ',', '.') }} × 100%)</span>
                        </td>
                    </tr>
                    <tr class="{{ $agreeRate >= 90 ? 'highlight-agree' : 'highlight-disagree' }}">
                        <td class="val-label" style="{{ $agreeRate >= 90 ? 'background-color: #dcfce7; color: #166534;' : 'background-color: #fee2e2; color: #991b1b;' }}">
                            10. Hasil Analisa Kesesuaian
                        </td>
                        <td class="val-value">
                            {{ $v?->hasil_analisa ?: "{$minCap} / {$maxCap} × 100% = {$agreeRate}% ----> 90% (standar " . ($agreeRate >= 90 ? 'terpenuhi' : 'belum terpenuhi') . ")" }}
                        </td>
                    </tr>
                    <tr>
                        <td class="val-label">11. Kesimpulan</td>
                        <td class="val-value">
                            <strong>{{ $v?->kesimpulan ?: "Data bulan {$eBulanName} {$eTahun} " . ($agreeRate >= 90 ? 'valid' : 'tidak valid') }}</strong>
                        </td>
                    </tr>
                    <tr>
                        <td class="val-label">12. Rencana Tindak Lanjut</td>
                        <td class="val-value">{{ $v?->rencana_tindak_lanjut ?: ($agreeRate >= 90 ? 'Lanjutkan pengukuran indicator mutu sesuai profil indikator' : 'Samakan persepsi dan lakukan validasi ulang bulan depan') }}</td>
                    </tr>
                </table>

                <p class="note-lampiran">* Lampiran data imut yang divalidasi dan hasil validasinya.</p>

                <!-- TANDA TANGAN PENGESAHAN -->
                <table class="sign-table">
                    <tr>
                        <td>
                            <p class="sign-title">Mengetahui,</p>
                            <p class="sign-role">Kepala Unit / Pengumpul Data {{ $unit->nama }}</p>
                            <div class="sign-space"></div>
                            <p class="sign-name">{{ $dataEntry->user?->nama_lengkap ?: ($dataEntry->user?->name ?: 'PIC / Kepala Unit') }}</p>
                            <p class="sign-sub">NIP / ID: {{ $dataEntry->user?->nip ?: ($dataEntry->user?->username ?: '-') }}</p>
                        </td>
                        <td>
                            <p class="sign-title">Validator,</p>
                            <p class="sign-role">
                                @if($v && $v->validator && $v->validator->isKomiteMutu())
                                    Komite Mutu & Keselamatan RS
                                @else
                                    Pengesah Validasi Internal Unit
                                @endif
                            </p>
                            <div class="sign-space"></div>
                            @php
                                $validatorName = $v?->validator?->nama_lengkap ?: ($v?->validator?->name ?: ($v && $v->validator && $v->validator->isKomiteMutu() ? 'Tim Validator Komite Mutu' : 'PIC / Pengesah Unit'));
                            @endphp
                            <p class="sign-name">{{ $validatorName }}</p>
                            <p class="sign-sub">
                                @if($v && $v->tanggal_validasi)
                                    Disahkan: {{ \Carbon\Carbon::parse($v->tanggal_validasi)->translatedFormat('d F Y') }}
                                @else
                                    Tanggal: {{ date('d F Y') }}
                                @endif
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        @endforeach
    @endif

    <div class="footer-page">
        <table>
            <tr>
                <td>MUTU RSYS • Sistem Informasi Mutu RS Yos Sudarso Padang</td>
                <td style="text-align: right;">Dicetak otomatis pada: {{ date('d/m/Y H:i') }} WIB</td>
            </tr>
        </table>
    </div>

</body>
</html>
