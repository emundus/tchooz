<?php
/**
 * Track Actions
 * @ author Jose A. Luque
 * @ Copyright (c) 2011 - Jose A. Luque
 *
 * @license GNU/GPL v2 or later http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('_JEXEC') or die;

use Joomla\CMS\Table\Table;

/**
 * Userlogs Table class
 *
 * @since __DEPLOY_VERSION__
 */
class TableTrackActions extends Table
{
    /**
     * Constructor
     *
     * @param object $db Database connector object
     *
     * @return void
     */
    public function __construct(&$db)
    {
        parent::__construct('#__securitycheckpro_trackactions', 'id', $db);
    }
}
