<?php
/**
 * @package     Unit\Component\Emundus\Class
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Repositories\NumericSign;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Repositories\NumericSign\RequestRepository;

/**
 * @package     Unit\Component\Emundus\Helper
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Entities\NumericSign\Request
 */
class RequestRepositoryTest extends UnitTestCase
{
	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct();

		$this->model = new RequestRepository($this->db);
	}

	public function testGetCountRequests()
	{
		$this->assertTrue(true, 'This test is not implemented yet');
	}
}