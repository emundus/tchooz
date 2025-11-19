<?php

namespace Tchooz\Repositories\Workflow;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Actions\ActionEntity;
use Tchooz\Entities\Actions\CrudEntity;
use Tchooz\Entities\Workflow\StepTypeEntity;
use Tchooz\Factories\Workflow\StepTypeFactory;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Traits\TraitTable;
use Joomla\Database\DatabaseDriver;

#[TableAttribute(table: 'jos_emundus_setup_step_types')]
class StepTypeRepository
{
	use TraitTable;
	private DatabaseDriver $db;

	public function __construct(?DatabaseDriver $db = null)
	{
		$this->db = $db ?? Factory::getContainer()->get('DatabaseDriver');
		Log::addLogger(['text_file' => 'com_emundus.repository.step.php'], Log::ALL, ['com_emundus.repository.step']);
	}

	/**
	 * @param   int  $id
	 *
	 * @return StepTypeEntity|null
	 */
	public function getStepTypeById(int $id): ?StepTypeEntity
	{
		$stepType = null;

		if (!empty($id))
		{
			$query = $this->db->createQuery()
				->select('est.*')
				->from($this->db->quoteName($this->getTableName(self::class), 'est'))
				->where('est.id = ' . $id);

			$this->db->setQuery($query);
			$result = $this->db->loadObject();

			if ($result) {
				$stepType = StepTypeFactory::fromDbObjects([$result])[0];
			}
		}

		return $stepType;
	}

	/**
	 * @param   string  $code
	 *
	 * @return StepTypeEntity|null
	 */
	public function getStepTypeByCode(string $code): ?StepTypeEntity
	{
		$stepType = null;

		if (!empty($code))
		{
			$query = $this->db->createQuery()
				->select('est.*')
				->from($this->db->quoteName($this->getTableName(self::class), 'est'))
				->where('est.code = ' . $this->db->quote($code));

			$this->db->setQuery($query);
			$result = $this->db->loadObject();

			if ($result) {
				$stepType = StepTypeFactory::fromDbObjects([$result])[0];
			}
		}

		return $stepType;
	}

	/**
	 * @param   StepTypeEntity  $stepType
	 *
	 * @return bool
	 */
	public function flush(StepTypeEntity $stepType): bool
	{
		$flushed = false;

		try {
			if (!empty($stepType->getId()))
			{
				$update = (object) [
					'parent_id'   => $stepType->getParentId(),
					'label'       => $stepType->getLabel(),
					'action_id'   => $stepType->getActionId(),
					'code'        => $stepType->getCode(),
					'published'   => $stepType->isPublished() ? 1 : 0,
					'system'      => $stepType->isSystem() ? 1 : 0,
					'class' 	 => $stepType->getClass(),
					'id'          => $stepType->getId(),
				];

				if ($this->db->updateObject($this->getTableName(self::class), $update, 'id'))
				{
					$flushed = true;
				}
			}
			else
			{
				if (empty($stepType->getActionId()))
				{
					$actionName = !empty($stepType->getCode()) ? $stepType->getCode() : 'custom_step_type_' . time();
					$crudAction = new ActionEntity(0, $actionName, $stepType->getLabel(),
						new CrudEntity(0, 1, 1, 1, 1),
						99999
					);

					$actionRepository = new ActionRepository();
					$actionRepository->flush($crudAction);

					$stepType->setActionId($crudAction->getId());
				}

				$insert = (object) [
					'parent_id'   => $stepType->getParentId(),
					'label'       => $stepType->getLabel(),
					'action_id'   => $stepType->getActionId(),
					'code'        => $stepType->getCode(),
					'published'   => $stepType->isPublished() ? 1 : 0,
					'system'      => $stepType->isSystem() ? 1 : 0,
					'class' 	 => $stepType->getClass(),
				];

				if ($this->db->insertObject($this->getTableName(self::class), $insert))
				{
					$flushed = true;
					$stepType->setId($this->db->insertid());
				}
			}
		}
		catch
		(\Exception $e)
		{
			Log::add('Failed to flush StepTypeEntity: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.step');
			$flushed = false;
		}

		return $flushed;
	}
}