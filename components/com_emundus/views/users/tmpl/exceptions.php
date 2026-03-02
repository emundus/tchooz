<?php

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Factories\LayoutFactory;
use Tchooz\Repositories\Actions\ActionRepository;

Text::script('COM_EMUNDUS_ONBOARD_ACTION_DELETE');

Text::script('COM_EMUNDUS_ONBOARD_USERS_EXCEPTIONS');
Text::script('COM_EMUNDUS_ONBOARD_USERS_EXCEPTIONS_INTRO');
Text::script('COM_EMUNDUS_ONBOARD_USERS_NOEXCEPTIONS');
Text::script('COM_EMUNDUS_ONBOARD_EXCEPTIONS_ADD');
Text::script('COM_EMUNDUS_ONBOARD_EXCEPTIONS_DELETE');
Text::script('COM_EMUNDUS_ONBOARD_EXCEPTIONS_ADD_APPLICANT');
Text::script('COM_EMUNDUS_ONBOARD_LABEL_EXCEPTIONS');
Text::script('COM_EMUNDUS_ONBOARD_EXCEPTIONS_ADD_SUCCESS');
Text::script('COM_EMUNDUS_ONBOARD_EXCEPTIONS_ADD_ERROR');

if (!class_exists('EmundusHelperAccess'))
{
	require_once(JPATH_ROOT . '/components/com_emundus/helpers/access.php');
}

$data = LayoutFactory::prepareVueData();

$user = Factory::getApplication()->getIdentity();

$actionRepository = new ActionRepository();
$usersAction   = $actionRepository->getByName('user');

$data['crud'] = [
	'user' => [
		'c' => $data['coordinator_access'] || $data['sysadmin_access'] || EmundusHelperAccess::asAccessAction($usersAction->getId(), 'c', $user->id),
		'r' => $data['coordinator_access'] || $data['sysadmin_access'] || EmundusHelperAccess::asAccessAction($usersAction->getId(), 'r', $user->id),
		'u' => $data['coordinator_access'] || $data['sysadmin_access'] || EmundusHelperAccess::asAccessAction($usersAction->getId(), 'u', $user->id),
		'd' => $data['coordinator_access'] || $data['sysadmin_access'] || EmundusHelperAccess::asAccessAction($usersAction->getId(), 'd', $user->id),
	],
];
?>

<div id="em-component-vue"
     component="Users/Exceptions"
     data="<?= htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8'); ?>">
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $data['hash'] ?>"></script>