<?php

/**
 * @package     Joomla
 * @subpackage  com_emunudus_onboard
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * eMundus Onboard Campaign View
 *
 * @since  0.0.1
 */
class EmundusViewWorkflows extends JViewLegacy
{

	public $user = null;
	private $model = null;
	function display($tpl = null)
	{
		$app = Factory::getApplication();
		$this->user = $app->getIdentity();

		if (EmundusHelperAccess::asPartnerAccessLevel($this->user->id)) {
			require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
			require_once(JPATH_ROOT . '/components/com_emundus/models/users.php');
			$this->model = new EmundusModelWorkflow();
			$m_user     = new EmundusModelUsers();
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$jinput = $app->input;
			$layout = $jinput->getString('layout', null);
			if ($layout !== 'evaluator_step') {
				return;
			} else {
				$step_id = $jinput->getInt('step_id', 0);
				$this->fnum = $jinput->getString('fnum', '');

				$query->clear()
					->select('applicant_id, id')
					->from('#__emundus_campaign_candidature')
					->where('fnum LIKE ' . $db->quote($this->fnum));
				$db->setQuery($query);
				$data = $db->loadAssoc();

				$this->ccid = $data['id'];
				$this->applicant  = $m_user->getUserById($data['applicant_id'])[0];
				if (!isset($this->applicant->profile_picture) || empty($this->applicant->profile_picture)) {
					$this->applicant->profile_picture = $m_user->getIdentityPhoto($this->fnum, $data['applicant_id']);
				}

				if (!empty($step_id)) {
					$this->step = $this->model->getStepData($step_id);

					$query->clear()
						->select('jfl.db_table_name')
						->from($db->quoteName('#__fabrik_lists', 'jfl'))
						->leftJoin($db->quoteName('#__emundus_setup_workflows_steps', 'esws') . 'ON esws.form_id = jfl.form_id')
						->where('esws.id = ' . $step_id);
					
					$db->setQuery($query);
					$this->step->db_table_name = $db->loadResult();

					try {
						$this->access = EmundusHelperAccess::getUserEvaluationStepAccess($this->ccid, $this->step, $this->user->id);
					} catch (Exception $e) {
						$app->enqueueMessage($e->getMessage(), 'error');
					}
				} else {
					$this->step = null;
				}
			}

			parent::display($tpl);
		} else {
			$app->enqueueMessage(Text::_('ACCESS_DENIED'), 'error');
			$app->redirect('/connexion');
		}
	}
}
