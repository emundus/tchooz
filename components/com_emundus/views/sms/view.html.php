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

require_once(JPATH_ROOT . '/components/com_emundus/models/sms.php');

/**
 * eMundus Onboard Campaign View
 *
 * @since  0.0.1
 */
class EmundusViewSms extends JViewLegacy
{

	public $hash = '';
	public $user = null;

	private ?EmundusModelSMS $model = null;
	public int $current_sms_template_id = 0;

	function display($tpl = null)
	{
		$app = Factory::getApplication();
		$this->model = new EmundusModelSMS();
		$this->user = $app->getIdentity();

		if (EmundusHelperAccess::asAccessAction($this->model->getSmsActionId(), 'c', $this->user->id)) {
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/cache.php');
			$this->hash = EmundusHelperCache::getCurrentGitHash();

			Text::script('COM_EMUNDUS_SMS_LABEL');
			Text::script('COM_EMUNDUS_SMS_MESSAGE');
			Text::script('SAVE');
			Text::script('COM_EMUNDUS_SMS_UPDATED_SUCCESSFULLY');
			Text::script('COM_EMUNDUS_ONBOARD_ADD_SMS');

			$jinput = $app->input;
			$layout = $jinput->getString('layout', null);
			if ($layout === 'add') {
				$id = $this->model->addTemplate($this->user->id, Text::_('COM_EMUNDUS_NEW_SMS_LABEL'));

				if (!empty($id)) {
					$app->enqueueMessage(Text::_('COM_EMUNDUS_SMS_TEMPLATE_ADDED'), 'message');
					$menu = Factory::getApplication()->getMenu();
					$item = $menu->getItems('link', 'index.php?option=com_emundus&view=sms&layout=edit', true);
					$app->redirect($item->route . '?sms_id=' . $id);
				} else {
					$app->enqueueMessage(Text::_('COM_EMUNDUS_SMS_TEMPLATE_NOT_ADDED'), 'error');
					$app->redirect('/index.php?option=com_emundus&view=sms');
				}
			}

			if ($layout === 'edit') {
				$this->current_sms_template_id = $jinput->getInt('sms_id', 0);

				if (empty($this->current_sms_template_id)) {
					$app->enqueueMessage(Text::_('COM_EMUNDUS_SMS_TEMPLATE_NOT_FOUND'), 'error');
					$app->redirect('/index.php?option=com_emundus&view=sms');
				}
			}

			parent::display($tpl);
		} else {
			$app->enqueueMessage(Text::_('ACCESS_DENIED'), 'error');
			$app->redirect('/connexion');
		}
	}
}