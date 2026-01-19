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

require_once(JPATH_ROOT . '/components/com_emundus/helpers/access.php');

Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_LASTNAME');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_FIRSTNAME');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_EMAIL');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_PHONENUMBER');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_CREATE');
Text::script('COM_EMUNDUS_ONBOARD_EDIT_CONTACT');
Text::script('COM_EMUNDUS_ONBOARD_SAVE');
Text::script('COM_EMUNDUS_ONBOARD_EDIT_CONTACT_PERSONAL_DETAILS');
Text::script('COM_EMUNDUS_ONBOARD_EDIT_CONTACT_ADDRESSES');
Text::script('COM_EMUNDUS_ONBOARD_EDIT_CONTACT_LINKS');
Text::script('COM_EMUNDUS_ONBOARD_EDIT_CONTACT_SETTINGS');
Text::script('COM_EMUNDUS_ONBOARD_PARAMS_ADD_ADDRESS');
Text::script('COM_EMUNDUS_ONBOARD_PARAMS_ADD_REPEATABLE_ADDRESS');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_ROLE');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_DEPARTMENT');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_FILES');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_GENDER');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_GENDER_WOMAN');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_GENDER_MAN');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_GENDER_OTHER');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_BIRTHDATE');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_COUNTRIES');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_COUNTRIES_PLACEHOLDER');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_ORGANIZATIONS');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_ORGANIZATIONS_PLACEHOLDER');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_PUBLISHED');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_ADDRESS_STREET');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_ADDRESS_EXTENDED');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_ADDRESS_POSTALCODE');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_ADDRESS_LOCALITY');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_ADDRESS_REGION');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_ADDRESS_COUNTRY');
Text::script('COM_EMUNDUS_MULTISELECT_NORESULTS');
Text::script('COM_EMUNDUS_MULTISELECT_NOKEYWORDS');
Text::script('COM_EMUNDUS_MULTISELECT_MAX_COUNTRIES_SELECTED');
Text::script('COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_CHECK_INPUT_MAIL_NO');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_PICTURE');
Text::script('COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_CHECK_INPUT_MAIL');

$data = LayoutFactory::prepareVueData();

$datas = [
	'id' => $this->id ?? 0,
	'shortLang' => $data['short_lang'],
	'currentLanguage' => $data['current_lang'],
	'defaultLang' => $data['default_lang'],
	'manyLanguages' => $data['many_languages'],
];
?>

<div id="em-component-vue"
     component="Contacts/ContactForm"
     data="<?= htmlspecialchars(json_encode($datas), ENT_QUOTES, 'UTF-8'); ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $data['hash'] ?>"></script>
