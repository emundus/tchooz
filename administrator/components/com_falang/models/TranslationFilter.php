<?php
/**
 * @package     Falang for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2010-2023. Faboba.com All rights reserved.
 */

// No direct access to this file
defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Object\CMSObject;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

function getTranslationFilters($catid, $contentElement)
{
    if (!$contentElement) return array();
    $filterNames=$contentElement->getAllFilters();

    //reset keyword filter is add with keyword search since joomla 3.0
    if (FALANG_J30) {} else {
        if (count($filterNames)>0) {
            $filterNames["reset"]="reset";
        }
    }

    $filters=array();
    foreach ($filterNames as $key=>$value){
        $filterType = "translation".ucfirst(strtolower($key))."Filter" ;
        $classFile = JPATH_SITE."/administrator/components/com_falang/contentelements/$filterType.php" ;
        if (!class_exists($filterType)){
            if (file_exists($classFile )) include_once($classFile);
            if (!class_exists($filterType)) {
                continue;
            }
        }
        $filters[strtolower($key)] =  new $filterType($contentElement);
    }
    return $filters;
}


class translationFilter
{
    var $filterNullValue;
    var $filterType;
    var $filter_value;
    var $filterField = false;
    var $tableName = "";
    var $filterHTML="";

    var $_createdField;//sbou5
    var $_modifiedField;//sbou5

    // Should we use session data to remember previous selections?
    var $rememberValues = true;

    public function __construct($contentElement=null){
        $jinput = Factory::getApplication()->input;
        if (intval($jinput->get('filter_reset',0,'INT'))){
            $this->filter_value =  $this->filterNullValue;
        }
        else if ($this->rememberValues){
            // TODO consider making the filter variable name content type specific
            $app	= Factory::getApplication();
            $this->filter_value = $app->getUserStateFromRequest($this->filterType.'_filter_value', $this->filterType.'_filter_value', $this->filterNullValue);
        }
        else {
            $this->filter_value =  $jinput->get( $this->filterType.'_filter_value', $this->filterNullValue );
        }
        //echo $this->filterType.'_filter_value = '.$this->filter_value."<br/>";
        $this->tableName = isset($contentElement)?$contentElement->getTableName():"";
    }

    function _createFilter(){
        if (!$this->filterField ) return "";
        $filter="";

        //since joomla 3.0 filter_value can be '' too not only filterNullValue
        if (isset($this->filter_value) && strlen($this->filter_value) > 0  && $this->filter_value!=$this->filterNullValue){
            if (is_int($this->filter_value)) {
                $filter = "c.".$this->filterField."=$this->filter_value";
            } else {
                $filter = "c.".$this->filterField."='".$this->filter_value."'";
            }
        }
        return $filter;
    }

    function _createfilterHTML(){ return "";}
}

class translationResetFilter extends translationFilter
{
    public function __construct($contentElement){
        $this->filterNullValue=-1;
        $this->filterType="reset";
        $this->filterField = "";
        parent::__construct($contentElement);
    }

    function _createFilter(){
        return "";
    }


    /**
     * Creates javascript session memory reset action
     *
     */
    function _createfilterHTML(){
        $reset["title"]= Text::_('COM_FALANG_FILTER_RESET');
        $reset['position'] = 'sidebar';
        $reset["html"] = '<input type=\'hidden\' name=\'filter_reset\' id=\'filter_reset\' value=\'0\' /><button class="btn hasTooltip" onclick="document.getElementById(\'filter_reset\').value=1;document.adminForm.submit()" type="button" data-original-title="'.Text::_("COM_FALANG_FILTER_RESET").'"> <i class="icon-remove"></i></button>';
        return $reset;

    }

}

class translationFrontpageFilter extends translationFilter
{
    public function __construct($contentElement){
        $this->filterNullValue=-1;
        $this->filterType="frontpage";
        $this->filterField = $contentElement->getFilter("frontpage");
        parent::__construct($contentElement);
    }

    function _createFilter(){
        if (!$this->filterField) return "";
        $filter="";

        //since joomla 3.0 filter_value can be '' too not only filterNullValue
        if (isset($this->filter_value) && strlen($this->filter_value) > 0 && $this->filter_value!=$this->filterNullValue){
            $db = Factory::getDBO();
            $sql = "SELECT content_id FROM #__content_frontpage order by ordering";
            $db->setQuery($sql);
            $ids = $db->loadColumn();

            if (is_null($ids)){
                $ids = array();
            }
            $ids[] = -1;
            $idstring = implode(",",$ids);
            $not = "";
            if ($this->filter_value==0){
                $not = " NOT ";
            }
            $filter =   " c.".$this->filterField.$not." IN (".$idstring.") ";
        }
        return $filter;
    }


    /**
     * Creates frontpage filter
     *
     * @param unknown_type $filtertype
     * @param unknown_type $contentElement
     * @return unknown
     */
    function _createfilterHTML(){

        if (!$this->filterField) return "";

        $FrontpageOptions=array();

        $FrontpageOptions[] = HTMLHelper::_('select.option', 1, Text::_('JYES'));
        $FrontpageOptions[] = HTMLHelper::_('select.option', 0, Text::_('JNO'));
        $frontpageList=array();

        $frontpageList["title"]= Text::_('COM_FALANG_SELECT_FRONTPAGE');
        $frontpageList["position"] = 'sidebar';
        $frontpageList["name"]= 'frontpage_filter_value';
        $frontpageList["type"]= 'frontpage';
        $frontpageList["options"] = $FrontpageOptions;
        $frontpageList["value"] = isset($this->filter_value)?$this->filter_value:null;

        return $frontpageList;

    }

}

class translationArchiveFilter extends translationFilter
{
    public function __construct($contentElement){
        $this->filterNullValue=-1;
        $this->filterType="archive";
        $this->filterField = $contentElement->getFilter("archive");
        parent::__construct($contentElement);
    }

    function _createFilter(){
        if (!$this->filterField) return "";
        $filter="";
        //since joomla 3.0 filter_value can be '' too not only filterNullValue
        if (isset($this->filter_value) && strlen($this->filter_value) > 0 && $this->filter_value!=$this->filterNullValue){
            if ($this->filter_value==0){
                $filter =   " c.".$this->filterField." >=0 ";
            }
            else {
                $filter =   " c.".$this->filterField." =-1 ";
            }
        }
        return $filter;
    }


    /**
     * Creates archive filter
     *
     * @param unknown_type $filtertype
     * @param unknown_type $contentElement
     * @return unknown
     */
    function _createfilterHTML(){
        $db = Factory::getDBO();

        if (!$this->filterField) return "";

        $FrontpageOptions=array();

        $FrontpageOptions[] = HTMLHelper::_('select.option', 1, Text::_('JYES'));
        $FrontpageOptions[] = HTMLHelper::_('select.option', 0, Text::_('JNO'));
        $frontpageList=array();

        $frontpageList["title"]= Text::_('COM_FALANG_SELECT_ARCHIVE');
        $frontpageList["position"] = 'sidebar';
        $frontpageList["name"]= 'archive_filter_value';
        $frontpageList["type"]= 'archive';
        $frontpageList["options"] = $FrontpageOptions;
        $frontpageList["value"] = isset($this->filter_value)?$this->filter_value:null;


        return $frontpageList;

    }

}

class translationCategoryFilter extends translationFilter
{
    var $section_filter_value;

    public function __construct($contentElement){
        $this->filterNullValue=-1;
        $this->filterType="category";
        $this->filterField = $contentElement->getFilter("category");
        parent::__construct($contentElement);

    }


    function _createFilter(){
        if (!$this->filterField) return "";
        $filter="";

        //since joomla 3.0 filter_value can be '' too not only filterNullValue
        if (isset($this->filter_value) && strlen($this->filter_value) > 0  && $this->filter_value!=$this->filterNullValue){
            $filter =   " c.".$this->filterField." = ".$this->filter_value;
        }
        return $filter;
    }

