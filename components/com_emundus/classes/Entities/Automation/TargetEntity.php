<?php

namespace Tchooz\Entities\Automation;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Factories\Automation\ConditionsQueryFactory;

class TargetEntity
{
	private int $id;
	private TargetTypeEnum $type;
	private ?TargetPredefinitionEntity $predefinition;

	/** @var ConditionEntity[] */
	private array $conditions = [];

	public function __construct(
		int                        $id,
		TargetTypeEnum             $type,
		?TargetPredefinitionEntity $predefinition = null,
		array                      $conditions = []
	)
	{
		$this->id            = $id;
		$this->type          = $type;
		$this->predefinition = $predefinition;

		foreach ($conditions as $condition) {
			assert($condition instanceof ConditionEntity);
		}
		$this->conditions    = $conditions;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getType(): TargetTypeEnum
	{
		return $this->type;
	}

	public function setType(TargetTypeEnum $type): void
	{
		$this->type = $type;
	}

	public function getPredefinition(): ?TargetPredefinitionEntity
	{
		return $this->predefinition;
	}

	public function setPredefinition(?TargetPredefinitionEntity $predefinition): void
	{
		$this->predefinition = $predefinition;
	}

	/**
	 * @return ConditionEntity[]
	 */
	public function getConditions(): array
	{
		return $this->conditions;
	}

	/**
	 * @param ConditionEntity[] $conditions
	 */
	public function setConditions(array $conditions): void
	{
		foreach ($conditions as $condition) {
			assert($condition instanceof ConditionEntity);
		}
		$this->conditions = $conditions;
	}

	/**
	 * Récupère un contexte d'évènement, en fonction des cibles définies, redéterminer les contextes applicables
	 *
	 * @param ActionTargetEntity  $context
	 *
	 * @return array<ActionTargetEntity>
	 */
	public function resolve(ActionTargetEntity $context): array
	{
		$finalTargets = [];

		// La prédéfinition est facultative. Le type de cible définit un comportement par défaut
		// la prédéfinition est la pour affiner la sélection
		// ex une prédéfinition "tous les utilisateurs avec role x" pour une cible de type "utilisateur" va renvoyer tous les utilisateurs avec le role x
		// a partir de ce ciblage il va falloir appliquer les conditions éventuelles pour affiner à nouveau la sélection
		$type = $this->getType();
		$predefinedTargets = [];
		if ($this->predefinition !== null) {
			$predefinedTargets = $this->predefinition->resolve($context);
		}

		if (!empty($this->conditions))
		{
			$db = Factory::getContainer()->get('DatabaseDriver');

			$queryFactory = new ConditionsQueryFactory($db);
			$query = $queryFactory->buildConditionsQuery($this->conditions, $this->getType(), $context);

			if (!empty($query))
			{
				if (!empty($predefinedTargets))
				{
					$ids = array_map(function (ActionTargetEntity $target) use ($type) {
						switch ($type) {
							case TargetTypeEnum::USER:
								return $target->getUserId();
							case TargetTypeEnum::FILE:
								return $target->getFile();
						}

						return null;
					}, $predefinedTargets);

					if (!empty($ids))
					{
						$query->where($type->getTableAlias() . '.' . $type->getPrimaryField() . ' IN (' . implode(',', $ids) . ')');
					} else {
						return $finalTargets;
					}
				}

				try {
					$db->setQuery($query);
					$results = $db->loadColumn();
				} catch (\Exception $e) {
					Log::addLogger(['text_file' => 'com_emundus.automation.log.php'], Log::ERROR, 'com_emundus.automation');
					Log::add('Error executing conditions query: ' . $e->getMessage(), Log::ERROR, 'com_emundus.automation');
				}

				if (!empty($results))
				{
					$finalTargets = array_map(function ($result) use ($context, $type) {
						if ($type === TargetTypeEnum::GROUP)
						{
							$context->updateParameter(TargetTypeEnum::GROUP->value, $result);
						}

						return new ActionTargetEntity(
							$context->getTriggeredBy(),
							$type === TargetTypeEnum::FILE ? $result : null,
							$type === TargetTypeEnum::USER ? $result : null,
							$context->getParameters(),
							null,
							$context
						);
					}, $results);
				}
			}
		} else if (!empty($predefinedTargets)) {
			$finalTargets = $predefinedTargets;
		}

		return $finalTargets;
	}

	public function serialize()
	{
		return [
			'id' => $this->id,
			'type' => $this->type->value,
			'predefinition' => $this->predefinition?->serialize(),
			'conditions' => array_map(function (ConditionEntity $condition) {
				return $condition->serialize();
			}, $this->conditions)
		];
	}
}