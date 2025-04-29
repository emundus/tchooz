<?php

namespace Tchooz\Entities\Messages;

use Google\Exception;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\ParameterType;

class TriggerEntity
{
	private DatabaseDriver $db;

	public function __construct(
		private $trigger_id = 0,
		public int $step = 0,
		public array $program_ids = [],
		public null|int $email_id = 0,
		public null|int $sms_id = 0,
		public int $to_current_user = 0,
		public int $to_applicant = 0,
		public array $user_ids = [],
		public array $role_ids = [],
		public array $group_ids = [],
		public int $all_program = 0
	) {
		$this->db = Factory::getContainer()->get('DatabaseDriver');
		Log::addLogger(['text_file' => 'com_emundus.entities.trigger.php'], Log::ALL, ['com_emundus.entities.trigger']);

		if (!empty($trigger_id)) {
			$this->loadTrigger();
		} else {
			if (empty($this->program_ids) && $all_program == 0) {
				throw new \Exception('No program IDs provided');
			}

			if (!isset($this->step) || (empty($this->email_id) && empty($this->sms_id))) {
				throw new \Exception('Invalid trigger data');
			}
		}
	}

	private function loadTrigger(): void
	{
		if (empty($this->trigger_id)) {
			return;
		}

		$query = $this->db->createQuery();
		$query->select('*')
			->from('#__emundus_setup_emails_trigger')
			->where('id = :trigger_id')
			->bind(':trigger_id', $this->trigger_id);

		try {
			$this->db->setQuery($query);
			$trigger = $this->db->loadObject();

			if ($trigger) {
				$this->step = $trigger->step;
				$this->email_id = $trigger->email_id;
				$this->sms_id = $trigger->sms_id;
				$this->to_current_user = $trigger->to_current_user;
				$this->to_applicant = $trigger->to_applicant;
				$this->all_program = $trigger->all_program;

				if ($trigger->all_program == 1) {
					$this->program_ids = [];
				} else {
					$query->clear()
						->select('programme_id')
						->from('#__emundus_setup_emails_trigger_repeat_programme_id')
						->where('parent_id = :trigger_id')
						->bind(':trigger_id', $this->trigger_id);

					$this->db->setQuery($query);
					$this->program_ids = $this->db->loadColumn();
				}

				$query->clear()
					->select('user_id')
					->from('#__emundus_setup_emails_trigger_repeat_user_id')
					->where('parent_id = :trigger_id')
					->bind(':trigger_id', $this->trigger_id);

				$this->db->setQuery($query);
				$this->user_ids = $this->db->loadColumn();

				$query->clear()
					->select('profile_id')
					->from('#__emundus_setup_emails_trigger_repeat_profile_id')
					->where('parent_id = :trigger_id')
					->bind(':trigger_id', $this->trigger_id);
				$this->db->setQuery($query);
				$this->role_ids = $this->db->loadColumn();

				$query->clear()
					->select('group_id')
					->from('#__emundus_setup_emails_trigger_repeat_group_id')
					->where('parent_id = :trigger_id')
					->bind(':trigger_id', $this->trigger_id);
				$this->db->setQuery($query);
				$this->group_ids = $this->db->loadColumn();
			}
		} catch (\Exception $e) {
			Log::add('Error loading trigger: ' . $e->getMessage(), Log::ERROR, 'com_emundus.entities.trigger');
			throw new \Exception('Error loading trigger: ' . $e->getMessage());
		}
	}


	public function getId(): int
	{
		return $this->trigger_id;
	}

	public function setId(int $trigger_id): void
	{
		$this->trigger_id = $trigger_id;
		$this->loadTrigger();
	}

