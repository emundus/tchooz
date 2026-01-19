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
use Joomla\CMS\Language\Text;
use Tchooz\Factories\LayoutFactory;

defined('_JEXEC') or die('Restricted Access');

Text::script('COM_EMUNDUS_PAGINATION_DISPLAY');

$app = Factory::getApplication();

$menu         = $app->getMenu();
$current_menu = $menu->getActive();

$Itemid            = $app->input->getInt('Itemid', $current_menu->id);
$menu_params       = $menu->getParams($Itemid);
$extension         = $menu_params->get('extension', '');
$extension_item_id = $menu_params->get('extension_item_id', 0);

$data = LayoutFactory::prepareVueData();

if (!empty($extension))
{
    ?>
    <div id="em-component-vue"
         component="History"
         shortLang="<?= $data['short_lang'] ?>"
         currentLanguage="<?= $data['current_lang'] ?>"
         defaultLang="<?= $data['default_lang'] ?>"
         coordinatorAccess="<?= $data['coordinator_access'] ?>"
         sysadminAccess="<?= $data['sysadmin_access'] ?>"
         manyLanguages="<?= $data['many_languages'] ?>"
         extension="<?= $extension ?>"
         itemId="<?= $extension_item_id ?>"
         columns="<?= $this->columns ?>"
         moredata="<?= $this->more_data_columns ?>"
    ></div>

    <script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $data['hash'] ?>"></script>
    <?php
}
else
{
    ?>
    <p><?= Text::_('COM_EMUNDUS_ERROR_NO_EXTENSION'); ?></p>
    <?php
}
?>
