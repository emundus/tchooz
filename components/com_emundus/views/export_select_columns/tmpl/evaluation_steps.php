<?php

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

$app = Factory::getApplication();
$document = $app->getDocument();
$document->addStyleSheet('media/com_emundus/css/emundus_export_select_columns.css');

function loadPanelHeading($id, $label, $type = 'tbl') {
	$inputId = 'emundus_checkall_' . $type . '_' . $id;

	$html = '<div class="panel panel-default tw-flex tw-flex-row">';
	$html .= '<input type="checkbox" id="' . $inputId .'" data-check=".emundusgroup_'. $id . '" class="tw-cursor-pointer">';
	$html .= '<label for="' . $inputId . '" class="!tw-mb-0 tw-cursor-pointer">' . Text::_($label) . '</label>';
	$html .= '</div>';

	return $html;
}

$html_steps = '';

if (!empty($this->elements)) {
	$current_step = 0;
	$current_table = 0;
	$current_group = 0;

	foreach ($this->elements as $element) {
		if ($element->step_id != $current_step) {
			if ($current_group != 0) {
				// close the group
				$html_steps .= '</div></div>';
			}

			if ($current_table != 0) {
				// close the previous table
				$html_steps .= '</div></div>';
			}

			if ($current_step != 0) {
				// close the previous step
				$html_steps .= '</div></div></div></div>';
			}

			$current_group = 0;
			$current_form = 0;
			$current_step = $element->step_id;
			$html_steps .= '<div class="tw-flex tw-flex-row tw-cursor-pointer tw-mt-2" id="showelements_' . $current_step .'">
				<span title="Show step elements" id="showelements_'. $current_step .'_icon" class="material-symbols-outlined tw-mr-2 tw-cursor-pointer" style="transform: rotate(-90deg);">expand_more</span>
				<h5 class="tw-cursor-pointer">' . $element->step_label . '</h5>
			</div>';

			$html_steps .= '<div id="felts' . $current_step . '" class="tw-p-2" style="display: none;" data-step-id="' . $current_step .'">
				<div class="tw-flex tw-flex-row tw-mb-4">
					<input type="checkbox" id="emundus_checkall'. $current_step .'" class="emundusall tw-cursor-pointer" data-check=".emunduspage">
					<label for="emundus_checkall' . $current_step . '" class="tw-cursor-pointer" style="margin-bottom: 0">Select all elements</label>
				</div>
				<div id="emundus_elements">
				';
		}

		if ($element->table_id !== $current_form) {
			if ($current_group != 0) {
				// close the group
				$html_steps .= '</div></div>';
			}

			if ($current_form != 0) {
				// close the previous form
				$html_steps .= '</div></div>';
			}

			$current_form = $element->table_id;
			$current_group = 0;
			$html_steps .= '<div id="emundus_table_' . $element->table_id . '" class="em-white-bg tw-p-2 tw-rounded tw-my-2">';
			$html_steps .= loadPanelHeading($element->table_id, $element->table_label, 'tbl');
			$html_steps .= '<div class="panel-body">';
		}

		if ($element->group_id !== $current_group) {
			if ($current_group !== 0) {
				// close the group
				$html_steps .= '</div></div>';
			}

			$current_group = $element->group_id;
			$html_steps .= '<div id="emundus_grp_' . $element->group_id . '"  class="em-white-bg tw-p-2 tw-rounded tw-my-2">';
			$html_steps .= loadPanelHeading($element->group_id, $element->group_label, 'grp');
			$html_steps .= '<div class="panel-body">';
		}

		$html_steps .= '<div class="tw-flex-row">
				<input name="ud[]" type="checkbox" id="emundus_elm_step_' . $element->step_id .'_' . $element->id . '" class="emundusitem_' .  $element->group_id . ' tw-cursor-pointer" value="' .  $element->id . '" data-step-id="' . $element->step_id . '">
				<label for="emundus_elm_step_' . $element->step_id .'_' . $element->id . '" class="tw-cursor-pointer">' . Text::_($element->element_label) . '</label>
			</div>';
	}

	// close last group
	$html_steps .= '</div></div>';
	// and form
	$html_steps .= '</div></div>';
	// close last step
	$html_steps .= '</div></div></div></div>';
} else {
	$html_steps = '<div class="alert alert-info">' . Text::_('COM_EMUNDUS_EXPORT_SELECT_COLUMNS_NO_ELEMENTS') . '</div>';
}

echo $html_steps;

?>