    /*
     * */
    function _createfilterHTML(){
        if (!$this->filterField) return "";

        $allCategoryOptions = array();
        $extension = 'com_'.$this->tableName;

        $options = HTMLHelper::_('category.options', $extension);

        $options = array_merge($allCategoryOptions, $options);

        $categoryList=array();

        $categoryList["title"]= Text::_('COM_FALANG_SELECT_CATEGORY');
        $categoryList["position"] = 'sidebar';
        $categoryList["name"]= 'category_filter_value';
        $categoryList["type"]= 'category';
        $categoryList["options"] = $options;
        $categoryList["value"] = isset($this->filter_value)?$this->filter_value:null;

        return $categoryList;

    }

}

class translationAuthorFilter extends translationFilter
{
    public function __construct($contentElement){
        $this->filterNullValue=-1;
        $this->filterType="author";
        $this->filterField = $contentElement->getFilter("author");
        parent::__construct($contentElement);
    }


    function _createfilterHTML(){
        $db = Factory::getDBO();

        if (!$this->filterField) return "";
        $AuthorOptions=array();

        //	$sql = "SELECT c.id, c.title FROM #__categories as c ORDER BY c.title";
        $sql = "SELECT DISTINCT auth.id, auth.username FROM #__users as auth, #__".$this->tableName." as c
			WHERE c.".$this->filterField."=auth.id ORDER BY auth.username";
        $db->setQuery($sql);
        $cats = $db->loadObjectList();
        $catcount=0;
        foreach($cats as $cat){
            $AuthorOptions[] = HTMLHelper::_('select.option', $cat->id,$cat->username);
            $catcount++;
        }
        $Authorlist=array();

        $Authorlist["title"]=Text::_('COM_FALANG_SELECT_AUTHOR');
        $Authorlist["position"] = 'sidebar';
        $Authorlist["name"]= 'author_filter_value';
        $Authorlist["type"]= 'author';
        $Authorlist["options"] = $AuthorOptions;
        $Authorlist["value"] = isset($this->filter_value)?$this->filter_value:null;

        return $Authorlist;

    }

}


class translationExtensionFilter extends translationFilter
{

    public function __construct($contentElement){
        $this->filterNullValue='';
        $this->filterType="extension";
        $this->filterField = $contentElement->getFilter("extension");
        parent::__construct($contentElement);
    }


    function _createfilterHTML(){
        $db = Factory::getDBO();

        if (!$this->filterField) return "";
        $ExtensionOptions=array();

        $query = $db->getQuery(true);
        $query
            ->select('DISTINCT c.extension')
            ->from('#__'.$this->tableName.' as c')
            ->where('c.'.$this->filterField.' != '.$db->q('system'))
            ->order('c.extension');

        $db->setQuery($query);
        $extensions = $db->loadObjectList();
        $extcount=0;
        foreach($extensions as $extension){
            $ExtensionOptions[] = HTMLHelper::_('select.option', $extension->extension,$extension->extension);
            $extcount++;
        }
        $Extensionlist=array();

        $Extensionlist["title"] = Text::_('COM_FALANG_SELECT_EXTENSION');
        $Extensionlist["position"] = 'sidebar';
        $Extensionlist["name"] = 'extension_filter_value';
        $Extensionlist["type"] = 'extension';
        $Extensionlist["options"] = $ExtensionOptions;
        $Extensionlist["value"] = isset($this->filter_value)?$this->filter_value:null;

        return $Extensionlist;

    }

}


class translationKeywordFilter extends translationFilter
{
    public function __construct($contentElement){
        $this->filterNullValue="";
        $this->filterType="keyword";
        $this->filterField = $contentElement->getFilter("keyword");
        parent::__construct($contentElement);
    }


    function _createFilter(){
        if (!$this->filterField) return "";
        $filter="";
        if ($this->filter_value!=""){
            $db = Factory::getDBO();
            $filter =  "LOWER(c.".$this->filterField." ) LIKE '%".$db->escape( $this->filter_value, true )."%'";
        }
        return $filter;
    }

    /**
     * Creates Keyword filter
     *
     * @param unknown_type $filtertype
     * @param unknown_type $contentElement
     * @return unknown
     */
    function _createfilterHTML(){
        if (!$this->filterField) return "";
        $Keywordlist=array();
        $Keywordlist["title"]= Text::_('COM_FALANG_KEYWORD_FILTER');

        $Keywordlist["position"] = 'top';
        $Keywordlist['html'] = '<div class="btn-group mr-2">';
        $Keywordlist['html'] .= '<div class="input-group">';
        $Keywordlist['html'] .= '<label class="sr-only" for="keyword_filter_value">'.$Keywordlist["title"].'</label>';
        $Keywordlist['html'] .= '<input type="text" name="keyword_filter_value" id="keyword_filter_value" title="'.$Keywordlist["title"].'" class="form-control" placeholder="'.$Keywordlist["title"].'" value="'.$this->filter_value.'" onChange="document.adminForm.submit();" />';
        $Keywordlist['html'] .= '</div>';
        $Keywordlist['html'] .= '<span class="input-group-append">';
        $Keywordlist['html'] .= '<button class="btn btn-primary hasTooltip" type="submit" data-original-title="'.Text::_('SEARCH').'"><span class="fa fa-search" aria-hidden="true"></span></button>';
        $Keywordlist['html'] .= '</span>';
        $Keywordlist['html'] .= '</div>';
        $Keywordlist['html'] .= '<button type="button" class="btn btn-primary hasTooltip js-stools-btn-clear mr-2" onclick="document.id(\'keyword_filter_value\').value=\'\';this.form.submit();" title="'.Text::_('JSEARCH_FILTER_CLEAR').'">'.Text::_('JSEARCH_FILTER_CLEAR').'</button>';
        $Keywordlist['html'] .= '';

        return $Keywordlist;
    }

}

class translationModuleFilter  extends translationFilter
{
    public function __construct($contentElement){
        $this->filterNullValue=-1;
        $this->filterType="module";
        $this->filterField = $contentElement->getFilter("module");
        parent::__construct($contentElement);
    }

    function _createFilter(){
        $filter = "c.".$this->filterField."<99";
        return $filter;
    }

    function _createfilterHTML(){
        return "";
    }
}

//new 2.8.2 filter by module type
class translationModuletypeFilter  extends translationFilter
{
    public function __construct($contentElement){
        $this->filterNullValue="-+-+";
        $this->filterType="moduletype";
        $this->filterField = $contentElement->getFilter("moduletype");
        parent::__construct($contentElement);
    }

    function _createFilter(){
        if (!$this->filterField ) return "";
        $filter="";

        //since joomla 3.0 filter_value can be '' too not only filterNullValue
        if (isset($this->filter_value) && strlen($this->filter_value) > 0  && $this->filter_value!=$this->filterNullValue){
            $filter = "c.".$this->filterField."='".$this->filter_value."'";
        }
        return $filter;
    }

    function _createfilterHTML(){
        $db = Factory::getDBO();
        $lang = Factory::getLanguage();

        if (!$this->filterField) return "";
        $MmoduletypeOptions=array();

        $sql = "SELECT DISTINCT module FROM #__modules WHERE client_id = 0 ORDER BY module ASC";
        $db->setQuery($sql);
        $cats = $db->loadObjectList();
        $catcount=0;
        foreach($cats as $cat){
            //get translate name system by administrator/components/com_modules/models/modules.php translate method
            $extension = $cat->module;
            $clientPath = JPATH_SITE;
            $source = $clientPath . "/modules/$extension";
            $lang->load("$extension.sys", $clientPath, null, false, true)
            || $lang->load("$extension.sys", $source, null, false, true);
            $name = Text::_($cat->module);
            $MmoduletypeOptions[] = HTMLHelper::_('select.option', $cat->module, $name);
            $catcount++;
        }
        $Menutypelist=array();

        $Menutypelist["title"] = Text::_('COM_FALANG_SELECT_MODULE');
        $Menutypelist["position"] = 'sidebar';
        $Menutypelist["name"]= 'moduletype_filter_value';
        $Menutypelist["type"]= 'moduletype';
        $Menutypelist["options"] = $MmoduletypeOptions;
        $Menutypelist["value"] = isset($this->filter_value)?$this->filter_value:null;

        return $Menutypelist;

    }
}

