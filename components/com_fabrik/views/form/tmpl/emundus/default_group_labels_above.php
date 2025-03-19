<?php
/**
 * Bootstrap Form Template: Labels Above
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die('Restricted access');

$element = $this->element;

?>
<?php echo $element->label; ?>

<?php if ($this->tipLocation == 'above' && !empty($element->tipAbove)) : ?>
    <span class="fabrikElementTip fabrikElementTipAbove"><?php echo $element->tipAbove ?></span>
<?php endif ?>

<div class="fabrikElement">
	<?php echo $element->element; ?>
</div>

<div class="<?php echo $this->class ?> tw-flex tw-items-start">
    <?php if ($element->error) : ?>
        <span class="material-symbols-outlined tw-mr-1" style="line-height: 18px;font-size: 18px">error</span>
        <?php echo $element->error ?>
    <?php endif; ?>
</div>

<?php if ($this->tipLocation == 'side' && !empty($element->tipSide)) : ?>
    <span class="fabrikElementTip"><?php echo $element->tipSide ?></span>
<?php endif ?>

<?php if ($this->tipLocation == 'below' && !empty($element->tipBelow)) : ?>
    <p class="fabrikElementTip fabrikElementTipBelow"><?php echo $element->tipBelow ?></p>
<?php endif ?>
