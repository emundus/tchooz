<?php
/**
 * Dropfiles
 *
 * We developed this code with our hearts and passion.
 * We hope you found it useful, easy to understand and to customize.
 * Otherwise, please feel free to contact us at contact@joomunited.com *
 *
 * @package   Dropfiles
 * @copyright Copyright (C) 2013 JoomUnited (http://www.joomunited.com). All rights reserved.
 * @copyright Copyright (C) 2013 Damien Barrère (http://www.crac-design.com). All rights reserved.
 * @license   GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 * @license
 */

// No direct access.
defined('_JEXEC') || die;

// Load the JavaScript and css
JHtml::_('behavior.keepalive');
JHtml::_('jquery.framework');
$doc = JFactory::getDocument();
if (DropfilesBase::isJoomla40()) {
    JHtml::_('behavior.core');
    JHtml::_('bootstrap.framework');
} else { // Joomla 3
    JHtml::_('behavior.framework', true);
    JHtml::_('behavior.modal', 'a.modal');
    $doc->addScript(JURI::root() . 'components/com_dropfiles/assets/js/fielduser.min.js');
    $doc->addScript(JURI::root() . 'components/com_dropfiles/assets/js/fieldmultiuser.js');
}

jimport('joomla.application.component.helper');
$params = JComponentHelper::getParams('com_dropfiles');
if ($params->get('custom_icon', 0)) {
    JHtml::_('script', 'media/mediafield.min.js', array('version' => 'auto', 'relative' => true));
}

$app = JFactory::getApplication();
$function = $app->input->get('function', 'jInsertCategory');

JText::script('COM_DROPFILES_JS_DELETE');
JText::script('COM_DROPFILES_JS_EDIT');
JText::script('COM_DROPFILES_JS_CANCEL');
JText::script('COM_DROPFILES_JS_OK');
JText::script('COM_DROPFILES_JS_CONFIRM');
JText::script('COM_DROPFILES_JS_SAVE');
JText::script('COM_DROPFILES_JS_SAVED');
JText::script('COM_DROPFILES_JS_DROP_FILES_HERE');
JText::script('COM_DROPFILES_JS_USE_UPLOAD_BUTTON');
JText::script('COM_DROPFILES_JS_ADD_REMOTE_FILE');
JText::script('COM_DROPFILES_JS_ARE_YOU_SURE');
JText::script('COM_DROPFILES_JS_BROWSER_NOT_SUPPORT_HTML5');
JText::script('COM_DROPFILES_JS_TOO_ANY_FILES');
JText::script('COM_DROPFILES_CTRL_FILES_UPLOAD_FILE_SUCCESS');
JText::script('COM_DROPFILES_CTRL_FILES_WRONG_FILE_EXTENSION');
JText::script('COM_DROPFILES_JS_FILE_TOO_LARGE');
JText::script('COM_DROPFILES_JS_ONLY_IMAGE_ALLOWED');
JText::script('COM_DROPFILES_JS_DBLCLICK_TO_EDIT_TITLE');
JText::script('COM_DROPFILES_JS_WANT_DELETE_CATEGORY');
JText::script('COM_DROPFILES_JS_SELECT_FILES');
JText::script('COM_DROPFILES_JS_IMAGE_PARAMETERS');
JText::script('COM_DROPFILES_JS_X_FILES_IMPORTED');
JText::script('COM_DROPFILES_JS_WAIT_UPLOADING');
JText::script('COM_DROPFILES_JS_ARE_YOU_SURE_DELETE');
JText::script('COM_DROPFILES_JS_FILE_MOVED');
JText::script('COM_DROPFILES_JS_FILE_COPIED');
JText::script('COM_DROPFILES_JS_FILES_MOVED');
JText::script('COM_DROPFILES_JS_FILES_COPIED');
JText::script('COM_DROPFILES_JS_FILES_REMOVED');
JText::script('COM_DROPFILES_JS_FILES_SAVED');
JText::script('COM_DROPFILES_FILE_TO_UPLOAD');
JText::script('COM_DROPFILES_JS_LINK_COPIED');
JText::script('COM_DROPFILES_JS_NO_FILES_SELETED');
JText::script('COM_DROPFILES_JS_NO_FILES_COPIED_CUT');
JText::script('COM_DROPFILES_DEFAULT_FRONT_COLUMNS');
JText::script('COM_DROPFILES_JS_REMOTE_FILE_TITLE');
JText::script('COM_DROPFILES_JS_REMOTE_FILE_URL');
JText::script('COM_DROPFILES_JS_REMOTE_FILE_REMOTE_URL');
JText::script('COM_DROPFILES_JS_REMOTE_FILE_TYPE');
JText::script('COM_DROPFILES_JS_CATEGORY_ORDER');
JText::script('COM_DROPFILES_JS_CATEGORY_SAVED');
JText::script('COM_DROPFILES_JS_CATEGORY_CREATED');
JText::script('COM_DROPFILES_JS_CATEGORY_RENAMED');
JText::script('COM_DROPFILES_JS_CATEGORY_REMOVED');
JText::script('COM_DROPFILES_JS_PLEASE_CREATE_A_FOLDER');
JText::script('COM_DROPFILES_MULTI_CATEGORY_FILE');
JText::script('COM_DROPFILES_MULTI_CATEGORY_EDIT_ORIGINAL_FILE');

