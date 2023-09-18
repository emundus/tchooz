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
class JFormFieldGoogleconnectmode extends JFormField
{

    /**
     * Type
     *
     * @var string
     */
    protected $type = 'Googleconnectmode';

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
     * Add field Google drive connection mode
     *
     * @return string
     */
    protected function getInput()
    {
        $path_dropfilesgoogleconnect = JPATH_ADMINISTRATOR . '/components/com_dropfiles/cloud-connector/cloud/GoogleDrive.php';
        JLoader::register('GoogleDrive', $path_dropfilesgoogleconnect);
        $googleConnect = new GoogleDrive();
        $input = $googleConnect->displayGGDSettings();
        ob_start();
        echo $input;

        return ob_get_clean();
    }
}
