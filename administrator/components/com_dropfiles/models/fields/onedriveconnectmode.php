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
class JFormFieldOnedriveconnectmode extends JFormField
{

    /**
     * Type
     *
     * @var string
     */
    protected $type = 'Onedriveconnectmode';

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
     * Add field OneDrive drive connection mode
     *
     * @return string
     */
    protected function getInput()
    {
        $path_dropfilesonedriveconnect = JPATH_ADMINISTRATOR . '/components/com_dropfiles/cloud-connector/cloud/OneDrive.php';
        JLoader::register('OneDrive', $path_dropfilesonedriveconnect);
        $oneDriveConnect = new OneDrive();
        $input = $oneDriveConnect->displayODSettings();
        ob_start();
        echo $input;

        return ob_get_clean();
    }
}
