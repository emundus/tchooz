<?php
/**
 * @version     2: emunduscampaign 2019-04-11 Hugo Moracchini
 * @package     Fabrik
 * @copyright   Copyright (C) 2018 emundus.fr. All rights reserved.
 * @license     GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 * @description Création de dossier de candidature automatique.
 */

// No direct access
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\User\UserHelper;

defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

class PlgFabrik_FormEmundusCampaignMore extends plgFabrik_Form
{
	public function onBeforeProcess()
	{
		$emParams = ComponentHelper::getParams('com_emundus');
		$force_campaigns_more = $emParams->get('force_campaigns_more', 0);
		
		if($force_campaigns_more == 1) {
			$formModel = $this->getModel();
			$data = $formModel->formData;
			$cid = $data['jos_emundus_setup_campaigns_more___campaign_id_raw'];
			if(is_array($cid))
			{
				$cid = $cid[0];
			}

			if(!empty($cid)) {
				// Check if already published
				$query = $this->_db->getQuery(true);

				$query->select('published')
					->from($this->_db->quoteName('#__emundus_setup_campaigns'))
					->where($this->_db->quoteName('id') . ' = ' . (int)$cid);
				$this->_db->setQuery($query);
				$published = $this->_db->loadResult();

				if($published == 1) {
					return true;
				}

				// Ask to publish via a sweetalert
				echo "<script type='text/javascript'>window.parent.postMessage('askPublishCampaign', '*')</script>";
			}
		}
	}
}
