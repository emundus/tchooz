<?php
/**
 * @package     Falang for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2010-2017. Faboba.com All rights reserved.
 */

// No direct access to this file
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Database\Mysql\MysqlDriver;

defined('_JEXEC') or die;

/**
 * MySQLi FaLang database driver now use for PDO
 *
 * @package     Joomla.Platform
 * @subpackage  Database
 * @see         http://php.net/manual/en/book.mysqli.php
 * @since       11.1
 */


class JOverrideDatabase extends MysqlDriver
{
    function __construct($options){
        Factory::getApplication()->enqueueMessage(Text::_('PLG_SYSTEM_FALANGDRIVER_PDO_NOT_SUPPORTED'),'alert');
        parent::__construct($options);
    }

    /**
     * Return the actual SQL Error number
     *
     * @return  integer  The SQL Error number
     *
     * @since   4.0.0
     */
    protected function getErrorNumber()
    {
        return (int) $this->connection->errorCode();
    }
}