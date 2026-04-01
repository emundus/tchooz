<?php
/**
 * Securitycheck Pro table class
 * @ author Jose A. Luque
 * @ Copyright (c) 2011 - Jose A. Luque
 *
 * @license GNU/GPL v2 or later http://www.gnu.org/licenses/gpl-2.0.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Table\Table;

/**
 * Logs Table class
 */
class TableLogs extends Table
{
    /**
     * Primary Key
     *
     * @var int
     */
    var $id = null;

    /**
     * @var string
     */
    var $ip = null;
	
	/**
     * @var string
     */
    var $time = null;
	
	/**
     * @var string
     */
    var $tag_description = null;
	
	/**
     * @var string
     */
    var $description = null;
	
	/**
     * @var string
     */
    var $type = null;
	
	/**
     * @var string
     */
    var $uri = null;
	
	/**
     * @var int
     */
    var $marked = 0;

    /**
     * Constructor
     *
     * @param object $db Database connector object
	 *
	 * @return void
     */
    function TableLogs(&$db)
    {
        parent::__construct('#__securitycheckpro_logs', 'id', $db);
    }
}
