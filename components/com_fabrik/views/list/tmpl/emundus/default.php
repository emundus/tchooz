<?php
/**
 * Bootstrap List Template - Default
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.1
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;

$pageClass = $this->params->get('pageclass_sfx', '');

if ($pageClass !== '') :
	echo '<div class="' . $pageClass . '">';
endif;

if ($this->tablePicker != '') : ?>
	<div style="text-align:right"><?php echo Text::_('COM_FABRIK_LIST') ?>: <?php echo $this->tablePicker; ?></div>
<?php
endif;

if ($this->params->get('show_page_heading')) :
	echo '<h1>' . $this->params->get('page_heading') . '</h1>';
endif;
?>
<div class="page-header-container">
<?php
if ($this->params->get('show-title')) : ?>
	<div class="page-header em-flex-row em-flex-space-between emundus-list-page-header">
		<h1><?php echo $this->table->label;?></h1>
		<?php if ($this->showAdd) :?>

            <div><a class="addbutton addRecord tw-btn-primary em-w-max-content tw-rounded-coordinator" href="<?php echo $this->addRecordLink;?>">
					<?php echo Text::_($this->addLabel);?>
                </a></div>
		<?php
		endif; ?>
	</div>
<?php
endif;

// Intro outside of form to allow for other lists/forms to be injected.
?>
<div class="page-intro <?php if ($this->showTitle != 1) : ?>em-mt-32<?php endif; ?>">
    <?php echo $this->table->intro; ?>
</div>
</div>
<form class="fabrikForm form-search" action="<?php echo $this->table->action;?>" method="post" id="<?php echo $this->formid;?>" name="fabrikList">

<?php
if ($this->hasButtons):
	echo $this->loadTemplate('buttons');
endif;

if ($this->showFilters && $this->bootShowFilters) :
	echo $this->layoutFilters();
endif;
//for some really ODD reason loading the headings template inside the group
//template causes an error as $this->_path['template'] doesn't contain the correct
// path to this template - go figure!
$headingsHtml = $this->loadTemplate('headings');
echo $this->loadTemplate('tabs');
?>

<div class="fabrikDataContainer em-mt-24">

<?php foreach ($this->pluginBeforeList as $c) :
	echo $c;
endforeach;
?>
	<table class="<?php echo $this->list->class;?>" id="list_<?php echo $this->table->renderid;?>" >
        <colgroup>
            <?php foreach ($this->headings as $key => $heading) : ?>
				<col class="col-<?php echo $key; ?>">
            <?php endforeach; ?>
        </colgroup>
		 <thead><?php echo $headingsHtml?></thead>
		 <tfoot>
			<tr class="fabrik___heading">
				<td colspan="<?php echo count($this->headings);?>">
					<?php echo $this->nav;?>
				</td>
			</tr>
		 </tfoot>
		<?php
		if ($this->isGrouped && empty($this->rows)) :
			?>
			<tbody style="<?php echo $this->emptyStyle?>">
				<tr class="groupDataMsg">
					<td class="emptyDataMessage" style="<?php echo $this->emptyStyle?>" colspan="<?php echo count($this->headings)?>">
						<div class="emptyDataMessage" style="<?php echo $this->emptyStyle?>">
                            <div>
                                <img src="/media/com_emundus/images/tchoozy/complex-illustrations/no-result.svg" alt="empty-list" style="width: 10vw; height: 10vw; margin: 0 auto;">
                                <p class="tw-text-center" style="width: fit-content; margin: 0 auto;">
									<?php echo $this->emptyDataMessage; ?>
									<?php if ($this->showAdd) :?>
                                        <a class="em-font-size-16 em-profile-color em-text-underline tw-w-full tw-block tw-text-center" href="<?php echo $this->addRecordLink;?>"><?php echo Text::_($this->addLabel);?></a>
									<?php endif; ?>
                                </p>
                            </div>
						</div>
					</td>
				</tr>
			</tbody>
			<?php
		endif;
		$gCounter = 0;
		foreach ($this->rows as $groupedBy => $group) :
			if ($this->isGrouped) : ?>
			<tbody>
				<tr class="fabrik_groupheading info">
					<td colspan="<?php echo $this->colCount;?>">
						<?php echo $this->layoutGroupHeading($groupedBy, $group); ?>
					</td>
				</tr>
			</tbody>
			<?php endif ?>
			<tbody class="fabrik_groupdata">
				<tr class="groupDataMsg" style="<?php echo $this->emptyStyle?>">
					<td class="emptyDataMessage" style="<?php echo $this->emptyStyle?>" colspan="<?php echo count($this->headings)?>">
						<div class="emptyDataMessage" style="<?php echo $this->emptyStyle?>">
                            <div>
                                <img src="/media/com_emundus/images/tchoozy/complex-illustrations/no-result.svg" alt="empty-list" style="width: 10vw; height: 10vw; margin: 0 auto;">
                                <p class="tw-text-center" style="width: fit-content; margin: 0 auto;">
                                    <?php echo $this->emptyDataMessage; ?>
                                    <?php if ($this->showAdd) :?>
                                        <a class="em-font-size-16 em-profile-color em-text-underline tw-w-full tw-block tw-text-center" href="<?php echo $this->addRecordLink;?>"><?php echo Text::_($this->addLabel);?></a>
                                    <?php endif; ?>
                                </p>
                            </div>
						</div>
					</td>
				</tr>
			<?php
			foreach ($group as $this->_row) :
				echo $this->loadTemplate('row');
		 	endforeach
		 	?>
		 	</tbody>
			<?php if ($this->hasCalculations) : ?>
			<tfoot>
				<tr class="fabrik_calculations">

				<?php
				foreach ($this->headings as $key => $heading) :
					$h = $this->headingClass[$key];
					$style = empty($h['style']) ? '' : 'style="' . $h['style'] . '"';?>
					<td class="<?php echo $h['class']?>" <?php echo $style?>>
						<?php
						$cal = $this->calculations[$key];
						echo array_key_exists($groupedBy, $cal->grouped) ? $cal->grouped[$groupedBy] : $cal->calc;
						?>
					</td>
				<?php
				endforeach;
				?>

				</tr>
			</tfoot>
			<?php endif ?>
		<?php
		$gCounter++;
		endforeach?>
	</table>
	<?php print_r($this->hiddenFields);?>
</div>
</form>
<?php
echo $this->table->outro;
if ($pageClass !== '') :
	echo '</div>';
endif;
?>

<script>

    /* descendre le titre en fonction de la hauteur de l'intro intro */
    let blocIntro = document.querySelector('.page-header-container');
    let blocBody = document.querySelector('.fabrikForm');

    let hauteurIntro = blocIntro.offsetHeight;
    blocBody.style.marginTop = hauteurIntro + 'px';

    /* descendre le titre en fonction de la hauteur de la bannière alerte */
    let blocBanniere = document.querySelector('.alerte-message-container');
    let hauteurBanniere = blocBanniere.offsetHeight;

    if (blocBanniere) {
        blocIntro.style.marginTop = hauteurBanniere + 'px';
    }

    let croixBanniere = document.querySelector('#close-preprod-alerte-container');

    croixBanniere.addEventListener('click', function () {
        let hauteurBanniereSansPx = parseInt(blocIntro.style.marginTop, 10);
        let hauteurSansBanniere = hauteurBanniereSansPx - hauteurBanniere;
        blocIntro.style.marginTop = hauteurSansBanniere + 'px';
       }
    );

</script>
