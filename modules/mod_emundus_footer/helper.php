<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_login
 *
 * @copyright   Copyright (C) 2005 - 2020 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Helper for mod_emundus_footer
 *
 * @since  1.5
 */
class ModEmundusFooterHelper
{
    /**
     * Retrieve the URL where the user should be returned after logging in
     *
     * @param   \Joomla\Registry\Registry  $params  module parameters
     * @param   string                     $type    return type
     *
     * @return string
     */
    public static function getReturnUrl($params, $type)
    {
        $app  = JFactory::getApplication();
        $item = $app->getMenu()->getItem($params->get($type));

        // Stay on the same page
        $url = JUri::getInstance()->toString();

        if ($item)
        {
            $lang = '';

            if ($item->language !== '*' && JLanguageMultilang::isEnabled())
            {
                $lang = '&lang=' . $item->language;
            }

            $url = 'index.php?Itemid=' . $item->id . $lang;
        }

        return base64_encode($url);
    }
}