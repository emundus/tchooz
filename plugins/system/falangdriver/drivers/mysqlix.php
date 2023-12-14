<?php
/**
 * @package     Falang for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2010-2023. Faboba.com All rights reserved.
 */

// No direct access to this file
defined('_JEXEC') or die;

use Joomla\Database\Mysqli\MysqliDriver;
use \Joomla\Database\StatementInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Falang\Database\FMysqliStatement;

/**
 * MySQLi FaLang database driver
 *
 * @package     Joomla.Platform
 * @subpackage  Database
 * @see         http://php.net/manual/en/book.mysqli.php
 * @since       11.1
 */
class JOverrideDatabase extends MysqliDriver
{

	protected $statement;

    function __construct($options){
        $db =  Factory::getDbo();
        // support for recovery of existing connections (Martin N. Brampton)
        if (isset($this->options)) $this->options = $options;

        $select		= array_key_exists('select', $options)	? $options['select']	: true;
        $database	= array_key_exists('database',$options)	? $options['database']	: '';

        // perform a number of fatality checks, then return gracefully
        if (!function_exists( 'mysqli_connect' )) {
            $this->_errorNum = 1;
            $this->_errorMsg = 'The MySQL adapter "mysqli" is not available.';
            return;
        }

		// connect to the server
		$this->connection = $db->getConnection();
        // finalize initialization
        parent::__construct($options);

        // select the database
        if ( $select ) {
            $this->select($database);
        }

	    // Register an override for the alias class
		//Sbou4
        require_once (JPATH_PLUGINS . '/system/falangdriver/drivers/FMysqliStatement.php');

    }

	protected function prepareStatement(string $query): StatementInterface
	{
		return new FMysqliStatement($this->connection, $query);
	}


    /*
     * J4 numFields dosen't exit
     * @update 5.0 clean code
     * */
    function _getFieldCount(){
    	//sbou4 numFiels n'existe pas
        if ($this->statement instanceof FMysqliStatement) {
        	return  $this->statement->numFields();
        } else {
		    return 0;
	    }
    }

    /*
     * @update 5.0 clean code
     * */
    function _getFieldMetaData($i){
	    $meta = $this->statement->getMeta($i);
        return $meta;
    }

	function setRefTables(){

		$pfunc = $this->_profile();

		//sbou4 on utilise plus le curson mais le statement
//		if (isset($this->statement)) {
//			$pfunc = $this->_profile($pfunc);
//			return;
//
//		}// && instanceof($this->statement

//		if($this->cursor===true || $this->cursor===false) {
//			$pfunc = $this->_profile($pfunc);
//			return;
//		}



		// only needed for selects at present - possibly add for inserts/updates later
        //sbou5
        //if (is_a($this->sql,'JDatabaseQueryMySQLi')) {

        if (is_a($this->sql,'Joomla\Database\Mysqli\MysqliQuery')) {
           $tempsql = $this->sql->__toString();
        } else {
           $tempsql = $this->sql;
       }

		if (strpos(strtoupper(trim($tempsql)),"SELECT")!==0) {
			$pfunc = $this->_profile($pfunc);
			return;
		}

		$config = Factory::getConfig();

		// get column metadata
		$fields = $this->_getFieldCount();

		if ($fields<=0) {
			$pfunc = $this->_profile($pfunc);
			return;
		}

		$this->_refTables=array();
		$this->_refTables["fieldTablePairs"]=array();
		$this->_refTables["tableAliases"]=array();
		$this->_refTables["reverseTableAliases"]=array();
		$this->_refTables["fieldAliases"]=array();
		$this->_refTables["fieldTableAliasData"]=array();
		$this->_refTables["fieldCount"]=$fields;
		// Do not store sql in _reftables it will disable the cache a lot of the time

		$tableAliases = array();
		for ($i = 0; $i < $fields; ++$i) {
			$meta = $this->_getFieldMetaData($i);
			if (!$meta) {
				echo Text::_('PLG_SYSTEM_FALANGDRIVER_META_NO_INFO');
			}
			else {
				$tempTable =  $meta->table;
				// if I have already found the table alias no need to do it again!
				if (array_key_exists($tempTable,$tableAliases)){
					$value = $tableAliases[$tempTable];
				}
				// mysqli only
				else if (isset($meta->orgtable)){
					$value = $meta->orgtable;
					if (isset($this->_table_prefix) && strlen($this->_table_prefix)>0 && strpos($meta->orgtable,$this->_table_prefix)===0) {
						$value = substr($meta->orgtable, strlen( $this->_table_prefix));
					}
					$tableAliases[$tempTable] = $value;
				}
				else {
					continue;
				}

				if ((!($value=="session" || strpos($value,"jf_")===0)) && $this->translatedContentAvailable($value)){
					/// ARGH !!! I must also look for aliases for fieldname !!
					if (isset($meta->orgname)){
						$nameValue = $meta->orgname;
					}
					else {
						 $nameValue = $meta->name;
					}

					if (!array_key_exists($value,$this->_refTables["tableAliases"])) {
						$this->_refTables["tableAliases"][$value]=$meta->table;
					}
					if (!array_key_exists($meta->table,$this->_refTables["reverseTableAliases"])) {
						$this->_refTables["reverseTableAliases"][$meta->table]=$value;
					}
					// I can't use the field name as the key since it may not be unique!
					if (!in_array($value,$this->_refTables["fieldTablePairs"])) {
						$this->_refTables["fieldTablePairs"][]=$value;
					}
					if (!array_key_exists($nameValue,$this->_refTables["fieldAliases"])) {
						$this->_refTables["fieldAliases"][$meta->name]=$nameValue;
					}

					// Put all the mapping data together so that everything is in sync and I can check fields vs aliases vs tables in one place
					$this->_refTables["fieldTableAliasData"][$i]=array("fieldNameAlias"=>$meta->name, "fieldName"=>$nameValue,"tableNameAlias"=>$meta->table,"tableName"=>$value);

				}

			}
		}
		$pfunc = $this->_profile($pfunc);
	}

}
