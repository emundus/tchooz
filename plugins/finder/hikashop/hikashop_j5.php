<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.0.4
 * @author	hikashop.com
 * @copyright	(C) 2010-2024 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
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

abstract class plgFinderHikashopBridge extends Adapter {

	public $resultClass = 'Joomla\Component\Finder\Administrator\Indexer\Result';

	public function __construct(&$subject, $config) {
		if(!isset($this->params)) {
			$plugin = PluginHelper::getPlugin('finder', 'hikashop');
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
		$item->params = ComponentHelper::getParams('com_hikashop', true);
		$item->params->merge($registry);

		$registry = new Registry;
		$registry->loadString($item->metadata);
		$item->metadata = $registry;

		$productClass = hikashop_get('class.product');
		$data = $productClass->getProduct($item->id);
		if(!empty($data->images) && count($data->images)) {
			$item->images = $data->images;
		}

		if(!empty($item->product_parent_id)) {
			$db = JFactory::getDBO();
			$query = 'SELECT * FROM '.hikashop_table('variant').' AS v '.
				' LEFT JOIN '.hikashop_table('characteristic') .' AS c ON v.variant_characteristic_id = c.characteristic_id '.
				' WHERE v.variant_product_id = '.(int)$item->product_id.' ORDER BY v.ordering';
			$db->setQuery($query);
			$item->characteristics = $db->loadObjectList();
			$parentProduct = $productClass->getProduct((int)$item->product_parent_id);
			$productClass->checkVariant($item, $parentProduct);
			if(empty($item->summary)) {
				$item->summary = $item->product_name;
			}
			if(empty($item->body)) {
				$item->body = $item->product_description;
			}
		}

		$item->summary = Helper::prepareContent($item->summary, $item->params);
		$item->body    = Helper::prepareContent($item->body, $item->params);

		$menusClass = hikashop_get('class.menus');
		$itemid = $menusClass->getPublicMenuItemId();
		$this->addAlias($item);
		$extra = '';
		if(!empty($itemid))
			$extra = '&Itemid='.$itemid;

		$item->url   = "index.php?option=com_hikashop&ctrl=product&task=show&cid=" . $item->id."&name=".$item->alias.$extra;
		$item->route = "index.php?option=com_hikashop&ctrl=product&task=show&cid=" . $item->id."&name=".$item->alias.$extra;

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


		$item->addInstruction(Indexer::META_CONTEXT, 'metakey');
		$item->addInstruction(Indexer::META_CONTEXT, 'metadesc');
		$item->addInstruction(Indexer::META_CONTEXT, 'created_by_alias');

		$fields = $this->params->get('fields');
		if(!is_array($fields)){
			$fields = explode(',',(string)$fields);
		}
		if(!empty($fields) && count($fields)) {
			foreach($fields as $field) {
				if(!in_array($field, array('product_name', 'product_description', 'product_keywords', 'product_meta_description')))
					$item->addInstruction(Indexer::TEXT_CONTEXT, $field);
			}
		}

		$this->item = $item;
		$item->state = $this->translateState($item->state, $item->cat_state);

		$item->addTaxonomy('Type', 'Product');



		$item->addTaxonomy('Language', 		$item->language);

		Helper::getContentExtras($item);

		if(!$this->handleOtherLanguages($item)) {
			$this->indexer->index($item);
		}
	}

	public function prepareContent($summary, $params) {
		return Helper::prepareContent($summary, $params);
	}

	protected function addAlias(&$element){
		if(empty($element->alias)){
			if(empty($element->product_alias)) {
				if(empty($element->title))
					return;
				$element->alias = strip_tags(preg_replace('#<span class="hikashop_product_variant_subname">.*</span>#isU','',$element->title));
			} else {
				$element->alias = $element->product_alias;
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

	public function toObject($row) {
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
