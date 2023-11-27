<?php

namespace Tests;

use ReflectionException;
use TestTask\CurrencyConverter;
use PHPUnit\Framework\TestCase;

class CurrencyConverterTest extends TestCase
{
    private CurrencyConverter $currencyConverter;

    protected function setUp(): void
    {
        $this->currencyConverter = new CurrencyConverter();
    }

    /**
     * @throws ReflectionException
     */
    public function testExtractValues(): void
    {
        $currencyConverter = new CurrencyConverter();

        $reflectionClass = new \ReflectionClass($currencyConverter);
        $method = $reflectionClass->getMethod('extractValues');

        $row = '"USD":100,"EUR":50,"USD":1000';
        $expected = ['100', '50', '1000'];
        $result = $method->invoke($currencyConverter, $row);

        $this->assertEquals($expected, $result);
    }

    /**
     * @throws ReflectionException
     */
    public function testIsEuropeanUnionMember(): void
    {
        $currencyConverter = new CurrencyConverter();

        $reflectionClass = new \ReflectionClass($currencyConverter);
        $method = $reflectionClass->getMethod('isEuropeanUnionMember');

        $euCountry = 'DE';
        $nonEuCountry = 'US';

        $this->assertTrue($method->invoke($currencyConverter, $euCountry));
        $this->assertFalse($method->invoke($currencyConverter, $nonEuCountry));
    }

    /**
     * @throws ReflectionException
     */
    public function testGetExchangeRate(): void
    {
        $currencyConverter = new CurrencyConverter();

        $reflectionClass = new \ReflectionClass($currencyConverter);
        $method = $reflectionClass->getMethod('getExchangeRate');

        $eurRate = $method->invoke($currencyConverter, 'EUR');
        $usdRate = $method->invoke($currencyConverter, 'USD');

        $this->assertEquals(1.0, $eurRate);
        $this->assertGreaterThan(0, $usdRate);
    }

    /**
     * @throws ReflectionException
     */
    public function testCalculateFixedAmount(): void
    {
        $currencyConverter = new CurrencyConverter();

        $reflectionClass = new \ReflectionClass($currencyConverter);
        $method = $reflectionClass->getMethod('calculateFixedAmount');

        $amount = 100;
        $eurAmount = $method->invoke($currencyConverter, $amount, 'EUR', 1.0);
        $usdAmount = $method->invoke($currencyConverter, $amount, 'USD', 1.2);

        $this->assertEquals($amount, $eurAmount);
        $this->assertEquals($amount / 1.2, $usdAmount);
    }

    public function testApplyCommissionCeilingNonEu(): void
    {
        $currencyConverter = new CurrencyConverter();

        $reflectionClass = new \ReflectionClass($currencyConverter);
        $method = $reflectionClass->getMethod('applyCommissionCeiling');

        $amount = 100;

        $commission = $method->invoke($currencyConverter, $amount, false);

        $this->assertEquals(0.02 * ceil($amount * 100) / 100, $commission);
    }

    /**
     * @throws ReflectionException
     */
    public function testApplyCommissionCeilingEu(): void
    {
        $currencyConverter = new CurrencyConverter();

        $reflectionClass = new \ReflectionClass($currencyConverter);
        $method = $reflectionClass->getMethod('applyCommissionCeiling');

        $amount = 100;

        $commission = $method->invoke($currencyConverter, $amount, true);

        $this->assertEquals(0.01 * ceil($amount * 100) / 100, $commission);
    }
}