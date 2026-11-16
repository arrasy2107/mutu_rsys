Add-Type -AssemblyName System.IO.Compression.FileSystem
$zip = [IO.Compression.ZipFile]::OpenRead("C:\laragon\www\mutu_rsys\public\mutu.docx")
$entry = $zip.Entries | ? { $_.FullName -eq 'word/document.xml' }
$stream = $entry.Open()
$reader = New-Object System.IO.StreamReader($stream)
$xmlStr = $reader.ReadToEnd()
$reader.Close()
$stream.Close()
$zip.Dispose()

$xml = [xml]$xmlStr
$ns = New-Object System.Xml.XmlNamespaceManager($xml.NameTable)
$ns.AddNamespace("w", "http://schemas.openxmlformats.org/wordprocessingml/2006/main")

$paragraphs = $xml.SelectNodes("//w:p", $ns)
$result = @()
foreach ($p in $paragraphs) {
    $text = $p.InnerText
    if ($text) { $result += $text }
}
$result -join "`n" | Out-File "C:\laragon\www\mutu_rsys\public\mutu_extracted.txt" -Encoding UTF8
