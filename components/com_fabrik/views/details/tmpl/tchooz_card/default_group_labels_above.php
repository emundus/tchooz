<?php
/**
 * Bootstrap Details Template
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.1
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$element = $this->element;?>
<div class=" <?php echo $element->containerClass .' '. $element->span; ?>">
	<div class="fabrikLabel">
       <?php
        $icon = '';
        if (preg_match('/material_icons-([a-z_]+)/', $element->containerClass, $matches)) {
          $icon = $matches[1];
	        echo '<span class="material-symbols-outlined em-mr-8">'. $icon . '</span>'. $element->label_raw;
        } else {
	      echo $element->label_raw;
        }
        ?>
	</div>

	<?php if ($this->tipLocation == 'above') : ?>
		<span class=""><?php echo $element->tipAbove ?></span>
	<?php endif ?>

	<div class="fabrikElement">
		<?php echo $element->element;?>
	</div>

	<?php if ($this->tipLocation == 'side') : ?>
		<span class=""><?php echo $element->tipSide ?></span>
	<?php endif ?>

	<?php if ($this->tipLocation == 'below') :?>
		<span class=""><?php echo $element->tipBelow ?></span>
	<?php endif ?>
</div><!-- end control-group -->


