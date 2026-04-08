<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

defined('JPATH_BASE') or die;

$d = $displayData;

$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
if ($d->editable)
{
    $wa->registerAndUseScript('imask', Uri::root() . 'plugins/fabrik_element/iban/assets/js/emundus_imask-min.js');
}
?>

<input type="text" class="fabrikinput inputbox" name="<?php echo $d->name; ?>"
       id="<?php echo $d->id ?>" value="<?php echo $d->value; ?>"
    <?= $d->editable ? '' : 'readonly' ?>
/>
