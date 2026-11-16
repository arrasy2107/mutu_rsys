<?php
require 'vendor/autoload.php';

$pdfPath = 'C:\\Users\\PC\\.gemini\\antigravity-ide\\brain\\92ea8844-3c54-4da4-8a18-787c779ef12c\\.tempmediaStorage\\media_1791601361513.pdf';

if (class_exists('\Smalot\PdfParser\Parser')) {
    $parser = new \Smalot\PdfParser\Parser();
    $pdf = $parser->parseFile($pdfPath);
    echo $pdf->getText();
} else {
    echo "Smalot\PdfParser\Parser not installed\n";
}