class translationMenutypeFilter  extends translationFilter
{
    public function __construct($contentElement){
        $this->filterNullValue="-+-+";
        $this->filterType="menutype";
        $this->filterField = $contentElement->getFilter("menutype");
        parent::__construct($contentElement);
    }

    function _createFilter(){
        if (!$this->filterField ) return "";
        $filter="";

        //since joomla 3.0 filter_value can be '' too not only filterNullValue
        if (isset($this->filter_value) && strlen($this->filter_value) > 0  && $this->filter_value!=$this->filterNullValue){
            $filter = "c.".$this->filterField."='".$this->filter_value."'";
        }
        return $filter;
    }

    function _createfilterHTML(){
        $db = Factory::getDBO();

        if (!$this->filterField) return "";
        $MenutypeOptions=array();

        //dont't add root menu to the list != 1
        $sql = "SELECT DISTINCT mt.menutype FROM #__menu as mt WHERE id != 1 ORDER BY menutype ASC";
        $db->setQuery($sql);
        $cats = $db->loadObjectList();
        $catcount=0;
        foreach($cats as $cat){
            $MenutypeOptions[] = HTMLHelper::_('select.option', $cat->menutype,$cat->menutype);
            $catcount++;
        }
        $Menutypelist=array();

        $Menutypelist["title"]= Text::_('COM_FALANG_SELECT_MENU');
        $Menutypelist["position"] = 'sidebar';
        $Menutypelist["name"]= 'menutype_filter_value';
        $Menutypelist["type"]= 'menutype';
        $Menutypelist["options"] = $MenutypeOptions;
        $Menutypelist["value"] = isset($this->filter_value)?$this->filter_value:null;

        return $Menutypelist;

    }
}

/**
 * filters translations based on creation/modification date of original
 *
 */
class translationChangedFilter extends translationFilter
{
    public function __construct($contentElement){
        $this->filterNullValue=-1;
        $this->filterType="lastchanged";
        $this->filterField = $contentElement->getFilter("changed");
        list($this->_createdField,$this->_modifiedField) = explode("|",$this->filterField);
        parent::__construct($contentElement);
    }

    function _createFilter(){
        if (!$this->filterField) return "";
        $filter="";

        //since joomla 3.0 filter_value can be '' too not only filterNullValue
        if (isset($this->filter_value) && strlen($this->filter_value) > 0 && $this->filter_value!=$this->filterNullValue && $this->filter_value==1){
            // translations must be created after creation date so no need to check this!
            $filter = "( c.$this->_modifiedField>0 AND jfc.modified < c.$this->_modifiedField)" ;
        }
        else if (isset($this->filter_value) && strlen($this->filter_value) > 0 && $this->filter_value!=$this->filterNullValue){
            $filter = "( ";
            $filter .= "( c.$this->_modifiedField>0 AND jfc.modified >= c.$this->_modifiedField)" ;
            $filter .= " OR ( c.$this->_modifiedField=0 AND jfc.modified >= c.$this->_createdField)" ;
            $filter .= " )";
        }

        return $filter;
    }


    function _createfilterHTML(){
        $db = Factory::getDBO();

        if (!$this->filterField) return "";
        $ChangedOptions=array();

        $ChangedOptions[] = HTMLHelper::_('select.option', 1, Text::_('COM_FALANG_FILTER_ORIGINAL_NEWER'));
        $ChangedOptions[] = HTMLHelper::_('select.option', 0, Text::_('COM_FALANG_FILTER_TRANSLATION_NEWER'));

        $ChangedList=array();
        $ChangedList["title"] = Text::_('COM_FALANG_SELECT_TRANSLATION_AGE');
        $ChangedList["position"] = 'sidebar';
        $ChangedList["name"]= 'lastchanged_filter_value';
        $ChangedList["type"]= 'lastchanged';
        $ChangedList["options"] = $ChangedOptions;
        $ChangedList["value"] = isset($this->filter_value)?$this->filter_value:null;

        return $ChangedList;
    }
}

/**
 * Look for unpublished translations - i.e. no translation or translation is unpublished
 * Really only makes sense with a specific language selected
 *
 */

class translationTrashFilter extends translationFilter
{
    public function __construct($contentElement){
        $this->filterNullValue=-1;
        $this->filterType="trash";
        $this->filterField = $contentElement->getFilter("trash");
        parent::__construct($contentElement);
    }

    function _createFilter(){
        // -1 = archive, -2 = trash
        $filter = "c.".$this->filterField.">=-1";
        return $filter;
    }

    function _createfilterHTML(){
        return "";
    }

}

/**
 * Look for unpublished translations - i.e. no translation or translation is unpublished
 * Really only makes sense with a specific language selected
 *
 */

class translationPublishedFilter extends translationFilter
{
    public function __construct($contentElement){
        $this->filterNullValue='';
        $this->filterType="published";
        $this->filterField = $contentElement->getFilter("published");
        parent::__construct($contentElement);
    }

    function _createFilter(){
        if (!$this->filterField) return "";
        $filter="";
        if ($this->filter_value!=$this->filterNullValue){
            if ($this->filter_value==1){
                $filter = "jfc.".$this->filterField."=$this->filter_value";
            }
            else if ($this->filter_value==0){
                $filter = " ( jfc.".$this->filterField."=$this->filter_value AND jfc.reference_field IS NOT NULL ) ";
            }
            else if ($this->filter_value==2){
                $filter = " jfc.reference_field IS NULL  ";
            }
            else if ($this->filter_value==3){
                $filter = " jfc.reference_field IS NOT NULL ";
            }
        }

        return $filter;
    }

    function _createfilterHTML(){
        $db = Factory::getDBO();

        if (!$this->filterField) return "";

        $PublishedOptions=array();

        $PublishedOptions[] = HTMLHelper::_('select.option', 3, Text::_('COM_FALANG_FILTER_AVAILABLE'));
        $PublishedOptions[] = HTMLHelper::_('select.option', 1, Text::_('COM_FALANG_TITLE_PUBLISHED'));
        $PublishedOptions[] = HTMLHelper::_('select.option', 0, Text::_('COM_FALANG_TITLE_UNPUBLISHED'));
        $PublishedOptions[] = HTMLHelper::_('select.option', 2, Text::_('COM_FALANG_FILTER_MISSING'));

        $publishedList=array();


        $publishedList["title"]= Text::_('COM_FALANG_SELECT_TRANSLATION_AVAILABILITY');
        $publishedList["position"] = 'sidebar';
        $publishedList["name"]= 'published_filter_value';
        $publishedList["type"]= 'published';
        $publishedList["options"] = $PublishedOptions;
        $publishedList["value"] = isset($this->filter_value)?$this->filter_value:null;

        return $publishedList;

    }

}

class TranslateParams
{
    var $origparams;
    var $defaultparams;
    var $transparams;
    var $fields;
    var $fieldname;

    var $trans_modelItem;


    public function __construct($original, $translation, $fieldname, $fields=null){

        $this->origparams =  $original;
        $this->transparams = $translation;
        $this->fieldname = $fieldname;
        $this->fields = $fields;
    }

    public function showOriginal()
    {
        echo $this->origparams;

    }

    public function showDefault()
    {
        echo "";

    }

    function editTranslation(){
        $returnval = array( "editor_".$this->fieldname, "refField_".$this->fieldname );
        $conf = Factory::getApplication()->getConfig();
        $editor = $conf->get('editor');
        $wysiwygeditor = \Joomla\CMS\Editor\Editor::getInstance($editor);
        $wysiwygeditor->display("editor_" . $this->fieldname, $this->transparams, "refField_" . $this->fieldname, '100%;', '300', '70', '15');
        echo $this->transparams;
        return $returnval;
    }
}

class TranslateParams_xml extends TranslateParams
{

