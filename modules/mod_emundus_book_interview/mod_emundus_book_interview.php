<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;

defined('_JEXEC') or die('Access Deny');

require_once(dirname(__FILE__) . DS . 'helper.php');

$app = Factory::getApplication();
$wa  = $app->getDocument()->getWebAssetManager();
$wa->registerAndUseStyle('bootstrap-336', 'media/com_emundus/lib/bootstrap-336/css/bootstrap.min.css');

$session          = $app->getSession();
$user             = $session->get('emundusUser');
$helper           = new modEmundusBookInterviewHelper;
$evaluated_status = $params->get('evaluated_status');

$interview = $helper->getLastFileInterviewStatus($user->id);
$jinput = $app->input;
$fnum = $jinput->get->get('fnum');

if(empty($fnum)){
	$fnum = $user->fnum;
}

if (!empty($fnum)) {
	// First we need to check if the user has booked.
	// If the user has not, we will display a button that opens a modal allowing them to book an event (and so we need to get the event info).
	// If the user has we will display the date of their interview.
	$user_booked = $helper->hasUserbooked($user->id, $user->start_date);

	$offset = $app->get('offset');

	$layout = $params->get('mod_em_book_interview_layout');
	if ($user_booked) {

		$next_interview = $helper->getNextInterview($user);
		$interview_dt   = new DateTime($next_interview->start_date, new DateTimeZone('GMT'));
		$interview_dt->setTimezone(new DateTimeZone($offset));
		$interview_date = $interview_dt->format('M j Y');
		$interview_time = $interview_dt->format('g:i A');

		$layout = 'showInterview_' . $layout;
	}
	elseif ($user->status == $evaluated_status) {

		$available_events = $helper->getEvents($user, $fnum);

		$contact_info = array();
		if ($params->get('skype') == 1) {
			$contact_info['skype'] = JText::_('MOD_EM_BOOK_INTERVIEW_ENTER_SKYPE_ID');
		}
		if ($params->get('facetime') == 1) {
			$contact_info['facetime'] = JText::_('MOD_EM_BOOK_INTERVIEW_ENTER_FACETIME_ID');
		}
		if ($params->get('whatsapp') == 1) {
			$contact_info['whatsapp'] = JText::_('MOD_EM_BOOK_INTERVIEW_ENTER_FACETIME_ID');
		}
		if ($params->get('google') == 1) {
			$contact_info['google'] = JText::_('ENTER_GOOGLE_ID');
		}

	}

	require(ModuleHelper::getLayoutPath('mod_emundus_book_interview', $layout));
}
