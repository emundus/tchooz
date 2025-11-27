<?php

/**
 * @package         Joomla.Site
 * @subpackage      com_users
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/** @var Joomla\Component\Users\Site\View\Profile\HtmlView $this */

HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');

// Load user_profile and emundus plugin language
$lang = $this->getLanguage();
$lang->load('plg_user_profile', JPATH_ADMINISTRATOR);
$lang->load('com_emundus', JPATH_SITE . '/components/com_emundus');
//

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $this->getDocument()->getWebAssetManager();
$wa->useScript('keepalive')
    ->useScript('form.validate');
$wa->registerAndUseStyle('com_users/profile/edit', 'templates/g5_helium/html/com_users/profile/style/com_users_profile_edit.css', array('version' => 'auto', 'relative' => true));

// Get the back url from the user dropdown module
$user_module = ModuleHelper::getModule('mod_emundus_user_dropdown');
$back_url    = '/';
if (!empty($user_module->id))
{
    $params            = json_decode($user_module->params);
    $link_edit_profile = $params->link_edit_profile;
    $menu              = Factory::getApplication()->getMenu()->getItem($link_edit_profile);
    if (!empty($menu->id))
    {
        $back_url = Route::_($menu->link . '&Itemid=' . $menu->id);
    }
}
//

// Check if the user is using external authentication
$user     = Factory::getApplication()->getIdentity();
$external = empty($user->password);
if (!$external && !empty($user->params))
{
    $user_params = json_decode($user->params);
    if (!empty($user_params->OAuth2) || !empty($user_params->saml))
    {
        $external = true;
    }
}
//

// Override core fields with emundus profile fields
/*$emundusForm       = new Form('emundus', array('control' => 'jform'));
$emundusProfileXml = file_get_contents(JPATH_SITE . '/components/com_emundus/forms/emundus_profile.xml');
if ($emundusProfileXml !== false)
{
    $emundusForm->load($emundusProfileXml);

    foreach ($this->form->getFieldsets() as $group => $fieldset)
    {
        $fields = $this->form->getFieldset($group);
        if (count($fields) > 0)
        {
            foreach ($fields as $field)
            {
                $name = $field->id;
                // Remove jform_ prefix
                $name         = str_replace('jform_', '', $name);
                $emundusField = $emundusForm->getField($name);
                if ($emundusField !== null && $emundusField !== false)
                {
                    $emundusForm->setValue($name, null, $field->value);
                }
            }
        }
    }
}*/
//
?>
<div class="com-users-profile__edit profile-edit">
    <?php if ($this->params->get('show_page_heading')) : ?>
        <div class="page-header">
            <h1>
                <?php echo $this->escape($this->params->get('page_heading')); ?>
            </h1>
        </div>
    <?php endif; ?>

    <form id="member-profile" action="<?php echo Route::_('index.php?option=com_users'); ?>" method="post"
          class="com-users-profile__edit-form form-validate form-horizontal well" enctype="multipart/form-data">

        <div>
            <button type="button"
                    class="tw-text-link-regular tw-cursor-pointer tw-font-semibold tw-flex tw-items-center tw-group"
                    onclick="window.location.href='<?php echo $back_url ?>'">
                <span class="material-symbols-outlined tw-mr-1">navigate_before</span>
                <span class="group-hover:tw-underline"><?php echo Text::_('GO_BACK'); ?></span>
            </button>
        </div>

        <?php if (!$external) : ?>
            <?php foreach ($this->form->getFieldsets() as $group => $fieldset) : ?>
                <?php $fields = $this->form->getFieldset($group); ?>
                <?php if (count($fields)) : ?>
                    <fieldset>
                        <?php if (isset($fieldset->label)) : ?>
                            <h1>
                                <?php echo Text::_($fieldset->label); ?>
                            </h1>
                        <?php endif; ?>
                        <?php if (isset($fieldset->description) && trim($fieldset->description)) : ?>
                            <p>
                                <?php echo $this->escape(Text::_($fieldset->description)); ?>
                            </p>
                        <?php endif; ?>
                        <?php foreach ($fields as $field) : ?>
                            <?php echo $field->renderField(); ?>
                        <?php endforeach; ?>
                    </fieldset>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($this->mfaConfigurationUI) : ?>
            <fieldset class="com-users-profile__multifactor">
                <h2><?php echo Text::_('COM_USERS_PROFILE_MULTIFACTOR_AUTH'); ?></h2>
                <?php echo $this->mfaConfigurationUI ?>
            </fieldset>
        <?php endif; ?>

        <div class="com-users-profile__edit-submit control-group">
            <div class="controls tw-flex !tw-justify-end tw-w-full">
                <input type="hidden" name="option" value="com_users">
                <button type="submit" class="tw-btn-primary validate" name="task" value="profile.save">
                    <?php echo Text::_('JSAVE'); ?>
                </button>
            </div>
        </div>
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</div>
