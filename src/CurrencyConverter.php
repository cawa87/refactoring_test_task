<?php

namespace TestTask;

class CurrencyConverter
{
    private array $euCountries = ['AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PO', 'PT', 'RO', 'SE', 'SI', 'SK'];

    private string $binLookupUrl = 'https://lookup.binlist.net/';

    private string $exchangeRateUrl = 'https://api.exchangeratesapi.io/latest';

    /**
     * Process a file and perform a series of calculations on each line
     *
     * @param string $filePath The path to the file to be processed
     *
     * @return void
     */
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

    /**
     * Extracts values from a given row
     *
     * @param string $row The row containing the values in a specific format
     *
     * @return array An array containing the extracted values
     */
    private function extractValues(string $row): array
    {
        [$value1, $value2, $value3] = array_map(fn($item) => trim(explode(':', $item)[1], '"'), explode(',', $row));

        return [$value1, $value2, $value3];
    }

    /**
     * Check if a country code is a member of the European Union
     *
     * @param string $countryCode The country code to check
     *
     * @return bool Returns true if the country is a member of the European Union, false otherwise
     */
    private function isEuropeanUnionMember(string $countryCode): bool
    {
        return in_array($countryCode, $this->euCountries);
    }

    /**
     * Retrieve the exchange rate for a given currency code
     *
     * @param string $currencyCode The currency code for which to retrieve the exchange rate
     *
     * @return float The exchange rate for the given currency code. Returns 0 if the currency code is 'EUR'
     *              or if the exchange rate is not available in the provided exchange rate data
     */
    private function getExchangeRate(string $currencyCode): float
    {
        $exchangeRates = json_decode(file_get_contents($this->exchangeRateUrl), true);

        return ($currencyCode == 'EUR' || !isset($exchangeRates['rates'][$currencyCode])) ? 0 : $exchangeRates['rates'][$currencyCode];
    }

    /**
     * Retrieves the bin results from the specified URL.
     *
     * @param string $bin The bin number to lookup.
     * @return string The bin results retrieved from the URL.
     */
    private function getBinResults(string $bin): string
    {
        return file_get_contents($this->binLookupUrl . $bin);
    }

    /**
     * Calculates the fixed amount based on the given parameters.
     *
     * @param float $amount The original amount.
     * @param string $currencyCode The currency code.
     * @param float $exchangeRate The exchange rate.
     *
     * @return float The calculated fixed amount.
     */
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