$doc->addScriptDeclaration('gcaninsert=' . ($app->input->getBool('caninsert', false) ? 'true' : 'false') . ';');
$doc->addScriptDeclaration('e_name="' . $app->input->getString('e_name') . '";');
$params = JComponentHelper::getParams('com_dropfiles');
$collapse = DropfilesBase::getParam('catcollapsed', 0);
$allowedext_list = '7z,ace,bz2,dmg,gz,rar,tgz,zip,csv,doc,docx,html,key,keynote,odp,ods,odt,pages,pdf,pps,ppt,'
    . 'pptx,rtf,tex,txt,xls,xlsx,xml,bmp,exif,gif,ico,jpeg,jpg,png,psd,tif,tiff,aac,aif,aiff,alac,amr,au,cdda,'
    . 'flac,m3u,m4a,m4p, mid, mp3, mp4, mpa, ogg, pac, ra, wav, wma, 3gp,asf,avi,flv,m4v,mkv,mov,mpeg,mpg,'
    . 'rm,swf,vob,wmv';
$declaration =
    "if(typeof(Dropfiles)=='undefined'){"
    . '     Dropfiles={};'
    . '}'
    . 'Dropfiles.can = {};'
    . 'Dropfiles.can.config=' . (int)$this->canDo->get('core.admin') . ';'
    . 'Dropfiles.can.create=' . (int)$this->canDo->get('core.create') . ';'
    . 'Dropfiles.can.edit=' . (int)$this->canDo->get('core.edit') . ';'
    . 'Dropfiles.can.editown=' . (int)$this->canDo->get('core.edit.own') . ';'
    . 'Dropfiles.can.delete=' . (int)$this->canDo->get('core.delete') . ';'
    . 'Dropfiles.can.upload=' . (int)$this->canDo->get('com_dropfiles.uploadfilesfrontend') . ';'
    . 'Dropfiles.author=' . (int)JFactory::getUser()->id . ';'
    . 'Dropfiles.selected = {};'
    . 'Dropfiles.selected.access = false;'
    . 'Dropfiles.selected.ordering = false;'
    . 'Dropfiles.selected.orderingdir = false;'
    . 'Dropfiles.selected.usergroup = false;'
    . 'Dropfiles.collapse=' . ($collapse ? 'true' : 'false') . ';'
    . "Dropfiles.version='" . DropfilesComponentHelper::getVersion() . "';"
    . 'Dropfiles.maxfilesize = ' . $params->get('maxinputfile', 10) . ';'
    . 'Dropfiles.chunkSize = ' . DropfilesComponentHelper::getTrunkSize() . ';'
    . 'Dropfiles.addRemoteFile = ' . (int)$params->get('addremotefile', 0) . ';'
    . 'Dropfiles.indexgoogle = ' . (int)$params->get('indexgoogle', 1) . ';'
    . "Dropfiles.ajaxurl = '" . JUri::root() . "';"
    . "Dropfiles.categoryrestriction = '" . $params->get('categoryrestriction', 'accesslevel') . "';"
    . "Dropfiles.allowedext = '" . $params->get('allowedext', $allowedext_list) . "';";