    function showOriginal()
    {
        $output = "";
        $fieldname = 'orig_' . $this->fieldname;
        $output .= $this->origparams->render($fieldname);
        $output .= <<<SCRIPT
		<script language='javascript'>
		function copyParams(srctype, srcfield){
			var orig = document.getElementsByTagName('select');
			for (var i=0;i<orig.length;i++){
				if (orig[i].name.indexOf(srctype)>=0 && orig[i].name.indexOf("[")>=0){
					// TODO double check the str replacement only replaces one instance!!!
					targetName = orig[i].name.replace(srctype,"refField");
					target = document.getElementsByName(targetName);
					if (target.length!=1){
						alert(targetName+" problem "+target.length);
					}
					else {
						target[0].selectedIndex = orig[i].selectedIndex;
					}
				}
			}
			var orig = document.getElementsByTagName('input');
			for (var i=0;i<orig.length;i++){
				if (orig[i].name.indexOf(srctype)>=0 && orig[i].name.indexOf("[")>=0){
					// treat radio buttons differently
					if (orig[i].type.toLowerCase()=="radio"){
						//alert( orig[i].id+" "+orig[i].checked);
						targetId = orig[i].id;
						if (targetId){
							targetId = targetId.replace(srctype,"refField");
							target = document.getElementById(targetId);
							if (!target){
								alert("missing target for radio button "+orig[i].name);
							}
							else {
								target.checked = orig[i].checked;
							}
						}
						else {
							alert("missing id for radio button "+orig[i].name);
						}
					}
					else {
						// TODO double check the str replacement only replaces one instance!!!
						targetName = orig[i].name.replace(srctype,"refField");
						target = document.getElementsByName(targetName);
						if (target.length!=1){
							alert(targetName+" problem "+target.length);
						}
						else {
							target[0].value = orig[i].value;
						}
					}
				}
			}
			var orig = document.getElementsByTagName('textarea');
			for (var i=0;i<orig.length;i++){
				if (orig[i].name.indexOf(srctype)>=0 && orig[i].name.indexOf("[")>=0){
					// TODO double check the str replacement only replaces one instance!!!
					targetName = orig[i].name.replace(srctype,"refField");
					target = document.getElementsByName(targetName);
					if (target.length!=1){
						alert(targetName+" problem "+target.length);
					}
					else {
						target[0].value = orig[i].value;
					}
				}
			}
		}

		var orig = document.getElementsByTagName('select');
		for (var i=0;i<orig.length;i++){
			if (orig[i].name.indexOf("$fieldname")>=0){
				orig[i].disabled = true;
			}
		}
		var orig = document.getElementsByTagName('input');
		for (var i=0;i<orig.length;i++){
			if (orig[i].name.indexOf("$fieldname")>=0){
				orig[i].disabled = true;
			}
		}
		</script>
SCRIPT;
        echo $output;

    }

    function showDefault()
    {
        $output = "<span style='display:none'>";
        $output .= $this->defaultparams->render("defaultvalue_" . $this->fieldname);
        $output .= "</span>\n";
        echo $output;

    }
    function editTranslation(){
        echo '<div class="form-horizontal translation-field-'.$this->fieldname.'">';
        echo $this->transparams->render("refField_".$this->fieldname);
        echo '</div';
        return false;
    }
}

class JFMenuParams extends CMSObject
{

    var $form = null;

    function __construct($form=null, $item=null)
    {
        $this->form = $form;

    }

    function render($type)
    {
        $this->menuform = $this->form;
        echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'collapse0'));
        $i = 0;


        $fieldSets = $this->form->getFieldsets('request');
        if ($fieldSets)
        {
            foreach ($fieldSets as $name => $fieldSet)
            {
                $hidden_fields = '';
                $label = !empty($fieldSet->label) ? $fieldSet->label : 'COM_MENUS_' . $name . '_FIELDSET_LABEL';
                echo HTMLHelper::_('uitab.addTab', 'myTab', 'collapse' . ($i++), addslashes(Text::_($label)), true);
                ?>
                <fieldset class="options-form" id="fieldset-<?php echo $name;?>">
                    <legend><?php echo Text::_($label)?> </legend>
                    <?php foreach ($this->form->getFieldset($name) as $field){ ?>
                        <?php if (!$field->hidden)
                        {
                            echo $field->renderField();
                        }
                        else
                        {
                            $hidden_fields.= $field->input;
                            ?>
                        <?php } ?>

                    <?php } ?>

                    <?php echo $hidden_fields; ?>
                </fieldset>

                <?php
                echo HTMLHelper::_('uitab.endTab');
            }
        }

        $paramsfieldSets = $this->form->getFieldsets('params');
        if ($paramsfieldSets)
        {
            foreach ($paramsfieldSets as $name => $fieldSet)
            {
                $label = !empty($fieldSet->label) ? $fieldSet->label : 'COM_MENUS_' . $name . '_FIELDSET_LABEL';
                echo HTMLHelper::_('uitab.addTab', 'myTab', 'collapse' . ($i++),Text::_($label), true);

                ?>
                <fieldset class="options-form" id="fieldset-<?php echo $name;?>">
                    <legend><?php echo Text::_($label)?> </legend>
                    <ul class="adminformlist">
                        <?php foreach ($this->form->getFieldset($name) as $field) : ?>
                            <?php echo $field->renderField(); ?>
                        <?php endforeach; ?>
                    </ul>
                </fieldset>

                <?php
                echo HTMLHelper::_('uitab.endTab');
            }
        }
        echo HTMLHelper::_('uitab.endTabSet');
        return;

    }

}


class JFContentParams extends CMSObject
{

    var $form = null;

    function __construct($form=null, $item=null)
    {
        $this->form = $form;

    }

