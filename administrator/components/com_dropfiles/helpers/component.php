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

jimport('joomla.application.component.helper');

/**
 * Class DropfilesComponentHelper
 */
class DropfilesComponentHelper extends JComponentHelper
{
    /**
     * Method to set config parameters
     *
     * @param array $datas Param data
     *
     * @return boolean
     * @since  version
     */
    public static function setParams($datas)
    {
        $component = JComponentHelper::getComponent('com_dropfiles');
        $table = JTable::getInstance('extension');
        // Load the previous Data
        if (!$table->load($component->id, false)) {
            return false;
        }
        $d = json_decode($table->params);
        foreach ($datas as $key => $data) {
            $d->$key = $data;
        }
        $table->params = json_encode($d);
        // Bind the data.
        if (!$table->bind($datas)) {
            return false;
        }
        // Check the data.
        if (!$table->check()) {
            return false;
        }
        // Store the data.
        if (!$table->store()) {
            return false;
        }
        $jcache = new JCache(array());
        $jcache->clean('_system');
        //unset(self::$components['com_dropfiles']);
        return true;
    }

    /**
     * Quick set single param
     *
     * @param string $key   Param key
     * @param mixed  $value Param value
     *
     * @return boolean
     *
     * @since 5.9.0
     */
    public static function setParam($key, $value)
    {
        $value = is_array($value) ? json_encode($value) : $value;

        return self::setParams(array(
            $key => $value
        ));
    }

    /**
     * Get single param
     *
     * @param string $key     Param key
     * @param mixed  $default Default param value
     *
     * @return mixed
     *
     * @since 5.9.0
     */
    public static function getParam($key, $default = false)
    {
        $params = JComponentHelper::getParams('com_dropfiles');
        return $params->get($key, $default);
    }

    /**
     * Method to get the version of a component
     *
     * @param null|string $option Component name
     *
     * @return null|string
     */
    public static function getVersion($option = 'com_dropfiles')
    {
        $manifest = self::getManifest($option);
        if (property_exists($manifest, 'version')) {
            return $manifest->version;
        }
        return null;
    }

    /**
     * Method to get an object containing the manifest values
     *
     * @param string $option Component name
     *
     * @return false|mixed
     */
    public static function getManifest($option)
    {
        $component = self::getComponent($option);
        $table = JTable::getInstance('extension');
        // Load the previous Data
        if (!$table->load($component->id, false)) {
            return false;
        }

        return json_decode($table->manifest_cache);
    }

    /**
     * Get all tags files
     *
     * @param array $cat_tags Category tags
     *
     * @return string
     * @since  version
     */
    public static function getAllTagsFiles($cat_tags)
    {
        $strTags = '';
        $allTags = array();
        if (!empty($cat_tags)) {
            foreach ($cat_tags as $catId => $tags) {
                if (is_array($tags)) {
                    $allTags = array_merge($allTags, $tags);
                } elseif (is_string($tags)) {
                    $allTags = array_merge($allTags, explode(',', $tags));
                }
            }
        }
        if (!empty($allTags)) {
            $allTags1 = array_unique($allTags);
            //reorder tag by Joomla ordering
            $allTags1 = self::getJoomlaTags($allTags1);
            $strTags = '[';
            foreach ($allTags1 as $value) {
                $strTags .= '"' . $value . '",';
            }
            $strTags = rtrim($strTags, ',');
            $strTags .= ']';
        }

        return $strTags;
    }

    /**
     * Get tag by ordering
     *
     * @param array $arr_tags Tags
     *
     * @return mixed
     * @since  version
     */
    public static function getJoomlaTags($arr_tags)
    {
        $db = JFactory::getDbo();
        $list_tags = '';
        if (!empty($arr_tags)) {
            foreach ($arr_tags as $tag) {
                $list_tags .= $db->quote($tag) . ',';
            }
            $list_tags = rtrim($list_tags, ',');
        }

        $query = $db->getQuery(true)
            ->select(
                array(
                    't.title'
                )
            )
            ->from($db->quoteName('#__tags', 't'));
        if (!empty($list_tags)) {
            $query->where($db->quoteName('t.title') . ' IN (' . $list_tags . ')');
        }

        // Only return published tags
        $query->where($db->quoteName('t.published') . ' = 1 ');

        $order_value = $db->quoteName('t.lft');
        $order_direction = 'ASC';
        $query->order($order_value . ' ' . $order_direction);
        //echo $query;
        $db->setQuery($query);
        $results = $db->loadColumn();

        return $results;
    }

    /**
     * Get trunk size depend on server upload limit (in byte)
     *
     * @return integer
     */
    public static function getTrunkSize()
    {
        $serverUploadLimit = JFilesystemHelper::fileUploadMaxSize(false) - 50 * 1024 ;

        return min($serverUploadLimit, 10 * 1024 * 1024) ;
    }
}
