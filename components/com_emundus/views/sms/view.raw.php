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

	function display($tpl = null)
	{
		$app = Factory::getApplication();
		$this->model = new EmundusModelSMS();
		$this->user = $app->getIdentity();

		if (EmundusHelperAccess::asAccessAction($this->model->getSmsActionId(), 'c', $this->user->id)) {
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/cache.php');
			$this->hash = EmundusHelperCache::getCurrentGitHash();
			$jinput = $app->input;
			$layout = $jinput->getString('layout', null);

			if ($layout === 'send') {
				$files = $jinput->getString('fnums', '');
				if (!empty($files)) {
					$files = json_decode($files, true);
					$this->fnums = [];

					foreach ($files as $file) {
						if (EmundusHelperAccess::asAccessAction($this->model->getSmsActionId(), 'c', $this->user->id, $file['fnum'])) {
							$this->fnums[] = $file['fnum'];
						}
					}
					$this->fnums = array_unique($this->fnums);
				}
			}else {
				$app->enqueueMessage(Text::_('ACCESS_DENIED'), 'error');
				$app->redirect('/connexion');
			}

			parent::display($tpl);
		} else {
			$app->enqueueMessage(Text::_('ACCESS_DENIED'), 'error');
			$app->redirect('/connexion');
		}
	}
}