    /*
     * @since 4.7 new display for Options
     * @update 4.12 add CW Attachement support for custom fields display
     *              add JA Content Type support for custom fields display
     * @update 5.0 url/image in first position
     *             add jolly extra param's
     * @update 5.10 display original value for custom fields (text, radio, checkbox)
     * @update 5.13 change the way the tab are open not on a list to open but on a list to not open (allow extra plugin to work directly)
     *              add display original value for list and list multiple
     * @update 5.18 add editor custom fields copy/translate (message for original)
     * */
    function render($type)
    {

        $params = ComponentHelper::getParams('com_content');

        echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'options'));

        //v2.1 add images in translation
        if ($params->get('show_urls_images_backend') == 1) {
            $imagesfields = $this->form->getGroup('images');
            $urlsfields = $this->form->getGroup('urls');
            echo HTMLHelper::_('uitab.addTab', 'myTab', 'images', Text::_('COM_CONTENT_FIELDSET_URLS_AND_IMAGES', true));
            ?> <div class="row-fluid"> <?php
                if ($imagesfields) {
                    ?>
                    <div class="span6">
                        <?php echo $this->form->renderField('images') ?>
                        <?php foreach ($imagesfields as $field) : ?>
                            <?php echo $field->renderField(); ?>
                        <?php endforeach; ?>
                    </div>
                    <?php
                }
                if ($urlsfields) {
                    ?>
                    <div class="span6">
                        <?php echo $this->form->renderField('urls') ?>
                        <?php foreach ($urlsfields as $field) : ?>
                            <?php echo $field->renderField(); ?>
                        <?php endforeach; ?>
                    </div>
                    <?php
                }
                ?> </div> <?php
            echo HTMLHelper::_('uitab.endTab');
        }

        $paramsfieldSets = $this->form->getFieldsets('attribs');
        if ($paramsfieldSets)
        {

            foreach ($paramsfieldSets as $name => $fieldSet)
            {

                //$new_open_tab = ['attribs','gsd','helix_ultimate_blog_options','articletypeoptions','articleblogoptions','articleogoptions','cwattachments','general_attribs','jollyanycourseoptions','jollyanyeventoptions'];
                $no_tab_to_open = ['basic','category','frontendassociations','author','date','other','basic-limited'];
                $label = !empty($fieldSet->label) ? $fieldSet->label : 'COM_CONTENT_' . $name . '_FIELDSET_LABEL';

                if (!in_array($name,$no_tab_to_open) ){
                    echo HTMLHelper::_('uitab.addTab', 'myTab', $name, Text::_($label), true);
                }


                if ($name == 'attribs') {
                    continue;
                }
                if ($name == 'basic-limited') {
                    continue;
                }
                if ($name == 'editorConfig' ) {
                    $label = 'COM_CONTENT_SLIDER_EDITOR_CONFIG';
                }

                ?>
                <fieldset class="options-form" id="fieldset-<?php echo $name;?>">
                    <legend><?php echo Text::_($label)?> </legend>

                    <?php foreach ($this->form->getFieldset($name) as $field) : ?>

                        <?php echo $field->renderField(); ?>
                    <?php endforeach; ?>
                </fieldset>

                <?php

                //$new_close_tab = ['editorConfig','gsd','helix_ultimate_blog_options','articletypeoptions','articleblogoptions','articleogoptions','cwattachments','general_attribs','jollyanycourseoptions','jollyanyeventoptions'];

                if (!in_array($name,$no_tab_to_open) ){
                    echo HTMLHelper::_('uitab.endTab');
                }
                //close the tab on the last of options the other fieldset
                if ($name == 'other'){
                    echo HTMLHelper::_('uitab.endTab');
                }
            }
        }


        //2.8.3 support of custom fields
        $customfieldSets = $this->form->getFieldsets('com_fields');
        $ignoreFieldsets = ['jmetadata', 'item_associations','workflow'];
        if (isset($customfieldSets))
        {
            $supported_original = array('Text','Checkboxes','Radio','Textarea','List','Editor');
            $default_lang = ComponentHelper::getParams('com_languages')->get('site', 'en-GB');
            $languages	= LanguageHelper::getLanguages('lang_code');
            $language = $languages[$default_lang];

            //get the original custom fields (article_id is not set in perhaps use reference_id
            $jinput = Factory::getApplication()->input;
            $reference_id = $jinput->get('reference_id', 0,'INT');
            $model = Factory::getApplication()->bootComponent('com_content')->getMVCFactory()->createModel('Article', 'Administrator');
            $article = $model->getItem($reference_id);
            $original_cfs = FieldsHelper::getFields('com_content.article', $article, true);

            foreach ($customfieldSets as $name => $fieldSet)
            {
                if (in_array($name, $ignoreFieldsets, true))
                {
                    continue;
                }

                $label = !empty($fieldSet->label) ? $fieldSet->label : 'COM_CONTENT_' . $name . '_FIELDSET_LABEL';
                echo HTMLHelper::_('uitab.addTab', 'myTab', $name, addslashes(Text::_($label)), true);

                if (isset($fieldSet->description) && trim($fieldSet->description)) :
                    echo '<p class="tip">' . htmlspecialchars(Text::_($fieldSet->description), ENT_QUOTES, 'UTF-8') . '</p>';
                endif;
                ?>
                <div class="clr"></div>
                <fieldset class="options-form">
                    <ul class="adminformlist">
                        <?php foreach ($this->form->getFieldset($name) as $field) : ?>
                            <?php echo $field->renderField(); ?>
                            <?php if (in_array($field->type , $supported_original) ) { ?>
                                <div class="control-group">
                                    <div class="control-label">&nbsp;</div>
                                    <div class="controls">
                                        <?php
                                            $originalValue = '';
                                            //loop throught all the item even we found the field
                                            foreach ($original_cfs as $original_cf){
                                                if ($original_cf->name == $field->fieldname){
                                                    $originalValue = $original_cf->value;
                                                }
                                            }
                                        ?>
                                        <div class="" style="float: left">
                                            <?php echo HTMLHelper::_('image', 'mod_languages/' .$language->image  . '.gif',$language->title_native, array('title'=>$language->title_native,'style'=>'opacity:0.5;width:18px;height:12px;') , true); ?>
                                        </div>
                                        <?php
                                        if ($field->type == 'Text' || $field->type == 'Textarea' || $field->type == 'Editor') { ?>
                                            <div class="falang-cf" style = "" >
                                                <a style="margin-right: 5px;" class="button btn-translate" onclick="TranslateCF('<?php echo $field->id;?>','<?php echo $originalValue;?>','translate')" title="<?php echo Text::sprintf('COM_FALANG_BTN_TRANSLATE','Translate'); ?>"><i class="fas fa-globe"></i></a>
                                                <a class="button btn-copy" onclick="TranslateCF('<?php echo $field->id;?>','<?php echo $originalValue;?>','copy')" title="<?php echo Text::_('COM_FALANG_BTN_COPY'); ?>"><i class="icon-copy"></i></a>
                                            </div>
                                            <?php } ?>
                                        <?php
                                        //don't display original value for Editor type
                                        if ($field->type == 'Editor') { ?>
                                            <div class="" style="font-style: italic;color: #ccc;"><?php echo Text::_('COM_FALANG_CUSTOM_FIELDS_EDITOR_MESSAGE'); ?></div>
                                        <?php } else { ?>
                                            <div class="" style="font-style: italic;color: #ccc;"><?php echo '&nbsp;'.$originalValue; ?></div>
                                        <?php }  ?>

                                    </div>
                                </div>
                            <?php } ?>
                        <?php endforeach; ?>
                    </ul>
                </fieldset>

                <?php
                echo HTMLHelper::_('uitab.endTab');
            }
        }
        echo HTMLHelper::_('uitab.endTabSet');

        return;
    }

}

class TranslateParams_menu extends TranslateParams_xml
{

    var $_menutype;
    var $_menuViewItem;
    var $orig_modelItem;
    var $trans_modelItem;

    function __construct($original, $translation, $fieldname, $fields=null)
    {
        parent::__construct($original, $translation, $fieldname, $fields);
        $lang = Factory::getLanguage();
        $lang->load("com_menus", JPATH_ADMINISTRATOR);

        $jinput = Factory::getApplication()->input;
        $cid = $jinput->get('cid', array(0),'STR');

        $oldcid = $cid;
        $translation_id = 0;
        if (strpos($cid[0], '|') !== false)
        {
            list($translation_id, $contentid, $language_id) = explode('|', $cid[0]);
        }

        $jinput->set('cid',array($contentid));
        $jinput->set('edit',true);

        JLoader::import('models.JFMenusModelItem', FALANG_ADMINPATH);
        $this->orig_modelItem = new JFMenusModelItem();


        // Get The Original State Data
        // model's populate state method assumes the id is in the request object!
        $oldid = $jinput->get('id',0,'INT');
        $jinput->set('id',$contentid);

        // NOW GET THE TRANSLATION - IF AVAILABLE
        $this->trans_modelItem = new JFMenusModelItem();
        $this->trans_modelItem->setState('item.id', $contentid);
        if ($translation != "")
        {
            //fix bug in hikashop force return as array
            $translation = json_decode($translation,true);
        }

        $translationMenuModelForm = $this->trans_modelItem->getForm();

        //2.8.4
        //due to hikashop bugfix we need to get jfrequest by $translation['jfrequest'] and no more by $translation->jfrequest
        if (isset($translation['jfrequest'])){
            $translationMenuModelForm->bind(array("params" => $translation, "request" =>$translation['jfrequest']));
        }
        else {
            $translationMenuModelForm->bind(array("params" => $translation));
        }
        $cid = $oldcid;
        $jinput->set('cid', $cid);
        $jinput->set('id', $oldid);

        $this->transparams = new JFMenuParams($translationMenuModelForm);

    }

    function editTranslation()
    {
        if ($this->_menutype == "wrapper")
        {
            ?>
            <table width="100%" class="paramlist">
                <tr>
                    <td width="40%" align="right" valign="top"><span class="editlinktip"><!-- Tooltip -->
							<span onmouseover="return overlib('Link for Wrapper', CAPTION, 'Wrapper Link', BELOW, RIGHT);" onmouseout="return nd();" >Wrapper Link</span></span></td>
                    <td align="left" valign="top"><input type="text" name="refField_params[url]" value="<?php echo $this->transparams->get('url', '') ?>" class="text_area" size="30" /></td>
                </tr>
            </table>
            <?php
        }
        parent::editTranslation();

    }

}

class JFModuleParams extends CMSObject
{

    protected $form = null;
    protected $item = null;

    function __construct($form=null, $item=null)
    {
        $this->form = $form;
        $this->item = $item;

    }

