<?php
/**
 * Webhook controller class
 *
 * @package     Joomla.Administrator
 * @subpackage  eMundus
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;

require_once (JPATH_SITE.'/components/com_emundus/helpers/access.php');
require_once (JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/update.php');

class EmundusAdminControllerWebhook extends JControllerLegacy
{
    function generate() {
        $results = ['status' => true];

		if(EmundusHelperAccess::asAdministratorAccessLevel(Factory::getApplication()->getIdentity()->id)) {
			$results['token'] = JUserHelper::genRandomPassword(32);
			$hash_token       = JApplicationHelper::getHash($results['token']);

			$options['webhook_token'] = $hash_token;
			EmundusHelperUpdate::updateConfigurationFile($options);
		}


	    echo json_encode((object)$results);
	    exit;
    }
}
