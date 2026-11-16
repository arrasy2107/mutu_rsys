$excel = New-Object -ComObject Excel.Application
$excel.Visible = $false
$excel.DisplayAlerts = $false

$filePath = "C:\laragon\www\mutu_rsys\public\Sample Data\IMUT  RSYS 2026.xlsx"
$password = "km"

function Dump-SheetHeader($sheet, $maxRow, $maxCol) {
    Write-Output "--------------------------------------------------"
    Write-Output "SHEET: $($sheet.Name)"
    Write-Output "--------------------------------------------------"
    for ($r = 1; $r -le $maxRow; $r++) {
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
    
    $sheetsToInspect = @("ALGORITMA IMP", "PROFIL IMP", "AGREGASI", "DAT-AN IMP", "RTL IMP")
    foreach ($name in $sheetsToInspect) {
        $sh = $null
        try {
            $sh = $wb.Sheets.Item($name)
            Dump-SheetHeader $sh 25 15
        } catch {
            Write-Output "Could not open sheet $name"
        }
    }
    
    $wb.Close($false)
} catch {
    Write-Output "Error: $($_.Exception.Message)"
} finally {
    $excel.Quit()
    [System.Runtime.Interopservices.Marshal]::ReleaseComObject($excel) | Out-Null
}
