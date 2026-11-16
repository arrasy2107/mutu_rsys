$excel = New-Object -ComObject Excel.Application
$excel.Visible = $false
$excel.DisplayAlerts = $false

$filePath = "c:\laragon\www\mutu_rsys\public\Sample Data\IMUT  RSYS 2026.xlsx"
$wb = $excel.Workbooks.Open($filePath, 0, $true, 5, "km")

Write-Output "=== SHEET: PROFIL IMP (Rows 1-25) ==="
$ws = $wb.Sheets.Item("PROFIL IMP")
for ($r = 1; $r -le 25; $r++) {
    $line = ""
    for ($c = 1; $c -le 10; $c++) {
        $v = $ws.Cells.Item($r, $c).Text
        if ($v -ne "") {
            $line += "[$c]: $v | "
        }
    }
    if ($line -ne "") {
        Write-Output "R$r -> $line"
    }
}

Write-Output "`n=== SHEET: DAT-AN IMP (Rows 1-30) ==="
$wsDat = $wb.Sheets.Item("DAT-AN IMP")
for ($r = 1; $r -le 30; $r++) {
    $line = ""
    for ($c = 1; $c -le 15; $c++) {
        $v = $wsDat.Cells.Item($r, $c).Text
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
