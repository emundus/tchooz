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
 *
 * Source code from Joomla's content search plugin
 */
namespace Joomla\Component\Dropfiles\Site\Helper;

\defined('_JEXEC') || die;

use Joomla\CMS\Categories\CategoryNode;
use Joomla\CMS\Language\Multilanguage;

/**
 * Content Component Route Helper.
 */
abstract class RouteHelper
{
    /**
     * Get the dropfiles route.
     *
     * @param integer $id       The route of the content item.
     * @param integer $catid    The category ID.
     * @param integer $language The language code.
     * @param string  $layout   The layout value.
     * @param string  $q        The key search.
     *
     * @return string  The dropfiles route.
     */
    public static function getDropfilesRoute($id, $catid = 0, $language = 0, $layout = null, $q = '')
    {
        // Create the link
        $link = 'index.php?option=com_dropfiles&view=frontsearch&id=' . $id;

        if ((int) $catid > 1) {
            $link .= '&catid=' . $catid;
        }

        if ($language && $language !== '*' && Multilanguage::isEnabled()) {
            $link .= '&lang=' . $language;
        }

        if ($layout) {
            $link .= '&layout=' . $layout;
        }

        if ($q !== '') {
            $link .= '&q=' . $q;
        }

        return $link;
    }

    /**
     * Get the category route.
     *
     * @param integer $catid    The category ID.
     * @param integer $language The language code.
     * @param string  $layout   The layout value.
     *
     * @return string  The dropfiles route.
     */
    public static function getCategoryRoute($catid, $language = 0, $layout = null)
    {
        if ($catid instanceof CategoryNode) {
            $id = $catid->id;
        } else {
            $id = (int) $catid;
        }

        if ($id < 1) {
            return '';
        }

        $link = 'index.php?option=com_dropfiles&view=category&id=' . $id;

        if ($language && $language !== '*' && Multilanguage::isEnabled()) {
            $link .= '&lang=' . $language;
        }

        if ($layout) {
            $link .= '&layout=' . $layout;
        }

        return $link;
    }

    /**
     * Get the form route.
     *
     * @param integer $id The form ID.
     *
     * @return string  The dropfiles route.
     */
    public static function getFormRoute($id)
    {
        return 'index.php?option=com_dropfiles&task=dropfiles.edit&a_id=' . (int) $id;
    }
}