$doc->addScriptDeclaration($declaration);

// Load editor for file description
if ($params->get('usereditor', 0)) {
    $editor = JEditor::getInstance('tinymce');
    $editor->initialise();
    $editor->display('dropfilesFileDescription', null, 500, 300, '20', '20', false, 'dropfilesFileDescription', null, null);
}
if ($this->menuItemParams->get('show_page_heading')) : ?>
    <div class="page-header">
        <h1>
            <?php echo $this->escape($this->menuItemParams->get('page_heading')); ?>
        </h1>
    </div>
<?php endif; ?>

<div id="mybootstrap" class="dropfiles-upload singlecategory <?php
if (DropfilesBase::isJoomla30()) {
    echo 'joomla30';
} ?>
<?php if (DropfilesBase::isJoomla40()) {
    echo 'joomla4';
} ?>">
    <input type="hidden" id="dropfiles_upload_target_category"
           data-id-category="<?php echo $this->category->id; ?>" data-author="<?php echo $this->category->created_user_id; ?>" />
    <input type="hidden" id="dropfiles_upload_messages" value="" />
    <?php if ($this->categories[0]) : ?>
        <div id="pwrapper" class="single-upload">
            <div id="wpreview">
                <div class="dropfiles-btn-toolbar" id="dropfiles-toolbar">
                    <?php if ($this->canDo->get('core.delete')) : ?>
                        <div class="btn-wrapper">
                            <button onclick="Joomla.submitbutton('files.delete')" class="btn btn-small" id="dropfiles-delete">
                                <span class="icon-trash"></span>
                                <?php echo JText::_('COM_DROPFILES_DELETE_FILES'); ?></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($this->canDo->get('com_dropfiles.viewfile_download')) : ?>
                        <div class="btn-wrapper">
                            <button onclick="Joomla.submitbutton('files.download')" class="btn btn-small" id="dropfiles-download">
                                <span class="icon-download"></span>
                                <?php echo JText::_('COM_DROPFILES_DOWNLOAD_FILES'); ?></button>
                        </div>
                    <?php endif; ?>
                    <div class="btn-wrapper">
                        <button onclick="Joomla.submitbutton('files.uncheck')" class="btn btn-small" id="dropfiles-uncheck">
                            <span class="icon-remove"></span>
                            <?php echo JText::_('COM_DROPFILES_UNCHECK'); ?></button>
                    </div>
                </div>
                <?php
                $hide_list_class = ($app->input->get('show_category_files', 0)) ? '' : 'hide-list-files';
                ?>
                <div id="preview" class="<?php echo $hide_list_class; ?>"></div>
            </div>
            <input type="hidden" name="id_category" value=""/>
        </div>
    <?php endif; ?>
    <?php if (!$this->categories[0]) : ?>
        <?php echo 'Note: ' . JText::_('COM_DROPFILES_RECONFIGURE_A_CATEGORY_TO_UPLOAD'); ?>
    <?php endif; ?>

</div>
<script>
    jQuery(document).ready(function ($) {
        $.ajax({
            url: "index.php?option=com_dropfiles&task=categories.getAllTags",
            type: "POST"
        }).done(function (data) {
        });
    })
</script>
