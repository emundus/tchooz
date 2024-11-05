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
 */

defined('_JEXEC') || die;

jimport('joomla.form.formfield');

/**
 * Form Field class for the Joomla Framework.
 */
class JFormFieldImportExport extends JFormField
{
    /**
     * Type
     *
     * @var string
     */
    protected $type = 'importexport';

    /**
     * Get label
     *
     * @return string
     */
    protected function getLabel()
    {
        return '';
    }

    /**
     * Field server folder
     *
     * @throws Exception Fire if errors
     *
     * @return string
     */
    protected function getInput()
    {
        // Initialize some field attributes.

        if (!class_exists('DropfilesFilesHelper')) {
            JLoader::register('DropfilesFilesHelper', JPATH_ADMINISTRATOR . '/components/com_dropfiles/helpers/files.php');
        }

        $config             = array();
        $export_folder_type = ( isset($config['export_folder_type']) ) ? $config['export_folder_type'] : 'only_folder';
        $import_file_params = ( isset($config['import_file_params']) ) ? $config['import_file_params'] : array();
        $xml_category_disc  = ( isset($config['import_xml_disc']) ) ? $config['import_xml_disc'] : '';
        $bytes              = (int)$this->getFileSize(ini_get('upload_max_filesize'));
        $size               = DropfilesFilesHelper::bytesToSize($bytes, 0);
        $importFolderOnly   = true;
        ob_start();
        ?>
        <div class="dropfiles-import-export-container">
            <div class="ju-settings-option full-width dropfiles-export">
                <label title="<?php echo JText::sprintf('COM_DROPFILES_CONFIG_IMPORT_EXPORT_SELECTION_TOOLTIP'); ?>" for="export_folder_type" class="ju-setting-label"><?php echo JText::sprintf('COM_DROPFILES_CONFIG_IMPORT_EXPORT_FILES_CATEGORIES'); ?></label>
                <select name="export_folder_type" id="export_folder_type" class="inputbox input-block-level ju-input">
                    <option value="all" style="width: 100%; max-width: 100%; box-sizing: border-box"><?php echo JText::sprintf('COM_DROPFILES_CONFIG_IMPORT_EXPORT_ALL_CATEGORIES_AND_FILES'); ?></option>
                    <option value="only_folder" style="width: 100%; max-width: 100%; box-sizing: border-box"><?php echo JText::sprintf('COM_DROPFILES_CONFIG_IMPORT_EXPORT_ONLY_CATEGORY_STRUCTURE'); ?></option>
                    <option value="selection_folder" style="width: 100%; max-width: 100%; box-sizing: border-box"><?php echo JText::sprintf('COM_DROPFILES_CONFIG_IMPORT_EXPORT_SELECTION_CATEGORIES_AND_FILES'); ?></option>
                </select>
                <input type="hidden" name="dropfiles_export_folders" class="dropfiles_export_folders">
                <a href="#" id="open_export_tree_folders_btn" class="ju-button no-background open_export_tree_folders" style="display: none;"><?php echo JText::sprintf('COM_DROPFILES_CONFIG_IMPORT_EXPORT_SELECT_CATEGORIES'); ?></a>
                <a href="#" id="dropfiles-run-export" class="ju-button orange-outline-button dropfiles-run-export"><span class="spinner" style="display:none; margin: 0; vertical-align: middle"></span><?php echo JText::sprintf('COM_DROPFILES_CONFIG_IMPORT_EXPORT_RUN_EXPORT'); ?></a>
            </div>
            <div class="ju-settings-option full-width dropfiles-import">
                <div class="ju-settings-option-item">
                    <label title="<?php echo JText::sprintf('COM_DROPFILES_CONFIG_IMPORT_EXPORT_BROWSE_AND_SELECT_FILES'); ?>" class="ju-setting-label"><?php echo JText::sprintf('COM_DROPFILES_CONFIG_IMPORT_EXPORT_IMPORT_FILES_CATEGORIES'); ?></label>
                    <input type="file" name="import" id="dropfiles_import_folders" class="dropfiles_import_folders">
                    <input type="hidden" name="max_file_size" value="<?php echo $bytes; ?>"/>
                    <button name="dropfiles_import_folders_btn" type="submit" id="dropfiles_import_folder_btn" class="ju-button dropfiles_import_folder_btn orange-outline-button waves-effect waves-light"
                            data-path="<?php echo ( isset($import_file_params['path']) ) ? $import_file_params['path'] : '' ?>"
                            data-id="<?php echo ( isset($import_file_params['id']) ) ? $import_file_params['id'] : '' ?>"
                            data-import_only_folder="<?php echo (isset($import_file_params['import_only_folder'])) ? $import_file_params['import_only_folder'] : 1 ?>">
                        <?php echo JText::sprintf('COM_DROPFILES_CONFIG_IMPORT_EXPORT_RUN_IMPORT'); ?>
                    </button>
                </div>
                <div class="ju-settings-option-item">
                    <label class="dropfilesqtip"><?php echo JText::sprintf('COM_DROPFILES_CONFIG_IMPORT_EXPORT_MAX_SIZE_SERVER_VALUE') . $size; ?></label>
                    <?php if ($importFolderOnly) : ?>
                        <p class="only-folder-option">
                            <input type="checkbox" value="1" name="import_only_folder" id="import-attachments" checked />
                            <label for="import-attachments"><?php echo JText::sprintf('COM_DROPFILES_CONFIG_IMPORT_EXPORT_IMPORT_ONLY_CATEGORY_STRUCTURE'); ?></label>
                        </p>
                    <?php endif; ?>
                    <input type="hidden" name="dropfiles-import-xml-disc" id="dropfiles-import-xml-disc" value="<?php echo $xml_category_disc; ?>" />
                    <div class="dropfiles_import_error_message_wrap"></div>
                </div>
            </div>
            <input type="hidden" id="dropfiles_import_export_action" value="<?php echo JRoute::_('index.php?option=com_dropfiles&task=config.savefolderimportexportparams'); ?>" />
        </div>
        <?php
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    /**
     * Get correct file size
     *
     * @param string|integer $val File size
     *
     * @throws Exception Fire if errors
     *
     * @return string|mixed
     */
    protected function getFileSize($val = 0)
    {
        $val  = trim($val);

        if (is_numeric($val)) {
            return $val;
        } else {
            $last = strtolower($val[strlen($val)-1]);
            $val  = substr($val, 0, -1);

            switch ($last) {
                case 'g':
                    $val = $val * 1024 * 1024 * 1024;
                    break;
                case 'm':
                    $val = $val * 1024 * 1024;
                    break;
                case 'k':
                    $val = $val * 1024;
                    break;
            }

            return $val;
        }
    }
}
