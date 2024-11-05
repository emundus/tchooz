<?php
defined('_JEXEC') or die('Access Deny');

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;

require_once (JPATH_SITE.'/components/com_emundus/helpers/access.php');

$fnum = Factory::getApplication()->input->getString('fnum', '');
if(!empty($fnum)) {
	$em_session = Factory::getSession()->get('emundusUser', []);

	if(in_array($fnum,array_keys($em_session->fnums))) {
		require_once(dirname(__FILE__).DS.'helper.php');
		require_once (JPATH_SITE.'/components/com_emundus/helpers/cache.php');
		$hash = EmundusHelperCache::getCurrentGitHash();

		$document = Factory::getApplication()->getDocument();
		$document->addStyleSheet("modules/mod_emundus_attachments/css/mod_emundus_attachments.css?".$hash);

		$title = $params->get('mod_emundus_attachments_title', '');
		$groups = $params->get('mod_emundus_attachments_groups', []);

		$attachments = modEmundusAttachmentsHelper::getAttachments($groups, $fnum);

		require(ModuleHelper::getLayoutPath('mod_emundus_attachments'));
	}
}

?>