<?php
$pdfPath = 'C:\\Users\\PC\\.gemini\\antigravity-ide\\brain\\92ea8844-3c54-4da4-8a18-787c779ef12c\\.tempmediaStorage\\media_1791601361513.pdf';
if (file_exists($pdfPath)) {
    echo "PDF exists, size: " . filesize($pdfPath) . " bytes\n";
} else {
    echo "PDF not found\n";
}
