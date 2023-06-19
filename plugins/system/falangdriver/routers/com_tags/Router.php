<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_tags
 *
 * @copyright   (C) 2013 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Falang\Component\Tags\Site\Service;

\defined('_JEXEC') or die;

use FalangManager;
use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Component\Tags\Site\Service\Router;


/**
 * Falang override Routing class from com_tags
 *
 * @since  4.0.6
 */
class FalangRouter extends Router
{

    protected function fixSegment($segment)
    {
        $db = Factory::getDbo();
        $lang         = Factory::getLanguage();
        $default_lang = ComponentHelper::getParams('com_languages')->get('site', 'en-GB');

        // Try to find tag id
        $alias = str_replace(':', '-', $segment);

        if ($lang->getTag() != $default_lang){

            $fManager = FalangManager::getInstance();
            $id_lang = $fManager->getLanguageID($lang->getTag());


            $query = $db->getQuery(true);
            $query->select('reference_id')
                ->from('#__falang_content fc')
                ->where('fc.value = ' .  $db->quote($alias))
                ->where('fc.language_id = ' . $query->q($id_lang))
                ->where('fc.reference_field = '.$query->q('alias'))
                ->where('fc.published = 1')
                ->where('fc.reference_table = '.$query->q('tags'));

            $db->setQuery($query);
            $refid = $db->loadResult();

            //if falang translation exist for this alias tag return the segment
            if ($refid){
                $segment = "$refid:$alias";
                return $segment;
            }


        }

        $query = $db->getQuery(true)
            ->select('id')
            ->from($db->quoteName('#__tags'))
            ->where($db->quoteName('alias') . " = " .$db->quote($alias));

        $id = $db->setQuery($query)->loadResult();

        if ($id)
        {
            $segment = "$id:$alias";
        }

        return $segment;


    }
}
