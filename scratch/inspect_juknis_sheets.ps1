$excel = New-Object -ComObject Excel.Application
$excel.Visible = $false
$excel.DisplayAlerts = $false

$filePath = "c:\laragon\www\mutu_rsys\public\JUKNIS.xlsx"
$wb = $excel.Workbooks.Open($filePath, 0, $true)

Write-Output "=== SHEETS IN JUKNIS.xlsx ==="
foreach ($sheet in $wb.Sheets) {
    Write-Output $sheet.Name
}

$wb.Close($false)
$excel.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($excel) | Out-Null
