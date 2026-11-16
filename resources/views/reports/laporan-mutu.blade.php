<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Indikator Mutu Rumah Sakit</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #1a1a2e; }
        .header { background: #1E1B4B; color: white; padding: 14px 20px; margin-bottom: 14px; }
        .header h1 { font-size: 15px; font-weight: bold; }
        .header p { font-size: 9px; opacity: 0.85; margin-top: 2px; }
        .meta { display: flex; gap: 15px; margin-bottom: 14px; padding: 8px 16px; background: #f8fafc; border-left: 4px solid #4F46E5; }
        .meta div { flex: 1; }
        .meta label { font-size: 8px; color: #6B7280; text-transform: uppercase; letter-spacing: 0.05em; }
        .meta p { font-size: 10px; font-weight: bold; color: #1E1B4B; }
        .summary { display: flex; gap: 10px; margin-bottom: 14px; padding: 0 20px; }
        .stat-box { flex: 1; padding: 8px; border-radius: 6px; text-align: center; }
        .stat-box.total  { background: #EEF2FF; border: 1px solid #C7D2FE; }
        .stat-box.green  { background: #ECFDF5; border: 1px solid #A7F3D0; }
        .stat-box.yellow { background: #FFFBEB; border: 1px solid #FDE68A; }
        .stat-box.red    { background: #FFF1F2; border: 1px solid #FECDD3; }
        .stat-box .num { font-size: 18px; font-weight: bold; }
        .stat-box .lbl { font-size: 8px; color: #6B7280; }
        .stat-box.total .num  { color: #4338CA; }
        .stat-box.green .num  { color: #059669; }
        .stat-box.yellow .num { color: #D97706; }
        .stat-box.red .num    { color: #DC2626; }
        
        .section-title { font-size: 10px; font-weight: bold; text-transform: uppercase; color: #1E1B4B; margin: 16px 20px 6px 20px; padding-bottom: 4px; border-bottom: 1.5px solid #E2E8F0; }
        
        table { width: 100%; border-collapse: collapse; font-size: 8.5px; }
        thead th { background: #1E1B4B; color: white; padding: 6px 8px; text-align: left; font-size: 8px; text-transform: uppercase; letter-spacing: 0.05em; }
        thead th.center { text-align: center; }
        thead.green th { background: #065F46; }
        tbody tr:nth-child(even) { background: #F8FAFC; }
        td { padding: 6px 8px; border-bottom: 1px solid #E5E7EB; vertical-align: top; }
        td.center { text-align: center; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 100px; font-size: 7.5px; font-weight: bold; }
        .badge.green  { background: #D1FAE5; color: #065F46; }
        .badge.yellow { background: #FEF3C7; color: #92400E; }
        .badge.red    { background: #FEE2E2; color: #991B1B; }
        .capaian.green  { color: #059669; font-weight: bold; }
        .capaian.yellow { color: #D97706; font-weight: bold; }
        .capaian.red    { color: #DC2626; font-weight: bold; }
        
        .signatures { margin: 24px 20px 10px 20px; display: flex; justify-content: space-between; page-break-inside: avoid; }
        .sig-box { flex: 1; text-align: center; }
        .sig-space { height: 50px; }
        .sig-name { font-weight: bold; border-top: 1px solid #94A3B8; display: inline-block; padding-top: 2px; min-width: 140px; }
        
        .footer { margin-top: 16px; padding: 8px 20px; border-top: 1px solid #E5E7EB; font-size: 7.5px; color: #9CA3AF; display: flex; justify-content: space-between; }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>LAPORAN PENCAPAIAN INDIKATOR MUTU RUMAH SAKIT</h1>
        <p>Sistem Informasi Indikator Mutu & Keselamatan Pasien (PMKP) — MUTU RSYS</p>
    </div>

    <!-- Meta Info -->
    <div class="meta">
        <div>
            <label>Unit Kerja</label>
            <p>{{ $unit ? $unit->nama : 'Seluruh Unit Rumah Sakit' }}</p>
        </div>
        <div>
            <label>Jenis Laporan</label>
            <p>{{ ucfirst($validated['jenis']) }}</p>
        </div>
        <div>
            <label>Periode</label>
            <p>
                @if($validated['jenis'] === 'bulanan')
                    {{ \Carbon\Carbon::createFromDate($validated['tahun'], $bulanMulai, 1)->translatedFormat('F Y') }}
                @elseif($validated['jenis'] === 'triwulan')
                    Triwulan {{ $validated['triwulan'] }} ({{ \Carbon\Carbon::createFromDate($validated['tahun'], $bulanMulai, 1)->translatedFormat('M') }} – {{ \Carbon\Carbon::createFromDate($validated['tahun'], $bulanSelesai, 1)->translatedFormat('M Y') }})
                @elseif($validated['jenis'] === 'semesteran')
                    Semester {{ $validated['semester'] ?? 1 }} ({{ \Carbon\Carbon::createFromDate($validated['tahun'], $bulanMulai, 1)->translatedFormat('M') }} – {{ \Carbon\Carbon::createFromDate($validated['tahun'], $bulanSelesai, 1)->translatedFormat('M Y') }})
                @else
                    Tahun {{ $validated['tahun'] }} (Januari – Desember)
                @endif
            </p>
        </div>
        <div>
            <label>Tanggal Cetak</label>
            <p>{{ now()->translatedFormat('d F Y, H:i') }} WIB</p>
        </div>
        <div>
            <label>Total Indikator</label>
            <p>{{ count($data) }} Indikator</p>
        </div>
    </div>

    <!-- Summary Stats -->
    @php
        $green  = collect($data)->where('status', 'green')->count();
        $yellow = collect($data)->where('status', 'yellow')->count();
        $red    = collect($data)->where('status', 'red')->count();
        $avg    = count($data) > 0 ? collect($data)->avg('capaian') : 0;
    @endphp
    <div class="summary">
        <div class="stat-box total">
            <div class="num">{{ count($data) }}</div>
            <div class="lbl">Total Indikator</div>
        </div>
        <div class="stat-box total">
            <div class="num">{{ number_format($avg, 1) }}%</div>
            <div class="lbl">Rata-rata Capaian</div>
        </div>
        <div class="stat-box green">
            <div class="num">{{ $green }}</div>
            <div class="lbl">Target Tercapai</div>
        </div>
        <div class="stat-box yellow">
            <div class="num">{{ $yellow }}</div>
            <div class="lbl">Hampir Target</div>
        </div>
        <div class="stat-box red">
            <div class="num">{{ $red }}</div>
            <div class="lbl">Di Bawah Target</div>
        </div>
    </div>

    <!-- Section 1: Tabel Capaian Indikator Mutu -->
    <div class="section-title">I. REKAPITULASI CAPAIAN INDIKATOR MUTU</div>
    <div style="padding: 0 20px;">
        <table>
            <thead>
                <tr>
                    <th style="width:4%" class="center">No</th>
                    <th style="width:10%">Kode</th>
                    <th style="width:8%">Jenis</th>
                    <th>Judul Indikator Mutu</th>
                    <th class="center" style="width:11%">Standar / Target</th>
                    <th class="center" style="width:10%">Capaian</th>
                    <th class="center" style="width:10%">Status Mutu</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $i => $item)
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td style="font-family: monospace; font-weight: bold; color: #4338CA;">{{ $item['kode'] }}</td>
                    <td class="center">{{ $item['kategori'] }}</td>
                    <td>{{ $item['judul'] }}</td>
                    <td class="center">{{ $item['operator'] === '>=' ? '≥' : ($item['operator'] === '<=' ? '≤' : $item['operator']) }} {{ $item['target'] }}%</td>
                    <td class="center capaian {{ $item['status'] }}">{{ $item['capaian'] }}%</td>
                    <td class="center">
                        <span class="badge {{ $item['status'] }}">
                            @if($item['status'] === 'green') Tercapai
                            @elseif($item['status'] === 'yellow') Hampir
                            @else Belum @endif
                        </span>
                    </td>
                </tr>
                @endforeach
                @if(empty($data))
                <tr>
                    <td colspan="7" class="center" style="padding: 16px; color: #9CA3AF;">Tidak ada data tercatat untuk periode ini.</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>

    <!-- Section 2: Analisis & RTL untuk Indikator yang Memerlukan Perbaikan -->
    @if(isset($pdsaRecords) && $pdsaRecords->isNotEmpty())
    <div class="section-title" style="margin-top: 20px;">II. ANALISIS & RENCANA TINDAK LANJUT (RTL / SIKLUS PDSA)</div>
    <div style="padding: 0 20px;">
        <table>
            <thead class="green">
                <tr>
                    <th style="width:4%" class="center">No</th>
                    <th style="width:8%" class="center">Bulan</th>
                    <th style="width:18%">Indikator Mutu</th>
                    <th style="width:9%" class="center">Capaian vs Target</th>
                    <th style="width:20%">Masalah / Gap</th>
                    <th style="width:21%">Penyebab / Akar Masalah</th>
                    <th style="width:20%">RTL (Rencana Tindak Lanjut)</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                @endphp
                @foreach($pdsaRecords as $idx => $pdsa)
                <tr>
                    <td class="center">{{ $idx + 1 }}</td>
                    <td class="center" style="font-weight: bold;">{{ $pdsa->period ? $months[$pdsa->period->bulan] : '-' }}</td>
                    <td>
                        <strong style="color: #065F46;">{{ $pdsa->indicator?->kode }}</strong><br>
                        {{ $pdsa->indicator?->judul }}
                    </td>
                    <td class="center">
                        <span style="color: #DC2626; font-weight: bold;">{{ $pdsa->capaian_aktual }}%</span><br>
                        <span style="color: #6B7280; font-size: 7.5px;">target {{ $pdsa->target_capaian }}%</span>
                    </td>
                    <td>{{ $pdsa->masalah }}</td>
                    <td>{{ $pdsa->akar_masalah ?: ($pdsa->analisa_penyebab_manusia ?: '-') }}</td>
                    <td>
                        <strong>{{ $pdsa->rekomendasi_tindak_lanjut ?: ($pdsa->plan ?: '-') }}</strong>
                        @if($pdsa->pj_rtl)
                        <br><span style="color: #4B5563; font-size: 7.5px;">PJ: {{ $pdsa->pj_rtl }}</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Lembar Tanda Tangan / Pengesahan Resmi -->
    <div class="signatures">
        <div class="sig-box">
            <p style="color: #6B7280;">Mengetahui,</p>
            <p style="font-weight: bold; color: #1E1B4B; margin-top: 2px;">Kepala Unit Kerja</p>
            <div class="sig-space"></div>
            <div class="sig-name">{{ $unit ? $unit->nama : 'Kepala Unit Pelayanan' }}</div>
        </div>
        <div class="sig-box">
            <p style="color: #6B7280;">Diverifikasi,</p>
            <p style="font-weight: bold; color: #1E1B4B; margin-top: 2px;">Ketua Komite Mutu & KP</p>
            <div class="sig-space"></div>
            <div class="sig-name">dr. Komite Mutu Rumah Sakit</div>
        </div>
        <div class="sig-box">
            <p style="color: #6B7280;">Menyetujui,</p>
            <p style="font-weight: bold; color: #1E1B4B; margin-top: 2px;">Direktur Utama RS</p>
            <div class="sig-space"></div>
            <div class="sig-name">dr. Direktur Utama, MARS</div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <span>Dicetak secara otomatis oleh Sistem Indikator Mutu — MUTU RSYS &bull; Standar Akreditasi KARS</span>
        <span>Dokumen Resmi Rumah Sakit | {{ now()->format('d/m/Y H:i') }}</span>
    </div>
</body>
</html>
