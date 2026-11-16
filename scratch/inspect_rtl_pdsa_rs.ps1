$excel = New-Object -ComObject Excel.Application
$excel.Visible = $false
$excel.DisplayAlerts = $false

$filePath = "c:\laragon\www\mutu_rsys\public\Sample Data\IMUT  RSYS 2026.xlsx"
$wb = $excel.Workbooks.Open($filePath, 0, $true, 5, "km")

Write-Output "=== SHEET: RTL IMP (Rows 1-25) ==="
$ws = $wb.Sheets.Item("RTL IMP")
for ($r = 1; $r -le 25; $r++) {
    $line = ""
    for ($c = 1; $c -le 12; $c++) {
        $v = $ws.Cells.Item($r, $c).Text
        if ($v -ne "") {
            $line += "[$c]: $v | "
        }
    }
    if ($line -ne "") {
        Write-Output "R$r -> $line"
    }
}

Write-Output "`n=== SHEET: PDSA (Rows 1-25) ==="
$wsPdsa = $wb.Sheets.Item("PDSA")
for ($r = 1; $r -le 25; $r++) {
    $line = ""
    for ($c = 1; $c -le 12; $c++) {
        $v = $wsPdsa.Cells.Item($r, $c).Text
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
