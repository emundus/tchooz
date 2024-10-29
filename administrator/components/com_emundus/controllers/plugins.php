<?php


// No direct access.
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

jimport('joomla.application.component.controlleradmin');

/**
 * Jobs list controller class.
 */
class EmundusControllerPlugins extends JControllerAdmin
{
	public function get_well_known_configuration() {
		$response = ['status' => false, 'message' => Text::_('ACCESS_DENIED')];

		$user = Factory::getApplication()->getIdentity();

		if ($user->authorise('core.admin')) {
			$jinput = Factory::getApplication()->input;
			$url = $jinput->getString('url', '');

			if (!empty($url)) {
				$json = file_get_contents($url);
				$response = ['status' => true, 'message' => Text::_('SUCCESS'), 'data' => json_decode($json)];
			}
		}

		echo json_encode($response);
		exit;
	}
}