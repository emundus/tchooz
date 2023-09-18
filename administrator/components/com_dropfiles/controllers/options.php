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
 * Class DropfilesControllerOptions
 */
class DropfilesControllerOptions extends JControllerAdmin
{
    /**
     * Method to retrieve option value
     *
     * @param string $name Option name
     *
     * @return string|boolean|void
     */
    public function get_option($name) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- GET data only
    {
        if (!$name || $name === '') {
            return false;
        }

        JFactory::getApplication();
        $model = $this->getModel('options');

        return $model->get_option($name);
    }

    /**
     * Method to update option value
     *
     * @param string       $name Option name
     * @param string|array $val  Option val
     *
     * @return string|boolean|void
     */
    public function update_option($name, $val = '') // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- UPDATE data only
    {
        $update = false;
        if (!$name || $name === '') {
            return $update;
        }

        JFactory::getApplication();
        $model = $this->getModel('options');
        $update = $model->update_option($name, $val);

        return $update;
    }

    /**
     * Method to delete option
     *
     * @param string $name Option name
     *
     * @return string|boolean|void
     */
    public function delete_option($name) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- DELETE data only
    {
        $delete = false;
        if (!$name || $name === '') {
            return $delete;
        }

        JFactory::getApplication();
        $model = $this->getModel('options');
        $delete = $model->delete_option($name);

        return $delete;
    }
}
