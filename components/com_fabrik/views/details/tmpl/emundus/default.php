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
use Joomla\CMS\Factory;

defined('_JEXEC') or die('Restricted access');

$form  = $this->form;
$model = $this->getModel();
require_once (JPATH_SITE.DS.'components'.DS.'com_emundus'.DS.'helpers'.DS.'access.php');

require_once JPATH_SITE . '/components/com_emundus/models/application.php';
$m_application = new EmundusModelApplication();

$app = Factory::getApplication();

$fnum = $app->input->getString('fnum','');
$this->collaborators = $m_application->getSharedFileUsers(null, $fnum);
$this->collaborator = false;
$e_user = $app->getSession()->get('emundusUser', null);
if(!empty($e_user->fnums)) {
	$fnumInfos = $e_user->fnums[$fnum];
	$this->collaborator = $fnumInfos->applicant_id != $e_user->id;
}

if ($this->params->get('show_page_heading', 1)) : ?>
    <div class="componentheading<?php echo $this->params->get('pageclass_sfx') ?>">
		<?php echo $this->escape($this->params->get('page_heading')); ?>
    </div>
<?php
endif;


$this->is_iframe = $app->input->get('iframe', 0);

?>

<?php if($this->params->get('goback_button', 1) == 1) : ?>
    <div class="btn-group">
        <?php
        echo '<div class="tw-text-link-regular tw-cursor-pointer tw-font-semibold tw-flex tw-items-center tw-group tw-mb-4 tw-mt-2 back-button-link"><span class="material-symbols-outlined tw-text-link-regular tw-mr-1">navigate_before</span>';
        echo $form->gobackButton  . ' ' . $this->message;
        echo '</div>';
        ?>
    </div>
<?php endif; ?>

<div id="fabrikDetailsContainer_<?php echo $form->id ?>" <?= $this->is_iframe ? 'class="tw-p-4"' : '' ?>>

	<?php if ($this->params->get('show-title', 1)) : ?>
        <div class="page-header em-mb-12 em-flex-row em-flex-space-between tw-mt-4">
            <h1><?php echo $form->label; ?></h1>
        </div>
	<?php
	endif;

	echo $form->intro;
	if ($this->isMambot) :
		echo '<div class="fabrikForm fabrikDetails fabrikIsMambot" id="' . $form->formid . '">';
	else :
		echo '<div class="fabrikForm fabrikDetails" id="' . $form->formid . '">';
	endif;
	echo $this->plugintop;
	echo $this->loadTemplate('buttons');
	echo $this->loadTemplate('relateddata');
	foreach ($this->groups as $group) :
		$this->group = $group;
		?>

        <div class="em-mt-16 <?php echo $group->class; ?>" id="group<?php echo $group->id; ?>"
             style="<?php echo $group->css; ?>">

			<?php
			if ($group->showLegend) :?>
                <h3 class="legend em-mb-8">
                    <?php echo $group->title; ?>
                </h3>
			<?php endif;

			if (!empty($group->intro)) : ?>
                <div class="groupintro"><?php echo $group->intro ?></div>
			<?php
			endif;

			// Load the group template - this can be :
			//  * default_group.php - standard group non-repeating rendered as an unordered list
			//  * default_repeatgroup.php - repeat group rendered as an unordered list
			//  * default_repeatgroup_table.php - repeat group rendered in a table.

			$this->elements = $group->elements;
			echo $this->loadTemplate($group->tmpl);

			if (!empty($group->outro)) : ?>
                <div class="groupoutro"><?php echo $group->outro ?></div>
			<?php
			endif;
			?>
        </div>
	<?php
	endforeach;

	echo $this->pluginbottom;
	echo $this->loadTemplate('actions');
	echo '</div>';
	echo $form->outro;
	echo $this->pluginend; ?>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.title = "<?php echo $form->label; ?>";
    });
</script>
