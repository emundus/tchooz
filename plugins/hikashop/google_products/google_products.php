<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.5
 * @author	hikashop.com
 * @copyright	(C) 2010-2025 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php
class plgHikashopGoogle_products extends JPlugin {
	public $error = '';
	private $siteAddress = '';
	private $itemID = '';

	function onHikashopCronTrigger(&$messages) {
		if(!hikashop_level(1))
			return;

		$pluginsClass = hikashop_get('class.plugins');
		$plugin = $pluginsClass->getByName('hikashop','google_products');

		if( empty($plugin->params['enable_auto_update']) && empty($plugin->params['local_path'])){
			return true;
		}

		if(empty($plugin->params['frequency'])){
			$plugin->params['frequency'] = 86400;
		}
		if(!empty($plugin->params['last_cron_update']) && $plugin->params['last_cron_update']+$plugin->params['frequency']>time()){
			return true;
		}

		$plugin->params['last_cron_update']=time();
		$pluginsClass->save($plugin);
		$pluginsClass->loadParams($plugin);
		$xml = $this->generateXML();
		if(empty($xml)) {
			if(!empty($this->error)) {
				$messages[] = $this->error;
				$app->enqueueMessage($this->error, 'error');
			}
			return;
		}

		$app = JFactory::getApplication();
		if(!empty($plugin->params['local_path'])) {
			$path=$this->_getRelativePath($plugin->params['local_path']);
			jimport('joomla.filesystem.file');
			if(!JFile::write(JPATH_ROOT.DS.$path,$xml)){
				$message = 'Could not write Google Merchant file to '.JPATH_ROOT.DS.$path;
			}else{
				$message = 'Google Merchant file written to '.JPATH_ROOT.DS.$path;
			}
			$messages[] = $message;
			$app->enqueueMessage($message);
		}

		if(empty($plugin->params['enable_auto_update']))
			return true;

		if(empty($plugin->params['google_password']) || empty($plugin->params['user_name']) || empty($plugin->params['file_name']))
			return true;

		$pwd = $plugin->params['google_password'];
		$user = $plugin->params['user_name'];
		$name = $plugin->params['file_name'];

		$message = $this->_connectionToGoogleDB($user,$pwd, $xml, $plugin, $name);
		if($message === true) {
			$message = 'Products information sent to Google Merchant';
		}

		$messages[] = $message;
		$app->enqueueMessage($message);
	}

	function _getRelativePath($path) {
		$lang = JFactory::getLanguage();
		$dt = new DateTime();
		$relativePath=str_replace(array(JPATH_ROOT.DS, '{language}','{date}','{time}'), array('', $lang->getTag(), $dt->format('Y-m-d'), $dt->format('H-i-s')),$path);
		return $relativePath;
	}

	function downloadXML(){
		if(!hikashop_level(1))
			return;

		$xml = $this->generateXML();

		if(empty($xml)) {
			if(!empty($this->error)) {
				$app = JFactory::getApplication();
				$app->enqueueMessage($this->error, 'error');
			}
			return;
		}

		@ob_clean();
		header("Pragma: public");
		header("Expires: 0"); // set expiration time
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header("Content-Disposition: attachment; filename=Google_data_feed_".time().".xml;");
		header("Content-Transfer-Encoding: binary");
		header('Content-Length: '.strlen($xml));
		echo $xml;
		exit;
	}

