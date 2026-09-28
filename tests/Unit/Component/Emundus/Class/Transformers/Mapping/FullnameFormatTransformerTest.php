<?php

namespace Unit\Component\Emundus\Class\Transformers\Mapping;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Transformers\Mapping\FullnameFormatTransformer;

class FullnameFormatTransformerTest extends UnitTestCase
{
	private FullnameFormatTransformer $transformer;

	protected function setUp(): void
	{
		parent::setUp();
		$this->transformer = new FullnameFormatTransformer();
	}

	/**
	 * @covers \Tchooz\Transformers\Mapping\FullnameFormatTransformer::transform
	 * @return void
	 */
	public function testWhenValueHasSeveralWordsThenFirstIsCapitalizedAndRestUppercased(): void
	{
		$this->assertEquals('Jean DUPONT', $this->transformer->transform('jean dupont'));
		$this->assertEquals('Élodie LEFÈVRE', $this->transformer->transform('élodie lefèvre'), 'Multibyte characters should be handled');
		$this->assertEquals('Jean DE LA TOUR', $this->transformer->transform('  jean   de la tour '), 'Surrounding and repeated whitespaces should not produce empty parts');
		$this->assertEquals('Jean-Pierre DURAND', $this->transformer->transform('  jean-pierre Durand '), ' Les prénoms composés avec tiret doivent avoir une majuscule à chaque partie');
	}

	/**
	 * @covers \Tchooz\Transformers\Mapping\FullnameFormatTransformer::transform
	 * @return void
	 */
	public function testWhenValueHasASingleWordThenItIsOnlyCapitalized(): void
	{
		$this->assertEquals('Jean', $this->transformer->transform('jean'));
	}

	/**
	 * @covers \Tchooz\Transformers\Mapping\FullnameFormatTransformer::transform
	 * @return void
	 */
	public function testWhenValueIsEmptyOrNotAStringThenItIsReturnedUnchanged(): void
	{
		$this->assertSame('', $this->transformer->transform(''));
		$this->assertNull($this->transformer->transform(null));
		$this->assertSame(12, $this->transformer->transform(12));
	}
}
