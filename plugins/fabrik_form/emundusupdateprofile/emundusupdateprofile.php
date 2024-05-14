<?php
/**
 * @version 2: emundusupdate 2024-04-04 Laura Grandin
 * @package Fabrik
 * @copyright Copyright (C) 2018 emundus.fr. All rights reserved.
 * @license GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 * @description Redirection du formulaire profil lors de la soumission.
 */

// No direct access
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;

defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Create a Joomla user from the forms data
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.juseremundus
 * @since       3.0
 */
class PlgFabrik_FormEmundusupdateprofile extends plgFabrik_Form {

	public function __construct(&$subject, $config = array()) {
		parent::__construct($subject, $config);
	}

	/**
	 * Main script.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function onAfterProcess() : void {
		jimport('joomla.log.log');
		Log::addLogger(['text_file' => 'com_emundus.emundusupdateprofile.php'], Log::ALL, array('com_emundus.emundusupdateprofile'));

		$app = Factory::getApplication();
		$base_route = Uri::base();

		$menu = $app->getMenu();
		$formModel = $this->getModel();
		$app->enqueueMessage(Text::_('PROFILE_SAVED'), 'info');

		$alias = $this->getParams()->get('emundusupdateprofile_field_alias','');

		if(empty($alias))
		{
			$item = $menu->getItems('link', 'index.php?option=com_fabrik&view=form&formid=' . $formModel->id, true);
			if(!empty($item)) {
				$alias = $item->route;
			}
		}
		
		$current_lang = $app->getLanguage()->getTag();
		$default_lang = ComponentHelper::getParams('com_languages')->get('site', 'en-GB');
		if($current_lang != $default_lang) {
			$base_route = $base_route.substr($current_lang, 0, 2).'/';
		}

		if (!empty($alias)) {
			$app->redirect($base_route . $alias);
		}
	}
}
