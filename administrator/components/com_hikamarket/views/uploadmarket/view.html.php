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
class uploadmarketViewuploadmarket extends hikamarketView {
	const ctrl = 'upload';
	const name = 'HIKA_UPLOAD';
	const icon = 'upload';

	public function display($tpl = null, $params = array()) {
		$this->params =& $params;
		$fct = $this->getLayout();
		if(method_exists($this, $fct)) {
			if($this->$fct() === false)
				return;
		}
		parent::display($tpl);
	}

	public function sendfile() {
		$uploadConfig = hikaInput::get()->getVar('uploadConfig', null);
		if(empty($uploadConfig) || !is_array($uploadConfig))
			return false;

		$this->assignRef('uploadConfig', $uploadConfig);
		$uploader = hikaInput::get()->getCmd('uploader', '');
		$this->assignRef('uploader', $uploader);
		$field = hikaInput::get()->getCmd('field', '');
		$this->assignRef('field', $field);
	}

	public function galleryimage() {
		hikamarket::loadJslib('otree');

		$app = JFactory::getApplication();
		$config = hikamarket::config();
		$this->assignRef('config', $config);
		$shopConfig = hikamarket::config(false);
		$this->assignRef('shopConfig', $shopConfig);

		$this->paramBase = HIKAMARKET_COMPONENT.'.'.$this->getName().'.gallery';

		$uploadConfig = hikaInput::get()->getVar('uploadConfig', null);
		if(empty($uploadConfig) || !is_array($uploadConfig))
			return false;
		$this->assignRef('uploadConfig', $uploadConfig);
		$uploader = hikaInput::get()->getCmd('uploader', '');
		$this->assignRef('uploader', $uploader);
		$field = hikaInput::get()->getCmd('field', '');
		$this->assignRef('field', $field);

		$uploadFolder = ltrim(JPath::clean(html_entity_decode($shopConfig->get('uploadfolder'))),DS);
		$uploadFolder = rtrim($uploadFolder,DS).DS;
		$basePath = JPATH_ROOT.DS.$uploadFolder.DS;

		$pageInfo = new stdClass();
		$pageInfo->limit = new stdClass();
		$pageInfo->limit->value = $app->getUserStateFromRequest( $this->paramBase.'.list_limit', 'limit', 20, 'int' );
		$pageInfo->limit->start = $app->getUserStateFromRequest( $this->paramBase.'.limitstart', 'limitstart', 0, 'int' );
		$pageInfo->search = $app->getUserStateFromRequest( $this->paramBase.'.search', 'search', '', 'string');

		$this->assignRef('pageInfo', $pageInfo);

		jimport('joomla.filesystem.folder');
		if(!JFolder::exists($basePath))
			JFolder::create($basePath);

		$galleryHelper = hikamarket::get('shop.helper.gallery');
		$galleryHelper->setRoot($basePath);
		$this->assignRef('galleryHelper', $galleryHelper);

		$folder = str_replace('|', '/', hikaInput::get()->getString('folder', ''));
		$destFolder = rtrim($folder, '/\\');
		if(!$galleryHelper->validatePath($destFolder))
			$destFolder = '';
		if(!empty($destFolder)) $destFolder .= '/';
		$this->assignRef('destFolder', $destFolder);

		$galleryOptions = array(
			'filter' => '.*' . str_replace(array('.','?','*','$','^'), array('\.','\?','\*','$','\^'), $pageInfo->search) . '.*',
			'offset' => $pageInfo->limit->start,
			'length' => $pageInfo->limit->value
		);
		$this->assignRef('galleryOptions', $galleryOptions);

		$treeContent = $galleryHelper->getTreeList(null, $destFolder);
		$this->assignRef('treeContent', $treeContent);

		$dirContent = $galleryHelper->getDirContent($destFolder, $galleryOptions);
		$this->assignRef('dirContent', $dirContent);

		jimport('joomla.html.pagination');
		$pagination = new JPagination( $galleryHelper->filecount, $pageInfo->limit->start, $pageInfo->limit->value );
		$this->assignRef('pagination', $pagination);
	}

	public function image_entry() {
		$imageHelper = hikamarket::get('shop.helper.image');
		$this->assignRef('imageHelper', $imageHelper);
		$popup = hikamarket::get('shop.helper.popup');
		$this->assignRef('popup', $popup);
	}
}