	public function save(int $user_id): bool
	{
		$query = $this->db->createQuery();

		if (empty($this->email_id) && empty($this->sms_id)) {
			throw new \Exception('No email or SMS ID provided');
		}

		if (!empty($this->trigger_id)) {
			$query->update('#__emundus_setup_emails_trigger')
				->set('step = :step')
				->set('email_id = :email_id')
				->set('sms_id = :sms_id')
				->set('to_current_user = :to_current_user')
				->set('to_applicant = :to_applicant')
				->set('all_program = :all_program')
				->set('user = :user_id')
				->where('id = :trigger_id')
				->bind(':trigger_id', $this->trigger_id, ParameterType::INTEGER)
				->bind(':step', $this->step, ParameterType::INTEGER)
				->bind(':to_current_user', $this->to_current_user, ParameterType::INTEGER)
				->bind(':to_applicant', $this->to_applicant, ParameterType::INTEGER)
				->bind(':all_program', $this->all_program, ParameterType::INTEGER)
				->bind(':user_id', $user_id, ParameterType::INTEGER);
		} else {
			$query->insert('#__emundus_setup_emails_trigger')
				->columns('step, email_id, sms_id, to_current_user, to_applicant, all_program, user')
				->values(':step, :email_id, :sms_id, :to_current_user, :to_applicant, :all_program, :user_id')
				->bind(':step', $this->step, ParameterType::INTEGER)
				->bind(':to_current_user', $this->to_current_user, ParameterType::INTEGER)
				->bind(':to_applicant', $this->to_applicant, ParameterType::INTEGER)
				->bind(':all_program', $this->all_program, ParameterType::INTEGER)
				->bind(':user_id', $user_id, ParameterType::INTEGER);
		}

		if ($this->sms_id == 0) {
			$null_value = null;
			$query->bind(':sms_id', $null_value, ParameterType::NULL);
		} else {
			$query->bind(':sms_id', $this->sms_id, ParameterType::INTEGER);
		}

		if ($this->email_id == 0) {
			$null_value = null;
			$query->bind(':email_id', $null_value, ParameterType::NULL);
		} else {
			$query->bind(':email_id', $this->email_id, ParameterType::INTEGER);
		}

		try {
			$this->db->setQuery($query);
			$saved = $this->db->execute();
		} catch (\Exception $e) {
			Log::add('Error saving trigger: ' . $e->getMessage(), Log::ERROR, 'com_emundus.entities.trigger');
			$saved = false;
		}

		if ($saved) {
			if (empty($this->trigger_id)) {
				$insert_id = $this->db->insertid();
				$this->trigger_id = $insert_id;
			}
			$saved_all = [];

			if ($this->all_program == 1) {
				$saved_all[] = $this->saveTriggerRepeatIds('programme_id', []);
			} else {
				$saved_all[] = $this->saveTriggerRepeatIds('programme_id', $this->program_ids);
			}

			$saved_all[] = $this->saveTriggerRepeatIds('user_id', $this->user_ids);
			$saved_all[] = $this->saveTriggerRepeatIds('profile_id', $this->role_ids);
			$saved_all[] = $this->saveTriggerRepeatIds('group_id', $this->group_ids);

			$saved = !in_array(false, $saved_all, true);
		}

		return $saved;
	}

	private function saveTriggerRepeatIds(string $column, array $ids): bool
	{
		$saved = false;
		$query = $this->db->createQuery();

		$query->delete('#__emundus_setup_emails_trigger_repeat_' . $column)
			->where('parent_id = :trigger_id')
			->bind(':trigger_id', $this->trigger_id);

		$this->db->setQuery($query);
		$this->db->execute();

		$saved_all = [];
		foreach ($ids as $id) {
			if (!empty($id)) {
				$query = $this->db->createQuery();
				$query->insert('#__emundus_setup_emails_trigger_repeat_' . $column)
					->columns('parent_id, ' . $column)
					->values(':trigger_id, :' . $column)
					->bind(':trigger_id', $this->trigger_id)
					->bind(':' . $column, $id);

				$this->db->setQuery($query);
				$saved_all[] = $this->db->execute();
			}
		}

		if (count($saved_all) === count($ids) && !in_array(false, $saved_all, true)) {
			$saved = true;
		}

		return $saved;
	}
}