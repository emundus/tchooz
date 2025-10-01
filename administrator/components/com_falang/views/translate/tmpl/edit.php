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
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\WebAsset\WebAssetManager;
use Joomla\Component\Finder\Administrator\Indexer\Parser\Html;

$input = Factory::getApplication()->input;
$document = Factory::getApplication()->getDocument();


/**
	* @return void
	* @param object $this->actContentObject
	* @param array $this->langlist
	* @param string $this->catid
	* @desc Shows the dialog for the content translation
	*/

if ($this->showMessage) {
	echo $this->loadTemplate('message');
}

$params = ComponentHelper::getParams('com_falang');

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $document->getWebAssetManager();
$wa->useScript('form.validate');

//if no translator selected.
$translate_button_available = false;
$translate_button_icon = 'fas fa-globe';
$translate_service = $params->get('translator');
if (!empty($translate_service) && ('none' != strtolower($translate_service)) &&
                                           !empty($params->get('translator_bingkey') ||
                                           !empty($params->get('translator_deeplkey'))  ||
                                           !empty($params->get('translator_googlekey'))  ||
                                           !empty($params->get('translator_yandexkey'))  ||
                                           !empty($params->get('translator_lingvanex')) )){
	require_once __DIR__ .'/../../../classes/translator.php';
	translatorFactory::getTranslator($this->select_language_id);
	$translate_button_available = true;
	switch ($translate_service){
        case 'Deepl':
            $translate_button_icon = 'fa fa-language';
            break;
	    case 'Bing':
            $translate_button_icon = 'fab fa-windows';
            break;
        case 'Google':
            $translate_button_icon = 'fab fa-google';
            break;
        case 'Yandex':
            $translate_button_icon = 'fab fa-yandex-international';
            break;
        case 'Lingvanex':
            $translate_button_icon = 'fas fa-language';
            break;
        default:
            $translate_button_icon = 'fas fa-globe';
            break;
    }
}

$act=$this->act;
$task=$this->task;
$select_language_id = $this->select_language_id;
$skip_params = false;

$jfmanager = FalangManager::getInstance();
$active_language = $jfmanager->getLanguageByID($select_language_id);

$user = Factory::getUser();
$db = Factory::getDBO();
$elementTable = $this->actContentObject->getTable();
$input = Factory::getApplication()->input;

/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
$wa = $document->getWebAssetManager();
$wa->useScript('form.validate');
$document->addScript('components/com_falang/assets/js/falang.js', array('version' => 'auto', 'relative' => true));
//use for images type
HTMLHelper::_('bootstrap.renderModal');

//use for toggle description
HTMLHelper::_('jquery.framework');
$document->addScript('components/com_falang/assets/js/jquery.cookie.js', array('version' => 'auto', 'relative' => true));

HTMLHelper::_('formbehavior.chosen', 'select');

//use to name form to allow form validation
$idForm = 'adminForm';
switch ($elementTable->Name) {
    case 'modules':
        $idForm = 'module-form';
		//add view-module for widgetkit support
		$input->set('view','module');
        break;
    case 'banners':
        $idForm = 'banner-form';
        break;
    case 'menu':
        $idForm = 'item-form';
        break;
    case 'categories':
        $idForm = 'item-form';
        break;
    case 'contact_details':
        $idForm = 'contact-form';
        break;
    case 'weblinks':
        $idForm = 'weblink-form';
        break;
}

jimport( 'joomla.html.editor' );
// check system and user editor and load appropriate copying script
$user = Factory::getUser();
$conf = Factory::getApplication()->getConfig();
$editor = $conf->get('editor');
$wysiwygeditor = \Joomla\CMS\Editor\Editor::getInstance($editor);

$editorFields=null;
foreach ($this->tranFilters as $filter) {
	echo "<input type='hidden' name='".$filter->filterType."_filter_value' value='".$filter->filter_value."'/>";
}

//check if we are editing a yootheme content
$yootheme_content = false;
if ($elementTable->Name == 'content'){
    foreach ($elementTable->Fields as $field){
        if ($field->Name == 'fulltext'){
            $yootheme_content = preg_match('/<!-- {\s?/', $field->originalValue);
        }
    }
}

