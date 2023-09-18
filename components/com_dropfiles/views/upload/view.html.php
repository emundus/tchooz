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


/**
 * Class DropfilesViewUpload
 */
class DropfilesViewUpload extends JViewLegacy
{
    /**
     * Display the view
     *
     * @param null|string $tpl Template
     *
     * @return void|boolean
     */
    public function display($tpl = null)
    {
        JLoader::register('DropfilesBase', JPATH_ADMINISTRATOR . '/components/com_dropfiles/classes/dropfilesBase.php');
        $app = JFactory::getApplication();
        $user = JFactory::getUser();
        $modelCat = JModelLegacy::getInstance('Frontcategory', 'dropfilesModel');
        $catid = $app->input->getInt('catid', 0);
        $category = $modelCat->getCategory($catid);
        $this->category = $category;
        $this->canDo = DropfilesHelper::getActions();
        $uploadRole = DropfilesBase::getAuthUploadFilesOnFrontend();
        $editRole = $this->canDo->get('core.edit');
        $editOwn = $this->canDo->get('core.edit.own');
        if ($editOwn && ((int) $category->created_user_id === (int) $user->id)) {
            $editOwnRole = true;
        } else {
            $editOwnRole = false;
        }
        $canUploadFiles = false;

        if ($editRole || $editOwnRole || $uploadRole) {
            $canUploadFiles = true;
        }

        if (!$canUploadFiles) {
            $app->enqueueMessage(JText::_('COM_DROPFILES_CONFIG_UPLOAD_FILES_ON_FRONTEND_WRONG_PERMISSION'), 'error');
            return false;
        }

        // Get active menu
        $this->menuItemParams = null;
        $currentMenuItem = $app->getMenu()->getActive();
        if ($currentMenuItem) {
            // Get params for active menu
            $this->menuItemParams = $currentMenuItem->getParams();
        }
        $this->params = JComponentHelper::getParams('com_dropfiles');
        $params = $this->params;

        $this->categories = array($modelCat->getCategory($catid));

        $params = JComponentHelper::getParams('com_dropfiles');
        if ($params->get('import') && !$app->input->getBool('caninsert', 0) && $user->authorise('core.admin')) {
            $this->importFiles = true;
        } else {
            $this->importFiles = false;
        }

        $this->setLayout($app->input->get('layout', 'default'));

        parent::display($tpl);
    }

    /**
     * Return a json response
     *
     * @param boolean $status Response status
     * @param array   $datas  Array of datas to return with the json string
     *
     * @return void
     * @throws \Exception Throw when application can not start
     * @since  version
     */
    private function exitStatus($status, $datas = array())
    {
        $response = array('response' => $status, 'datas' => $datas);
        echo $status;
        JFactory::getApplication()->close();
    }
}
