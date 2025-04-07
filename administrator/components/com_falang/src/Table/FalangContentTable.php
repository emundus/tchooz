<?php
/**
 * @package     Falang for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2010-2023. Faboba.com All rights reserved.
 */
namespace Falang\Component\Administrator\Table;

// No direct access to this file
use Joomla\CMS\Table\Table;
use Joomla\CMS\Language\Text;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\DispatcherInterface;


defined('_JEXEC') or die;

/**
 * Database class for handling the falang contents
 *
 * @rename with Table Suffix
 *
 * @package falang
 * @subpackage administrator
 * @author Alex Kempkens <joomfish@thinknetwork.com>
 * @author Stéphane Bouey <@faboba.com>
 */
class FalangContentTable extends Table  {
	/** @var int Primary ke */
	var $id=null;
	/** @var int Reference id for the language */
	var $language_id=null;
	/** @var int Reference id for the original content */
	var $reference_id=null;
	/** @var int Reference table of the original content */
	var $reference_table=null;
	/** @var int Reference field of the original content */
	var $reference_field=null;
	/** @var string translated value*/
	var $value=null;
	/** @var string original value for equals check*/
	var $original_value=null;
	/** @var string original value for equals check*/
	var $original_text=null;
	/** @var date Date of last modification*/
	var $modified=null;
	/** @var string Last translator*/
	var $modified_by=null;
	/** @var boolean Flag of the translation publishing status*/
	var $published=false;


	/** Standard constructur
	 * front 5.16
	 * constuctor with $db removed
	*/

	public function __construct(DatabaseDriver $db, ?DispatcherInterface $dispatcher = null)
	{
		parent::__construct('#__falang_content', 'id', $db, $dispatcher);
	}

	/**
	 * Bind the content of the newValues to the object. Overwrite to make it possible
	 * to use also objects here
	 *
	 * @update 5.16 fix bind signature and rename $newValue by $src
	 */
	function bind( $src, $ignore = [] ) {
		if (is_array( $src )) {
			return parent::bind( $src );
		} else {
			foreach (get_object_vars($this) as $k => $v) {
				if ( isset($src->$k) ) {
					$this->$k = $src->$k;
				}
			}
		}
		return true;
	}


	/**
	 * Validate language information
	 * Name and Code name are mandatory
	 * activated will automatically set to false if not set
	 */
	function check() {
		if (trim( $this->language_id ) == '') {
			$this->_error = Text::_('NO_LANGUAGE_DBERROR');
			return false;
		}

		return true;
	}

	function toString() {
		$retString = "<p>content field:<br />";
		$retString .= "id=$this->id; language_id=$this->language_id<br>";
		$retString .= "reference_id=$this->reference_id, reference_table=$this->reference_table, reference_field=$this->reference_field<br>";
		$retString .= "value=>" .htmlspecialchars($this->value). "<<br />";
		$retString .= "original_value=>" .htmlspecialchars($this->original_value). "<<br />";
		$retString .="modified=$this->modified, modified_by=$this->modified_by, published=$this->published</p>";

		return $retString;
	}
}

?>
