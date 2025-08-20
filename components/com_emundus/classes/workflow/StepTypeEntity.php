<?php

namespace Emundus\Workflow;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseDriver;

class StepTypeEntity
{
	public int $id;
	public int $parent_id = 0;

	public string $label;

	public int $action_id;

	public bool $system = false;

	private DatabaseDriver $db;

	public function __construct(int $id)
	{
		$this->id = $id;
		$this->db = Factory::getContainer()->get('DatabaseDriver');

		$this->load();
	}

	public function load(): void
	{
		$query = $this->db->createQuery();

		$query->select('est.*')
			->from($this->db->quoteName('#__emundus_setup_step_types', 'est'))
			->where('est.id = ' . $this->id);

		$this->db->setQuery($query);
		$stepType = $this->db->loadObject();

		if (!empty($stepType)) {
			$this->parent_id = $stepType->parent_id;
			$this->label = $stepType->label;
			$this->action_id = $stepType->action_id;
			$this->system = $stepType->system;
		}
	}
}