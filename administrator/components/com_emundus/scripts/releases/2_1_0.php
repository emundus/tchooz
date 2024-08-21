<?php
/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use EmundusHelperUpdate;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;

class Release2_0_0Installer extends ReleaseInstaller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];

		require_once(JPATH_ROOT . '/administrator/components/com_emundus/models/workflow.php');
		$m_workflow = new EmundusModelAdministratorWorkflow();
		$result['status'] = $m_workflow->install();

		return $result;
	}
}