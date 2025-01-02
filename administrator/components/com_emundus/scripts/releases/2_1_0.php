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

		/* Fix yesno for program form */
		$query = $this->db->getQuery(true);
		$query->clear()
			->select('id,plugin,params')
			->from($this->db->quoteName('#__fabrik_elements'))
			->where($this->db->quoteName('name') . ' IN ("published","apply_online")')
			->where($this->db->quoteName('plugin') . ' = ' . $this->db->quote('radiobutton'))
			->where($this->db->quoteName('group_id') . ' = 182');
		$this->db->setQuery($query);
		$elements = $this->db->loadObjectList();

		foreach ($elements as $element) {
			$element->plugin = 'yesno';
			$params = json_decode($element->params, true);
			$params['yesno_default'] = 1;
			$params['yesno_icon_yes'] = '';
			$params['yesno_icon_no'] = '';
			$params['options_per_row'] = 1;
			$params['toggle_others'] = 0;
			$params['toggle_where'] = '';
			unset($params['sub_options']);
			unset($params['btnGroup']);
			unset($params['btnClass']);
			unset($params['allow_frontend_addtoradio']);
			unset($params['rad-allowadd-onlylabel']);
			unset($params['rad-savenewadditions']);
			unset($params['dropdown_populate']);
			unset($params['bootstrap_class']);

			$element->params = json_encode($params);
			$this->db->updateObject('#__fabrik_elements', $element, 'id');
		}

		return $result;
	}
}