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
require_once(JPATH_SITE . '/administrator/components/com_emundus/models/workflow.php');

class Release2_1_0Installer extends ReleaseInstaller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];
		EmundusHelperUpdate::displayMessage('Installation du workflow builder.', 'success');

		if (!class_exists('EmundusModelAdministratorWorkflow')) {
			require_once(JPATH_ROOT . '/administrator/components/com_emundus/models/workflow.php');
		}
		$m_workflow = new \EmundusModelAdministratorWorkflow();

		$result['status'] = $m_workflow->install();
		if (!$result['status']) {
			throw new \Exception('Erreur lors de l\'installation du Workflow Builder.');
		}

		EmundusHelperUpdate::addColumn('jos_emundus_setup_letters', 'for_all', 'TINYINT(1)', null, 0, 0);
		EmundusHelperUpdate::addColumn('jos_emundus_setup_emails_trigger', 'all_program', 'TINYINT(1)', null, 0, 0);

		return $result;
	}
}