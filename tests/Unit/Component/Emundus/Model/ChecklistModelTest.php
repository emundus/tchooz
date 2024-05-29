<?php
/**
 * @package     Unit\Component\Emundus\Model
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Model;

use Joomla\CMS\Factory;
use Joomla\Tests\Unit\UnitTestCase;
use stdClass;

class ChecklistModelTest extends UnitTestCase
{

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct('checklist', $data, $dataName, 'EmundusModelChecklist');
	}

	public function testgetAttachmentsList()
	{
		$attachments = $this->model->getAttachmentsList();
		$this->assertIsArray($attachments);

		// set session
		$user               = new stdClass();
		$user->id           = $this->dataset['coordinator'];
		$user->profile      = 1;
		$user->fnum         = '00000000';
		$user->applicant_id = 1;
		$user->email        = '';
		$user->fnums        = array('00000000');

		Factory::getApplication()->getSession()->set('emundusUser', $user);

		$attachments = $this->model->getAttachmentsList();
		$this->assertIsArray($attachments);
	}
}