<?php
/**
 * Bootstrap Form Template - Group
 *
 * @package     Joomla
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.1
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

$layout          = FabrikHelperHTML::getLayout('form.fabrik-control-group', [JPATH_SITE . '/components/com_fabrik/views/form/tmpl/emundus/layouts/']);
$rowStarted      = 0;

$model     = $this->getModel();
$element_ids = $model->getElementIds();
foreach ($this->elements as $element) :
	$element->element_fabrik_id = $element_ids[$this->index_element_id];
	$this->index_element_id++;
	$this->element = $element;
	$this->class = 'fabrikErrorMessage';

	// Don't display hidden element's as otherwise they wreck multi-column layouts
	if (trim($element->error) !== '') :
		$element->error = $element->error;
		$element->containerClass .= ' error';
		$this->class .= ' help-inline text-danger';
	endif;
	$rowStarted = $rowStarted + (int)$element->startRow - (int)$element->endRow; //see getLayout('form.fabrik-control-group')

	$displayData = array(
		'class' => $element->containerClass . ($element->hidden ? ' d-none' : ''),
		'startRow' => $element->startRow,
		'endRow' => $element->endRow,
		'column' => $element->column,
		'element' => $element,
		'display_comments' => $this->display_comments,
	);

	$labelsAbove = $element->labels;

	if ($labelsAbove == 1)
	{
		$displayData['row'] = $this->loadTemplate('group_labels_above');
	}
	elseif ($labelsAbove == 2)
	{
		$displayData['row'] = $this->loadTemplate('group_labels_none');
	}
	elseif ($labelsAbove == 0)
	{
		$displayData['row'] = $this->loadTemplate('group_labels_side');
	}
	else
	{
		// Multi columns - best to use simplified layout with labels above field
		$displayData['row'] = $this->loadTemplate('group_labels_above');
	}

	echo $layout->render((object) $displayData);

	?><?php
endforeach;

// If the last element was not closing the row add an additional div
if ($rowStarted > 0) :?>
</div><!-- end row for open row -->
<?php endif;?>