<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

namespace SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model;

defined('_JEXEC') or die;

use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\FilemanagerModel;

class FilesintegrityModel extends FilemanagerModel
{

	public function __construct($config = [])
	{		
		parent::__construct($config);
	}
}