//TODO sbou check this
// Place a reference to the element Table in the config so that it can be used in translation of urlparams !!!
$conf->set('falang.elementTable',$elementTable);


echo "\n<!-- editor is $editor //-->\n";
$editorFile = FALANG_ADMINPATH."/editors/".strtolower($editor).".php";
if (file_exists($editorFile)){
	require_once($editorFile);
}
else {
	?>
	<script type="text/javascript">
      Console.log("Editor not supported");
    </script>

<?php } ?>

<script type="text/javascript">

    /*function necessary to decode special caractère ü, ß, ä*/
    function b64DecodeUnicode(str) {
        // Going backwards: from bytestream, to percent-encoding, to original string.
        return decodeURIComponent(atob(str).split('').map(function(c) {
            return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
        }).join(''));
    }

    function TranslateCF(field,value,action){
        console.log("TranslateCF:"+action);
        value = b64DecodeUnicode(value);
        try {
            if (action == "copy") {
                setTranslation(field, value)
            }
            if (action == "translate"){
                translateService(field, value);
            }

        } catch (e) {
            console.log(e.message);
        }

    }

    //add insert image name for image type
    function jInsertFieldValue(value,id) {
        var old_id = document.getElementById(id).value;
        if (old_id != id) {
            document.getElementById(id).value = value;
        }
    }

    </script>

