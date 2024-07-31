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

	public $hash = '';
	private $user = null;

	private $model = null;

	public $current_workflow_id = 0;

	public $current_workflow = null;
	function display($tpl = null)
	{
		$app = Factory::getApplication();
		$this->user = $app->getIdentity();

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id)) {
			require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
			$this->model = new EmundusModelWorkflow();

			$jinput = $app->input;
			$layout = $jinput->getString('layout', null);
			if ($layout === 'add')
			{
				$id = $this->model->add();

				if (!empty($id)) {
					$app->enqueueMessage(Text::_('COM_EMUNDUS_WORKFLOW_ADDED'), 'message');
					$app->redirect('/workflows/edit?wid=' . $id);
				} else {
					$app->enqueueMessage(Text::_('COM_EMUNDUS_WORKFLOW_NOT_ADDED'), 'error');
					$app->redirect('/workflows');
				}
			}

			if ($layout === 'edit')
			{
				$this->current_workflow_id = $jinput->getString('wid', null);

				if (empty($this->current_workflow_id))
				{
					$app->enqueueMessage(Text::_('COM_EMUNDUS_WORKFLOW_NOT_FOUND'), 'error');
					$app->redirect('/workflows');
				}

				$workflows = $this->model->getWorkflows([$this->current_workflow_id]);
				$this->current_workflow = $workflows[0];
			}

			require_once(JPATH_ROOT . '/components/com_emundus/helpers/cache.php');
			$this->hash = EmundusHelperCache::getCurrentGitHash();

			parent::display($tpl);
		} else {
			$app->enqueueMessage(Text::_('ACCESS_DENIED'), 'error');
			$app->redirect('/connexion');
		}
	}
}
