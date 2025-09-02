<?php


/**
 * Execute PHP Code on any list event
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.phpevents
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\ArrayHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\Registry\Registry;

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-list.php';

/**
 * Execute PHP Code on any list event
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.phpevents
 * @since       3.0
 */
class PlgFabrik_ListHideanonymdata extends PlgFabrik_List
{
	// TODO: put this result in cache against the user id
	// so we don't keep querying the db for each row
	// when showing a list of records
	// i.e. $this->users_anonym_status[$user_id] = true/
	private array $users_anonym_status = [];


	public function onShowInList(&$args)
	{
		$this->hideAnonymAccountsData($args);
	}

	private function hideAnonymAccountsData(array $args)
	{
		$params = $this->getParams();
		$fields_to_hide = $params->get('fields_to_hide');
		$fields_to_hide = explode(',', $fields_to_hide);
		$user_identifier_field_name = $this->getIdentifierFieldName($params);

		$model = $this->getModel();
		$table         = $model->getTable();
		$db_table_name = $table->db_table_name;
		$data = $model->getData();

		if (!empty($data) && !empty($fields_to_hide) && !empty($user_identifier_field_name))
		{
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			foreach ($data as $key => $row)
			{
				$user_id = $row->{$table->db_table_name . '___' . $user_identifier_field_name . '_raw'};

				if (!empty($user_id)) {
					$is_anonym = $this->getUserAnonymStatus($user_id);
					if ($is_anonym)
					{
						foreach ($fields_to_hide as $field_to_hide)
						{
							$row->{$db_table_name . '___' . $field_to_hide} = Text::_('COM_EMUNDUS_ANONYM_DATA_HIDDEN');
						}

						$data[$key] = $row;
					}
				}
			}
		}
	}

	public function onCanView($row): bool
	{
		$canView = true;
		// If $row is null, we were called from the list's canEdit() in a per-table rather than per-row context,
		// and we don't have an opinion on per-table view permissions, so just return true.
		if (is_null($row) || is_null($row[0]))
		{
			$this->result = true;
			return true;
		}

		if (is_array($row[0]))
		{
			$data = ArrayHelper::toObject($row[0]);
		}
		else
		{
			$data = $row[0];
		}

		if (empty($data->__pk_val))
		{
			$this->result = true;
			return true;
		}

		$user_identifier_field_name = $this->getIdentifierFieldName($this->getParams());
		if (!empty($user_identifier_field_name))
		{
			$table = $this->getModel()->getTable();
			$user_id = $data->{$table->db_table_name . '___' . $user_identifier_field_name . '_raw'};

			if (!empty($user_id))
			{
				$canView = !$this->getUserAnonymStatus($user_id);
			}
		}

		return $canView;
	}

	private function getUserAnonymStatus($user_id): bool
	{
		$is_anonym = false;

		if (array_key_exists($user_id, $this->users_anonym_status))
		{
			$is_anonym = $this->users_anonym_status[$user_id];
		} else {
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$query->select('is_anonym')
				->from('#__emundus_users')
				->where('user_id = ' . $user_id);

			try {
				$db->setQuery($query);
				$is_anonym = $db->loadResult() == 1;

				$this->users_anonym_status[$user_id] = $is_anonym;
			} catch (Exception $e)
			{
				Log::add('Failed to get user anonym status: ' . $e->getMessage(), Log::ERROR, 'com_emundus');
			}
		}

		return $is_anonym;
	}

	private function getIdentifierFieldName($params): string
	{
		$field_name = '';
		if (empty($params)) {
			$this->getParams();
		}
		$user_identifier_field = $params->get('user_id_field');

		if (!empty($user_identifier_field))
		{
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$query->select('name')
				->from('#__fabrik_elements')
				->where('id = ' . $user_identifier_field);
			$db->setQuery($query);
			$field_name = $db->loadResult();
		}

		return $field_name;
	}
}