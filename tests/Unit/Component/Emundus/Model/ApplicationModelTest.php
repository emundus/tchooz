<?php

/**
 * @package     Joomla.UnitTest
 * @subpackage  Extension
 *
 * @copyright   (C) 2022 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Tests\Unit\Component\Emundus\Model;

use EmundusModelApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\TestCase;

require_once JPATH_BASE . '/components/com_emundus/models/application.php';

ini_set( 'display_errors', false );
error_reporting(E_ALL);

jimport('joomla.user.helper');
jimport( 'joomla.application.application' );
jimport('joomla.plugin.helper');

session_start();

/**
 * Test class for ActionlogConfigModel
 *
 * @package     Joomla.UnitTest
 * @subpackage  Actionlog
 * @since       4.2.0
 */
class ApplicationModelTest extends UnitTestCase
{
	/**
	 * @testdox  Test that getLogContentTypeParams returns the correct params
	 *
	 * @return  void
	 *
	 * @since   4.2.0
	 */
	public function testGetApplicantInfos()
	{
		$config = new \stdClass();
		$db     = $this->createStub(DatabaseInterface::class);
		$db->method('loadObject')->willReturn($config);

		$model = new EmundusModelApplication(['dbo' => $db], $this->createStub(MVCFactoryInterface::class));

		$applicant_infos = $model->getApplicantInfos(0, []);
		$this->assertSame([], $applicant_infos);
	}
}
