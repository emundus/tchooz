<?php
/**
 * Bootstrap Form Template: Repeat group rendered as standard form
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
$i = 1;
$w = new FabrikWorker;

$current_user_id = Factory::getApplication()->getIdentity()->id;

foreach ($group->subgroups as $key => $subgroup) :
	$can_edit = true;
	$can_see = true;
	if (!empty($subgroup['user']) && !EmundusHelperAccess::asPartnerAccessLevel($current_user_id) && $this->collaborator) {
		if(!empty($subgroup['user']->element_raw[$i-1]) && $subgroup['user']->element_raw[$i-1] != $current_user_id) {
			$can_edit = false;
			$can_see = false;
		}
	} else if (!$this->collaborator && $this->is_applicant) {
		if(!empty($subgroup['user']->element_raw[$i-1]) && $subgroup['user']->element_raw[$i-1] != $current_user_id) {
			$can_edit = false;
		}
	}

	$introData = array_merge($input->getArray(), array('i' => $i));
    $index = !empty($subgroup['id']->value) ? $subgroup['id']->value : $key;
	?>
    <span class="fabrik-anchor" id="<?php echo 'fabrikSubGroup_'.$index; ?>"></span>
	<div class="fabrikSubGroup <?php if (!$can_edit) : ?> hidden<?php endif; ?>">
        <?php if(!empty($group->repeatIntro)) : ?>
            <div data-role="group-repeat-intro">
                <?php echo $w->parseMessageForPlaceHolder($group->repeatIntro, $introData);?>
            </div>
        <?php endif; ?>
		<div class="fabrikSubGroupElements <?= $this->display_comments ? 'has-comments' : '' ?> em-repeat-card tw-mb-4 <?php if(!$group->showLegend || empty($group->title)) : ?>tw-mt-7<?php endif; ?>">
            <?php if ($group->canDeleteRepeat) : ?>
                <div class="fabrikGroupRepeater">
                    <?php echo $this->removeRepeatGroupButton; ?>
                </div>
            <?php endif; ?>

			<?php

			// Load each group in a <ul>
			$this->elements = $subgroup;
            if ($can_edit)
            {
                echo $this->loadTemplate('group');
            } else {
	            echo $this->loadTemplate('group_details');
            }
			?>
		</div><!-- end fabrikSubGroupElements -->
        <?php
        // Add the add/remove repeat group buttons
        if ($group->editable && ($group->canAddRepeat || $group->canDeleteRepeat)) : ?>
            <div class="fabrikGroupRepeater">
                <?php if ($group->canAddRepeat) :
                    echo $this->addRepeatGroupButton;
                endif; ?>
            </div>
        <?php
        endif;
        ?>
	</div><!-- end fabrikSubGroup -->
	<?php
	$i ++;
endforeach;
