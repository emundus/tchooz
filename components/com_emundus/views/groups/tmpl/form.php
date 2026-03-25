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
use Symfony\Component\Yaml\Yaml;
use Tchooz\Factories\Fabrik\FabrikFactory;
use Tchooz\Factories\LayoutFactory;
use Tchooz\Repositories\ApplicationFile\StatusRepository;
use Tchooz\Repositories\Attachments\AttachmentTypeRepository;
use Tchooz\Repositories\Fabrik\FabrikRepository;

Text::script('BACK');
Text::script('COM_EMUNDUS_MULTISELECT_NORESULTS');

Text::script('COM_EMUNDUS_GROUPS_EDIT_TITLE');
Text::script('COM_EMUNDUS_GROUPS_ADD_TITLE');
Text::script('COM_EMUNDUS_GROUPS_ADD_GROUP_GENERAL');
Text::script('COM_EMUNDUS_GROUPS_ADD_GROUP_PROGRAMMES');
Text::script('COM_EMUNDUS_GROUPS_ADD_GROUP_RIGHTS');
Text::script('COM_EMUNDUS_GROUPS_GROUP_LABEL');
Text::script('COM_EMUNDUS_GROUPS_EDIT_SAVE');
Text::script('COM_EMUNDUS_GROUPS_ADD_SAVE');
Text::script('COM_EMUNDUS_GROUPS_GROUP_PUBLISHED_LABEL');
Text::script('COM_EMUNDUS_GROUPS_GROUP_ANONYMIZE_LABEL');
Text::script('COM_EMUNDUS_GROUPS_GROUP_FILTER_STATUS_LABEL');
Text::script('COM_EMUNDUS_ONBOARD_GROUPS_DESCRIPTION');
Text::script('COM_EMUNDUS_GROUPS_ADD_PLEASE_CREATE_FIRST');
Text::script('COM_EMUNDUS_ONBOARD_GROUPS_COLOR');
Text::script('COM_EMUNDUS_WORKFLOW_SEARCH_PROGRAMS_PLACEHOLDER');
Text::script('COM_EMUNDUS_WORKFLOW_CHECK_ALL');
Text::script('COM_EMUNDUS_WORKFLOW_NO_PROGRAMS');
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
Text::script('COM_EMUNDUS_GROUPS_GROUP_HIDE_ELEMENTS_LABEL');
Text::script('COM_EMUNDUS_GROUPS_GROUP_HIDE_ATTACHMENTS_LABEL');
Text::script('COM_EMUNDUS_GROUPS_GROUP_HIDE_ELEMENTS_YES_LABEL');
Text::script('COM_EMUNDUS_GROUPS_GROUP_HIDE_ELEMENTS_NO_LABEL');
Text::script('COM_EMUNDUS_GROUPS_GROUP_ATTACHMENTS_LABEL');
Text::script('COM_EMUNDUS_GROUPS_GROUP_ATTACHMENTS_DESC');
Text::script('COM_EMUNDUS_MULTISELECT_ADDKEYWORDS');
Text::script('PRESS_ENTER_TO_SELECT');
Text::script('PRESS_ENTER_TO_SELECT_GROUP');
Text::script('PRESS_ENTER_TO_DESELECT_GROUP');
Text::script('PRESS_ENTER_TO_REMOVE');
Text::script('COM_EMUNDUS_MULTISELECT_NOKEYWORDS');
Text::script('SELECTED');
Text::script('COM_EMUNDUS_MULTISELECT_NORESULTS');
Text::script('COM_EMUNDUS_GROUPS_VISIBLE_GROUPS_LABEL');
Text::script('COM_EMUNDUS_GROUPS_VISIBLE_GROUPS_DESC');
Text::script('COM_EMUNDUS_GROUPS_USERS_ASSOCIATE');
Text::script('COM_EMUNDUS_USERS_SEARCH_PLACEHOLDER');
Text::script('COM_EMUNDUS_GROUPS_USERS_ASSOCIATE_NO_USERS');
Text::script('COM_EMUNDUS_GROUPS_USERS_ASSOCIATE_ADD_USER');
Text::script('COM_EMUNDUS_GROUPS_ADD_GROUP_GENERAL_INTRO');
Text::script('COM_EMUNDUS_GROUPS_ADD_GROUP_PROGRAMMES_INTRO');

$colors = [];
$yaml   = Yaml::parse(file_get_contents('templates/g5_helium/custom/config/default/styles.yaml'));
if (!empty($yaml))
{
    $colors = array_values($yaml['accent']);
}

$statusRepository = new StatusRepository();
$statuses         = $statusRepository->getAll();

$attachmentRepository = new AttachmentTypeRepository();
$attachments = $attachmentRepository->get();

$fabrikRepository = new FabrikRepository(false);
$fabrikFactory = new FabrikFactory($fabrikRepository);
$fabrikRepository->setFactory($fabrikFactory);

$data = LayoutFactory::prepareVueData();

$data['colors']   = $colors;
$data['statuses'] = array_map(function ($status) {
    return ['value' => $status->getStep(), 'name' => $status->getLabel()];
}, $statuses);
$data['attachments'] = array_map(function ($attachment) {
    return ['value' => $attachment->getId(), 'name' => $attachment->getName()];
}, $attachments);
$data['group_id']       = $this->id;


$h_menu = new EmundusHelperMenu();
if(!class_exists('EmundusModelUsers')) {
    require_once JPATH_SITE.'/components/com_emundus/models/users.php';
}
$m_users = new EmundusModelUsers();
$profiles = $m_users->getApplicantProfiles();

$groups = [];
foreach ($profiles as $profile)
{
    $menu_list = $h_menu->buildMenuQuery($profile->id);
    foreach ($menu_list as $m)
    {
        $formGroups = $fabrikRepository->getGroupsByFormId($m->form_id);
        if(!empty($formGroups))
        {
            $formGroupsObject = [
                'formLabel' => Text::_($m->label),
                'groups' => []
            ];

            foreach ($formGroups as $group)
            {
                $params = json_decode($group->getParamsRaw());
                if($group->isPublished() && in_array($params->repeat_group_show_first, [1, 3]))
                {
                    $formGroupsObject['groups'][] = [
                        'id'    => $group->getId(),
                        'label' => Text::_($group->getLabel()) !== '' ? Text::_($group->getLabel()) : (Text::_('COM_EMUNDUS_FORM_BUILDER_UNNAMED_SECTION') . ' [' . Text::_($m->label) . ']')
                    ];
                }
            }
            
            if(!empty($formGroupsObject['groups']))
            {
                $groups[] = $formGroupsObject;
            }
        }
    }
}
$data['groups'] = $groups;
?>

<div id="em-component-vue"
     component="Groups/GroupForm"
     data="<?= htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8'); ?>">
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $data['hash'] ?>"></script>
