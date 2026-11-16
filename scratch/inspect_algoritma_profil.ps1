$excel = New-Object -ComObject Excel.Application
$excel.Visible = $false
$excel.DisplayAlerts = $false

$filePath = "C:\laragon\www\mutu_rsys\public\Sample Data\IMUT  RSYS 2026.xlsx"
$password = "km"

function Dump-SheetRows($sheet, $startRow, $endRow, $maxCol) {
    Write-Output "=== SHEET: $($sheet.Name) ==="
    for ($r = $startRow; $r -le $endRow; $r++) {
        $rowVals = @()
        for ($c = 1; $c -le $maxCol; $c++) {
            $val = $sheet.Cells.Item($r, $c).Text
            if ($val) {
                $rowVals += "[$c]: $val"
            }
        }
        if ($rowVals.Count -gt 0) {
            Write-Output "R$r -> $($rowVals -join ' | ')"
        }
    }
}

try {
    $wb = $excel.Workbooks.Open($filePath, 0, $true, 5, $password)
    
    $shAlgo = $wb.Sheets.Item("ALGORITMA IMP")
    Dump-SheetRows $shAlgo 1 35 15
    
    $shProfil = $wb.Sheets.Item("PROFIL IMP")
    Dump-SheetRows $shProfil 1 35 12
    
    $wb.Close($false)
} catch {
    Write-Output "Error: $($_.Exception.Message)"
} finally {
    $excel.Quit()
    [System.Runtime.Interopservices.Marshal]::ReleaseComObject($excel) | Out-Null
}
