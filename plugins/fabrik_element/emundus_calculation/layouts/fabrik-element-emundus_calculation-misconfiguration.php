<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

defined('JPATH_BASE') or die;

$d   = $displayData;
$app = Factory::getApplication();

if (!class_exists('EmundusHelperAccess'))
{
	require_once(JPATH_ROOT . '/components/com_emundus/helpers/access.php');
}

if (EmundusHelperAccess::asPartnerAccessLevel($app->getIdentity()->id))
{
	?>
    <div class="tw-flex tw-flex-row tw-w-full tw-items-start tw-gap-2 tw-p-5 tw-rounded-form"
         style="background-color: #fae9e9; border: solid 1px rgba(255, 255, 255, 0);">
        <span class="material-symbols-outlined" style="color: #a60e15">cancel</span>
        <div class="tw-flex tw-flex-col">
            <p class="!tw-mt-0"><?php echo Text::_('PLG_ELEMENT_EMUNDUS_CALCULATION_LAYOUT_MISCONFIGURATION'); ?></p>
            <p class="tw-mt-2"><?php echo $d->errorMessage; ?></p>
        </div>
    </div>
	<?php
}

// add a default value display for every user
?>
<input type="text" class="fabrikinput emundus-calculation !tw-border-none tw-pl-2 tw-pointer-events-none tw-w-fit"
       readonly="readonly" name="<?php echo $d->name; ?>"
       id="<?php echo $d->id ?>" value="<?php echo $d->value; ?>" />