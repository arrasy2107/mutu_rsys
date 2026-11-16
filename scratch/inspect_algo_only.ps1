$excel = New-Object -ComObject Excel.Application
$excel.Visible = $false
$excel.DisplayAlerts = $false

$filePath = "C:\laragon\www\mutu_rsys\public\Sample Data\IMUT  RSYS 2026.xlsx"
$password = "km"

try {
    $wb = $excel.Workbooks.Open($filePath, 0, $true, 5, $password)
    $sh = $wb.Sheets.Item("ALGORITMA IMP")
    Write-Output "=== ALGORITMA IMP ROWS 1-25 ==="
    for ($r = 1; $r -le 25; $r++) {
        $rowVals = @()
        for ($c = 1; $c -le 20; $c++) {
            $val = $sh.Cells.Item($r, $c).Text
            if ($val) {
                $rowVals += "[$c]: $val"
            }
        }
        if ($rowVals.Count -gt 0) {
            Write-Output "R$r -> $($rowVals -join ' | ')"
        }
    }
    $wb.Close($false)
} catch {
    Write-Output "Error: $($_.Exception.Message)"
} finally {
    $excel.Quit()
    [System.Runtime.Interopservices.Marshal]::ReleaseComObject($excel) | Out-Null
}
