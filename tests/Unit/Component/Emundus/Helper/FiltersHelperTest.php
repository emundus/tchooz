<?php
/**
 * @package     Unit\Component\Emundus\Helper
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Helper;

use Joomla\Tests\Unit\UnitTestCase;

require_once JPATH_SITE . '/components/com_emundus/helpers/filters.php';

/**
 * @package     Unit\Component\Emundus\Helper
 *
 * @since       version 1.0.0
 * @covers      EmundusHelperFiles
 */
class FiltersHelperTest extends UnitTestCase
{
	/**
	 * @var    EmundusHelperFiles
	 * @since  4.2.0
	 */
	private $helper;

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);

		$this->helper = new \EmundusHelperFilters();
	}

	public function testsearchClosestWord() {
		$default = 'Jérémy LEGENDRE';
		$words = ['Jérémy LEGENDRE'];

		$result = $this->helper->searchClosestWord($default, $words);
		$this->assertSame(0, $result['lev']);
		$this->assertSame(0, $result['position']);
		$this->assertSame('Jérémy LEGENDRE', $result['word'], 'Le mot est identique');

		$words = ['Jérôme rienavoir', 'Jérémy LEGENDRE', 'Jéremy LEGNDRE', 'Jérémie LEGAN'];
		$result = $this->helper->searchClosestWord($default, $words);
		$this->assertSame(0, $result['lev'], 'Le mot identique est trouvé parmi les mots proposés');
		$this->assertSame(1, $result['position'], 'La position du mot identique est correcte');
		$this->assertSame('Jérémy LEGENDRE', $result['word'], 'Le mot identique est correct');

		$words = ['Jérôme different', 'Jéremy LEGENDRE', 'Jérémie LEGANDRE'];
		$result = $this->helper->searchClosestWord($default, $words);
		$this->assertSame(2, $result['lev'], 'Le mot identique n\'est pas trouvé parmi les mots proposés');
		$this->assertSame(1, $result['position'], 'La position du mot le plus proche est correcte');
		$this->assertSame('Jéremy LEGENDRE', $result['word'], 'Le mot le plus proche est correct');


		$words = ['JEREMY LeGeNdRe', 'JEREMI LEGENDRE', 'JEREMIE LEGENDRe'];
		$result = $this->helper->searchClosestWord('JeReMY LEGENDRE', $words);
		$this->assertSame(0, $result['lev'], 'La comparaison est insensible à la casse');

		$words = ['JÉRÉMY LeGeNdRe'];
		$result = $this->helper->searchClosestWord('JeReMY LEGENDRE', $words);
		$this->assertGreaterThan(0, $result['lev'], 'La comparaison est sensible aux accents');
	}

	public function testSearchClosestWordPerformance(): void
	{
		$words = array_map(fn($i) => "word{$i}", range(1, 1000));
		$start = microtime(true);

		$result = $this->helper->searchClosestWord('word42', $words);

		$this->assertLessThan(0.1, microtime(true) - $start, "Trop lent pour 1000 mots");
		$this->assertEquals('word42', $result['word']);
	}
}