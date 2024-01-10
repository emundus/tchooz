<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.0.2
 * @author	hikashop.com
 * @copyright	(C) 2010-2023 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php
include_once(JPATH_ROOT.'/administrator/components/com_hikashop/pluginCompat.php');
if(!class_exists('hikashopJoomlaPlugin')) return;
class plgSystemHikashopsocial extends hikashopJoomlaPlugin {
	var $meta = array();
	var $headScripts = array();
	var $uploadFolder = null;
	var $main_uploadFolder = null;
	var $uploadFolder_url = null;
	var $main_uploadFolder_url = null;
	var $thumbnail = null;
	var $thumbnail_x = null;
	var $thumbnail_y = null;
	var $main_thumbnail_x = null;
	var $main_thumbnail_y = null;

	function __construct(&$subject, $params) {
		parent::__construct($subject, $params);
	}

	function onAfterRender() {

		if(HIKASHOP_J50 && !class_exists('JFactory'))
			class_alias('Joomla\CMS\Factory', 'JFactory');
		$app = JFactory::getApplication();

		if(version_compare(JVERSION,'3.0','>=')) {
			$option = $app->input->getVar('option');
			$ctrl = $app->input->getVar('ctrl');
			$task = $app->input->getVar('task');
		} else {
			$option = JRequest::getVar('option');
			$ctrl = JRequest::getVar('ctrl');
			$task = JRequest::getVar('task');
		}
		if(version_compare(JVERSION,'4.0','>=')) {
			$admin = $app->isClient('administrator');
		} else {
			$admin = $app->isAdmin();
		}

		if($admin || !in_array($option, array('com_hikashop', 'com_hikamarket', '')) || !in_array($ctrl, array('product', 'category', 'vendor')) || !in_array($task, array('show', 'listing')))
			return;

		if(!defined('HIKASHOP_COMPONENT'))
			return;

		if(class_exists('JResponse'))
			$body = JResponse::getBody();
		$alternate_body = false;
		if(empty($body)){
			$body = $app->getBody();
			$alternate_body = true;
		}
		if(strpos($body,'{hikashop_social}') === false)
			return;

		$pluginsClass = hikashop_get('class.plugins');
		$plugin = $pluginsClass->getByName('system', 'hikashopsocial');


		$default = array(
			'position' => 0,
			'display_twitter' => 1,
			'display_pinterest' => 1,
			'display_fb' => 1,
			'display_linkedin' => 1,
			'fb_style' => 0,
			'fb_faces' => 1,
			'fb_verb' => 0,
			'fb_theme' => 0,
			'fb_font' => 0,
			'fb_type' => 0,
			'fb_send' => 0,
			'fb_tag' => 'iframe',
			'fb_mode' => 'fb-like',
			'twitter_count' => 0,
			'display_url_btn' => 0,
			'redirection_btn' => 'tab',
			'redirect' => 'tab',
			'display_mode' => 'hikashopsocial_inline',
			'hashtag_twitter' => '',
		);

		if(empty($plugin->params))
			$plugin->params = array();
		$plugin->params = array_merge($default, $plugin->params);

		$plugin->params['redirect'] = $plugin->params['redirection_btn'];
		$plugin->params['display'] = $plugin->params['display_mode'];
		$plugin->params['legacy'] = $plugin->params['display_url_btn'];

		$PinterestButton = '';
		if ($plugin->params['display_pinterest'] == 1)
			$PinterestButton = $this->_addPinterestButton($plugin, $plugin->params);
		$TwitterButton = '';
		if ($plugin->params['display_twitter'] == 1)
			$TwitterButton = $this->_addTwitterButton($plugin, $plugin->params);
		$FacebookButton = '';
		if (!empty($plugin->params['display_fb']))
			$FacebookButton = $this->_addFacebookButton($plugin, $plugin->params);
		$LinkedInButton = '';
		if ($plugin->params['display_twitter'] == 1)
				$LinkedInButton = $this->_addLinkedInButton($plugin, $plugin->params);

		$html = array(
			$TwitterButton,
			$PinterestButton,
			$FacebookButton,
			$LinkedInButton
		);

		$styles = 'text-align:left;';
		if($plugin->params['position'] == 1) {
			$styles = 'text-align:right;';
			if(!empty($plugin->params['width']) && (int)$plugin->params['width'] > 0)
				$styles .= 'width:'.(int)$plugin->params['width'].'px';
			else
				$styles .= 'width:100%';
		}

		$html = implode('', $html);

		if(!empty($html))
			$html = '<div id="hikashop_social" style="'.$styles.'">' . $html . '</div>';

		$body = str_replace('{hikashop_social}', $html, $body);

		if(!empty($plugin->params['display_fb'])) {
			$body = str_replace('<html ', '<html xmlns:fb="https://www.facebook.com/2008/fbml" xmlns:og="http://ogp.me/ns# " xmlns:fb="http://ogp.me/ns/fb#" ', $body);
			if($plugin->params['fb_tag'] == "xfbml" && $plugin->params['display_fb'] != 2) {
				$mainLang = JFactory::getLanguage();
				$tag = str_replace('-', '_', $mainLang->get('tag'));
				$fb = '
<div id="fb-root"></div>
<script>
(function(d,s,id) {
	var js, fjs = d.getElementsByTagName(s)[0];
	if(d.getElementById(id)) return;
	js = d.createElement(s); js.id = id;
	js.src = "//connect.facebook.net/'.$tag.'/all.js#xfbml=1";
	fjs.parentNode.insertBefore(js, fjs);
}(document, "script", "facebook-jssdk"));
</script>';
				$body = preg_replace('#<body.*>#Us', '$0'.$fb, $body);
			}
		}

		if(!empty($this->headScripts))
			$body = str_replace('</head>', implode("\r\n", $this->headScripts)."\r\n".'</head>', $body);

		if(!empty($this->meta)) {
			$meta = array();
			foreach($this->meta as $k => $v) {
				if(strpos($body, $k) === false)
					$meta[] = $v;
			}
			if(!empty($meta))
				$body = str_replace('</head>', implode("\r\n", $meta)."\r\n".'</head>', $body);
		}
		if($alternate_body){
			$app->setBody($body);
		}else{
			JResponse::setBody($body);
		}

	}
	function _addLinkedInButton(&$plugin, $params) {
		if(!empty($plugin->params['legacy']) || empty($plugin->params['display_linkedin']))
			return '';

		$current_url = urlencode(hikashop_currentURL());
		$btn_mode = $this->rs_mode($params['redirect'], 'https://www.linkedin.com/sharing/share-offsite/?url='. $current_url, '');

		$array_elem = array(
			'icon' => 'linkedin_icon.png',
			'class' => 'hikasocial_linkedin',
			'display' => $params['display'],
			'btn_mode' => $btn_mode,
			'name' => 'Linkedin'
		);

		$btn_html = $this->btn_build($array_elem);

		return $btn_html;
	}

