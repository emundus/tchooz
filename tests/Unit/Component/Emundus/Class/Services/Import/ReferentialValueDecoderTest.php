<?php
/**
 * @package     Unit\Component\Emundus\Class\Services\Import
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Unit\Component\Emundus\Class\Services\Import;

use PHPUnit\Framework\TestCase;
use Tchooz\Enums\Import\FieldTypeEnum;
use Tchooz\Services\Import\ImportContext;
use Tchooz\Services\Import\Mapping\AliasColumnMap;
use Tchooz\Services\Import\Mapping\ColumnMap;
use Tchooz\Services\Import\Referential\CallableReferentialProvider;
use Tchooz\Services\Import\ReferentialValueDecoder;

/**
 * @covers \Tchooz\Services\Import\ReferentialValueDecoder
 */
class ReferentialValueDecoderTest extends TestCase
{
	private ReferentialValueDecoder $decoder;
	private ColumnMap               $columnMap;

	protected function setUp(): void
	{
		$this->decoder = new ReferentialValueDecoder();

		// "Orga [007]" deliberately carries brackets in its label to prove the
		// whole-string-first strategy.
		$provider = new CallableReferentialProvider('countries', 'Countries', fn(): array => [
			['value' => 'FR', 'label' => 'France'],
			['value' => '55', 'label' => 'Orga [007]'],
		]);

		// One REFERENTIAL field + one plain field, so we can assert the decoder
		// only touches referential columns.
		$this->columnMap = AliasColumnMap::create()
			->field('country', aliases: ['Pays'], type: FieldTypeEnum::REFERENTIAL, referential: $provider)
			->field('name', aliases: ['Nom'])
			->build();
	}

	private function decode(array $row): array
	{
		return $this->decoder->decode($row, $this->columnMap, new ImportContext('Test', 2));
	}

	public function testDropdownFormResolvesToTheBracketedValue(): void
	{
		$row = $this->decode(['country' => 'France [FR]', 'name' => 'Doe']);

		$this->assertSame('FR', $row['country'], '"Label [id]" should resolve to the id');
	}

	public function testPlainLabelResolvesToItsValue(): void
	{
		$row = $this->decode(['country' => 'France', 'name' => 'Doe']);

		$this->assertSame('FR', $row['country'], 'A typed label should resolve to its value');
	}

	public function testPlainValueResolvesToItself(): void
	{
		$row = $this->decode(['country' => 'FR', 'name' => 'Doe']);

		$this->assertSame('FR', $row['country'], 'A typed value should resolve to itself');
	}

	public function testWholeStringIsTriedBeforeStrippingBrackets(): void
	{
		// "Orga [007]" is a real label: its own brackets must not be mistaken for an id.
		$row = $this->decode(['country' => 'Orga [007]', 'name' => 'Doe']);

		$this->assertSame('55', $row['country'], 'A label containing brackets must resolve as a whole');
	}

	public function testTrailingBracketIsStrippedWhenWholeStringDoesNotResolve(): void
	{
		// Our dropdown appends "[id]" last, so the id is still recovered.
		$row = $this->decode(['country' => 'Orga [007] [55]', 'name' => 'Doe']);

		$this->assertSame('55', $row['country'], 'The last bracket group should be used as the id');
	}

	public function testUnknownValueIsKeptAsIsForLaterValidation(): void
	{
		$row = $this->decode(['country' => 'Klingon', 'name' => 'Doe']);

		$this->assertSame('Klingon', $row['country'], 'An unresolved value should be left untouched');
	}

	public function testNonReferentialFieldsAreNeverTouched(): void
	{
		$row = $this->decode(['country' => 'FR', 'name' => 'Brackets [inside] name']);

		$this->assertSame('Brackets [inside] name', $row['name'], 'A plain field must be left verbatim');
	}

	public function testLabelMismatchAddsANonBlockingWarning(): void
	{
		$context = new ImportContext('Test', 4);

		// "Angleterre [FR]": the id FR is France, the typed label disagrees.
		$row = $this->decoder->decode(['country' => 'Angleterre [FR]'], $this->columnMap, $context);

		$this->assertSame('FR', $row['country'], 'The value still resolves on the id');
		$this->assertCount(1, $context->getWarnings(), 'A mismatching label should raise exactly one warning');
	}

	public function testMatchingLabelRaisesNoWarning(): void
	{
		$context = new ImportContext('Test', 4);

		$this->decoder->decode(['country' => 'France [FR]'], $this->columnMap, $context);

		$this->assertSame([], $context->getWarnings(), 'A matching label must not raise any warning');
	}

	public function testPlainValueRaisesNoWarning(): void
	{
		$context = new ImportContext('Test', 4);

		$this->decoder->decode(['country' => 'FR'], $this->columnMap, $context);

		$this->assertSame([], $context->getWarnings(), 'A bare value has no typed label to disagree with');
	}
}