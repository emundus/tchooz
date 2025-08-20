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

class Release2_8_1Installer extends ReleaseInstaller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];

		$query  = $this->db->createQuery();
		$tasks = [];

		try
		{
			$query->update('#__menu')
				->set('link = ' . $this->db->quote('index.php?option=com_emundus&view=accessibility'))
				->set('component_id = ' . ComponentHelper::getComponent('com_emundus')->id)
				->where('alias = ' . $this->db->quote('accessibilite'));
			$this->db->setQuery($query);
			$tasks[] = $this->db->execute();

			$result['status']  = !in_array(false, $tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}
}