	function _addPinterestButton(&$plugin, $params) {
		if(!empty($element->url_canonical))
			$url = hikashop_cleanURL($element->url_canonical);
		else
			$url = hikashop_currentURL('',false);

		$element = $this->_getElementInfo();
		$imageUrl = $this->_getImageURL($element);
		$description = $this->_cleanDescription($element->description, 500);
		$layouts = array(0 => 'horizontal', 1 => 'vertical', 2 => 'none');
		$count = $layouts[ (int)@$plugin->params['pinterest_display'] ];

		if ($params['legacy']) {
			if(empty($plugin->params['display_pinterest']))
				return '';

			$this->headScripts['pinterest'] = '<script type="text/javascript" src="//assets.pinterest.com/js/pinit.js"></script>';

			$c = '';
			if($plugin->params['position'] == 1)
				$c = '_right';

			$return = '<span class="hikashop_social_pinterest'.$c.'"><a href="//pinterest.com/pin/create/button/?url='.urlencode($url).'&media='.urlencode($imageUrl).'&description='.rawurlencode($description).'" class="pin-it-button" count-layout="'.$count.'"><img border="0" src="//assets.pinterest.com/images/PinExt.png" title="Pin It" /></a></span>';
		}
		else {
			$current_url = urlencode(hikashop_currentURL());
			$btn_mode = $this->rs_mode($params['redirect'], 'http://pinterest.com/pin/create/button/?url='.$current_url.'&media='.$imageUrl, '');

			$array_elem = array(
				'icon' => 'pintinterest_icon.png',
				'class' => 'hikasocial_pintinterest',
				'display' => $params['display'],
				'btn_mode' => $btn_mode,
				'name' => 'Pinterest'
			);

			$btn_html = $this->btn_build($array_elem);
			$return = $btn_html;
		}
		return $return;
	}

