<?php
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

$user = Factory::getApplication()->getIdentity();

if (!$user->guest && ($applicant || EmundusHelperAccess::asAccessAction(36,'c',$user->id)))
{
	$app          = Factory::getApplication();
	$language     = $app->getLanguage();
	$current_lang = $language->getTag();

	$language->load('com_emundus', JPATH_SITE . '/components/com_emundus', $current_lang, true);

	$user = $app->getSession()->get('emundusUser');

	$fnum = '';
	$name = $user->name;
	if (!empty($user->fnum))
	{
		$fnum = $user->fnum;
	}

	require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'messenger.php');
	$m_messenger     = new EmundusModelMessenger();
	$unread_messages = $m_messenger->getNotifications($user->id, $applicant, true);

	require_once(JPATH_BASE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'cache.php');
	$hash = EmundusHelperCache::getCurrentGitHash();

	Text::script('COM_EMUNDUS_MESSENGER_TITLE');
	Text::script('COM_EMUNDUS_MESSENGER_SEND_DOCUMENT');
	Text::script('COM_EMUNDUS_MESSENGER_ASK_DOCUMENT');
	Text::script('COM_EMUNDUS_MESSENGER_DROP_HERE');
	Text::script('COM_EMUNDUS_MESSENGER_SEND');
	Text::script('COM_EMUNDUS_MESSENGER_WRITE_MESSAGE');
	Text::script('COM_EMUNDUS_MESSENGER_TYPE_ATTACHMENT');
	Text::script('COM_EMUNDUS_PLEASE_SELECT');
	Text::script('COM_EMUNDUS_MESSENGER_NO_MESSAGES');
	Text::script('COM_EMUNDUS_MESSENGER_CREATE_CHATROOM');
	Text::script('COM_EMUNDUS_MESSENGER_SELECT_FILE');
	Text::script('COM_EMUNDUS_MESSENGER_SEARCH_IN_MESSAGES');
	Text::script('COM_EMUNDUS_MESSENGER_ALL_MESSAGES');
	Text::script('COM_EMUNDUS_MESSENGER_CLOSE_CHATROOM');
	Text::script('COM_EMUNDUS_MESSENGER_OPEN_CHATROOM');
	Text::script('COM_EMUNDUS_MESSENGER_CHATROOM_CLOSED');
	Text::script('COM_EMUNDUS_MESSENGER_CLOSED_CHATROOMS');
	Text::script('COM_EMUNDUS_MESSENGER_SELECT_CHATROOM');
	Text::script('COM_EMUNDUS_MESSENGER_NO_MESSAGES_COORDINATOR');
	Text::script('COM_EMUNDUS_MESSENGER_NOTIFICATIONS_HAS_SENT_MESSAGES');
	Text::script('COM_EMUNDUS_MESSENGER_NOTIFICATIONS_HAS_SENT_ONE_MESSAGE');
	Text::script('COM_EMUNDUS_MESSENGER_NOTIFICATIONS');
	Text::script('COM_EMUNDUS_MESSENGER_NO_NOTIFICATIONS');

	Text::script('COM_EMUNDUS_MULTISELECT_NORESULTS');

	$datas = [
		'fnum'            => $fnum,
		'fullname'        => $name,
		'unread_messages' => $unread_messages,
		'applicant'       => $applicant
	];
	?>

    <div id="em-messenger"
         component="Messenger/Messenger"
         data="<?= htmlspecialchars(json_encode($datas), ENT_QUOTES, 'UTF-8'); ?>"
    ></div>

    <script type="module" src="media/com_emundus_vue/app_emundus.js"></script>

<?php } ?>