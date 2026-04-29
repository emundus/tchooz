<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

use Joomla\CMS\Component\ComponentHelper;
use Tchooz\Factories\Language\LanguageFactory;

class Release2_19_1Installer extends ReleaseInstaller
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
				->update($this->db->quoteName('#__emundus_setup_actions'))
				->set($this->db->quoteName('status') . ' = 1')
				->where($this->db->quoteName('name') . ' = ' . $this->db->quote('share_filters'));
			$this->db->setQuery($query);
			$this->tasks[] = $this->db->execute();

			$this->tasks[] = \EmundusHelperUpdate::createTable(
				'jos_emundus_setup_workflows_steps_hidden_steps',
				[
					new \EmundusTableColumn('step_id', \EmundusColumnTypeEnum::INT, null, false),
					new \EmundusTableColumn('hidden_step', \EmundusColumnTypeEnum::INT, null, false),
				],
				[
					new \EmundusTableForeignKey('jos_emundus_setup_workflows_steps_hidden_steps_fk', 'step_id', 'jos_emundus_setup_workflows_steps', 'id', \EmundusTableForeignKeyOnEnum::CASCADE, \EmundusTableForeignKeyOnEnum::CASCADE),
					new \EmundusTableForeignKey('jos_emundus_setup_workflows_steps_hidden_steps_fk_2', 'hidden_step', 'jos_emundus_setup_workflows_steps', 'id', \EmundusTableForeignKeyOnEnum::CASCADE, \EmundusTableForeignKeyOnEnum::CASCADE),
				]
			)['status'];

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