    function render($type)
    {

        echo HTMLHelper::_('uitab.startTabSet', 'module-sliders', array('active' => 'basic-options'));

        $paramsfieldSets = $this->form->getFieldsets('params');
        if ($paramsfieldSets)
        {
            foreach ($paramsfieldSets as $name => $fieldSet)
            {
                $label = !empty($fieldSet->label) ? $fieldSet->label : 'COM_MODULES_' . $name . '_FIELDSET_LABEL';
                echo HTMLHelper::_('uitab.addTab', 'module-sliders', $name.'-options', addslashes(Text::_($label)));
                ?>
                <fieldset class="options-form" id="fieldset-<?php echo $name;?>">
                    <legend><?php echo Text::_($label)?> </legend>
                    <?php foreach ($this->form->getFieldset($name) as $field) : ?>
                        <?php echo $field->renderField(); ?>
                    <?php endforeach; ?>
                </fieldset>
                <?php
                echo HTMLHelper::_('uitab.endTab');
            }
        }
        //not render assignment menu
        //depends on the original menu
        echo HTMLHelper::_('uitab.endTabSet');
        return;

    }

}

class JFTagsParams extends CMSObject
{

    protected $form = null;
    protected $item = null;

    function __construct($form=null, $item=null)
    {
        $this->form = $form;
        $this->item = $item;

    }

    function render($type)
    {

            //opn the tab
            echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'options'));
            echo HTMLHelper::_('uitab.addTab', 'myTab', 'options', Text::_('COM_TAGS_FIELDSET_OPTIONS', true));

            $paramsfieldSets = $this->form->getFieldset('options');//not getFieldset"s"
            if ($paramsfieldSets)
            {
                ?>
                    <fieldset class="options-form" id="fieldset-options">
                    <legend><?php echo Text::_("COM_TAGS_FIELDSET_OPTIONS")?> </legend>
                <?php
                foreach ($paramsfieldSets as $name => $fieldSet)
                {
                    ?>
                        <?php echo $fieldSet->renderField(); ?>
                    <?php
                }
                ?>
                   </fieldset>

                <?php
            }


        //v2.1 add images in translation
            $imagesfields = $this->form->getGroup('images');
            ?> <div class="col-12 col-lg-6"> <?php
                if ($imagesfields) {
                    ?>
                <fieldset id="fieldset-metadata" class="options-form">
                    <legend><?php echo Text::_("COM_TAGS_FIELD_INTRO_LABEL")?> </legend>
                        <?php echo $this->form->renderField('images') ?>
                        <?php foreach ($imagesfields as $field) : ?>
                            <?php echo $field->renderField(); ?>
                        <?php endforeach; ?>
                </fieldset>
                    <?php
                }
                ?> </div> <?php
            echo HTMLHelper::_('uitab.endTab');
            echo HTMLHelper::_('uitab.endTabSet');

        //not render assignment menu
        //depends on the original menu
        return;

    }

}

class JFFieldsParams extends CMSObject
{

    protected $form = null;
    protected $item = null;

    function __construct($form=null, $item=null)
    {
        $this->form = $form;
        $this->item = $item;

    }

    function render($type)
    {
        $options = ['readonly' => true];
        $paramsfieldSets = $this->form->getFieldsets('fieldparams');
        if ($paramsfieldSets) {
            foreach ($paramsfieldSets as $name => $fieldSet) {
                foreach ($this->form->getFieldset($name) as $field) {
                    echo $field->renderField($options);
                }
            }
        }
        return;
    }

}



class TranslateParams_modules extends TranslateParams_xml
{

    function __construct($original, $translation, $fieldname, $fields=null)
    {
        if (FALANG_J30){
            require_once JPATH_ADMINISTRATOR.'/components/com_modules/helpers/modules.php';
        }
        parent::__construct($original, $translation, $fieldname, $fields);
        $lang = Factory::getLanguage();
        $lang->load("com_modules", JPATH_ADMINISTRATOR);
        $jinput = Factory::getApplication()->input;

        $cid = $jinput->get('cid', array(0),'STR');
        $oldcid = $cid;
        $translation_id = 0;
        if (strpos($cid[0], '|') !== false)
        {
            list($translation_id, $contentid, $language_id) = explode('|', $cid[0]);
        }

        // if we have an existing translation then load this directly!
        // This is important for modules to populate the assignement fields

        //$contentid = $translation_id?$translation_id : $contentid;

        //TODO sbou check this
        $jinput->set('cid',array($contentid));
        $jinput->set('edit',true);

        JLoader::import('models.JFModuleModelItem', FALANG_ADMINPATH);

        // Get The Original State Data
        // model's populate state method assumes the id is in the request object!
        $oldid = $jinput->get('id',0,'INT');
        $jinput->set('id',$contentid);

        // NOW GET THE TRANSLATION - IF AVAILABLE
        $this->trans_modelItem = new JFModuleModelItem();
        $this->trans_modelItem->setState('module.id', $contentid);
        if ($translation != "")
        {
            //for return as associated array and not a stdclass
            //fix bug with easyblog
            $translation = json_decode($translation,true);
        }
        $translationModuleModelForm = $this->trans_modelItem->getForm();
        if (isset($translation->jfrequest)){
            $translationModuleModelForm->bind(array("params" => $translation, "request" =>$translation->jfrequest));
        }
        else {
            $translationModuleModelForm->bind(array("params" => $translation));
        }

        $cid = $oldcid;
        $jinput->set('cid', $cid);
        $jinput->set("id", $oldid);

        $this->transparams = new JFModuleParams($translationModuleModelForm, $this->trans_modelItem->getItem());

    }

    function showOriginal()
    {
        parent::showOriginal();

        $output = "";
        if ($this->origparams->getNumParams('advanced'))
        {
            $fieldname = 'orig_' . $this->fieldname;
            $output .= $this->origparams->render($fieldname, 'advanced');
        }
        if ($this->origparams->getNumParams('other'))
        {
            $fieldname = 'orig_' . $this->fieldname;
            $output .= $this->origparams->render($fieldname, 'other');
        }
        if ($this->origparams->getNumParams('legacy'))
        {
            $fieldname = 'orig_' . $this->fieldname;
            $output .= $this->origparams->render($fieldname, 'legacy');
        }
        echo $output;

    }


    function editTranslation()
    {
        parent::editTranslation();

    }

}

class TranslateParams_tags extends TranslateParams_xml
{

    function __construct($original, $translation, $fieldname, $fields=null)
    {
        parent::__construct($original, $translation, $fieldname, $fields);
        $lang = Factory::getLanguage();
        $lang->load("com_tags", JPATH_ADMINISTRATOR);
        $jinput = Factory::getApplication()->input;

        $cid = $jinput->get('cid', array(0),'STR');
        $oldcid = $cid;
        $translation_id = 0;
        if (strpos($cid[0], '|') !== false)
        {
            list($translation_id, $contentid, $language_id) = explode('|', $cid[0]);
        }

        // if we have an existing translation then load this directly!
        // This is important for modules to populate the assignement fields

        //$contentid = $translation_id?$translation_id : $contentid;

        //TODO sbou check this
        $jinput->set('cid',array($contentid));
        $jinput->set('edit',true);

        JLoader::import('models.JFTagModelItem', FALANG_ADMINPATH);

        // Get The Original State Data
        // model's populate state method assumes the id is in the request object!
        $oldid = $jinput->get('id',0,'INT');
        $jinput->set('id',$contentid);

        // NOW GET THE TRANSLATION - IF AVAILABLE
        $this->trans_modelItem = new JFTagModelItem();
        $this->trans_modelItem->setState('tag.id', $contentid);
        if ($translation != "")
        {
            //for return as associated array and not a stdclass
            //fix bug with easyblog
            $translation = json_decode($translation,true);
        }
        $translationTagModelForm = $this->trans_modelItem->getForm();
        if (isset($translation->jfrequest)){
            $translationTagModelForm->bind(array("params" => $translation,"images" => $translation, "request" => $translation->jfrequest));
        }
        else {
            $translationTagModelForm->bind(array("params" => $translation,"images" => $translation));
        }

        $cid = $oldcid;
        $jinput->set('cid', $cid);
        $jinput->set("id", $oldid);

        $this->transparams = new JFTagsParams($translationTagModelForm, $this->trans_modelItem->getItem());

    }

