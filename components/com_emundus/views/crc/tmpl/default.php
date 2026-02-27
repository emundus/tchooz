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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Factories\LayoutFactory;
use Tchooz\Repositories\Actions\ActionRepository;

require_once(JPATH_ROOT . '/components/com_emundus/helpers/access.php');

Text::script('COM_EMUNDUS_ONBOARD_MODIFY');
Text::script('COM_EMUNDUS_ONBOARD_CRC');
Text::script('COM_EMUNDUS_ONBOARD_CRC_INTRO');
Text::script('COM_EMUNDUS_ONBOARD_LABEL_CONTACTS');
Text::script('COM_EMUNDUS_ONBOARD_CRC_INTRO');
Text::script('COM_EMUNDUS_ONBOARD_CRC_CONTACTS');
Text::script('COM_EMUNDUS_ONBOARD_NOCONTACTS');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT');
Text::script('COM_EMUNDUS_ONBOARD_CRC_CONTACT_EMAIL');
Text::script('COM_EMUNDUS_ONBOARD_CRC_CONTACT_PHONENUMBER');
Text::script('COM_EMUNDUS_ONBOARD_CRC_CONTACT_STATUS');
Text::script('COM_EMUNDUS_ONBOARD_CONTACT_DELETE');
Text::script('COM_EMUNDUS_ONBOARD_ORGANIZATIONS');
Text::script('COM_EMUNDUS_ONBOARD_NOORGANIZATIONS');
Text::script('COM_EMUNDUS_ONBOARD_CONTACT_FILTER_PUBLISH');
Text::script('COM_EMUNDUS_ONBOARD_CONTACT_FILTER_NAME');
Text::script('COM_EMUNDUS_ONBOARD_CONTACT_FILTER_PHONE_NUMBER');
Text::script('COM_EMUNDUS_ONBOARD_CONTACT_FILTER_NO_PHONE_NUMBER');
Text::script('COM_EMUNDUS_ONBOARD_CONTACT_FILTER_ORGANIZATION');
Text::script('COM_EMUNDUS_ONBOARD_ORG_FILTER_NO_ORGANIZATION');
Text::script('COM_EMUNDUS_ONBOARD_CONTACT_FILTER_NATIONALITY');
Text::script('COM_EMUNDUS_ONBOARD_CONTACT_FILTER_NO_NATIONALITY');
Text::script('COM_EMUNDUS_SETTINGS_INTEGRATION_COUNTRIES_LIST');
Text::script('COM_EMUNDUS_ONBOARD_FILTER_ALL');
Text::script('COM_EMUNDUS_ONBOARD_FILTER_PUBLISH');
Text::script('COM_EMUNDUS_ONBOARD_FILTER_UNPUBLISH');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_UNPUBLISH');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_PUBLISH');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_DUPLICATE');
Text::script('COM_EMUNDUS_ONBOARD_ADD_ORGANIZATION');
Text::script('COM_EMUNDUS_ONBOARD_ORG_DELETE');
Text::script('COM_EMUNDUS_ONBOARD_ORGS_ASSOCIATED');
Text::script('COM_EMUNDUS_ONBOARD_ORGS_ASSOCIATED_TITLE');
Text::script('COM_EMUNDUS_ONBOARD_ORGS_ASSOCIATED_NOT');
Text::script('COM_EMUNDUS_ONBOARD_ORG_FILTER_NAME');
Text::script('COM_EMUNDUS_ONBOARD_ORG_FILTER_IDENTIFIER');
Text::script('COM_EMUNDUS_ONBOARD_ORG_FILTER_NO_IDENTIFIER_CODE');
Text::script('COM_EMUNDUS_ONBOARD_CONTACTS_EXPORTS_EXCEL');
Text::script('COM_EMUNDUS_ONBOARD_CONTACTS_PUBLISHED');
Text::script('COM_EMUNDUS_ONBOARD_CONTACTS_UNPUBLISHED');
Text::script('COM_EMUNDUS_ONBOARD_CONTACTS_STATUS');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_CRC_SHOW_DETAILS');
Text::script('COM_EMUNDUS_ONBOARD_ADRESSE');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_DEPARTMENT');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_ROLE');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_FILES');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_FILES_CONSULT');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_ORGANIZATIONS');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_GENDER');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_BIRTHDATE');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_COUNTRIES');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_AGE');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_BIRTH');
Text::script('COM_EMUNDUS_ONBOARD_ADD_ORG_DESCRIPTION');
Text::script('COM_EMUNDUS_ONBOARD_ADD_ORG_CONTACT');
Text::script('COM_EMUNDUS_ONBOARD_ADD_ORG_OTHER_CONTACT');
Text::script('COM_EMUNDUS_REGISTRANTS_FILE_READY');
Text::script('COM_EMUNDUS_URL_UNVERIFIED_AND_UNSECURED');

$data = LayoutFactory::prepareVueData();

$user = Factory::getApplication()->getIdentity();

$actionRepository = new ActionRepository();
$contactAction    = $actionRepository->getByName('contact');
$orgAction        = $actionRepository->getByName('organization');

$data['crud'] = [
    'contact'      => [
        'c' => $data['coordinator_access'] || $data['sysadmin_access'] || EmundusHelperAccess::asAccessAction($contactAction->getId(), 'c', $user->id),
        'r' => $data['coordinator_access'] || $data['sysadmin_access'] || EmundusHelperAccess::asAccessAction($contactAction->getId(), 'r', $user->id),
        'u' => $data['coordinator_access'] || $data['sysadmin_access'] || EmundusHelperAccess::asAccessAction($contactAction->getId(), 'u', $user->id),
        'd' => $data['coordinator_access'] || $data['sysadmin_access'] || EmundusHelperAccess::asAccessAction($contactAction->getId(), 'd', $user->id),
    ],
    'organization' => [
        'c' => $data['coordinator_access'] || $data['sysadmin_access'] || EmundusHelperAccess::asAccessAction($orgAction->getId(), 'c', $user->id),
        'r' => $data['coordinator_access'] || $data['sysadmin_access'] || EmundusHelperAccess::asAccessAction($orgAction->getId(), 'r', $user->id),
        'u' => $data['coordinator_access'] || $data['sysadmin_access'] || EmundusHelperAccess::asAccessAction($orgAction->getId(), 'u', $user->id),
        'd' => $data['coordinator_access'] || $data['sysadmin_access'] || EmundusHelperAccess::asAccessAction($orgAction->getId(), 'd', $user->id),
    ],
];

$datas = [
    'shortLang'       => $data['short_lang'],
    'currentLanguage' => $data['current_lang'],
    'defaultLang'     => $data['default_lang'],
    'manyLanguages'   => $data['many_languages'],
    'crud'            => $data['crud'],
];
?>

<div id="em-component-vue"
     component="Crc"
     data="<?= htmlspecialchars(json_encode($datas), ENT_QUOTES, 'UTF-8'); ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $data['hash'] ?>"></script>
