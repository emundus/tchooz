<?php
/**
 * @package     Unit\Component\Emundus\Model
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Model;

use Joomla\Tests\Unit\UnitTestCase;

/**
 * @package     Unit\Component\Emundus\Model
 *
 * @since       version 1.0.0
 * @covers      EmundusModelProgramme
 */
class ProgrammeModelTest extends UnitTestCase
{
	public function __construct(?string $name = null, array $data = [], $dataName = '', $className = null)
	{
		parent::__construct('programme', $data, $dataName, 'EmundusModelProgramme');
	}

	/**
	 * @covers EmundusModelProgramme::getProgrammes
	 *
	 * @since version 1.0.0
	 */
	public function testGetProgrammes()
	{
		$this->assertIsArray($this->model->getProgrammes());
		$this->assertIsArray($this->model->getProgrammes(0));
		$this->assertIsArray($this->model->getProgrammes(0, [
			'IN'     => ['code_1'],
			'NOT_IN' => ['code_2'],
		]));
	}
}