    function showOriginal()
    {
        parent::showOriginal();

        $output = "";
        if ($this->origparams->getNumParams('advanced'))
        {
            $fieldname = 'orig_' . $this->fieldname;
            $output .= $this->origparams->render($fieldname, 'advanced');
        }
        if ($this->origparams->getNumParams('other'))
        {
            $fieldname = 'orig_' . $this->fieldname;
            $output .= $this->origparams->render($fieldname, 'other');
        }
        if ($this->origparams->getNumParams('legacy'))
        {
            $fieldname = 'orig_' . $this->fieldname;
            $output .= $this->origparams->render($fieldname, 'legacy');
        }
        echo $output;

    }


    function editTranslation()
    {
        parent::editTranslation();

    }

}

class TranslateParams_fields extends TranslateParams_xml
{

    function __construct($original, $translation, $fieldname, $fields=null)
    {
        //require_once JPATH_ADMINISTRATOR.'/components/com_fields/helpers/fields.php';

        parent::__construct($original, $translation, $fieldname, $fields);
        $lang = Factory::getLanguage();
        $lang->load("com_fields", JPATH_ADMINISTRATOR);
        $jinput = Factory::getApplication()->input;

        $cid = $jinput->get('cid', array(0),'STR');
        $oldcid = $cid;
        $translation_id = 0;
        if (strpos($cid[0], '|') !== false)
        {
            list($translation_id, $contentid, $language_id) = explode('|', $cid[0]);
        }

        // if we have an existing translation then load this directly!
        // This is important for modules to populate the assignement fields

        //$contentid = $translation_id?$translation_id : $contentid;

        //TODO sbou check this
        $jinput->set('cid',array($contentid));
        $jinput->set('edit',true);

        JLoader::import('models.JFFieldModelItem', FALANG_ADMINPATH);
        $this->orig_modelItem = new JFFieldModelItem();


        // Get The Original State Data
        // model's populate state method assumes the id is in the request object!
        $oldid = $jinput->get('id',0,'INT');
        $jinput->set('id',$contentid);

        // NOW GET THE TRANSLATION - IF AVAILABLE
        $this->trans_modelItem = new JFFieldModelItem();
        $this->trans_modelItem->setState('field.id', $contentid);
        if ($translation != "")
        {
            //for return as associated array and not a stdclass
            //fix bug with easyblog
            $translation = json_decode($translation,true);
        }

        //4.0.5 try to fix when a new value exist in original or original changed.
        //the value of each option is the key because not modified.
        if (isset($original) && !empty($original)){
            $original = json_decode($original,true);
        }

        $translation = $this->update_translation($translation,$original);

        $translationFieldModelForm = $this->trans_modelItem->getForm();
        if (isset($translation->jfrequest)){
            $translationFieldModelForm->bind(array("fieldparams" => $translation, "request" =>$translation->jfrequest));
        }
        else {
            $translationFieldModelForm->bind(array("fieldparams" => $translation));
        }

        $cid = $oldcid;
        $jinput->set('cid', $cid);
        $jinput->set("id", $oldid);

        $this->transparams = new JFFieldsParams($translationFieldModelForm, $this->trans_modelItem->getItem());

    }

    /*
     * update translation for options only (checkbox)
     * necessary if original change new options/options removed
     *
     * @since 4.0.5
     * */
    private function update_translation($translation,$original){
        $updated_translation = array();
        $idx = 0;
        if (array_key_first($original) != 'options'){
            return $translation;
        }

        foreach ($original['options'] as $name => $item){
            if ($this->visit($item['value'],$translation['options'])){
                $key = $this->visit($item['value'],$translation['options']);
                $updated_translation['options']['options'.$idx] =$translation['options'][$key];
            } else {
                $updated_translation['options']['options'.$idx] = $item;
            }
            $idx++;
        }

        return $updated_translation;
    }

    /*
     * look in all options if the value exist (translation change the key not the value
     * $return $key where the translation is to make the right copy
     * */
    private function visit($value,$options){
        foreach ($options as $key => $item){
            if ($item['value'] == $value){
                return $key;
            }
        }
        return false;
    }


    function showOriginal()
    {
        parent::showOriginal();

        $output = "";
        if ($this->origparams->getNumParams('advanced'))
        {
            $fieldname = 'orig_' . $this->fieldname;
            $output .= $this->origparams->render($fieldname, 'advanced');
        }
        if ($this->origparams->getNumParams('other'))
        {
            $fieldname = 'orig_' . $this->fieldname;
            $output .= $this->origparams->render($fieldname, 'other');
        }
        if ($this->origparams->getNumParams('legacy'))
        {
            $fieldname = 'orig_' . $this->fieldname;
            $output .= $this->origparams->render($fieldname, 'legacy');
        }
        echo $output;

    }


    function editTranslation()
    {
        parent::editTranslation();

    }

}

/*
 * @update 5.10 add reference_id in the input for easier original custom fields value display
 * @update 5.14 fix astroid article param's translation (2 times load form execute onContentPrepare Form with the addFormPath
 *              \libraries\astroid\framework\library\astroid\Helper\Client.php
 * */
class TranslateParams_content extends TranslateParams_xml
{

    var $orig_contentModelItem;
    var $trans_contentModelItem;

    function __construct($original, $translation, $fieldname, $fields=null)
    {
        $jinput = Factory::getApplication()->input;
        require_once JPATH_ADMINISTRATOR.'/components/com_content/helpers/content.php';

        parent::__construct($original, $translation, $fieldname, $fields);
        $lang = Factory::getApplication()->getLanguage();
        $lang->load("com_content", JPATH_ADMINISTRATOR);

        $cid = $jinput->get('cid', array(0),'STR');
        $oldcid = $cid;
        $translation_id = 0;
        if (strpos($cid[0], '|') !== false)
        {
            list($translation_id, $contentid, $language_id) = explode('|', $cid[0]);
        }

        $jinput->set('cid',array($contentid));
        $jinput->set('edit',true);

        // model's populate state method assumes the id is in the request object!
        $oldid = $jinput->get("article_id", 0, 'INT');
        // Take care of the name of the id for the item
        $jinput->set("article_id", $contentid);

        JLoader::import('models.JFContentModelItem', FALANG_ADMINPATH);
        // Get The Original form
        // (remove in 5.14)
        //  because this load Form::addFormPath who make problem with the article
        // libraries\astroid\framework\library\astroid\Helper\Client.php
        // the get translation form path after load fist the astroid article.xml
        //
        //
        //$this->orig_contentModelItem = new JFContentModelItem();
        //$this->orig_contentModelItem->setState('article.id',$contentid);
        //$jfcontentModelForm = $this->orig_contentModelItem->getForm();

        // NOW GET THE TRANSLATION - IF AVAILABLE
        $this->trans_contentModelItem = new JFContentModelItem();
        $this->trans_contentModelItem->setState('article.id', $contentid);
        if ($translation != "")
        {
            $translation = json_decode($translation,true);
        }
        $translationcontentModelForm = $this->trans_contentModelItem->getForm();

        if (isset($translation->jfrequest)) {
            $translationcontentModelForm->bind(array("attribs" => $translation,"images" => $translation,"urls" => $translation, "request" => $translation->jfrequest));
        } else {
            $translationcontentModelForm->bind(array("attribs" => $translation,"images" => $translation,"urls" => $translation));
        }

        // reset old values in REQUEST array
        $cid = $oldcid;
        $jinput->set('cid', $cid);
        $jinput->set("article_id", $oldid);

        //set the reference_id (translated article id, use for original custom fields display)
        $jinput->set("reference_id", $contentid);

        //	$this->origparams = new JFContentParams( $jfcontentModelForm);
        $this->transparams = new JFContentParams($translationcontentModelForm);


    }

    function showOriginal()
    {
        parent::showOriginal();

        $output = "";
        if ($this->origparams->getNumParams('advanced'))
        {
            $fieldname = 'orig_' . $this->fieldname;
            $output .= $this->origparams->render($fieldname, 'advanced');
        }
        if ($this->origparams->getNumParams('legacy'))
        {
            $fieldname = 'orig_' . $this->fieldname;
            $output .= $this->origparams->render($fieldname, 'legacy');
        }
        echo $output;

    }

