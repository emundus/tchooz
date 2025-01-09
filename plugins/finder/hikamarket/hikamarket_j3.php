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
jimport('joomla.application.component.helper');
require_once JPATH_ADMINISTRATOR . '/components/com_finder/helpers/indexer/adapter.php';

abstract class plgFinderHikamarketBridge extends FinderIndexerAdapter
{
	public $resultClass = 'FinderIndexerResult';

	public function __construct(&$subject, $config)
	{
		if(!isset($this->params)) {
			$plugin = JPluginHelper::getPlugin('finder', 'hikashop');
			$this->params = new JRegistry(@$plugin->params);
		}

		$this->setup();
		parent::__construct($subject, $config);
	}

	public function onFinderGarbageCollection() {
		return $this->_onFinderGarbageCollection();
	}
	public function onFinderCategoryChangeState($extension, $pks, $value) {
		$this->_onFinderCategoryChangeState($extension, $pks, $value);
	}
	public function onFinderAfterDelete($context, $table) {
		return $this->_onFinderAfterDelete($context, $table);
	}
	public function onFinderAfterSave($context, $row, $isNew) {
		return $this->_onFinderAfterSave($context, $row, $isNew);
	}
	public function onFinderBeforeSave($context, $row, $isNew) {
		return $this->_onFinderBeforeSave($context, $row, $isNew);
	}
	public function onFinderChangeState($context, $pks, $value) {
		$this->_onFinderChangeState($context, $pks, $value);
	}

	protected function index(FinderIndexerResult $item, $format = 'html')
	{
		if (JComponentHelper::isEnabled($this->extension) == false)
		{
			return;
		}

		$registry = new JRegistry;
		$registry->loadString($item->params);
		$item->params = JComponentHelper::getParams('com_hikamarket', true);
		$item->params->merge($registry);

		$registry = new JRegistry;
		if(!empty($item->params))
			$registry->loadString($item->metadata);
		$item->metadata = $registry;

		$item->summary = FinderIndexerHelper::prepareContent($item->summary, $item->params);
		$item->body    = FinderIndexerHelper::prepareContent($item->body, $item->params);

		$this->addAlias($item);

		$item->url   = $this->getUrl($item->id, 'com_hikamarket','vendor');
		$item->route = $this->getUrl($item->id, 'com_hikamarket','vendor');
		$item->path  = FinderIndexerHelper::getContentPath($item->route);

		$title = $this->getItemMenuTitle($item->url);

		if (!empty($title) && $this->params->get('use_menu_title', true))
		{
			$item->title = $title;
		}

		$item->metaauthor = $item->metadata->get('author');

		$vendorClass = hikamarket::get('class.vendor');
		$data = $vendorClass->get($item->id);
		if(!empty($data->vendor_image)) {
			$image = $data->vendor_image;
		}
		$imageHelper = hikashop_get('helper.image');
		$imageHelper->uploadFolder_url =  rtrim(HIKASHOP_LIVE,'/').'/';
		$append = trim(str_replace(array(JPATH_ROOT, DS), array('', '/'),$imageHelper->uploadFolder), '/');
		if(!empty($append)) {
			$imageHelper->uploadFolder_url .= $append.'/';
		}
		$img = $imageHelper->getThumbnail(@$image->file_path, array('width' => $imageHelper->main_thumbnail_x, 'height' => $imageHelper->main_thumbnail_y), array('default' => true));
		if($img->success) {
			$item->imageUrl = $img->url;
			$item->imageAlt = @$image->file_name;
		}

		$item->addInstruction(FinderIndexer::META_CONTEXT, 'metakey');
		$item->addInstruction(FinderIndexer::META_CONTEXT, 'metadesc');
		$item->addInstruction(FinderIndexer::META_CONTEXT, 'created_by_alias');

		$fields = $this->params->get('fields');
		if(!is_array($fields)){
			$fields = explode(',',(string)$fields);
		}
		if(!empty($fields) && count($fields)) {
			foreach($fields as $field) {
				if(!in_array($field, array('vendor_name', 'vendor_description')))
					$item->addInstruction(FinderIndexer::TEXT_CONTEXT, $field);
			}
		}

		$this->item = $item;

		$item->addTaxonomy('Type', 'Vendor');



		$item->addTaxonomy('Language', 		$item->language);

		FinderIndexerHelper::getContentExtras($item);

		if(!$this->handleOtherLanguages($item)) {
			$this->indexer->index($item);
		}
	}

	public function prepareContent($summary, $params)
	{
		return FinderIndexerHelper::prepareContent($summary, $params);
	}

	protected function addAlias(&$element)
	{
		if(empty($element->alias)){
			if(empty($element->vendor_alias)) {
				if(empty($element->title))
					return;
				$element->alias = strip_tags($element->title);
			} else {
				$element->alias = $element->vendor_alias;
			}
		}

		$config = JFactory::getConfig();
		if(!$config->get('unicodeslugs')){
			$lang = JFactory::getLanguage();
			$element->alias = str_replace(array(',', "'", '"'), array('-', '-', '-'), $lang->transliterate($element->alias));
		}
		$app = JFactory::getApplication();
		if(method_exists($app,'stringURLSafe')){
			$element->alias = $app->stringURLSafe($element->alias);
		}elseif(method_exists('JFilterOutput','stringURLUnicodeSlug')){
			$element->alias = JFilterOutput::stringURLUnicodeSlug($element->alias);
		}else{
			$element->alias = JFilterOutput::stringURLSafe($element->alias);
		}
	}

	public function toObject($row)
	{
		if(HIKASHOP_J30) {
			$item = Joomla\Utilities\ArrayHelper::toObject($row, 'FinderIndexerResult');
		} else {
			$item = ArrayHelper::toObject((array) $row, 'FinderIndexerResult');
		}
		return $item;
	}
}
