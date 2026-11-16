<?php

$f = __DIR__ . '/../public/Sample Data/IV. HVA/HVA RSYS 2026.xlsx';
echo "Path: $f" . PHP_EOL;
echo "Realpath: " . realpath($f) . PHP_EOL;
echo "file_exists: " . (file_exists($f) ? 'true' : 'false') . PHP_EOL;
echo "is_file: " . (is_file($f) ? 'true' : 'false') . PHP_EOL;
echo "is_readable: " . (is_readable($f) ? 'true' : 'false') . PHP_EOL;
echo "filesize: " . filesize($f) . PHP_EOL;

