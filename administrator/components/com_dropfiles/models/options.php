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
 * @copyright Copyright (C) 2013 Damien Barr�re (http://www.crac-design.com). All rights reserved.
 * @license   GNU General Public License version 2 or later; http://www.gnu.org/licenses/gpl-2.0.html
 * @since     1.6
 */

// no direct access
defined('_JEXEC') || die;

jimport('joomla.application.component.modellist');
jimport('joomla.access.access');


/**
 * Class DropfilesModelOptions
 */
class DropfilesModelOptions extends JModelList
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

        $dbo = $this->getDbo();
        $query = 'SELECT value FROM #__dropfiles_options WHERE name = ' . $dbo->quote($name);
        $dbo->setQuery($query);

        if (!$dbo->execute()) {
            return false;
        }

        $rs = $dbo->loadResult();

        if ($rs === 'true') {
            $rs = true;
        } elseif ($rs === 'false') {
            $rs = false;
        }

        return $rs;
    }

    /**
     * Method to update option
     *
     * @param string       $name Option name
     * @param string|array $val  Option val
     *
     * @return string|boolean|void
     */
    public function update_option($name, $val = '') // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- UPDATE data only
    {
        if (!$name || $name === '') {
            return false;
        }

        $dbo = $this->getDbo();
        $has = $this->get_option($name);
        $type = gettype($val);

        switch ($type) {
            case 'array':
                $val = json_encode($val);
                break;
            case 'boolean':
                if ($val === true) {
                    $val = 'true';
                } else {
                    $val = 'false';
                }
                break;
        }

        if (!is_null($has)) {
            $query = 'UPDATE #__dropfiles_options SET value=' . $dbo->quote($val) . ' WHERE name=' . $dbo->quote($name);
        } else {
            $query = 'INSERT INTO #__dropfiles_options (name,value) VALUES (' . $dbo->quote($name) . ',' . $dbo->quote($val) . ')';
        }

        $dbo->setQuery($query);

        if (!$dbo->execute()) {
            return false;
        }

        return true;
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
        if (!$name || $name === '') {
            return false;
        }

        $dbo = $this->getDbo();
        $query = 'DELETE From #__dropfiles_options WHERE name = ' . $dbo->quote($name);
        $dbo->setQuery($query);

        if (!$dbo->execute()) {
            return false;
        }

        return true;
    }

    /**
     * Method to delete option groups
     *
     * @param string $name     Option name
     * @param string $position Position join
     *
     * @return string|boolean|void
     */
    public function delete_option_groups($name, $position = 'right') // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- DELETE data group only
    {
        if (!$name || $name === '') {
            return false;
        }

        $dbo = $this->getDbo();
        switch ($position) {
            case 'both':
                $query = 'DELETE From `#__dropfiles_options` WHERE name like "%' . $name . '%"';
                break;
            case 'left':
                $query = 'DELETE From `#__dropfiles_options` WHERE name like "%' . $name . '"';
                break;
            case 'right':
            default:
                $query = 'DELETE From `#__dropfiles_options` WHERE name like "' . $name . '%"';
                break;
        }
        $dbo->setQuery($query);

        if (!$dbo->execute()) {
            return false;
        }

        return true;
    }
}
