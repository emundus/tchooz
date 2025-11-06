<?php
/**
 * @package     Unit\Component\Emundus\Class
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Repositories\Actions;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Contacts\AddressEntity;
use Tchooz\Entities\Contacts\ContactEntity;
use Tchooz\Entities\Contacts\OrganizationEntity;
use Tchooz\Enums\Contacts\Gender;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Repositories\Contacts\AddressRepository;
use Tchooz\Repositories\Contacts\ContactRepository;
use Tchooz\Repositories\Contacts\OrganizationRepository;
use Tchooz\Repositories\CountryRepository;

/**
 * @package     Unit\Component\Emundus\Helper
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Entities\NumericSign\Request
 */
class ActionRepositoryTest extends UnitTestCase
{
	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct();
		$this->initDataSet();

		$this->model = new ActionRepository();
	}

	public function testGetByName(): void
	{
		$action = $this->model->getByName('file');
		$this->assertNotNull($action);
		$this->assertEquals('file', $action->getName());
	}
}