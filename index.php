<?php

use TestTask\CurrencyConverter;

require __DIR__ . '/vendor/autoload.php';

$currencyConverter = new CurrencyConverter();

if ($argc < 2) {
    echo "Usage: php script.php <input_file>".PHP_EOL;
    exit(1);
}

$currencyConverter->processFile($argv[1]);