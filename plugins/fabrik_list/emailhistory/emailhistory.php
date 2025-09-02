<?php

defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-list.php';

/**
 * Execute PHP Code on any list event
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.phpevents
 * @since       3.0
 */
class PlgFabrik_ListEmailhistory extends PlgFabrik_List
{
	// used to hide messages that are not emails from table #__messages. Should only be set on fabrik list with db_table_name = #__messages

	/**
	 * On build query where
	 *
	 * @return boolean
	 */
	public function onBuildQueryWhere()
	{
		$params = $this->getParams();

		$model = $this->getModel();
		$table         = $model->getTable();
		$db_table_name = $table->db_table_name;

		if ($db_table_name === 'jos_messages') {
			$model->setPluginQueryWhere('only-emails', "page IS NULL");
		}

		return true;
	}
}
