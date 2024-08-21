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
			$this->model = new EmundusModelWorkflow();

			$jinput = $app->input;
			$layout = $jinput->getString('layout', null);
			if ($layout !== 'evaluator_step') {
				return;
			} else {
				$step_id = $jinput->getInt('step_id', 0);
				$this->fnum = $jinput->getString('fnum', '');

				if (!empty($step_id)) {
					$this->step = $this->model->getStepData($step_id);
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
