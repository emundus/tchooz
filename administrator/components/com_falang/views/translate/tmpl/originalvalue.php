<?php
/**
 * @package     Falang for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2010-2023. Faboba.com All rights reserved.
 */

// No direct access to this file
defined('_JEXEC') or die;

$elementTable = $this->actContentObject->getTable();
$field =  $this->field;
foreach ($elementTable->Fields as $fld) {
	if ($fld->Name ==$this->field ){
		echo $fld->originalValue;
	}
}
