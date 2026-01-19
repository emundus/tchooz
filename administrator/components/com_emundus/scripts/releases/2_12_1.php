<?php

/**
 * @package     scripts
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace scripts;

class Release2_12_1Installer extends ReleaseInstaller
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
			$this->tasks[] = \EmundusHelperUpdate::installExtension('plg_fabrik_element_action', 'action', null, 'plugin', 1, 'fabrik_element');

			$canBeSentColumnExists = $this->db->setQuery("SHOW COLUMNS FROM `jos_emundus_setup_workflow_step_choices_rules` LIKE 'can_be_sent';")->loadObject();
			if(!$canBeSentColumnExists)
			{
				$this->tasks[] = $this->db->setQuery('ALTER TABLE jos_emundus_setup_workflow_step_choices_rules ADD COLUMN `can_be_sent` TINYINT(3) NOT NULL DEFAULT 1;')->execute();
			}

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