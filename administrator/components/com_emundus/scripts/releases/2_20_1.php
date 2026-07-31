<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use scripts\ReleaseInstaller;

class Release2_20_1Installer extends ReleaseInstaller
{
	private array $tasks = [];

	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];

		$query = $this->db->createQuery();

		try
		{
			$query->clear()
				->update($this->db->quoteName('#__menu'))
				->set($this->db->quoteName('published') . ' = 0')
				->where($this->db->quoteName('link') . ' = ' . $this->db->quote('index.php?option=com_content&view=article&id=53'));
			$this->db->setQuery($query);
			$this->tasks[] = $this->db->execute();

			$result['status'] = !in_array(false, $this->tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}
}
