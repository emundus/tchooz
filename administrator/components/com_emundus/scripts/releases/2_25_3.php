<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

class Release2_25_3Installer extends ReleaseInstaller
{
	private array $tasks = [];

	public function __construct()
	{
		parent::__construct();
	}

	public function install()
	{
		$result = ['status' => false, 'message' => ''];

		try
		{
			$this->removeTutorialsModuleDeprecated();

			$result['status'] = !in_array(false, $this->tasks);
		}
		catch (\Exception $e)
		{
			$result['status']  = false;
			$result['message'] = $e->getMessage();
		}

		return $result;
	}

	private function removeTutorialsModuleDeprecated(): void
	{
		$query = $this->db->createQuery();

		$query->select('id')
			->from($this->db->qn('#__modules'))
			->where($this->db->qn('module') . ' = ' . $this->db->q('mod_emundus_tutorial'));
		$this->db->setQuery($query);
		$tutorialModules = $this->db->loadColumn();

		if(!empty($tutorialModules))
		{
			$query->clear()
				->delete($this->db->qn('#__modules_menu'))
				->whereIn($this->db->qn('moduleid'), $tutorialModules);
			$this->db->setQuery($query);
			$this->tasks[] = $this->db->execute();

			$query->clear()
				->delete($this->db->qn('#__modules'))
				->where($this->db->qn('module') . ' = ' . $this->db->q('mod_emundus_tutorial'));
			$this->db->setQuery($query);
			$this->tasks[] = $this->db->execute();
		}
	}
}
