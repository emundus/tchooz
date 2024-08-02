<?php
/**
 * Bootstrap Details Template: Repeat group rendered as standard form
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;

$input = Factory::getApplication()->input;
$group = $this->group;
if (!$group->newGroup) :
	$i = 1;
	$w = new FabrikWorker;

	foreach ($group->subgroups as $subgroup) :
		$introData = array_merge($input->getArray(), array('i' => $i));
		?>
		<div class="fabrikSubGroup">
			<div data-role="group-repeat-intro">
				<?php echo $w->parseMessageForPlaceHolder($group->repeatIntro, $introData);?>
			</div>

			<div class="fabrikSubGroupElements">
				<?php

				// Load each group in a <ul>
				$this->elements = $subgroup;
				echo $this->loadTemplate('group');
				?>
			</div><!-- end fabrikSubGroupElements -->
		</div><!-- end fabrikSubGroup -->
		<?php
		$i ++;
	endforeach;
endif;