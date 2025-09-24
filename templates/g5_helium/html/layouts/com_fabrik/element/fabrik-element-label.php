<?php
defined('JPATH_BASE') or die;

use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;

HTMLHelper::_('bootstrap.tooltip','[data-bs-toggle="tooltip"]');

$eMConfig = JComponentHelper::getParams('com_emundus');
$display_required_icon = $eMConfig->get('display_required_icon', 1);
$required_position_icon = $eMConfig->get('required_icon_position', 1);

$d         = $displayData;

$labelText = Text::_($d->label);
$labelText = $labelText == '' ? $d->hasLabel = false : $labelText;

$l = '';
if($display_required_icon == 1 && !empty($labelText)) {
    if($required_position_icon == 0)
    {
        $l .= $d->icons . $labelText;
    } else if($required_position_icon == 1) {
        $l  = $labelText . $d->icons;
    }
} else if($display_required_icon == 0) {
    if($d->tipOpts->heading != 'Validation')
    {
	    $l .= $labelText . '<small class="tw-ml-1 em-text-neutral-600">' . JText::_('COM_FABRIK_OPTIONNAL_FIELD') . '</small>';
    } else {
        $l .= $labelText;
    }
}


$tip = '';
$d->style = "";

if ($d->labelPosition == 0) {
	$d->labelClass .= ' col-sm-2 col-form-label';
	if (Factory::getApplication()->input->getBool('ajax', false) == true) {
		$d->style = " style='width:16.6%;'";
	}
}

if ($d->tipText !== '')
{
	switch ($d->tipOpts->position)
	{
		default;
		case 'left':
			$placement = 'left';
			break;
		case 'top-left':
		case 'top-right':
		case 'top-left':
		case 'top':
			$placement = 'top';
			break;
		case 'right':
		case 'bottom-right':
			$placement = 'right';
			break;
		case 'bottom':
		case 'bottom-left':
			$placement = 'bottom';
			break;

	}
	$heading = isset($d->tipOpts->heading) ? $d->tipOpts->heading : '';
	$tip = ' data-bs-toggle="tooltip" data-bs-html="true" data-bs-trigger="' . $d->tipOpts->trigger . '"  data-bs-placement="' . $placement . '" title="'  . $d->tipText . '"';
}

if ($d->view == 'form' && !($d->canUse || $d->canView))
{
	return '';
}

if ($d->view == 'details' && !$d->canView)
{
	return '';
}

if ($d->canView || $d->canUse)
{
	if ($d->hasLabel && !$d->hidden)
	{
		?>

        <label for="<?php echo $d->id; ?>" class="<?php echo $d->labelClass; ?>" <?php echo $d->style;?>>
		<?php
	}
    elseif (!$d->hasLabel && !$d->hidden)
	{
		?>
        <span class="<?php echo $d->labelClass; ?> faux-label">
		<?php
	}
	?>
	<?php echo $l;?>
	<?php
	if ($d->hasLabel && !$d->hidden)
	{
		?>
        </label>
		<?php
	}
    elseif (!$d->hasLabel && !$d->hidden)
	{
		?>
        </span>
		<?php
	}
}
?>
