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

class Release2_19_0Installer extends ReleaseInstaller
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
			$this->tasks[] = \EmundusHelperUpdate::addColumn('jos_emundus_setup_campaigns', 'program_id', 'INT', 11)['status'];

			// set program id for existing campaigns
			$query->clear()
				->select('esc.id, esp.id AS program_id')
				->from($this->db->quoteName('jos_emundus_setup_campaigns', 'esc'))
				->leftJoin($this->db->quoteName('jos_emundus_setup_programmes', 'esp') . ' ON ' . $this->db->quoteName('esc.training') . ' = ' . $this->db->quoteName('esp.code'))
				->where($this->db->quoteName('esc.program_id') . ' IS NULL');

			$campaigns = $this->db->setQuery($query)->loadObjectList();

			foreach ($campaigns as $campaign)
			{
				$query->clear()
					->update($this->db->quoteName('jos_emundus_setup_campaigns'))
					->set($this->db->quoteName('program_id') . ' = ' . (int) $campaign->program_id)
					->where($this->db->quoteName('id') . ' = ' . (int) $campaign->id);
				$this->tasks[] = $this->db->setQuery($query)->execute();
			}

			// add foreign key constraint if not exists
			// check if the foreign key already exists
			$query->clear()
				->select('CONSTRAINT_NAME')
				->from($this->db->quoteName('information_schema.KEY_COLUMN_USAGE'))
				->where($this->db->quoteName('TABLE_NAME') . ' = ' . $this->db->quote('jos_emundus_setup_campaigns'))
				->where($this->db->quoteName('COLUMN_NAME') . ' = ' . $this->db->quote('program_id'))
				->where($this->db->quoteName('CONSTRAINT_NAME') . ' = ' . $this->db->quote('fk_jesc_program_id'));
			$fkExists = $this->db->setQuery($query)->loadResult();

			if (!$fkExists)
			{
				$query = "ALTER TABLE `jos_emundus_setup_campaigns`
				ADD CONSTRAINT `fk_jesc_program_id`
				FOREIGN KEY (`program_id`)
				REFERENCES `jos_emundus_setup_programmes`(`id`)
				ON DELETE SET NULL
				ON UPDATE CASCADE";
				$this->tasks[] = $this->db->setQuery($query)->execute();
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
