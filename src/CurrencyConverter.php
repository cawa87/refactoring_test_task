<?php

namespace TestTask;

class CurrencyConverter
{
    private array $euCountries = ['AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PO', 'PT', 'RO', 'SE', 'SI', 'SK'];

    private string $binLookupUrl = 'https://lookup.binlist.net/';

    private string $exchangeRateUrl = 'https://api.exchangeratesapi.io/latest';

    public function processFile(string $filePath): void
    {
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $values = $this->extractValues($line);

            $binResults = $this->getBinResults($values[0]);
            $countryCode = json_decode($binResults)->country->alpha2;
            $isEu = $this->isEuropeanUnionMember($countryCode);

            $exchangeRate = $this->getExchangeRate($values[2]);

            $amntFixed = $this->calculateFixedAmount($values[1], $values[2], $exchangeRate);

            $commission = $this->applyCommissionCeiling($amntFixed, $isEu);

            echo $commission . PHP_EOL;
        }
    }

    private function extractValues(string $row): array
    {
        [$value1, $value2, $value3] = array_map(fn($item) => trim(explode(':', $item)[1], '"'), explode(',', $row));

        return [$value1, $value2, $value3];
    }

    private function isEuropeanUnionMember(string $countryCode): bool
    {
        return in_array($countryCode, $this->euCountries);
    }

    private function getExchangeRate(string $currencyCode): float
    {
        $exchangeRates = json_decode(file_get_contents($this->exchangeRateUrl), true);

        return ($currencyCode == 'EUR' || !isset($exchangeRates['rates'][$currencyCode])) ? 0 : $exchangeRates['rates'][$currencyCode];
    }

    private function getBinResults(string $bin): string
    {
        return file_get_contents($this->binLookupUrl . $bin);
    }

    private function calculateFixedAmount(float $amount, string $currencyCode, float $exchangeRate): float
    {
        return ($currencyCode == 'EUR' || $exchangeRate == 0) ? $amount : $amount / $exchangeRate;
    }

    private function applyCommissionCeiling(float $amount, bool $isEu): float
    {
        $commissionRate = $isEu ? 0.01 : 0.02;
        return round(ceil($amount * 100) / 100 * $commissionRate, 2);
    }
}
