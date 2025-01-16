<?php
/**
 * @package     Joomla
 * @subpackage  com_emundus
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die('Restricted Access');

$app = Factory::getApplication();
$lang         = $app->getLanguage();
$short_lang   = substr($lang->getTag(), 0, 2);
$current_lang = $lang->getTag();
$languages    = LanguageHelper::getLanguages();
if (count($languages) > 1) {
	$many_languages = '1';
	require_once JPATH_SITE . '/components/com_emundus/models/translations.php';
	$m_translations = new EmundusModelTranslations();
	$default_lang   = $m_translations->getDefaultLanguage()->lang_code;
}
else {
	$many_languages = '0';
	$default_lang   = $current_lang;
}

$user               = Factory::getApplication()->getIdentity();
$coordinator_access = EmundusHelperAccess::asCoordinatorAccessLevel($user->id);
$sysadmin_access    = EmundusHelperAccess::isAdministrator($user->id);

require_once(JPATH_ROOT . '/components/com_emundus/helpers/cache.php');
$hash = EmundusHelperCache::getCurrentGitHash();


$menu         = $app->getMenu();
$current_menu = $menu->getActive();

$Itemid      = $app->input->getInt('Itemid', $current_menu->id);
$menu_params = $menu->getParams($Itemid);
$extension = $menu_params->get('extension', '');
$extension_item_id = $menu_params->get('extension_item_id', 0);

if (!empty($extension)) {
?>
    <div id="em-component-vue"
         component="History"
         shortLang="<?= $short_lang ?>"
         currentLanguage="<?= $current_lang ?>"
         defaultLang="<?= $default_lang ?>"
         coordinatorAccess="<?= $coordinator_access ?>"
         sysadminAccess="<?= $sysadmin_access ?>"
         manyLanguages="<?= $many_languages ?>"
         extension="<?= $extension ?>"
         itemId="<?= $extension_item_id ?>"
         columns="<?= $this->columns ?>"
    ></div>

    <script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $hash ?>"></script>
<?php
} else {
    ?>
    <p><?= Text::_('COM_EMUNDUS_ERROR_NO_EXTENSION'); ?></p>
<?php
}
?>
