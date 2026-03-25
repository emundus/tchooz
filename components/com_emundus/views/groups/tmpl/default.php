<?php
/**
 * @package     Joomla
 * @subpackage  com_emundus
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Language\Text;
use Tchooz\Factories\LayoutFactory;

Text::script('COM_EMUNDUS_ONBOARD_GROUPS');
Text::script('COM_EMUNDUS_ONBOARD_GROUPS_INTRO');
Text::script('COM_EMUNDUS_ONBOARD_NOGROUPS');
Text::script('COM_EMUNDUS_ONBOARD_ADD_GROUP');
Text::script('COM_EMUNDUS_ONBOARD_GROUPS_DELETE');
Text::script('COM_EMUNDUS_ONBOARD_FILTER_UNPUBLISH');
Text::script('PUBLISHED');

Text::script('COM_EMUNDUS_ONBOARD_CAMPAIGNS_FILTER_PROGRAMS');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_DUPLICATE');
Text::script('COM_EMUNDUS_ONBOARD_MODIFY');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_DELETE');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_SHOW_DETAILS');
Text::script('COM_EMUNDUS_ONBOARD_ALL_PROGRAMS');
Text::script('COM_EMUNDUS_GROUPS_ADD_GROUP_GENERAL');
Text::script('COM_EMUNDUS_GROUPS_ADD_GROUP_RIGHTS');
Text::script('COM_EMUNDUS_ACTION_RESOURCE');
Text::script('COM_EMUNDUS_ACTION_CREATE');
Text::script('COM_EMUNDUS_ACTION_READ');
Text::script('COM_EMUNDUS_ACTION_UPDATE');
Text::script('COM_EMUNDUS_ACTION_DELETE');
Text::script('COM_EMUNDUS_ACTION_TYPE_FILE');
Text::script('COM_EMUNDUS_ACTION_TYPE_PLATFORM');
Text::script('COM_EMUNDUS_ACTION_TYPE_USERS');
Text::script('COM_EMUNDUS_ACTION_SEARCH_PLACEHOLDER');
Text::script('COM_EMUNDUS_GROUPS_SHOW_RIGHTS_INTRO');
Text::script('COM_EMUNDUS_ONBOARD_GROUPS_PROGRAMS');
Text::script('COM_EMUNDUS_GROUPS_ADD_GROUP_PROGRAMMES');
Text::script('COM_EMUNDUS_GROUPS_USERS_ASSOCIATE');
Text::script('COM_EMUNDUS_USERS_SEARCH_PLACEHOLDER');
Text::script('COM_EMUNDUS_GROUPS_USERS_ASSOCIATE_NO_USERS');
Text::script('COM_EMUNDUS_ONBOARD_GROUPS_DUPLICATE_NAME_INPUT_LABEL');
Text::script('COM_EMUNDUS_ONBOARD_GROUPS_DUPLICATE_CONFIRM');

$data = LayoutFactory::prepareVueData();
?>

<div id="em-component-vue"
     component="Groups/GroupList"
     data="<?= htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8'); ?>">
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $data['hash'] ?>"></script>
