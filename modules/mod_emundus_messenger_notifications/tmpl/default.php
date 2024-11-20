<?php

defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;

Text::script('COM_EMUNDUS_MESSENGER_TITLE');
Text::script('COM_EMUNDUS_MESSENGER_SEND_DOCUMENT');
Text::script('COM_EMUNDUS_MESSENGER_ASK_DOCUMENT');
Text::script('COM_EMUNDUS_MESSENGER_DROP_HERE');
Text::script('COM_EMUNDUS_MESSENGER_SEND');
Text::script('COM_EMUNDUS_MESSENGER_WRITE_MESSAGE');
Text::script('COM_EMUNDUS_MESSENGER_TYPE_ATTACHMENT');
Text::script('COM_EMUNDUS_PLEASE_SELECT');

$app = Factory::getApplication();
$user = $app->getSession()->get('emundusUser');

if (!empty($user->fnum)) {
	$fnum = $user->fnum;
}
?>

<div id="em-notifications" user="<?= $user->id ?>" fnum="<?= $fnum ?>"></div>

<script src="media/mod_emundus_messenger_notifications/app.js"></script>
