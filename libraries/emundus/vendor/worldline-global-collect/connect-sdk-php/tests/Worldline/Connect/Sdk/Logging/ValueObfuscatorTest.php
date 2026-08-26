<?php
namespace Worldline\Connect\Sdk\Logging;

use PHPUnit\Framework\TestCase;

/**
 * @group obfuscation
 */
class ValueObfuscatorTest extends TestCase
{
    /**
     * @dataProvider valueObfuscatorProvider
     * @param string $value
     * @param int $obfuscationLength
     * @param string $valueStartObfuscated
     * @param string $valueEndObfuscated
     * @param string $valueAllObfuscated
     * @param string $obfuscatedFixedLength
     */
    public function testValueObfuscators(
        string $value,
        int    $obfuscationLength,
        string $valueStartObfuscated,
        string $valueEndObfuscated,
        string $valueAllObfuscated,
        string $obfuscatedFixedLength
    ) {
        $valueObfuscator = new ValueObfuscator();
        $this->assertEquals($valueStartObfuscated, $valueObfuscator->obfuscateAllKeepEnd($value, $obfuscationLength));
        $this->assertEquals($valueEndObfuscated, $valueObfuscator->obfuscateAllKeepStart($value, $obfuscationLength));
        $this->assertEquals($valueAllObfuscated, $valueObfuscator->obfuscateAll($value));
        $this->assertEquals($obfuscatedFixedLength, $valueObfuscator->obfuscateFixedLength($obfuscationLength));
    }

    public function valueObfuscatorProvider(): array
    {
        return array(
            array('', 0, '', '', '', ''),
            array('', 3, '', '', '', '***'),
            array('foo', -1, '***', '***', '***', ''),
            array('foo', 0, '***', '***', '***', ''),
            array('foo', 1, '**o', 'f**', '***', '*'),
            array('foo', 2, '*oo', 'fo*', '***', '**'),
            array('foo', 3, 'foo', 'foo', '***', '***'),
            array('foo', 4, 'foo', 'foo', '***', '****'),
            array('fóo', 1, '**o', 'f**', '***', '*'),
            array('fóo', 2, '*óo', 'fó*', '***', '**'),
            array('fóo', 3, 'fóo', 'fóo', '***', '***')
        );
    }
}
