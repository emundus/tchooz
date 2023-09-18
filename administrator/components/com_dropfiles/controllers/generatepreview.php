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

// no direct access
defined('_JEXEC') || die;

jimport('joomla.application.component.controllerform');
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

/**
 * Class DropfilesControllerGeneratepreview
 */
class DropfilesControllerGeneratepreview extends JControllerAdmin
{
    /**
     * Model
     *
     * @var DropfilesModelGeneratepreview
     */
    private $model;

    /**
     * AJAX: Generate files queue
     *
     * @return void
     */
    public function generatequeue()
    {
        JFactory::getApplication();

        if (!class_exists('DropfilesModelGeneratepreview')) {
            $modelGeneratePreviewPath = JPATH_ADMINISTRATOR . '/components/com_dropfiles/models/generatepreview.php';
            JLoader::register('DropfilesModelGeneratepreview', $modelGeneratePreviewPath);
        }

        $model = new DropfilesModelGeneratepreview();
        $model->generateQueue();
        header('Content-Type: application/json');
        header('Status: 200');
        echo json_encode(array('success' => true));
        die;
    }

    /**
     * AJAX: Run queue
     *
     * @return void
     */
    public function runqueue()
    {
        JFactory::getApplication();

        if (!class_exists('DropfilesModelGeneratepreview')) {
            $modelGeneratePreviewPath = JPATH_ADMINISTRATOR . '/components/com_dropfiles/models/generatepreview.php';
            JLoader::register('DropfilesModelGeneratepreview', $modelGeneratePreviewPath);
        }

        $model = new DropfilesModelGeneratepreview();
        $model->runQueue();
        header('Content-Type: application/json');
        header('Status: 200');
        echo json_encode(array('success' => true));
        die;
    }

    /**
     * AJAX: Restart queue
     *
     * @return void
     */
    public function restartqueue()
    {
        JFactory::getApplication();

        if (!class_exists('DropfilesModelGeneratepreview')) {
            $modelGeneratePreviewPath = JPATH_ADMINISTRATOR . '/components/com_dropfiles/models/generatepreview.php';
            JLoader::register('DropfilesModelGeneratepreview', $modelGeneratePreviewPath);
        }

        $model = new DropfilesModelGeneratepreview();
        $generatedQueue = $model->restartQueue();

        header('Content-Type: application/json');
        header('Status: 200');
        $result = array('success' => true);

        if (is_array($generatedQueue) && count($generatedQueue) === 0) {
            $result = array('success' => false, 'code' => 'no_file_vaild', 'message' => 'There is no file to generate preview!');
        }

        echo json_encode($result);
        die;
    }

    /**
     * AJAX: Get current status for generator
     *
     * @return void
     */
    public function status()
    {
        JFactory::getApplication();

        if (!class_exists('DropfilesModelGeneratepreview')) {
            $modelGeneratePreviewPath = JPATH_ADMINISTRATOR . '/components/com_dropfiles/models/generatepreview.php';
            JLoader::register('DropfilesModelGeneratepreview', $modelGeneratePreviewPath);
        }

        $model = new DropfilesModelGeneratepreview();

        $status = $model->getStatus();
        header('Content-Type: application/json');
        header('Status: 200');
        echo json_encode($status);
        die;
    }

    /**
     * Exit with application/json header
     *
     * @param string $status Status
     * @param array  $datas  Data to return
     *
     * @return void
     */
    protected function exitStatus($status = '', $datas = array())
    {
        header('Content-Type: application/json');
        $response = array('response' => $status, 'datas' => $datas);
        echo json_encode($response);
        die();
    }
}