	function _addTwitterButton(&$plugin, $params) {
		if ($params['legacy']) {
			if(empty($plugin->params['display_twitter']))
				return '';

			$element = $this->_getElementInfo();
			if(empty($element))
				return '';

			$layouts = array(0 => 'horizontal', 1 => 'vertical', 2 => 'none');
			$count = $layouts[ (int)$plugin->params['twitter_count'] ];

			$c = '';
			if($plugin->params['twitter_count'] == 0)
				$c .= '_horizontal';
			if($plugin->params['position'] == 1)
				$c .= '_right';

			$message = '';
			if(!empty($plugin->params['twitter_text']))
				$message = ' data-text="'.$plugin->params['twitter_text'].'"';

			$mention = '';
			if(!empty($plugin->params['twitter_mention']))
				$mention = ' data-via="'.$plugin->params['twitter_mention'].'"';

			$mainLang = JFactory::getLanguage();
			$locale = strtolower(substr($mainLang->get('tag'), 0, 2));

			$lang = '';
			if(in_array($locale, array('fr', 'de', 'es', 'it', 'ja', 'ru', 'tr', 'ko')))
				$lang = ' data-lang="'.$locale.'"';

			if (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) != 'off')) {
				$this->meta['hikashop_twitter_js_code'] = '
	<script type="text/javascript">
	function twitterPop(str) {
		mywindow = window.open(\'http://twitter.com/share?url=\'+str,"Tweet_widow","channelmode=no,directories=no,location=no,menubar=no,scrollbars=no,toolbar=no,status=no,width=500,height=375,left=300,top=200");
		mywindow.focus();
	}
	</script>';
				if(!empty($element->url_canonical))
					$url = hikashop_cleanURL($element->url_canonical);
				else
					$url = hikashop_currentURL('',false);

				return '<span class="hikashop_social_tw'.$c.'"><a href="javascript:twitterPop(\''.$url.'\')"><img src="'.HIKASHOP_IMAGES.'icons/tweet_button.jpg"></a></span>';
			}
			$return = '<span class="hikashop_social_tw'.$c.'"><a href="//twitter.com/share" class="twitter-share-button"'.$message.' data-count="'.$count.'"'.$mention.$lang.'>Tweet</a><script type="text/javascript" src="//platform.twitter.com/widgets.js"></script></span>';
		}
		else {
			$hashtag = '';
			if ($plugin->params['hashtag_twitter'] != "")
				$hashtag = '&hashtags=' . $plugin->params['hashtag_twitter'];

			$current_url = urlencode(hikashop_currentURL());
			$btn_mode = $this->rs_mode($params['redirect'], 'https://twitter.com/intent/tweet?url='.$current_url, $hashtag);

			$array_elem = array(
				'icon' => 'twitter_icon.png',
				'class' => 'hikasocial_twitter',
				'display' => $params['display'],
				'btn_mode' => $btn_mode,
				'name' => 'Twitter'
			);

			$return = $this->btn_build($array_elem);
		}
		return $return;
	}

	function _addFacebookButton(&$plugin, $params) {
		if(empty($plugin->params['display_fb']))
			return '';

		$element = $this->_getElementInfo();
		if(empty($element))
			return '';

		if(!empty($element->url_canonical))
			$url = hikashop_cleanURL($element->url_canonical);
		else
			$url = hikashop_currentURL('', false);

		$this->_addOpenGraph($element, $plugin, $url);

		$html = '';

		if ($params['legacy']) {
			$options = array(
				'layout' => 'standard',
				'width' => 400
			);
			$xfbml_options = array();

			$classname = 'standard';
			switch((int)$plugin->params['fb_style']) {
				case 1:
					$classname = 'button_count';
					$options['layout'] = 'button_count';
					$xfbml_options['layout'] = 'button_count';
					$options['width'] = 115;
					break;
				case 2:
					$classname = 'box_count';
					$options['layout'] = 'box_count';
					$xfbml_options['layout'] = 'box_count';
					$options['width'] = 115;
					break;
				case 3:
					$classname = 'button';
					$options['layout'] = 'button';
					$xfbml_options['layout'] = 'button';
					$options['width'] = 65;
					break;
			}

			if(empty($plugin->params['fb_faces'])) {
				$options['show_faces'] = 'false';
				$xfbml_options['show-faces'] = 'false';
			} else {
				$options['show_faces'] = 'true';
				$xfbml_options['show-faces'] = 'false'; // in the first version of the plugin, in fact is was set to "false", so...
			}

			if(empty($plugin->params['fb_verb'])) {
				$options['action'] = 'like';
			} else {
				$options['action'] = 'recommend';
				$xfbml_options['action'] = 'recommend';
			}

			if(empty($plugin->params['fb_theme'])) {
				$options['colorscheme'] = 'light';
			} else {
				$options['colorscheme'] = 'dark';
				$xfbml_options['colorscheme'] = 'dark';
			}

			$fonts = array(
				0 => 'arial',
				1 => 'lucida%20grande',
				2 => 'segoe%20ui',
				3 => 'tahoma',
				4 => 'trebuchet%20ms',
				5 => 'verdana',
			);
			if(isset($fonts[ (int)$plugin->params['fb_font'] ])) {
				$options['font'] = $fonts[ (int)$plugin->params['fb_font'] ];
				$xfbml_options['font'] = $fonts[ (int)$plugin->params['fb_font'] ];
			}

			if(!empty($plugin->params['fb_send']))
				$xfbml_options['send'] = 'true';


			if($plugin->params['display_fb'] != 2) {
				$html = '<span class="hikashop_social_fb">';
				if($plugin->params['position'] == 1)
					$html = '<span class="hikashop_social_fb_right">';

				$url_options = array();
				if($plugin->params['fb_tag'] == 'iframe') {

					foreach($options as $k => $v) {
						$url_options[] = $k . '=' . urlencode($v);
					}

					$html .= '<iframe '.
						'src="//www.facebook.com/plugins/like.php?href='.urlencode($url).'&amp;send=false&amp;'.implode('&amp;', $url_options).'&amp;height=30" '.
						'scrolling="no" frameborder="0" allowTransparency="true" '.
						'style="border:none; overflow:hidden;" class="hikashop_social_fb_'.$classname.'"></iframe>';
				} else {
					foreach($xfbml_options as $k => $v) {
						$url_options[] = 'data-' . $k . '="' . urlencode($v) . '"';
					}
					if(empty($plugin->params['fb_mode'])){
						$plugin->params['fb_mode'] = 'fb-like';
					}
					$html .= '<div class="'.$plugin->params['fb_mode'].'" data-href="'.$url.'" '.implode(' ', $url_options).'></div>';
				}

				$html .= '</span>';
			}
		}
		elseif($plugin->params['display_fb'] != 2) {
			$display = 'popup';
			if($params['redirection_btn'] == 'tab') {
				$display = 'page';
			}
			$current_url = urlencode(hikashop_currentURL());
			$share_url ='https://www.facebook.com/sharer/sharer.php?u='.$current_url.'&display='.$display;
			$btn_mode = $this->rs_mode($params['redirect'], $share_url, '');

			$array_elem = array(
				'icon' => 'facebook_icon.png',
				'class' => 'fb-share-button hikasocial_facebook " data-href="'.HIKASHOP_LIVE,
				'display' => $params['display'],
				'btn_mode' => $btn_mode,
				'name' => 'Facebook'
			);

			$html = $this->btn_build($array_elem);
		}
		return $html;
	}

	function _addOpenGraph(&$element, &$plugin, $url) {
		$this->meta['property="og:title"'] = '<meta property="og:title" content="'.htmlspecialchars($element->name, ENT_COMPAT,'UTF-8').'"/> ';

		$types = array(
			0 => 'product',
			1 => 'album',
			2 => 'book',
			3 => 'company',
			4 => 'drink',
			5 => 'game',
			6 => 'movie',
			7 => 'song',
		);
		if(isset($types[ (int)$plugin->params['fb_type']]))
			$this->meta['property="og:type"']='<meta property="og:type" content="'.$types[ (int)$plugin->params['fb_type']].'"/> ';

		$config = hikashop_config();
		$uploadFolder = ltrim(JPath::clean(html_entity_decode($config->get('uploadfolder','media/com_hikashop/upload/'))), DS);
		$uploadFolder = rtrim($uploadFolder,DS) . DS;
		$this->uploadFolder_url = str_replace(DS, '/', $uploadFolder);
		$this->uploadFolder = JPATH_ROOT . DS . $uploadFolder;
		$this->thumbnail = $config->get('thumbnail', 1);
		$this->thumbnail_y = $config->get('product_image_y', $config->get('thumbnail_y'));
		$this->thumbnail_x = $config->get('product_image_x', $config->get('thumbnail_x'));
		$this->main_thumbnail_x = $this->thumbnail_x;
		$this->main_thumbnail_y = $this->thumbnail_y;
		$this->main_uploadFolder_url = $this->uploadFolder_url;
		$this->main_uploadFolder = $this->uploadFolder;

		$imageUrl = $this->_getImageURL($element);
		if(!empty($imageUrl))
			$this->meta['property="og:image"']='<meta property="og:image" content="'.$imageUrl.'" /> ';

		$this->meta['property="og:url"']='<meta property="og:url" content="'.$url.'" />';
		$description = $this->_cleanDescription($element->description);
		$this->meta['property="og:description"'] = '<meta property="og:description" content="'.$description.'"/> ';

		$jconf = JFactory::getConfig();
		if(HIKASHOP_J30)
			$siteName = $jconf->get('sitename');
		else
			$siteName = $jconf->getValue('config.sitename');
		$this->meta['property="og:site_name"'] = '<meta property="og:site_name" content="'.htmlspecialchars($siteName, ENT_COMPAT,'UTF-8').'"/> ';

		if(!empty($plugin->params['admin']))
			$this->meta['property="fb:admins"'] = '<meta property="fb:admins" content="'.htmlspecialchars($plugin->params['admin'], ENT_COMPAT,'UTF-8').'" />';

	}

	function _getElementInfo() {
		if(version_compare(JVERSION,'3.0','>=')) {
			$app = JFactory::getApplication();
			$option = $app->input->getVar('option');
			$ctrl = $app->input->getVar('ctrl');
			$task = $app->input->getVar('task');
		} else {
			$option = JRequest::getVar('option');
			$ctrl = JRequest::getVar('ctrl');
			$task = JRequest::getVar('task');
		}

		$ret = new stdClass();

		if(in_array($option, array('com_hikamarket', '')) && $ctrl == 'vendor' && $task == 'show') {
			$element = $this->_getVendorInfo();
			$ret->type = 'vendor';
			$ret->id = (int)$element->vendor_id;
			$ret->name = $element->vendor_name;
			$ret->description = $element->vendor_description;
			$ret->url_canonical = @$element->vendor_canonical;
			$ret->image = $element->vendor_image;
		} else {
			$element = $this->_getProductInfo();
			$ret->type = 'product';
			$ret->id = (int)$element->product_id;
			$ret->name = $element->product_name;
			$ret->description = $element->product_description;
			$ret->url_canonical = @$element->product_canonical;
		}

		return $ret;
	}

	function _getProductInfo() {
		static $product = null;
		if($product !== null)
			return $product;

		$app = JFactory::getApplication();
		$product_id = (int)hikashop_getCID('product_id');
		$menus = $app->getMenu();
		$menu = $menus->getActive();
		global $Itemid;
		if(empty($menu) && !empty($Itemid)) {
			$menus->setActive($Itemid);
			$menu = $menus->getItem($Itemid);
		}
		if(empty($product_id) && is_object($menu)) {
			if(!empty($menu)) {
				if(HIKASHOP_J30)
					$menuParams = $menu->getParams();
				else
					$menuParams = @$menu->params;
			}
			$product_id = $menuParams->get('product_id');
		}
		$product = false;
		if(!empty($product_id)) {
			$productClass = hikashop_get('class.product');
			$product = $productClass->get($product_id);
			if(!empty($product) && $product->product_type == 'variant') {
				$product = $productClass->get($product->product_parent_id);
			}
		}
		return $product;
	}

	function _getVendorInfo() {
		static $vendor = null;
		if($vendor !== null)
			return $vendor;

		$app = JFactory::getApplication();
		$vendor_id = (int)hikashop_getCID('vendor_id');
		$menus = $app->getMenu();
		$menu = $menus->getActive();
		if(empty($menu) && !empty($Itemid)) {
			$menus->setActive($Itemid);
			$menu = $menus->getItem($Itemid);
		}
		if(empty($vendor_id) && is_object($menu)) {
			if(!empty($menu)) {
				if(HIKASHOP_J30)
					$menuParams = $menu->getParams();
				else
					$menuParams = @$menu->params;
			}
			$vendor_id = $menuParams->get('vendor_id');
		}
		$vendor = false;
		if(!empty($vendor_id)) {
			$vendorClass = hikamarket::get('class.vendor');
			$vendor = $vendorClass->get($vendor_id);
		}
		return $vendor;
	}

	function _getImageURL($element) {
		$config =& hikashop_config();
		$uploadFolder = ltrim(JPath::clean(html_entity_decode($config->get('uploadfolder','media/com_hikashop/upload/'))),DS);
		$uploadFolder = rtrim($uploadFolder,DS).DS;
		$this->uploadFolder_url = str_replace(DS,'/',$uploadFolder);
		$this->main_uploadFolder_url = $this->uploadFolder_url;

		$imageUrl = '';

		if($element->type == 'vendor') {
			$imageUrl = JURI::base() . $this->main_uploadFolder_url . ltrim($element->image, '/');
		} else {
			$product_id = (int)$element->id;

			$db = JFactory::getDBO();
			$queryImage = 'SELECT * FROM '.hikashop_table('file').' WHERE file_ref_id='.$product_id.'  AND file_type=\'product\' ORDER BY file_ordering ASC, file_id ASC';
			$db->setQuery($queryImage);
			$image = $db->loadObject();
			if(empty($image)) {
				$queryImage = 'SELECT * FROM '.hikashop_table('file').' as a LEFT JOIN '.hikashop_table('product').' as b ON a.file_ref_id=b.product_id  WHERE product_parent_id='.$product_id.'  AND file_type=\'product\' ORDER BY file_ordering ASC, file_id ASC';
				$db->setQuery($queryImage);
				$image = $db->loadObject();
			}
			if(!empty($image))
				$imageUrl = JURI::base() . $this->main_uploadFolder_url . ltrim($image->file_path, '/');
		}
		return $imageUrl;
	}

	function _cleanDescription($description, $max = 0){
		$description = preg_replace('#\{(load)?(module|position|modulepos) +[a-z_0-9]+\}#i','',$description);

		$description = preg_replace('#\{(slider|tab|modal|tip|article|snippet)(-[a-z_0-9]+)? +[a-z_ 0-9\|]+\}.*\{\/(slider|tab|modal|tip|article|snippet)s?(-[a-z_0-9]+)?\}#Usi','',$description);

		$description = htmlspecialchars(strip_tags($description), ENT_COMPAT,'UTF-8');

		if($max && strlen($description) > $max)
			$description = substr($description, 0, $max-4).'...';
		return $description;
	}

	function btn_build($array_elem){
	$btn_html = ''.
		'<span class="'.$array_elem['display'].' hikasocial_btn '.$array_elem['class'].'">'.
			$array_elem['btn_mode'].
				'<span class="hikasocial_icon">'.
					'<img src="'.HIKASHOP_IMAGES.'icons/'.$array_elem['icon'].'" >'.
				'</span>'.
				'<span class="hikasocial_name">'.$array_elem['name'].'</span>'.
			'</a>'.
		'</span>';

		return $btn_html;
	}

	function rs_mode($redirect, $url, $hashtag) {

		if ($redirect == 'popup') {
			$js = "window.open('".$url.$hashtag."','popup','width=600,height=600'); return false;";
			$js = 'onclick="'.$js.'"';

			$btn_mode = '<a href="'.$url.$hashtag.'" target="popup" '.$js.'>';
		}

		else 
			$btn_mode = '<a href="'.$url.$hashtag.'" target="_blank" >';

		return $btn_mode;
	}
}
