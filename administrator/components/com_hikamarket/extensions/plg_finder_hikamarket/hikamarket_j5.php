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
use Joomla\Component\Finder\Administrator\Indexer\Helper;
use Joomla\Component\Finder\Administrator\Indexer\Indexer;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Registry\Registry;
use Joomla\Component\Finder\Administrator\Indexer\Adapter;
use Joomla\Component\Finder\Administrator\Indexer\Result;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Event\Finder as FinderEvent;
jimport('joomla.application.component.helper');

abstract class plgFinderHikamarketBridge extends Adapter
{

	public $resultClass = 'Joomla\Component\Finder\Administrator\Indexer\Result';

	public function __construct(&$subject, $config)
	{
		if(!isset($this->params)) {
			$plugin = PluginHelper::getPlugin('finder', 'hikamarket');
			$this->params = new Registry(@$plugin->params);
		}

		$this->setup();
		parent::__construct($subject, $config);
	}

	public static function getSubscribedEvents(): array
	{
		return array_merge([
			'onFinderGarbageCollection' => 'onFinderGarbageCollection',
			'onFinderCategoryChangeState' => 'onFinderCategoryChangeState',
			'onFinderAfterDelete' => 'onFinderAfterDelete',
			'onFinderBeforeSave' => 'onFinderBeforeSave',
			'onFinderAfterSave' => 'onFinderAfterSave',
			'onFinderChangeState' => 'onFinderChangeState',
		], parent::getSubscribedEvents());
    }

	public function onFinderGarbageCollection() {
		return $this->_onFinderGarbageCollection();
	}
	public function onFinderCategoryChangeState(FinderEvent\AfterCategoryChangeStateEvent $event) {
		$this->_onFinderCategoryChangeState($event->getExtension(), $event->getPks(), $event->getValue());
	}
	public function onFinderAfterDelete(FinderEvent\AfterDeleteEvent $event) {
		return $this->_onFinderAfterDelete($event->getContext(), $event->getItem());
	}
	public function onFinderAfterSave(FinderEvent\AfterSaveEvent $event) {
		return $this->_onFinderAfterSave($event->getContext(), $event->getItem(), $event->getIsNew());
	}
	public function onFinderBeforeSave(FinderEvent\BeforeSaveEvent $event) {
		return $this->_onFinderBeforeSave($event->getContext(), $event->getItem(), $event->getIsNew());
	}
	public function onFinderChangeState(FinderEvent\AfterChangeStateEvent $event) {
		$this->_onFinderChangeState($event->getContext(), $event->getPks(), $event->getValue());
	}

	protected function index(Joomla\Component\Finder\Administrator\Indexer\Result $item)
	{
		if (ComponentHelper::isEnabled($this->extension) == false)
		{
			return;
		}

		$registry = new Registry;
		if(!empty($item->params))
			$registry->loadString($item->params);
		$item->params = ComponentHelper::getParams('com_hikamarket', true);
		$item->params->merge($registry);

		$registry = new Registry;
		$registry->loadString($item->metadata);
		$item->metadata = $registry;

		$vendorClass = hikamarket::get('class.vendor');
		$data = $vendorClass->get($item->id);
		if(!empty($data->vendor_image)) {
			$item->images = array($data->vendor_image);
		}

		$item->summary = Helper::prepareContent($item->summary, $item->params);
		$item->body    = Helper::prepareContent($item->body, $item->params);

		$this->addAlias($item);

		$item->url   = $this->getUrl($item->id, 'com_hikamarket','vendor');
		$item->route = $this->getUrl($item->id, 'com_hikamarket','vendor');

		$title = $this->getItemMenuTitle($item->url);

		if (!empty($title) && $this->params->get('use_menu_title', true))
		{
			$item->title = $title;
		}

		$item->metaauthor = $item->metadata->get('author');


		if(!empty($item->images) && count($item->images)) {
			$keys = array_keys($item->images);
			$image = $item->images[$keys[0]];
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
		$fields = $this->params->get('fields');
		if(!is_array($fields)){
			$fields = explode(',',(string)$fields);
		}
		if(!empty($fields) && count($fields)) {
			foreach($fields as $field) {
				if(!in_array($field, array('vendor_name', 'vendor_description')))
					$item->addInstruction(Indexer::TEXT_CONTEXT, $field);
			}
		}

		$this->item = $item;

		$item->addTaxonomy('Type', 'Vendor');



		$item->addTaxonomy('Language', 		$item->language);

		Helper::getContentExtras($item);

		if(!$this->handleOtherLanguages($item)) {
			$this->indexer->index($item);
		}
	}

	public function prepareContent($summary, $params)
	{
		return Helper::prepareContent($summary, $params);
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
		}elseif(method_exists('Joomla\CMS\Filter\OutputFilter','stringURLUnicodeSlug')){
			$element->alias = Joomla\CMS\Filter\OutputFilter::stringURLUnicodeSlug($element->alias);
		}else{
			$element->alias = Joomla\CMS\Filter\OutputFilter::stringURLSafe($element->alias);
		}
	}

	public function toObject($row)
	{
		if(HIKASHOP_J40) {
			$item = Joomla\Utilities\ArrayHelper::toObject($row, 'Joomla\Component\Finder\Administrator\Indexer\Result');
		}elseif(HIKASHOP_J30) {
			$item = Joomla\Utilities\ArrayHelper::toObject($row, 'FinderIndexerResult');
		} else {
			$item = ArrayHelper::toObject((array) $row, 'FinderIndexerResult');
		}

		return $item;
	}
}
