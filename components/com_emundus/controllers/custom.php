<?php
/**
 * @package     Joomla
 * @subpackage  eMundus
 * @link        http://www.emundus.fr
 * @copyright   Copyright (C) 2016 eMundus. All rights reserved.
 * @license     GNU/GPL
 * @author      eMundus - Benjamin Rivalland
 */

// No direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.application.component.controller');

/**
 * Campaign Controller
 *
 * @package    Joomla
 * @subpackage eMundus
 * @since      v6
 */
class EmundusControllerCustom extends JControllerLegacy {

	public function getmyletter(){
		$db = JFactory::getDBo();
		$query = $db->getQuery(true);
		$app = JFactory::getApplication();
		$fnum = $app->input->getString('fnum');

		$user = JFactory::getUser();

		$query
			->select('*')
			->from($db->quoteName('#__emundus_uploads'))
			->where($db->quoteName('fnum') . ' LIKE ' . $db->quote($fnum))
			->andWhere($db->quoteName('attachment_id') . ' = 105');
		$db->setQuery($query);

		try {
			$res = $db->loadObject();

			if (empty($res)) {
				$app->enqueueMessage("Vous n'avez pas de demande de financement. Contacter le gestionnaire de la plateforme pour plus d'informations.", ' warning');
				$app->redirect('index.php', '');
			}

			if(is_file(JPATH_SITE . '/images/emundus/files/' . $user->id . DS . $res->filename)){
				$app->redirect('/images/emundus/files/' . $user->id . DS . $res->filename);
			}
		} catch (Exception $e) {
			$app->enqueueMessage("Vous n'avez pas de demande de financement à signer. Contacter le gestionnaire de la plateforme pour plus d'informations.", ' warning');
			$app->redirect('index.php', '');
		}
	}
}

?>