	function generateXML() {
		if(!hikashop_level(1))
			return '';

		$app = JFactory::getApplication();
		$db = JFactory::getDBO();
		$pluginsClass = hikashop_get('class.plugins');
		$plugin = $pluginsClass->getByName('hikashop','google_products');

		if(empty($plugin->params) || !isset($plugin->params['in_stock_only'])) {
			$this->error = 'Please configure the Google Products plugin\'s settings and save them before generating the XML feed.';
			return '';
		}

		if(empty($plugin->params['condition'])){
			$plugin->params['condition'] = "new";
		}

		if(@$plugin->params['increase_perf']){
			$memory = '128M';
			$max_execution = '120';
			if($plugin->params['increase_perf'] == 2){
				$memory = '512M';
				$max_execution = '600';
			}elseif($plugin->params['increase_perf'] == 3){
				$memory = '1024M';
				$max_execution = '6000';
			}elseif($plugin->params['increase_perf'] == 10){
				$memory = '4096M';
				$max_execution = '0';
			}
			ini_set('memory_limit',$memory);
			ini_set('max_execution_timeout',$max_execution);
		}

		$query = 'SELECT * FROM '.hikashop_table('product').' WHERE product_published=1 AND product_type=\'main\'';

		if(!empty($plugin->params['categories'])){
			if(is_string($plugin->params['categories']))
				$plugin->params['categories'] = explode(',', $plugin->params['categories']);
			hikashop_toInteger($plugin->params['categories']);
			$db->setQuery('SELECT category_left, category_right FROM '.hikashop_table('category').' WHERE category_id IN ('.implode(',',$plugin->params['categories']).')');
			$categories = $db->loadObjectList();
			if(!empty($categories)) {
				$filters = array();
				foreach($categories as $category) {
					$filters[] = '(category_left >='.$category->category_left.' AND category_right <='.$category->category_right.')';
				}

				$db->setQuery('SELECT category_id FROM '.hikashop_table('category').' WHERE '.implode(' OR ', $filters));
				$allCategories = $db->loadColumn();
				if(!empty($allCategories)) {
					$query = 'SELECT DISTINCT prod.product_id, prod.* FROM '.hikashop_table('product').' AS prod INNER JOIN '.hikashop_table('product_category').' AS prodcat ON prod.product_id = prodcat.product_id WHERE product_published=1 AND product_type=\'main\' AND prodcat.category_id IN ('.implode(',', $allCategories).')';
				}
			}
		}
		if(!empty($plugin->params['in_stock_only'])){
			$query .= ' AND product_quantity!=0';
		}

		$acl_filters = array('product_access=\'all\'');
		if(!empty($plugin->params['user_group']) && is_array($plugin->params['user_group']) && count($plugin->params['user_group']) >= 1) {
			foreach($plugin->params['user_group'] as $userGroup) {
				$acl_filters[] = 'product_access '."LIKE '%,".(int)$userGroup.",%'";
			}
		}
		$query .= ' AND (' . implode(' OR ', $acl_filters) . ')';


		$db->setQuery($query);
		$products = $db->loadObjectList();
		if(empty($products)){
			return true;
		}

		$ids = array();
		foreach($products as $key => $row){
			$ids[] = (int)$row->product_id;
			$products[$key]->alias = JFilterOutput::stringURLSafe($row->product_name);
		}
		$queryCategoryId = 'SELECT * FROM '.hikashop_table('product_category').' WHERE product_id IN ('.implode(',',$ids).')';
		$db->setQuery($queryCategoryId);
		$categoriesId = $db->loadObjectList();
		foreach($products as $k => $row){
			foreach($categoriesId as $catId){
				if($row->product_id == $catId->product_id){
					$products[$k]->categories_id[] = $catId->category_id;
				}
			}
		}

		$usedCat=array();
		$catList="";
		foreach($products as $product){
			if(!empty($product->categories_id)){
				foreach($product->categories_id as $catId){
					if(!isset($usedCat[$catId])){
						$usedCat[$catId] = $catId;
						$catList .= $catId.',';
					}
				}
			}
		}
		$catList = substr($catList,0,-1);

		$parentCatId = 'product';
		$categoryClass = hikashop_get('class.category');
		$categoryClass->getMainElement($parentCatId);

		$query = 'SELECT DISTINCT b.* FROM '.hikashop_table('category').' AS a LEFT JOIN '.
					hikashop_table('category').' AS b ON a.category_left >= b.category_left WHERE '.
					'b.category_right >= a.category_right AND a.category_id IN ('.$catList.') AND a.category_published=1 AND a.category_type=\'product\' '.
					'ORDER BY b.category_left';
		$db->setQuery($query);
		$categories = $db->loadObjectList();

		$category_path=array();
		$discard_products_without_valid_categories = array();
		foreach($products as $k => $product){
			if(empty($product->categories_id)){
				$discard_products_without_valid_categories[] = $k;
			}else{
				$path = array();
				$at_least_a_category_valid = false;
				foreach($categories as $category){
					foreach($product->categories_id as $catID){
						if( $catID == $category->category_id){
							$at_least_a_category_valid = true;
							if( !isset($category_path[$catID])){
								$category_path[$catID] = $this->_getCategoryParent($category, $categories, $path, $parentCatId);
							}
						}
					}
				}
				if(!$at_least_a_category_valid){
					$discard_products_without_valid_categories[] = $k;
				}
			}
		}
		if(!empty($discard_products_without_valid_categories)){
			foreach($discard_products_without_valid_categories as $k){
				unset($products[$k]);
			}
		}

		foreach($category_path as $id => $mainCat){
			$path='';
			for($i=count($mainCat);$i>0;$i--){
				$path .= $mainCat[$i-1]->category_name.' > ';
			}
			$category_path[$id]['path'] = substr($path,0,-3);
		}

		$queryImage = 'SELECT * FROM '.hikashop_table('file').' WHERE file_ref_id IN ('.implode(',',$ids).') AND file_type=\'product\' ORDER BY file_ordering ASC, file_id ASC';
		$db->setQuery($queryImage);
		$images = $db->loadObjectList();
		foreach($products as $k => $row){
			$products[$k]->images = array();
			$i=0;
			foreach($images as $image){
				if($row->product_id == $image->file_ref_id){
					$products[$k]->images[$i] = new stdClass();
					foreach(get_object_vars($image) as $key => $name){
						if(!empty($name) && in_array($key, array('file_name','file_path', 'file_description'))) {
							$name = hikashop_translate($name);
						}
						$products[$k]->images[$i]->$key = $name;
					}
				}
				$i++;
			}
		}

		$currencyClass = hikashop_get('class.currency');
		$config =& hikashop_config();
		$main_currency = (int)$config->get('main_currency',1);
		if(empty($plugin->params['price_displayed'])) $plugin->params['price_displayed'] = 'cheapest';

		if($plugin->params['price_displayed'] == 'average'){
			$currencyClass->getProductsPrices($products, array('currency_id' => $main_currency, 'price_display_type' => 'range', 'no_discount' => (int)@$plugin->params['no_discount']));
			$tmpPrice = 0;
			$tmpTaxPrice = 0;
			foreach($products as $product){
				if(isset($product->prices[0]->price_value)){
					if(count($product->prices) > 1){
						for($i=0;$i<count($product->prices);$i++){
							if($product->prices[$i]->price_value > $tmpPrice){
								$tmpPrice += $product->prices[$i]->price_value;
								$tmpTaxPrice += @$product->prices[$i]->price_value_with_tax;
							}
						}
						$product->prices[0]->price_value = $tmpPrice/count($product->prices);
						$product->prices[0]->price_value_with_tax = $tmpTaxPrice/count($product->prices);
						for($i=1;$i<count($product->prices);$i++){
							unset($product->prices[$i]);
						}
					}
				}
			}
		}else{
			$currencyClass->getProductsPrices($products, array('currency_id' => $main_currency, 'price_display_type' => $plugin->params['price_displayed'], 'no_discount' => (int)@$plugin->params['no_discount']));
		}

		$db->setQuery('SELECT * FROM '.hikashop_table('variant').' WHERE variant_product_id IN ('.implode(',',$ids).')');
		$variants = $db->loadObjectList();

		if(!empty($variants)){
			$product_ids_with_variants = array();
			foreach($products as $k => $product){
				foreach($variants as $variant){
					if($product->product_id == $variant->variant_product_id){
						$products[$k]->has_options = true;
						$product_ids_with_variants[] = (int)$product->product_id;
						break;
					}
				}
			}
			if(!empty($plugin->params['include_variants']) && count($product_ids_with_variants)) {
				$plugin->params['item_group_id'] = 'item_group_id';

				$query = 'SELECT * FROM '.hikashop_table('product').' WHERE product_published > 0 AND product_parent_id IN ('.implode(',',$product_ids_with_variants).')';
				if(!empty($plugin->params['in_stock_only'])){
					$query .= ' AND product_quantity!=0';
				}
				$db->setQuery($query);
				$variantsData = $db->loadObjectList();
				$variantsIds = array();
				foreach($variantsData as $variant) {
					$variantsIds[] = (int)$variant->product_id;
				}
				if(!empty($variantsIds)) {
					$query = 'SELECT variant.variant_product_id, characteristic.characteristic_value FROM '.hikashop_table('variant').' as variant LEFT JOIN '.hikashop_table('characteristic').' AS characteristic ON variant.variant_characteristic_id = characteristic.characteristic_id WHERE variant.variant_product_id IN ('.implode(',',$variantsIds).')';
					$db->setQuery($query);
					$characteristics = $db->loadObjectList();
					if(!empty($characteristics)) {
						foreach($variantsData as $k => $variant) {
							$variant->characteristics = array();
							foreach($characteristics as $characteristic) {
								if($variant->product_id == $characteristic->variant_product_id)
									$variantsData[$k]->characteristics[] = $characteristic;
							}
						}
					}
					$queryImage = 'SELECT * FROM '.hikashop_table('file').' WHERE file_ref_id IN ('.implode(',',$variantsIds).') AND file_type=\'product\' ORDER BY file_ordering ASC, file_id ASC';
					$db->setQuery($queryImage);
					$images = $db->loadObjectList();
					foreach($variantsData as $k => $row){
						$variantsData[$k]->images = array();
						$i=0;
						foreach($images as $image){
							if($row->product_id == $image->file_ref_id){
								$variantsData[$k]->images[$i] = new stdClass();
								foreach(get_object_vars($image) as $key => $name){
									$variantsData[$k]->images[$i]->$key = $name;
								}
							}
							$i++;
						}
					}

					if($plugin->params['price_displayed'] == 'average'){
						$currencyClass->getProductsPrices($variantsData, array('currency_id' => $main_currency, 'price_display_type' => 'range', 'no_discount' => (int)@$plugin->params['no_discount']));
						$tmpPrice = 0;
						$tmpTaxPrice = 0;
						foreach($variantsData as $product){
							if(isset($product->prices[0]->price_value)){
								if(count($product->prices) > 1){
									for($i=0;$i<count($product->prices);$i++){
										if($product->prices[$i]->price_value > $tmpPrice){
											$tmpPrice += $product->prices[$i]->price_value;
											$tmpTaxPrice += @$product->prices[$i]->price_value_with_tax;
										}
									}
									$product->prices[0]->price_value = $tmpPrice/count($product->prices);
									$product->prices[0]->price_value_with_tax = $tmpTaxPrice/count($product->prices);
									for($i=1;$i<count($product->prices);$i++){
										unset($product->prices[$i]);
									}
								}
							}
						}
					}else{
						$currencyClass->getProductsPrices($variantsData, array('currency_id' => $main_currency, 'price_display_type' => $plugin->params['price_displayed'], 'no_discount' => (int)@$plugin->params['no_discount']));
					}
				}
				$productClass = hikashop_get('class.product');
				$newProducts = array();
				$unsetProducts = array();
				foreach($products as $k => $product) {
					$products[$k]->item_group_id = '';
					$unsetVariants = array();
					foreach($variantsData as $j => $variant) {
						if($variant->product_parent_id == $product->product_id) {
							$productClass->checkVariant($variant, $product, array(), true);

							$variant->has_options = false;
							$variant->item_group_id = $product->product_id;
							$variant->product_name = strip_tags($variant->product_name);
							$newProducts[] = hikashop_copy($variant);
							$unsetVariants[] = $j;
							$unsetProducts[] = $k;
						}
					}
					if(count($unsetVariants)) {
						foreach($unsetVariants as $u) {
							unset($variantsData[$u]);
						}
					}
				}
				if(count($unsetProducts)) {
					foreach($unsetProducts as $u) {
						unset($products[$u]);
					}
				}
				if(count($newProducts)) {
					foreach($newProducts as $p) {
						$products[] = $p;
					}
					unset($newProducts);
				}
			}
		}

		if(!empty($plugin->params['use_brand'])){
			$parentCatId = 'manufacturer';
			$categoryClass->getMainElement($parentCatId);
			$query = 'SELECT DISTINCT * FROM '.hikashop_table('category').' AS a WHERE a.category_published=1 AND a.category_type=\'manufacturer\' AND a.category_parent_id='.$parentCatId;
			$db->setQuery($query);
			$brands = $db->loadObjectList('category_id');
		}

		$config =& hikashop_config();
		$uploadFolder = ltrim(JPath::clean(html_entity_decode($config->get('uploadfolder'))),DS);
		$uploadFolder = rtrim($uploadFolder,DS).DS;
		$this->uploadFolder_url = str_replace(DS,'/',$uploadFolder);
		$this->uploadFolder = JPATH_ROOT.DS.$uploadFolder;
		$app = JFactory::getApplication();
		$this->thumbnail = $config->get('thumbnail',1);
		$this->thumbnail_x = $config->get('thumbnail_x',100);
		$this->thumbnail_y = $config->get('thumbnail_y',100);
		$this->main_thumbnail_x = $this->thumbnail_x;
		$this->main_thumbnail_y = $this->thumbnail_y;
		$this->main_uploadFolder_url = $this->uploadFolder_url;
		$this->main_uploadFolder = $this->uploadFolder;

		$conf = JFactory::getConfig();
		if(!HIKASHOP_J30) {
			$siteName = $conf->getValue('config.sitename');
			$siteDesc = $conf->getValue('config.MetaDesc');
		} else {
			$siteName = $conf->get('sitename');
			$siteDesc = $conf->get('MetaDesc');
		}
		if(!empty($plugin->params['channel_description'])) {
			$siteDesc = $plugin->params['channel_description'];
		}

		if(!empty($plugin->params['item_id'])){
			$this->itemID = '&Itemid='.$plugin->params['item_id'];
		}
		$this->siteAddress = JURI::base();
		$this->siteAddress = str_replace('administrator/','',$this->siteAddress);

		$xml = '<?xml version="1.0" encoding="UTF-8" ?>'."\n".
					'<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">'."\n".
					"\t".'<channel>'."\n".
								"\t\t".'<title><![CDATA[ '.$siteName.' ]]></title>'."\n".
								"\t\t".'<description><![CDATA[ '.$siteDesc.' ]]></description>'."\n".
								"\t\t".'<link><![CDATA[ '.$this->siteAddress.' ]]></link>'."\n"."\n";
		$productClass = hikashop_get('class.product');
		$volumeHelper = hikashop_get('helper.volume');
		$weightHelper = hikashop_get('helper.weight');
		foreach($products as $product) {
			if(!empty($plugin->params['skip_field'])) {
				$skipColumn = $plugin->params['skip_field'];
				if(!empty($product->$skipColumn)) continue;
			}
			if(isset($product->prices[0]->price_value)){
				$price_name = 'price_value';
				if(!empty($plugin->params['taxed_price'])){
					$price_name = 'price_value_with_tax';
				}
				if(empty($product->product_min_per_order)){
					$price = round($product->prices[0]->$price_name, 2);
				}
				else{
					$price = round($product->prices[0]->$price_name, 2)*$product->product_min_per_order;
				}
				$currencies = array();
				$currencyClass = hikashop_get('class.currency');
				$ids[$product->prices[0]->price_currency_id] = $product->prices[0]->price_currency_id;
				$currencies = $currencyClass->getCurrencies($ids[$product->prices[0]->price_currency_id],$currencies);
				$currency = reset($currencies);
				$xml .= '<item>'."\n";
				$productClass->addAlias($product);
				if(in_array($product->product_weight_unit, array('mg', 'kg'))) {
					$product->product_weight = $weightHelper->convert($product->product_weight, $product->product_weight_unit, 'g');
					$product->product_weight_unit = 'g';
				}
				if(in_array($product->product_weight_unit, array('ozt'))) {
					$product->product_weight = $weightHelper->convert($product->product_weight, $product->product_weight_unit, 'oz');
					$product->product_weight_unit = 'oz';
				}
				if(in_array($product->product_dimension_unit, array('m', 'dm', 'mm'))) {
					$product->product_length = $volumeHelper->convert($product->product_length, $product->product_dimension_unit, 'cm', 'dimension');
					$product->product_width = $volumeHelper->convert($product->product_width, $product->product_dimension_unit, 'cm', 'dimension');
					$product->product_height = $volumeHelper->convert($product->product_height, $product->product_dimension_unit, 'cm', 'dimension');
					$product->product_dimension_unit = 'cm';
				}
				if(in_array($product->product_dimension_unit, array('ft', 'yd'))) {
					$product->product_length = $volumeHelper->convert($product->product_length, $product->product_dimension_unit, 'in', 'dimension');
					$product->product_width = $volumeHelper->convert($product->product_width, $product->product_dimension_unit, 'in', 'dimension');
					$product->product_height = $volumeHelper->convert($product->product_height, $product->product_dimension_unit, 'in', 'dimension');
					$product->product_dimension_unit = 'in';
				}

				$xml .= "\t".'<g:id>'.$product->product_id.'</g:id>'."\n";
				$xml .= "\t".'<title><![CDATA[ '.mb_substr($product->product_name, 0 ,150).' ]]></title>'."\n";

				if(!empty($product->product_canonical)){
					$xml .= "\t".'<g:link><![CDATA[ '.str_replace('/administrator/','/',hikashop_cleanURL($product->product_canonical)).' ]]></g:link>'."\n";
				}else{
					$xml .= "\t".'<g:link><![CDATA[ '.$this->siteAddress.'index.php?option=com_hikashop&ctrl=product&task=show&cid='.$product->product_id.'&name='.$product->alias.$this->itemID.' ]]></g:link>'."\n";
				}
				if(!empty($product->discount)) {
					$xml .= "\t".'<g:sale_price>'.$price.' '.$currency->currency_code.'</g:sale_price>'."\n";

					$price_name = 'price_value_without_discount';
					if(!empty($plugin->params['taxed_price'])){
						$price_name = 'price_value_without_discount_with_tax';
					}
					if(empty($product->product_min_per_order)){
						$price = round($product->prices[0]->$price_name, 2);
					}
					else{
						$price = round($product->prices[0]->$price_name, 2)*$product->product_min_per_order;
					}
					$xml .= "\t".'<g:price>'.$price.' '.$currency->currency_code.'</g:price>'."\n";
				} else {
					$xml .= "\t".'<g:price>'.$price.' '.$currency->currency_code.'</g:price>'."\n";
				}
				if(@$plugin->params['preview'] == 'meta') {
						$xml .= "\t".'<g:description><![CDATA[ '.mb_substr(strip_tags($product->product_meta_description),0,5000).' ]]></g:description>'."\n";
				} elseif(!empty($product->product_description)){
					$product->product_description = JHTML::_('content.prepare', $product->product_description);
					if(@$plugin->params['preview']) {
						 $xml .= "\t".'<g:description><![CDATA[ '.mb_substr(strip_tags(preg_replace('#<hr *id="system-readmore" */>.*#is','',$product->product_description)),0,5000).' ]]></g:description>'."\n";
					} else {
						$xml .= "\t".'<g:description><![CDATA[ '.mb_substr(strip_tags($product->product_description),0,5000).' ]]></g:description>'."\n";
					}
				}elseif(!empty($plugin->params['message'])){
					$xml .= "\t".'<g:description><![CDATA[ '.mb_substr($plugin->params['message'], 0 ,5000).' ]]></g:description>'."\n";
				}else{
					$xml .= "\t".'<g:description>No description</g:description>'."\n";
				}

				$column = @$plugin->params['identifier_exists'];
				if(!empty($column) && ($column == 'TRUE' || (!empty($product->$column) && $product->$column == 'TRUE')))
					$xml .= $this->_additionalParameter($product,$plugin,'gtin','gtin');

				$parameters = array(
					'age_group',
					'size',
					'color',
					'identifier_exists',
					'is_bundle',
					'multipack',
					'unit_pricing_measure',
					'unit_pricing_base_measure',
					'energy_efficiency_class',
					'min_energy_efficiency_class',
					'max_energy_efficiency_class',
					'shipping_label',
					'gender',
					'item_group_id',
					'condition',
				);
				foreach($parameters as $parameter) {
					$xml .= $this->_additionalParameter($product,$plugin,$parameter,$parameter);
				}

				$xml .= $this->_addCheckoutLink($product,$plugin);

				$xml .= $this->_addShipping($product,$plugin);

				if(!empty($plugin->params['use_brand']) && !empty($brands[$product->product_manufacturer_id]->category_name)){
					$xml .= "\t".'<g:brand><![CDATA[ '.$brands[$product->product_manufacturer_id]->category_name.' ]]></g:brand>'."\n";
				}else{
					$xml .= $this->_additionalParameter($product,$plugin,'brand','brand');
				}

				$xml .= $this->_additionalParameter($product,$plugin,'category','google_product_category');

				if($plugin->params['add_code']){
					$xml .= "\t".'<g:mpn><![CDATA[ '.str_replace(array(' ','-'),array('',''),$product->product_code).' ]]></g:mpn>'."\n";
				}else {
					$xml .= $this->_additionalParameter($product,$plugin,'mpn','mpn');
				}

				if(isset($product->images) && count($product->images)){
					$i = 0;
					$name = "image_link";
					foreach($product->images as $image){
						if($i < 10){
							 $xml .= "\t".'<g:'.$name.'>'.htmlspecialchars($this->siteAddress.$this->main_uploadFolder_url.(ltrim($image->file_path,'/'))).'</g:'.$name.'>'."\n";
							 $name = "additional_image_link";
							 $i++;
						}
					}
				}

				$type='';
				foreach($product->categories_id as $catID){
					foreach($category_path as $id=>$catPath){
						if($id == $catID){
							if(strlen($type.str_replace(',', ' ', $catPath['path']).',') > 750) continue;
							$type .= str_replace(',', ' ', $catPath['path']).',';
						}
					}
				}
				if(!empty($type)){
					$type = substr($type,0,-1);
					$xml .= "\t".'<g:product_type><![CDATA[ '.$type.' ]]></g:product_type>'."\n";
				}


				if($product->product_quantity != -1){
					$xml .= "\t".'<g:quantity_to_sell_on_facebook>'.$product->product_quantity.'</g:quantity_to_sell_on_facebook>'."\n";
				}
				if($product->product_quantity == 0){
					$stock = 'out of stock';
					if(!empty($product->product_sale_start) && $product->product_sale_start > time()) {
						$stock = 'preorder';
						$date = new DateTime("@".$product->product_sale_start);
						$date->setTimezone(new DateTimeZone("UTC"));
						$formattedDate = $date->format('Y-m-d\TH:i\Z');
						$xml .= "\t".'<g:availability_date>'.$formattedDate.'</g:availability_date>'."\n";
					}elseif(!empty($plugin->params['availability_date'])) {
						$availability_date = $plugin->params['availability_date'];
						if(!empty($product->$availability_date)) {
							$formattedDate = '';
							if(ctype_digit((string)$product->$availability_date)) {
								$date = new DateTime("@".$product->$availability_date);
								$date->setTimezone(new DateTimeZone("UTC"));
								$formattedDate = $date->format('Y-m-d\TH:i\Z');
							} elseif (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}Z$/', (string)$product->$availability_date)) {
								$formattedDate = $product->$availability_date;
							} elseif(preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$product->$availability_date)) {
								$formattedDate = $product->$availability_date.'T00:00Z';
							}
							if(!empty($formattedDate)) {
								$stock = 'backorder';
								$xml .= "\t".'<g:availability_date>'.$formattedDate.'</g:availability_date>'."\n";
							}
						}
					}
					$xml .= "\t".'<g:availability>'.$stock.'</g:availability>'."\n";
				}
				else{
					$xml .= "\t".'<g:availability>in stock</g:availability>'."\n";
				}
				if( $product->product_weight > 0 && 
					(
						($product->product_weight < 32000 && $product->product_weight_unit == 'oz') ||
						($product->product_weight < 1000000 && $product->product_weight_unit == 'g') ||
						($product->product_weight < 1000 && $product->product_weight_unit == 'kg') ||
						($product->product_weight < 2000 && $product->product_weight_unit == 'lb' )
					))
					$xml .= "\t".'<g:shipping_weight>'.ceil($product->product_weight).' '.$product->product_weight_unit.'</g:shipping_weight>'."\n";

				if( (
					$product->product_length > 0 &&
					$product->product_width > 0 &&
					$product->product_height > 0 &&
					$product->product_length < 400 &&
					$product->product_width < 1000 &&
					$product->product_height < 1000 &&
					$product->product_dimension_unit == 'cm')
					|| (
					$product->product_length > 0 &&
					$product->product_width > 0 &&
					$product->product_height > 0 &&
					$product->product_length < 150 &&
					$product->product_width < 1000 &&
					$product->product_height < 1000 &&
					$product->product_dimension_unit == 'in')) {
					$xml .= "\t".'<g:shipping_length>'.ceil($product->product_length).' '.$product->product_dimension_unit.'</g:shipping_length>'."\n";
					$xml .= "\t".'<g:shipping_width>'.ceil($product->product_width).' '.$product->product_dimension_unit.'</g:shipping_width>'."\n";
					$xml .= "\t".'<g:shipping_height>'.ceil($product->product_height).' '.$product->product_dimension_unit.'</g:shipping_height>'."\n";
				}
				$xml .= '</item>'."\n";
			}
		}

		$xml .= '</channel>'."\n".'</rss>'."\n";
		return $xml;
	}

	function _addCheckoutLink(&$product, &$plugin) {
		$xml = '';

		if(empty($plugin->params['checkout_link_template'])){
			return $xml;
		}

		$column = $plugin->params['checkout_link_template'];
		if(isset($product->$column)){
			if(empty($product->$column)) return $xml;
		}
		$qty = 1;
		if(!empty($product->product_min_per_order)){
			$qty = (int)$product->product_min_per_order;
		}
		$url = $this->siteAddress.'index.php?option=com_hikashop&ctrl=product&task=updatecart&add=1&cid='.$product->product_id.'&qty='.$qty.$this->itemID;
		$xml.="\t".'<g:checkout_link_template><![CDATA[ '.$url.' ]]></g:checkout_link_template>'."\n";
		return $xml;
	}

	function _addShipping(&$product,&$plugin){
		$xml = '';

		if(empty($plugin->params['shipping'])){
			return $xml;
		}

		$column = $plugin->params['shipping'];
		if(isset($product->$column)){
			if(empty($product->$column)) return $xml;

			$text = $product->$column;
		}else{
			$text = $column;
		}

		$shipping_methods = explode(',',$text);

		foreach($shipping_methods as $shipping_method){
			$shipping_data = explode(':',$shipping_method);
			if(count($shipping_data)!=4) continue;
			$xml.="\t".'<g:shipping>'."\n";
			$xml.="\t\t".'<g:country>'.$shipping_data[0].'</g:country>'."\n";
			if(!empty($shipping_data[1])) $xml.="\t\t".'<g:region>'.$shipping_data[1].'</g:region>'."\n";
			if(!empty($shipping_data[2])) $xml.="\t\t".'<g:service>'.$shipping_data[2].'</g:service>'."\n";
			$xml.="\t\t".'<g:price>'.$shipping_data[3].'</g:price>'."\n";
			if(!empty($shipping_data[4])) $xml.="\t\t".'<g:min_handling_time>'.$shipping_data[4].'</g:min_handling_time>'."\n";
			if(!empty($shipping_data[5])) $xml.="\t\t".'<g:max_handling_time>'.$shipping_data[5].'</g:max_handling_time>'."\n";
			if(!empty($shipping_data[6])) $xml.="\t\t".'<g:min_transit_time>'.$shipping_data[6].'</g:min_transit_time>'."\n";
			if(!empty($shipping_data[7])) $xml.="\t\t".'<g:max_transit_time>'.$shipping_data[7].'</g:max_transit_time>'."\n";
			$xml.="\t".'</g:shipping>'."\n";
		}
		return $xml;
	}

	function _additionalParameter(&$product,&$plugin,$param,$attribute){
		$xml = '';
		if(!empty($plugin->params[$param])){
			$column = $plugin->params[$param];
			if(isset($product->$column)){
				if(empty($product->$column)) return $xml;

				$text = $product->$column;
			} else {
				$text = $column;
			}
			$xml="\t".'<g:'.$attribute.'><![CDATA[ '.$text.' ]]></g:'.$attribute.'>'."\n";
		}
		return $xml;
	}


	function _connectionToGoogleDB($login, $pwd, $xml, $plugin, $name){
		try {
			$ftp = new \phpseclib3\Net\SFTP("partnerupload.google.com",19321);
			$result = $ftp->login($login, $pwd);
			if(!$result)
				return "Could not login to uploads.google.com. Please check the FTP credentials in the google products plugin settings.";
			$ftp->write($name, $xml);
		} catch(Exception $e) {
			return $e->getMessage();
		}
		return true;
	}

	function _getCategoryParent($theCat, &$categories, $path, $parentCatId){
		if($theCat->category_parent_id==$parentCatId){
			$path[]=$theCat;
			return $path;
		}
		foreach($categories as $category){
			if($category->category_id==$theCat->category_parent_id){
				$path[]=$theCat;
				$path=$this->_getCategoryParent($category,$categories,$path, $parentCatId);
			}
		}
		return $path;
	}

}
