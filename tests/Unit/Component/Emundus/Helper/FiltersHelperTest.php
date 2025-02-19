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
		$this->assertSame('Jérémy LEGENDRE', $result['word']);

		$words = ['Jérôme rienavoir', 'Jérémy LEGENDRE', 'Jéremy LEGNDRE', 'Jérémie LEGAN'];
		$result = $this->helper->searchClosestWord($default, $words);
		$this->assertSame(0, $result['lev']);
		$this->assertSame(1, $result['position']);
		$this->assertSame('Jérémy LEGENDRE', $result['word']);

		$words = ['Jérôme different', 'Jéremy LEGENDRE', 'Jérémie LEGANDRE'];
		$result = $this->helper->searchClosestWord($default, $words);
		$this->assertSame(2, $result['lev']);
		$this->assertSame(1, $result['position']);
		$this->assertSame('Jéremy LEGENDRE', $result['word']);
	}
}