<!-- Panel Header -->
<form action="index.php" method="post" name="adminForm" id="<?php echo $idForm; ?>" class="form-validate form-falang">
    <div class="container-fluid ">
        <div class="row falang-controls">
            <div class="left form-horizontal">
                <button id="toogle-source-panel" class="btn btn-sm btn-secondary"
                        data-show-reference="<?php echo Text::_('COM_FALANG_EDIT_SHOW_REFERENCE');?>"
                        data-hide-reference="<?php echo Text::_('COM_FALANG_EDIT_HIDE_REFERENCE');?>"><?php echo Text::_('COM_FALANG_EDIT_HIDE_REFERENCE');?>
                </button>            </div>
            <div class="right">
                    <div class="alert alert-info infos">
                        <div class="container">
                            <div class="row">
                                <div class="col">
                                    <div class="form-check form-switch form-switch-reverse">
                                        <input class="form-check-input" type="checkbox" id="published" name="published" value="on" <?php echo $this->actContentObject->published == 1 ?'checked':''; ?>>
                                        <label class="form-check-label pull-right" for="published"><?php echo Text::_('COM_FALANG_TRANSLATE_TITLE_PUBLISHED')?></label>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="control-group">
                                        <div class="control-label"><?php echo Text::_('COM_FALANG_TRANSLATE_TITLE_STATE').': ';?><?php echo $this->actContentObject->state > 0 ? Text::_('COM_FALANG_STATE_OK') : ($this->actContentObject->state < 0 ? Text::_('COM_FALANG_STATE_NOTEXISTING') : Text::_('COM_FALANG_STATE_CHANGED'));?></div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="control-group">
                                        <div class="control-label"><?php echo Text::_('COM_FALANG_TRANSLATE_TITLE_DATECHANGED').': ';?><?php echo  $this->actContentObject->lastchanged ? HTMLHelper::_('date',  $this->actContentObject->lastchanged, 'Y-m-d H:i'):Text::_('new');?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
            </div>
        </div>
    </div>
    <div class="container-fluid ">
        <div class="row falang-headers">
            <div class="left form-horizontal">
                <h3><?php echo Text::_('COM_FALANG_EDIT_REFERENCE_TITLE');?></h3>
            </div>
            <div class="right form-horizontal ">
                <h3><?php echo Text::_('COM_FALANG_EDIT_TARGET_TITLE').' : '.$active_language->title;?><span id="flag">
							<?php echo HTMLHelper::_('image', 'mod_languages/' .$active_language->image  . '.gif', $active_language->title , null, true); ?>
						</span>
                </h3>
            </div>
        </div>
    </div>


    <div class="falang-sidebyside">
            <?php
                foreach ($elementTable->Fields as $field) { ?>


                    <?php
                    //field params is not sidebyside field
                    //but we can set the skip_params here before the params
                    if (strtolower($field->Type)=='params'){
                        $skip_params = ($this->actContentObject->state  >= 0 && empty($field->translationContent->value))?true:false;
                        continue;
                    }

	                $field->preHandle($elementTable);

	                // if we supress blank originals
	                if ($field->ignoreifblank && $field->originalValue==="") continue;

	                //display translatable field only
                    if( $field->Translate )
                    {
                        $translationContent = $field->translationContent;

                        //dispay title and alias
                        ?>
                        <!-- ************************* Field Translation <?php echo $field->Name;?>  *************************-->

                        <!-- set id to allow edit or update of the field  -->
                        <input type="hidden" name="id_<?php echo $field->Name;?>" value="<?php echo $translationContent->id;?>" />

                        <!-- hiddentext display only here and loop to the other field -->

                        <?php if( strtolower($field->Type)=='hiddentext') { ?>
                            <input type="hidden" name="id_<?php echo $field->Name;?>" value="<?php echo $translationContent->id;?>" />
                            <input type="hidden" name="origValue_<?php echo $field->Name;?>" value='<?php echo md5( $field->originalValue );?>' />
                            <textarea  name="origText_<?php echo $field->Name;?>" style="display:none"><?php echo $field->originalValue;?></textarea>
                            <textarea name="refField_<?php echo $field->Name;?>"  style="display:none"><?php echo $translationContent->value; ?></textarea>
                            <?php

                            continue;
                            }  ?>

                        <!-- ************************* SOURCE   ***************************** -->

                        <div class="outer-panel source-panel form-horizontal">

	                        <?php if ( $field->Name =='title' || $field->Name =='alias' ) { ?>
                                <div class="form-row"><!--form-inline form-inline-header -->
                                    <div class="control-group">
                                        <div class="control-label">
                                            <label><?php echo $field->Lable; ?></label>
                                        </div>
                                        <div class="controls">
                                            <input class="form-control" type="text" readonly value="<?php echo htmlspecialchars($field->originalValue ?? '');?>" >
                                        </div>
                                    </div>
                                </div>
	                        <?php }  else { // end if title - label ?>

                            <!-- display other field exept title and alias -->
                            <div class="control-group">
                                    <div class="control-label">
                                            <label><?php echo $field->Lable; ?></label>
                                    </div>

                                    <div class="controls <?php echo strtolower($field->Type)=='htmltext'?'controls-htmltext':''; ?>">

                                    <!-- fin display other field exept title and alias -->
                                    <!-- htmltext,text,textarea,image,param's,readonlytext,hiddentext-->
                                    <?php if (strtolower($field->Type)=='htmltext') { ?>
                                        <?php
                                            //need to pass the id at the end or it was not working with codemirror
                                            if (!$yootheme_content){
                                                $editorFields[] = array( "editor_".$field->Name, "origText_".$field->Name );
                                                echo $wysiwygeditor->display("origText_".$field->Name,htmlspecialchars($field->originalValue ?? '', ENT_COMPAT, 'UTF-8'), '100%','300', '70', '15',$field->ebuttons,"origText_".$field->Name);
                                            } else {
                                                ?> <div style="height:100px"><?php echo Text::_('COM_FALANG_YOOTHEME_BUILDER_CONTENT_MESSAGE'); ?></div>
                                           <?php }
                                    //end for yootheme content ?>
                                    <?php } //end if htmltext?>
                                    <?php if (strtolower($field->Type)=='titletext') { ?>
                                        <input class="form-control" type="text"  readonly value="<?php echo $field->originalValue;?>" >
                                    <?php } //end if text?>
                                    <?php if (strtolower($field->Type)=='text') { ?>
                                        <input class="form-control" type="text"  readonly value="<?php echo $field->originalValue;?>" >
                                    <?php } //end if text?>
                                    <?php if (strtolower($field->Type)=='textarea') { ?>
                                        <textarea class="form-control" readonly ><?php echo $field->originalValue; ?></textarea>
                                    <?php } //end if textarea?>

                                    <?php if (strtolower($field->Type)=='readonlytext') { ?>
                                        <input class="form-control" type="text" readonly placeholder="<?php echo $field->originalValue;?>">
                                    <?php } //end if readonlytext ?>

                                    <?php if (strtolower($field->Type)=='images') { ?>
                                        <input class="form-control" type="text"  readonly value="<?php echo $field->originalValue;?>" >
                                    <?php } //end if textarea?>

                                    <?php if (strtolower($field->Type)!='htmltext' &&
	                                    strtolower($field->Type)!='referenceid' &&
	                                    strtolower($field->Type)!='titletext' &&
                                        strtolower($field->Type)!='text' &&
                                        strtolower($field->Type)!='textarea' &&
                                        strtolower($field->Type)!='readonlytext' &&
	                                    strtolower($field->Type)!='images' &&
                                        strtolower($field->Type)!='hiddentext') { ?>
                                        <?php echo Text::_('COM_FALANG_TRANSATE_TYPE_NOT_EXIST')?>
                                    <?php } //end if other ?>
                                </div>
                            </div>
                        <?php }//end else title,alias ?>
                        </div><!-- source panel -->

                        <!-- ************************** ACTION   ******************************* -->
                        <div class="outer-panel action-panel">
                            <!-- add hidden use for translate/copy -->
                            <textarea  name="origText_<?php echo $field->Name;?>" style="display:none"><?php echo $field->originalValue;?></textarea>
                            <!-- use for html copy/translate htmltext -->
                            <span style="display:none" id="original_value_<?php echo $field->Name?>" name="original_value_<?php echo $field->Name;?>">
                                <?php
                                      if (preg_match("/<form/i",$field->originalValue)){
                                        $ovhref = Route::_("index.php?option=com_falang&task=translate.originalvalue&field=".$field->Name."&cid=".$this->actContentObject->id."&lang=".$select_language_id);
                                        echo '<a class="modal" rel="{handler: \'iframe\', size: {x: 700, y: 500}}" href="'.$ovhref.'" >'.Text::_("Content contains form - click here to view in popup window").'</a>';
                                      }
                                      else {
                                        echo $field->originalValue;
                                      }
                                      ?>
                            </span>
                            <!-- use for -->
                            <input type="hidden" name="origValue_<?php echo $field->Name;?>" value='<?php echo md5( $field->originalValue );?>' />


		                    <?php if ( strtolower($field->Type)=='readonlytext'){
			                    //specific case for menutype link
			                    if ($elementTable->Name == 'menu' && $field->Name == 'link') { ?>
                                    <a class="button btn" onclick="document.adminForm.refField_<?php echo $field->Name;?>.value = document.adminForm.origText_<?php echo $field->Name;?>.value;" title="<?php echo Text::_('COM_FALANG_BTN_COPY'); ?>"><i class="icon-copy"></i></a>
			                    <?php }
                                    if ($elementTable->Name == 'menu' && $field->Name == 'path') { ?>
                                        <!-- space need to have a side-->
                                        &nbsp;
			                    <?php } ?>
		                    <?php } ?>

		                    <?php if( strtolower($field->Type)!='htmltext' && strtolower($field->Type)!='readonlytext') {?>
                                <!-- Translate button -->
                                <a class="button btn btn-translate <?php echo $translate_button_available ? '': 'disabled';?>" title="<?php echo !$translate_button_available ?Text::_('COM_FALANG_SERVICE_NOT_CONFIGURED'):'';?>"  onclick="copyToClipboard('<?php echo $field->Name;?>','translate')" title="<?php echo Text::sprintf('COM_FALANG_BTN_TRANSLATE',$translate_service); ?>"><i class="<?php echo $translate_button_icon;?>"></i></a>
                                <!-- Copy button -->
                                <a class="button btn btn-copy" onclick="copyToClipboard('<?php echo $field->Name;?>','copy')" title="<?php echo Text::_('COM_FALANG_BTN_COPY'); ?>"><i class="icon-copy"></i></a>
                                <!-- Delete button -->
                                <a class="button btn btn-delete" onclick="copyToClipboard('<?php echo $field->Name;?>','clear')" title="<?php echo Text::_('Delete'); ?>"><i class="icon-delete"></i></a>
		                    <?php } ?>

                            <!-- don't display button for htmltext and non yootheme content default case -->
                            <?php if( strtolower($field->Type)=='htmltext' && strtolower($field->Type)!='readonlytext' && !$yootheme_content) {?>
                                <!-- Translate button -->
                                <a class="button btn btn-translate <?php echo $translate_button_available ? '': 'disabled';?>"  onclick="copyToClipboard('<?php echo $field->Name;?>','translate');" title="<?php echo Text::sprintf('COM_FALANG_BTN_TRANSLATE',$translate_service); ?>"><i class="<?php echo $translate_button_icon;?>"></i></a>
                                <!-- Copy button -->
                                <a class="button btn btn-copy" onclick="copyToClipboard('<?php echo $field->Name;?>','copy');" title="<?php echo Text::_('COM_FALANG_BTN_COPY'); ?>"><i class="icon-copy"></i></a>
                                <!-- Delete button -->
                                <a class="button btn btn-delete" onclick="copyToClipboard('<?php echo $field->Name;?>','clear');" title="<?php echo Text::_('Delete'); ?>"><i class="icon-delete"></i></a>
		                    <?php } ?>

                            <!-- need a space for yoothem content to display the space for button -->
                            <?php if( $yootheme_content) {?>
                                &nbsp;
                            <?php } ?>


                        </div>

                        <!-- ********************** TARGET   ************************** -->
                        <!-- display title and alias -->
                        <div class="outer-panel target-panel form-horizontal">
                            <?php if ( $field->Name =='title' || $field->Name =='alias' ) { ?>
                                <div class="form-row"><!--form-inline form-inline-header -->
                                    <div class="control-group">
                                        <div class="control-label">
                                            <label><?php echo $field->Lable; ?></label>
                                        </div>
                                        <div class="controls">
                                            <input class="form-control" type="text" name="refField_<?php echo $field->Name;?>" id="refField_<?php echo $field->Name;?>" value="<?php echo htmlspecialchars($translationContent->value ?? ''); ?>" >
                                        </div>
                                    </div>
                                </div>
                            <?php } else { // end if title - label ?>

                            <!-- display other field exept title and alias -->
                            <div class="control-group">
                                <div class="control-label">
                                    <label><?php echo $field->Lable; ?></label>
                                </div>
                                <div class="controls <?php echo strtolower($field->Type)=='htmltext'?'controls-htmltext':''; ?>">
                                    <!-- fin display other field exept title and alias -->
                                    <!-- htmltext,text,textarea,image,param's,readonly,hiddentext-->
                                    <?php if (strtolower($field->Type)=='htmltext') { ?>
                                        <?php
                                        if (!$yootheme_content) {
                                            $editorFields[] = array("editor_" . $field->Name, "refField_" . $field->Name);
                                            //need to pass the id at the end or it was not working with codemirror
                                            echo $wysiwygeditor->display("refField_" . $field->Name, htmlspecialchars($translationContent->value ?? '', ENT_COMPAT, 'UTF-8'), '100%', '300', '70', '15', $field->ebuttons, "refField_" . $field->Name);
                                        } else { ?>
                                            <div><?php echo Text::_('COM_FALANG_YOOTHEME_BUILDER_CONTENT_MESSAGE'); ?></div>
                                            <?php } ?>
                                    <?php } //end if htmltext?>

	                                <?php if (strtolower($field->Type)=='titletext') { ?>
		                                <?php
		                                $length = ($field->Length>0)?$field->Length:60;
		                                $maxLength = ($field->MaxLength>0) ? "maxlength=".$field->MaxLength:"";?>
                                        <input class="form-control" type="text" name="refField_<?php echo $field->Name;?>" id="refField_<?php echo $field->Name;?>" size="<?php echo $length;?>" value="<?php echo htmlspecialchars($translationContent->value ?? ''); ?>" "<?php echo $maxLength;?>"/>
	                                <?php } //end if titletext?>

                                    <?php if (strtolower($field->Type)=='text') { ?>
                                        <?php
                                        $length = ($field->Length>0)?$field->Length:60;
	                                    $maxLength = ($field->MaxLength>0) ? "maxlength=".$field->MaxLength:"";?>
                                        <input class="form-control" type="text" name="refField_<?php echo $field->Name;?>" id="refField_<?php echo $field->Name;?>"  size="<?php echo $length;?>" value="<?php echo htmlspecialchars($translationContent->value ?? ''); ?>" "<?php echo $maxLength;?>"/>
                                    <?php } //end if text?>

                                    <?php if (strtolower($field->Type)=='textarea') { ?>
                                        <textarea class="form-control" name="refField_<?php echo $field->Name;?>" id="refField_<?php echo $field->Name;?>"  ><?php echo $translationContent->value; ?></textarea>
                                    <?php } //end if textarea?>

                                    <?php if (strtolower($field->Type)=='readonlytext') {
                                        $value =  strlen($translationContent->value ?? '')>0? $translationContent->value:$field->originalValue;
	                                    $length = ($field->Length>0)?$field->Length:60;
	                                    $maxLength = ($field->MaxLength>0) ? "maxlength=".$field->MaxLength:"";
                                        ?>
                                        <input class="form-control" type="text" name="refField_<?php echo $field->Name;?>" id="refField_<?php echo $field->Name;?>"  size="<?php echo $length;?>" placeholder="<?php echo $value; ?>" value="<?php echo $value; ?>" maxlength="<?php echo $maxLength;?>" readonly>
                                    <?php } //end if readonlytext ?>

	                                <?php if (strtolower($field->Type)=='images') {
                                        $length = ($field->Length>0)?$field->Length:60;
                                        $maxLength = ($field->MaxLength>0) ? "maxlength=".$field->MaxLength:"";
                                        ?>
                                        <div class="input-prepend input-append">
                                            <input class="input-large" type="text" name="refField_<?php echo $field->Name;?>" id="refField_<?php echo $field->Name;?>" size="<?php echo $length;?>" value="<?php echo $translationContent->value; ?>" "<?php echo $maxLength;?>"/>
                                            <a class="modal btn" title="<?php echo Text::_("JSELECT")?>"
                                               href="index.php?option=com_media&amp;view=images&amp;tmpl=component&amp;fieldid=refField_<?php echo $field->Name;?>"<?php echo $field->Name;?>"
                                            rel="{handler: 'iframe', size: {x: 800, y: 500}}"><?php echo Text::_("JSELECT")?></a>
                                            <a class="btn hasTooltip" href="#" onclick="jInsertFieldValue('', 'refField_<?php echo $field->Name;?>');return false;" data-original-title="<?php echo Text::_("JDELETE")?>">
                                                <i class="icon-remove"></i></a>
                                        </div>
	                                <?php } //end if images ?>

                                    <?php if (strtolower($field->Type)!='htmltext' &&
	                                    strtolower($field->Type)!='referenceid' &&
	                                    strtolower($field->Type)!='titletext' &&
	                                    strtolower($field->Type)!='text' &&
	                                    strtolower($field->Type)!='textarea' &&
	                                    strtolower($field->Type)!='readonlytext' &&
	                                    strtolower($field->Type)!='images' &&
	                                    strtolower($field->Type)!='hiddentext') { ?>
                                        <?php echo Text::_('COM_FALANG_TRANSATE_TYPE_NOT_EXIST').':'.$field->Type; ?>
                                    <?php } //end if other ?>
                                </div>
                        </div>
                        <?php } //end else title,alias?>
                    </div><!-- target panel -->

                    <?php } // end if translatable  ?>
                    <div class="clr"></div>
                <?php }//end foreach ?>



    </div> <!-- sidebyside-->

    <!-- ********************  PARAMS   ********************* -->


    <div class="params-title alert alert-info infos falang-controls">
            <div class="row">
                <div class="col-md-6">
                    <?php echo Text::_('COM_FALANG_TRANSLATE_PARAMS')?>
                </div>
                <?php if ($elementTable->Name == 'modules' || $elementTable->Name == 'menu'  || $elementTable->Name == 'categories' || $elementTable->Name == 'tags'  ) { ?>
                        <div class="form-check form-switch form-switch-reverse col-md-6">
                            <!-- $skip_params is tested in the first loop-->
                            <input class="form-check-input" type="checkbox" id="skip_params" name="skip_params" value="on" <?php echo $skip_params == true ?'checked':''; ?>>
                            <label class="form-check-label pull-right" for="published"><?php echo Text::_('COM_FALANG_TRANSLATE_SKIP_PARAMS');?></label>
                        </div>
                    <?php } ?>
            </div>
    </div>

    <div id="falang-params" class="form-horizontal falang-params" style="display:<?php echo $skip_params?'none':'block' ?> ">
        <?php   foreach ($elementTable->Fields as $field)
        {

	        //field params is the only filed managed here
	        //skip other
	        if (strtolower($field->Type)!='params'){continue;}

	        $field->preHandle($elementTable);

            if( $field->Translate )
            {
	            $translationContent = $field->translationContent;

	            $falangManager =  FalangManager::getInstance();
	            if ($falangManager->getCfg('copyparams',1) &&  $translationContent->value==""){
		            $translationContent->value = $field->originalValue;
	            }
	            ?>

                <input type="hidden" name="id_<?php echo $field->Name; ?>"
                       value="<?php echo $translationContent->id; ?>"/>
                <input type="hidden" name="origValue_<?php echo $field->Name; ?>"
                       value='<?php echo md5($field->originalValue); ?>'/>

                <textarea name="origText_<?php echo $field->Name; ?>"
                          style="display:none"><?php echo $field->originalValue; ?></textarea>
	            <?php
	            JLoader::import('models.TranslateParams', FALANG_ADMINPATH);
	            $tpclass = "TranslateParams_" . $elementTable->Name;
	            if (!class_exists($tpclass))
	            {
		            $tpclass = "TranslateParams";
	            }
	            $transparams = new $tpclass($field->originalValue, $translationContent->value, $field->Name, $elementTable->Fields);
	            // TODO sort out default value for author in params when editing new translation
	            $retval = $transparams->editTranslation();
	            if ($retval)
	            {
		            $editorFields[] = $retval;
	            }
            }//if translate
        }//end foreach
        ?>

    </div>
    <!-- ************************ Extra   ************************* -->
    <!-- extra for k2 items -->
    <div id="extras"></div>


	<!-- v 2.8.1 : submit code put at the end to have the editorFields set-->
	<script language="javascript" type="text/javascript">
		Joomla.submitbutton = function(task) {
			<?php
			if( isset($editorFields) && is_array($editorFields) ) {
				foreach ($editorFields as $editor) {
					// Where editor[0] = your areaname and editor[1] = the field name (ex 0:editor_introtext , 1:refField_introtext)
					//TODO 4.0 check below why it's not working anymore
                    //echo $wysiwygeditor->save( $editor[1]);
				}
			}
			?>

			if (task == 'translate.cancel') {
				Joomla.submitform( task, document.getElementById('<?php echo $idForm;?>') );
				return;
			} else {
				Joomla.submitform( task, document.getElementById('<?php echo $idForm;?>') );
			}
		}

        jQuery(document).ready(function($) {
            // Attach behaviour to toggle button.
            $(document).on('click', '#toogle-source-panel', function () {
                var referenceHide = this.getAttribute('data-hide-reference');
                var referenceShow = this.getAttribute('data-show-reference');

                //trim necessary here but not in the joomla association ??
                if ($(this).text().trim() === referenceHide.trim()) {
                    $(this).text(referenceShow);
                }
                else {
                    $(this).text(referenceHide);
                }

                $('.source-panel').toggle();
                $('.action-panel').toggle();
                $('.falang-headers .left').toggle();
                $('.target-panel').toggleClass('full-width');
                //return false the toggle button is in the form
                return false;
            });

            $("#skip_params").on('change', function() {
                $('#falang-params').toggle();
            });
        });


	</script>


    <input type="hidden" name="select_language_id" value="<?php echo $select_language_id;?>" />
    <input type="hidden" name="reference_id" value="<?php echo $this->actContentObject->id;?>" />
    <input type="hidden" name="reference_table" value="<?php echo (isset($elementTable->name) ? $elementTable->name : '');?>" />
    <input type="hidden" name="catid" value="<?php echo $this->catid;?>" />
	<input type="hidden" name="option" value="com_falang" />
	<input type="hidden" name="task" value="translate.edit" />
	<input type="hidden" name="direct" value="<?php echo $input->getInt('direct',0);?>" />

	<?php echo HTMLHelper::_( 'form.token' ); ?>

</form>
