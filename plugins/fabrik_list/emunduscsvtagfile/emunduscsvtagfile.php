<?php
/**
 * Allow processing of CSV import / export on a per row basis
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.listcsv
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-list.php';

/**
 * Allow processing of CSV import / export on a per row basis
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.listcsv
 * @since       3.0
 */

class PlgFabrik_ListEmunduscsvtagfile extends PlgFabrik_List
{
	
	public function onAfterImportCSVRow($args)
	{
		$params = $this->getParams();
		$tag = $params->get('emunduscsvtagfile_tag');
		$fnum_column = $params->get('emunduscsvtagfile_fnumcolumn', 'fnum');

		$listModel = $this->getModel();
		$formModel = $listModel->getFormModel();

		if(!empty($tag) && !empty($formModel->formData) && array_key_exists($fnum_column, $formModel->formData))
		{
			if(!class_exists('EmundusModelFiles'))
			{
				require_once(JPATH_SITE . '/components/com_emundus/models/files.php');
			}

			$mFiles = new EmundusModelFiles();
			$mFiles->tagFile([$formModel->formData['fnum']], [$tag]);
		}

		return true;
	}
}
