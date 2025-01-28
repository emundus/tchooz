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

$form = $this->form;
if ($this->hasActions) : ?>
<div class="fabrikActions form-actions <?= $this->is_iframe ? 'tw-mt-4' : '' ?>">
	<div class="row-fluid">
		<div class="<?php echo FabrikHelperHTML::getGridSpan('12'); ?>">
			<div class="btn-group">
				<?php echo $form->prevButton . ' ' . $form->nextButton;
				?>
			</div>
		</div>
	</div>
</div>
<?php
endif;