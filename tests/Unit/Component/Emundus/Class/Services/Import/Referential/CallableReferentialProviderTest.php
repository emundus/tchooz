<?php
/**
 * @package     Unit\Component\Emundus\Class\Services\Import\Referential
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Unit\Component\Emundus\Class\Services\Import\Referential;

use PHPUnit\Framework\TestCase;
use Tchooz\Services\Import\Referential\CallableReferentialProvider;

/**
 * @covers \Tchooz\Services\Import\Referential\CallableReferentialProvider
 * @covers \Tchooz\Services\Import\Referential\AbstractReferentialProvider
 */
class CallableReferentialProviderTest extends TestCase
{
	/**
	 * Entries cover different shapes: a plain pair, a duplicate label
	 * ("Acme" twice), and a value that is also another entry's label
	 * (value "99" vs the entry labeled "99").
	 *
	 * @return array<int, array{value: string, label: string}>
	 */
	private function entries(): array
	{
		return [
			['value' => 'FR', 'label' => 'France'],
			['value' => 'GB', 'label' => 'United Kingdom'],
			['value' => '12', 'label' => 'Acme'],
			['value' => '34', 'label' => 'Acme'],
			['value' => '99', 'label' => 'X'],
			['value' => '7',  'label' => '99'],
		];
	}

	private function provider(?\Closure $loader = null): CallableReferentialProvider
	{
		return new CallableReferentialProvider(
			'countries',
			'Countries',
			$loader ?? fn(): array => $this->entries()
		);
	}

	public function testGetKeyAndLabelReturnConstructorValues(): void
	{
		$provider = new CallableReferentialProvider('organizations', 'Organizations', fn(): array => []);

		$this->assertSame('organizations', $provider->getKey(), 'getKey should return the configured key');
		$this->assertSame('Organizations', $provider->getLabel(), 'getLabel should return the configured label');
	}

	public function testGetEntriesReturnsTheLoadedPairs(): void
	{
		$this->assertSame($this->entries(), $this->provider()->getEntries(), 'getEntries should return the loader output');
	}


	public function testResolveByValueReturnsCanonicalValue(): void
	{
		$this->assertSame('FR', $this->provider()->resolve('FR'), 'A known value should resolve to itself');
	}

	public function testResolveByLabelReturnsItsValue(): void
	{
		$this->assertSame('FR', $this->provider()->resolve('France'), 'A unique label should resolve to its value');
	}

	public function testResolveIsCaseAndWhitespaceInsensitive(): void
	{
		$provider = $this->provider();

		$this->assertSame('FR', $provider->resolve('fr'), 'Value match should be case-insensitive');
		$this->assertSame('FR', $provider->resolve('  France  '), 'Label match should ignore surrounding whitespace');
		$this->assertSame('GB', $provider->resolve('united kingdom'), 'Label match should be case-insensitive');
	}

	public function testResolveUnknownInputReturnsNull(): void
	{
		$this->assertNull($this->provider()->resolve('does-not-exist'), 'An unknown input should not resolve');
	}

	public function testResolveEmptyInputReturnsNull(): void
	{
		$this->assertNull($this->provider()->resolve('   '), 'An empty input should not resolve');
	}

	public function testResolveDuplicateLabelReturnsNull(): void
	{
		$this->assertNull($this->provider()->resolve('Acme'), 'A label shared by several entries cannot be resolved');
	}

	public function testResolveValueLabelCollisionReturnsNull(): void
	{
		// "99" is the value of one entry and the label of another → ambiguous.
		$this->assertNull($this->provider()->resolve('99'), 'A value that is also another entry label cannot be resolved');
	}

	public function testIsAmbiguousLabelTrueOnlyForDuplicateLabel(): void
	{
		$provider = $this->provider();

		$this->assertTrue($provider->isAmbiguousLabel('Acme'), 'Duplicate label should be flagged ambiguous');
		$this->assertFalse($provider->isAmbiguousLabel('France'), 'A unique label is not ambiguous');
		$this->assertFalse($provider->isAmbiguousLabel('FR'), 'A known value is not an ambiguous label');
		$this->assertFalse($provider->isAmbiguousLabel('99'), 'A value/label collision is not a label ambiguity');
	}

	public function testIsAmbiguousValueTrueOnlyForValueLabelCollision(): void
	{
		$provider = $this->provider();

		$this->assertTrue($provider->isAmbiguousValue('99'), 'A value that doubles as another label should be flagged');
		$this->assertFalse($provider->isAmbiguousValue('FR'), 'A plain value is not ambiguous');
		$this->assertFalse($provider->isAmbiguousValue('Acme'), 'A duplicate label is not a value ambiguity');
		$this->assertFalse($provider->isAmbiguousValue('does-not-exist'), 'An unknown input is not a value ambiguity');
	}

	public function testLabelForReturnsCanonicalLabel(): void
	{
		$provider = $this->provider();

		$this->assertSame('France', $provider->labelFor('FR'), 'labelFor should return the value canonical label');
		$this->assertSame('France', $provider->labelFor('fr'), 'labelFor should be case-insensitive on the value');
	}

	public function testLabelForUnknownValueReturnsNull(): void
	{
		$this->assertNull($this->provider()->labelFor('does-not-exist'), 'labelFor on an unknown value should be null');
	}

	public function testLoaderIsInvokedOnlyOnceAcrossSeveralCalls(): void
	{
		$calls  = 0;
		$loader = function () use (&$calls): array {
			$calls++;

			return $this->entries();
		};

		$provider = $this->provider($loader);

		$provider->resolve('FR');
		$provider->resolve('Acme');
		$provider->isAmbiguousValue('99');
		$provider->labelFor('GB');
		$provider->getEntries();

		$this->assertSame(1, $calls, 'The loader must run a single time and be memoized');
	}
}
