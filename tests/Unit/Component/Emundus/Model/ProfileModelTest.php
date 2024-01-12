<?php
/**
 * @package     Unit\Component\Emundus\Model
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Model;

use EmundusModelFormbuilder;
use Joomla\CMS\Factory;
use Joomla\Tests\Unit\UnitTestCase;

require_once JPATH_BASE . '/components/com_emundus/models/formbuilder.php';

class ProfileModelTest extends UnitTestCase
{
	/**
	 * @var EmundusModelFormbuilder
	 */
	private $m_formbuilder;
	
	public function __construct(?string $name = null, array $data = [], $dataName = '', $className = null)
	{
		parent::__construct('profile', $data, $dataName, 'EmundusModelProfile');

		$this->m_formbuilder = new EmundusModelFormbuilder();
	}

	/**
	 * @covers EmundusModelProfile::getApplicantFnums
	 * @covers EmundusHelperFiles::getApplicantFnums
	 * @return void
	 */
	public function testgetApplicantFnums()
	{
		$user_id = Factory::getUser()->id;
		$fnums   = $this->model->getApplicantFnums($user_id);
		$this->assertIsArray($fnums);
		$this->assertEmpty($fnums, 'Empty user return empty array');

		$user_id     = $this->h_dataset->createSampleUser(9, 'userunittest' . rand(0, 1000) . '@emundus.test.fr');
		$program     = $this->h_dataset->createSampleProgram();
		$campaign_id = $this->h_dataset->createSampleCampaign($program);
		$fnum        = $this->h_dataset->createSampleFile($campaign_id, $user_id);

		$fnums = $this->model->getApplicantFnums($user_id);
		$this->assertIsArray($fnums);
		$this->assertNotEmpty($fnums);
		$this->assertContains($fnum, array_keys($fnums));
	}
}