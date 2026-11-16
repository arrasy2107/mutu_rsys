$excel = New-Object -ComObject Excel.Application
$excel.Visible = $false
$excel.DisplayAlerts = $false

$filePath = "c:\laragon\www\mutu_rsys\public\Sample Data\IMUT  RSYS 2026.xlsx"
$wb = $excel.Workbooks.Open($filePath, 0, $true, 5, "km")

Write-Output "=== SHEET: ALGORITMA IMP (Rows 26-65) ==="
$ws = $wb.Sheets.Item("ALGORITMA IMP")
for ($r = 26; $r -le 65; $r++) {
    $line = ""
    for ($c = 1; $c -le 14; $c++) {
        $v = $ws.Cells.Item($r, $c).Text
        if ($v -ne "") {
            $line += "[$c]: $v | "
        }
    }
    if ($line -ne "") {
        Write-Output "R$r -> $line"
    }
}

Write-Output "`n=== SHEET: AGREGASI (Rows 1-40) ==="
$wsAgregasi = $wb.Sheets.Item("AGREGASI")
for ($r = 1; $r -le 40; $r++) {
    $line = ""
    for ($c = 1; $c -le 15; $c++) {
        $v = $wsAgregasi.Cells.Item($r, $c).Text
        if ($v -ne "") {
            $line += "[$c]: $v | "
        }
    }
    if ($line -ne "") {
        Write-Output "R$r -> $line"
    }
}

$wb.Close($false)
$excel.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($excel) | Out-Null
