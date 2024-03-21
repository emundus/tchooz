<?php
/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use EmundusHelperUpdate;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;

require_once JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/update.php';

class ReleaseInstaller
{
	/**
	 * @var mixed
	 * @since version 2.0.0
	 */
	protected $db;


	public function __construct()
	{
		$this->db = Factory::getContainer()->get('DatabaseDriver');
	}
}