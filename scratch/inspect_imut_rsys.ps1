$excel = New-Object -ComObject Excel.Application
$excel.Visible = $false
$excel.DisplayAlerts = $false

$filePath = "C:\laragon\www\mutu_rsys\public\Sample Data\IMUT  RSYS 2026.xlsx"
$password = "km"

try {
    $wb = $excel.Workbooks.Open($filePath, 0, $true, 5, $password)
    Write-Output "=== WORKBOOK OPENED SUCCESSFULLY ==="
    Write-Output "Sheets count: $($wb.Sheets.Count)"
    
    foreach ($sheet in $wb.Sheets) {
        Write-Output "Sheet: $($sheet.Name)"
    }
    
    $wb.Close($false)
} catch {
    Write-Output "Error: $($_.Exception.Message)"
} finally {
    $excel.Quit()
    [System.Runtime.Interopservices.Marshal]::ReleaseComObject($excel) | Out-Null
}
