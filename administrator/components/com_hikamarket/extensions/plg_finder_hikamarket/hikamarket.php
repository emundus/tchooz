<?php
/**
 * @package    HikaMarket for Joomla!
 * @version    5.0.0
 * @author     Obsidev S.A.R.L.
 * @copyright  (C) 2011-2024 OBSIDEV. All rights reserved.
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php
$jversion = preg_replace('#[^0-9\.]#i','',JVERSION);
if(version_compare($jversion,'5.0.0','>=')) {
	include_once(__DIR__.'/hikamarket_j5.php');
} elseif(version_compare($jversion,'4.0.0','>=')) {
	include_once(__DIR__.'/hikamarket_j4.php');
} else {
	include_once(__DIR__.'/hikamarket_j3.php');
}

class plgFinderHikamarket extends plgFinderHikamarketBridge
{
	protected $context = 'Vendor';
	protected $extension = 'com_hikamarket';
	protected $layout = 'vendor';
	protected $type_title = 'Vendor';
	protected $table = '#__hikamarket_vendor';
	protected $state_field = 'vendor_published';
	protected $item = null;
	protected $elementBeingDeleted = null;

	protected function handleOtherLanguages(&$item)
	{
		return false;
	}

	public function _onFinderGarbageCollection()
	{
		$db      = $this->db;
		$type_id = $this->getTypeId();

		$query    = $db->getQuery(true);
		$subquery = $db->getQuery(true);
		$subquery->select('CONCAT(' . $db->quote($this->getUrl('%', $this->extension, $this->layout)) . ', vendor_id)')
			->from($db->quoteName($this->table));
		$query->select($db->quoteName('l.link_id'))
			->from($db->quoteName('#__finder_links', 'l'))
			->where($db->quoteName('l.type_id') . ' = ' . $type_id)
			->where($db->quoteName('l.url') . ' LIKE ' . $db->quote($this->getUrl('%', $this->extension, $this->layout)))
			->where($db->quoteName('l.url') . ' NOT IN (' . $subquery . ')');
		$db->setQuery($query);
		$items = $db->loadColumn();

		foreach ($items as $item) {
			$this->indexer->remove($item);
		}

		return count($items);
	}

	public function _onFinderCategoryChangeState($extension, $pks, $value)
	{
		if ($extension == 'com_hikamarket')
		{
			$this->categoryStateChange($pks, $value);
		}
	}

	public function _onFinderAfterDelete($context, $table)
	{
		$this->elementBeingDeleted = $table;
		if ($context == 'com_hikamarket.vendor' && !empty($table->vendor_id))
		{
			$id = $table->vendor_id;
		}
		else if ($context == 'com_finder.index' && !empty($table->link_id))
		{
			$id = $table->link_id;
		}
		else
		{
			return true;
		}
		$result = $this->remove($id);
		$this->elementBeingDeleted = null;
		return $result;
	}

	public function _onFinderAfterSave($context, $row, $isNew)
	{
		if ($context == 'com_hikamarket.vendor' && !is_null($row))
		{
			if(isset($row->old->vendor_published) && isset($row->vendor_published) && $row->old->vendor_published != $row->vendor_published) {
				$this->itemStateChange(array($row->vendor_id), $row->vendor_published);
			}
			$this->reindex($row->vendor_id);
		}

		return true;
	}

	public function _onFinderBeforeSave($context, $row, $isNew)
	{
		return true;
	}

	public function _onFinderChangeState($context, $pks, $value)
	{
		if ($context == 'com_hikamarket.vendor')
		{
			$this->itemStateChange($pks, $value);
		}
		if ($context == 'com_plugins.plugin' && $value === 0)
		{
			$this->pluginDisable($pks);
		}
	}

	protected function setup()
	{
		$this->_setup();
		return true;
	}

	protected function _setup()
	{
		if(!defined('DS'))
			define('DS', DIRECTORY_SEPARATOR);
		include_once(rtrim(JPATH_ADMINISTRATOR,DS).DS.'components'.DS.'com_hikamarket'.DS.'helpers'.DS.'helper.php');
	}

	protected function getUrl($id, $extension, $view)
	{
		$url = 'index.php?option=' . $extension . '&ctrl=' . $view . '&task=show&cid=';
		if(empty($id))
			return $url;

		if($id === '%') {
			$url .= $id;
			return $url;
		}

		if(!is_numeric($id))
			return $url;


		$this->_setup();
		$vendorClass = hikamarket::get('class.vendor');

		if(!empty($this->elementBeingDeleted) && $this->elementBeingDeleted->vendor_id == $id) {
			$item = $this->elementBeingDeleted;
		} else {
			$item = $vendorClass->get($id);
		}

		if(empty($item)) {
			$url .= $id;
			return $url;
		}

		if(empty($item->vendor_alias))
			$vendorClass->addAlias($item);
		else
			$item->alias = $item->vendor_alias;

		$extra = $this->_getElementMenuItem($item);
		if(!empty($this->elementBeingDeleted->currentLanguage)) {
			$extra .= $this->elementBeingDeleted->currentLanguage;
		}
		$url .= $id ."&name=".$item->alias. $extra;
		return $url;
	}

	protected function _getElementMenuItem(&$item)
	{
		if(empty($extra)) {
			$menusClass = hikamarket::get('class.menus');
			$itemid = $menusClass->getPublicMenuItemId();
			if($itemid)
				$extra = '&Itemid='.$itemid;
			else
				$extra = '';
		}
		return $extra;
	}

	protected function getListQuery($query = null)
	{
		$db = JFactory::getDbo();
		$query = $query instanceof JDatabaseQuery ? $query : $db->getQuery(true)
			->select('v.*')
			->select('v.vendor_id AS id, v.vendor_name AS title, v.vendor_alias AS alias, "" AS link, v.vendor_description AS summary')
			->select('"" AS metakey, "" AS metadesc, "" AS metadata')
			->select('"" AS created_by_alias, v.vendor_modified AS modified, "" AS modified_by')
			->select($this->getStateColumn().' AS state, v.vendor_created AS start_date, 1 AS access');

		$case_when_item_alias = ' CASE WHEN v.vendor_alias != "" THEN v.vendor_alias ELSE v.vendor_name END as slug';
		$query->select($case_when_item_alias);

		$query->from('#__hikamarket_vendor AS v');

		return $query;
	}

	protected function getItem($id)
	{
		$query = $this->getListQuery();
		$query->where('v.vendor_id = ' . (int) $id);

		$this->db->setQuery($query);
		$row = $this->db->loadAssoc();

		if(empty($row))
			$row = array();

		$item = $this->toObject($row);

		$item->type_id = $this->type_id;

		$item->layout = $this->layout;

		return $item;
	}

	protected function categoryStateChange($pks, $value) {}

	protected function checkItemAccess($row)
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('access'))
			->from($this->db->quoteName($this->table))
			->where($this->db->quoteName('vendor_id') . ' = ' . (int) $row->id);
		$this->db->setQuery($query);

		$this->old_access = $this->db->loadResult();
	}

	protected function itemStateChange($pks, $value)
	{
		foreach ($pks as $pk)
		{
			$query = clone $this->getStateQuery();
			$query->where('v.vendor_id = ' . (int) $pk);

			$this->db->setQuery($query);
			$item = $this->db->loadObject();

			$temp = $this->translateState($value, $item->cat_state);

			$this->change($pk, 'state', $temp);

			$this->reindex($pk);
		}
	}

	protected function getUpdateQueryByTime($time)
	{
		$query = $this->db->getQuery(true)
			->where('v.vendor_modified >= ' . $this->db->quote($time));

		return $query;
	}

	protected function getUpdateQueryByIds($ids)
	{
		$query = $this->db->getQuery(true)
			->where('v.vendor_id IN(' . implode(',', $ids) . ')');

		return $query;
	}

	protected function getStateQuery()
	{
		$query = $this->db->getQuery(true);

		$query->select('v.*, v.vendor_id AS id');

		$query->select($this->getStateColumn().' AS state');
		$query->select('1 AS access')
			->from($this->table . ' AS v');

		return $query;
	}

	protected function getStateColumn()
	{
		return 'v.vendor_published';
	}
}