    function editTranslation()
    {
        parent::editTranslation();
    }

}

class TranslateParams_components extends TranslateParams_xml
{
    var $_menutype;
    var $_menuViewItem;
    var $orig_menuModelItem;
    var $trans_menuModelItem;

    public function __construct($original, $translation, $fieldname, $fields=null){
        $lang = Factory::getLanguage();
        $lang->load("com_config", JPATH_ADMINISTRATOR);

        $this->fieldname = $fieldname;
        global $mainframe;
        $content = null;
        foreach ($fields as $field) {
            if ($field->Name=="option"){
                $comp = $field->originalValue;
                break;
            }
        }
        $lang->load($comp, JPATH_ADMINISTRATOR);

        $path = DS."components".DS.$comp.DS."config.xml";
        //sbou
        $xmlfile = $path;
        //$xmlfile = JApplicationHelper::_checkPath($path);
        //fin sbou

        $this->origparams = new JParameter($original, $xmlfile,"component");
        $this->transparams = new JParameter($translation, $xmlfile,"component");
        $this->defaultparams = new JParameter("", $xmlfile,"component");
        $this->fields = $fields;

    }

    function showOriginal(){
        if ($this->_menutype=="wrapper"){
            ?>
            <table width="100%" class="paramlist">
                <tr>
                    <td width="40%" align="right" valign="top"><span class="editlinktip"><!-- Tooltip -->
			<span onmouseover="return overlib('Link for Wrapper', CAPTION, 'Wrapper Link', BELOW, RIGHT);" onmouseout="return nd();" >Wrapper Link</span></span></td>

                    <td align="left" valign="top"><input type="text" name="orig_params[url]" value="<?php echo $this->origparams->get('url','')?>" class="text_area" size="30" /></td>
                </tr>
            </table>
            <?php
        }
        parent::showOriginal();
    }

    function editTranslation(){
        if ($this->_menutype=="wrapper"){
            ?>
            <table width="100%" class="paramlist">
                <tr>
                    <td width="40%" align="right" valign="top"><span class="editlinktip"><!-- Tooltip -->
			<span onmouseover="return overlib('Link for Wrapper', CAPTION, 'Wrapper Link', BELOW, RIGHT);" onmouseout="return nd();" >Wrapper Link</span></span></td>
                    <td align="left" valign="top"><input type="text" name="refField_params[url]" value="<?php echo $this->transparams->get('url','')?>" class="text_area" size="30" /></td>
                </tr>
            </table>
            <?php
        }
        parent::editTranslation();
    }

}

//new Falang 2.0
class JFCategoryParams extends CMSObject
{

    protected $form = null;
    protected $item = null;

    function __construct($form=null, $item=null)
    {
        $this->form = $form;
        $this->item = $item;

    }

    function render($type)
    {

        echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'options'));
        //see layouts/joomla/edit/params.php
        $paramsfieldSets = $this->form->getFieldsets('params');
        $xml = $this->form->getXml();

        if ($paramsfieldSets)
        {
            foreach ($paramsfieldSets as $name => $fieldSet)
            {
                $hasChildren  = $xml->xpath('//fieldset[@name="' . $name . '"]//fieldset[not(ancestor::field/form/*)]');

                if (!$hasChildren){
                    continue;
                }

                $label = !empty($fieldSet->label) ? $fieldSet->label :  strtoupper('JGLOBAL_FIELDSET_' . $name);

                echo HTMLHelper::_('uitab.addTab', 'myTab', $name, addslashes(Text::_($label)), true);

                ?>
                <fieldset class="options-form" id="fieldset-<?php echo $name;?>">
                    <legend><?php echo Text::_($label)?> </legend>
                    <ul class="adminformlist">
                        <?php foreach ($this->form->getFieldset($name) as $field) : ?>
                            <?php echo $field->renderField(); ?>
                        <?php endforeach; ?>
                    </ul>
                </fieldset>

                <?php
                echo HTMLHelper::_('uitab.endTab');
            }
        }

        //4.0.3 support of custom fields
        $customfieldSets = $this->form->getFieldsets('com_fields');
        $ignoreFieldsets = ['jmetadata', 'item_associations','workflow'];

        if (isset($customfieldSets))
        {
            foreach ($customfieldSets as $name => $fieldSet)
            {
                if (in_array($name, $ignoreFieldsets))
                {
                    continue;
                }

                $label = !empty($fieldSet->label) ? $fieldSet->label : 'COM_CONTENT_' . $name . '_FIELDSET_LABEL';
                echo HTMLHelper::_('uitab.addTab', 'myTab', $name, addslashes(Text::_($label)), true);
                ?>
                <fieldset class="options-form" id="fieldset-<?php echo $name;?>">
                    <ul class="adminformlist">
                        <?php foreach ($this->form->getFieldset($name) as $field) : ?>
                            <?php echo $field->renderField(); ?>
                        <?php endforeach; ?>
                    </ul>
                </fieldset>

                <?php
                echo HTMLHelper::_('uitab.endTab');
            }
        }

        echo HTMLHelper::_('uitab.endTabSet');
        return;

    }

}
class TranslateParams_categories extends TranslateParams_xml
{

    function __construct($original, $translation, $fieldname, $fields=null)
    {
        require_once JPATH_ADMINISTRATOR.'/components/com_categories/helpers/categories.php';
        parent::__construct($original, $translation, $fieldname, $fields);
        $jinput = Factory::getApplication()->input;

        $lang = Factory::getLanguage();
        $lang->load("com_categories", JPATH_ADMINISTRATOR);

        $cid = $jinput->get('cid', array(0),'STR');
        $oldcid = $cid;
        $translation_id = 0;
        if (strpos($cid[0], '|') !== false)
        {
            list($translation_id, $contentid, $language_id) = explode('|', $cid[0]);
        }

        // if we have an existing translation then load this directly!
        // This is important for modules to populate the assignement fields

        //$contentid = $translation_id?$translation_id : $contentid;

        //TODO sbou check this
        $jinput->set('cid',array($contentid));
        $jinput->set('edit',true);

        JLoader::import('models.JFCategoryModelItem', FALANG_ADMINPATH);

        // Get The Original State Data
        // model's populate state method assumes the id is in the request object!
        $oldid = $jinput->get('id',0,'INT');
        $jinput->set('id',$contentid);
        $jinput->set('extension','');//sbou5  //remove deprectaed  Passing null to parameter #2 ($value) of type string is deprecated in D:\Projet\falang\www5\administrator\components\com_categories\src\Model\CategoryModel.php on line 484

        // NOW GET THE TRANSLATION - IF AVAILABLE
        $this->trans_modelItem = new JFCategoryModelItem();
        $this->trans_modelItem->setState('category.id', $contentid);
        if ($translation != "")
        {
            $translation = json_decode($translation,true);
        }
        $translationCategoryModelForm = $this->trans_modelItem->getForm();
        if (isset($translation->jfrequest)){
            $translationCategoryModelForm->bind(array("params" => $translation, "request" =>$translation->jfrequest));
        }
        else {
            $translationCategoryModelForm->bind(array("params" => $translation));
        }

        // reset old values in REQUEST array
        $cid = $oldcid;
        $jinput->set('cid', $cid);
        $jinput->set("id", $oldid);


        $this->transparams = new JFCategoryParams($translationCategoryModelForm, $this->trans_modelItem->getItem());

    }

    function showOriginal()
    {
        parent::showOriginal();

        $output = "";
        if ($this->origparams->getNumParams('advanced'))
        {
            $fieldname = 'orig_' . $this->fieldname;
            $output .= $this->origparams->render($fieldname, 'advanced');
        }
        if ($this->origparams->getNumParams('other'))
        {
            $fieldname = 'orig_' . $this->fieldname;
            $output .= $this->origparams->render($fieldname, 'other');
        }
        if ($this->origparams->getNumParams('legacy'))
        {
            $fieldname = 'orig_' . $this->fieldname;
            $output .= $this->origparams->render($fieldname, 'legacy');
        }
        echo $output;

    }


    function editTranslation()
    {
        parent::editTranslation();

    }

}
