<?php

namespace Unit\Plugin\Fabrik\Element;

use Joomla\Tests\Unit\UnitTestCase;

require_once JPATH_SITE . '/components/com_fabrik/models/plugin.php';
require_once JPATH_SITE . '/components/com_fabrik/models/element.php';
require_once JPATH_SITE . '/plugins/fabrik_element/average/average.php';

/**
 * @covers \PlgFabrik_ElementAverage
 */
class AverageElementTest extends UnitTestCase
{
	public static function roundAverageProvider(): array
	{
		return [
			'round 2 decimals'             => [13.33333, 2, 'round', 13.33],
			'round 1 decimal'              => [13.36, 1, 'round', 13.4],
			'round 0 decimal'              => [13.5, 0, 'round', 14.0],
			'ceil 2 decimals'              => [13.331, 2, 'ceil', 13.34],
			'ceil 0 decimal'               => [13.01, 0, 'ceil', 14.0],
			'floor 1 decimal'              => [13.39, 1, 'floor', 13.3],
			'floor 0 decimal'              => [13.99, 0, 'floor', 13.0],
			'ceil exact value float noise' => [12.3, 1, 'ceil', 12.3],
			'floor exact value float noise' => [0.29, 2, 'floor', 0.29],
			'decimals clamped to max'      => [13.33333, 5, 'round', 13.33],
			'negative decimals clamped'    => [13.6, -1, 'round', 14.0],
			'unknown rounding is round'    => [13.336, 2, 'unknown', 13.34],
		];
	}

	/**
	 * @dataProvider roundAverageProvider
	 */
	public function testRoundAverage(float $value, int $decimals, string $rounding, float $expected): void
	{
		$this->assertSame($expected, \PlgFabrik_ElementAverage::roundAverage($value, $decimals, $rounding));
	}

	public function testRoundAverageDefaultsKeepTwoDecimals(): void
	{
		$this->assertSame(13.33, \PlgFabrik_ElementAverage::roundAverage(13.33333));
	}

	public static function parseNumberProvider(): array
	{
		return [
			'dot decimal'             => ['10.5', 10.5],
			'comma decimal'           => ['10,5', 10.5],
			'space thousands'         => ['1 000,5', 1000.5],
			'nbsp thousands'          => ["1\u{00A0}000,5", 1000.5],
			'integer'                 => ['20', 20.0],
			'empty'                   => ['', 0.0],
			'not a number'            => ['abc', 0.0],
		];
	}

	/**
	 * @dataProvider parseNumberProvider
	 */
	public function testParseNumber(string $value, float $expected): void
	{
		$this->assertSame($expected, \PlgFabrik_ElementAverage::parseNumber($value));
	}

	public function testAverageWithCommaDecimal(): void
	{
		$fields = [
			['value' => \PlgFabrik_ElementAverage::parseNumber('20'), 'max' => 20, 'weight' => 1],
			['value' => \PlgFabrik_ElementAverage::parseNumber('10,5'), 'max' => 20, 'weight' => 1],
		];

		$this->assertSame(15.25, \PlgFabrik_ElementAverage::calculateAverage($fields, 20));
	}
}
