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
class JFormFieldOnedrivebusinessconnectmode extends JFormField
{

    /**
     * Type
     *
     * @var string
     */
    protected $type = 'Onedrivebusinessconnectmode';

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
     * Add field OneDrive Business drive connection mode
     *
     * @return string
     */
    protected function getInput()
    {
        $path_dropfilesonedrivebusinessconnect = JPATH_ADMINISTRATOR . '/components/com_dropfiles/cloud-connector/cloud/OneDriveBusiness.php';
        JLoader::register('OneDriveBusiness', $path_dropfilesonedrivebusinessconnect);
        $onedriveBusinessConnect = new OneDriveBusiness();
        $input = $onedriveBusinessConnect->displayODBSettings();
        ob_start();
        echo $input;

        return ob_get_clean();
    }
}
