<?php

namespace Tchooz\Entities\Automation;

use Joomla\CMS\Log\Log;
use Tchooz\Entities\Automation\Comparators\ArrayComparator;
use Tchooz\Entities\Automation\Comparators\DateComparator;
use Tchooz\Entities\Automation\Comparators\ScalarComparator;
use Tchooz\Enums\Automation\ConditionMatchModeEnum;
use Tchooz\Enums\Automation\ConditionOperatorEnum;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Services\Automation\Condition\ConditionTargetResolverInterface;
use Tchooz\Services\Automation\ConditionRegistry;

class ConditionEntity
{
	private int $id;

	private int $group_id;

	private ConditionTargetTypeEnum $targetType;

	private string $field;

	private ConditionOperatorEnum $operator;

	private mixed $value;

	private ConditionMatchModeEnum $matchMode;

	public CONST SAME_AS_CURRENT_FILE = '__SAME_AS_CURRENT_FILE__';

	public function __construct(int $id, int $group_id, ConditionTargetTypeEnum $targetType, string $field, ConditionOperatorEnum $operator, mixed $value, ConditionMatchModeEnum $matchMode = ConditionMatchModeEnum::ANY)
	{
		$this->id         = $id;
		$this->group_id   = $group_id;
		$this->targetType = $targetType;
		$this->field      = $field;
		$this->operator   = $operator;
		$this->value      = $value;
		$this->matchMode  = $matchMode;

		Log::addLogger(['text_file' => 'com_emundus.condition.entity.log.php'], Log::ALL, ['com_emundus.condition.entity']);
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getGroupId(): int
	{
		return $this->group_id;
	}

	public function setGroupId(int $group_id): void
	{
		$this->group_id = $group_id;
	}

	public function getTargetType(): ConditionTargetTypeEnum
	{
		return $this->targetType;
	}

	public function setTargetType(ConditionTargetTypeEnum $targetType): void
	{
		$this->targetType = $targetType;
	}

	public function getField(): string
	{
		return $this->field;
	}

	public function setField(string $field): void
	{
		$this->field = $field;
	}

	public function getOperator(): ConditionOperatorEnum
	{
		return $this->operator;
	}

	public function setOperator(ConditionOperatorEnum $operator): void
	{
		$this->operator = $operator;
	}

	public function getValue(): mixed
	{
		return $this->value;
	}

	public function setValue(mixed $value): void
	{
		$this->value = $value;
	}

	public function getMatchMode(): ConditionMatchModeEnum
	{
		return $this->matchMode;
	}

	public function setMatchMode(ConditionMatchModeEnum $matchMode): void
	{
		$this->matchMode = $matchMode;
	}

	public function isSatisfied(ActionTargetEntity $context): bool
	{
		$satisfied = false;

		$registry = new ConditionRegistry();
		$resolver = $registry->getResolver($this->targetType->value);
		if ($resolver)
		{
			try {
				$foundValue = $resolver->resolveValue($context, $this->field);
			} catch (\Exception $e) {
				Log::add('Error resolving condition value: ' . $e->getMessage(), Log::ERROR, 'com_emundus.condition.entity');
				return false;
			}
		}
		else
		{
			throw new \RuntimeException("No resolver found for target type: " . $this->targetType->value);
		}

		try {
			$transformedValue = $this->getTransformedValue($context, $resolver);
		} catch (\Exception $e) {
			Log::add('Error transforming condition value: ' . $e->getMessage(), Log::ERROR, 'com_emundus.condition.entity');
			return false;
		}

		// todo: get comparators in another way ?
		$comparators = [
			new ArrayComparator(),
			new DateComparator(),
			new ScalarComparator(),
		];

		foreach ($comparators as $comparator) {
			if ($comparator->supports($transformedValue, $foundValue)) {
				$satisfied = $comparator->compare(
					$foundValue,
					$transformedValue,
					$this->operator,
					$this->getMatchMode()
				);
				break;
			}
		}

		return $satisfied;
	}

	/**
	 * Transform the value if it contains special placeholders like SAME_AS_CURRENT_FILE
	 * @param   ActionTargetEntity                $context
	 * @param   ConditionTargetResolverInterface  $resolver
	 * @throws \Exception
	 * @return mixed
	 */
	public function getTransformedValue(ActionTargetEntity $context, ConditionTargetResolverInterface $resolver): mixed
	{
		$transformedValue = $this->getValue();

		if ($this->value === self::SAME_AS_CURRENT_FILE)
		{
			if (!empty($context->getOriginalContext()))
			{

				$transformedValue = $resolver->resolveValue($context->getOriginalContext(), $this->getField());
			}
			else
			{
				throw new \Exception('Cannot use SAME_AS_CURRENT_FILE when there is no original context available.');
			}
		} else if (is_array($this->getValue()) && in_array(self::SAME_AS_CURRENT_FILE, $this->getValue()))
		{
			if (!empty($context->getOriginalContext()))
			{
				$sameAsCurrentValue = $resolver->resolveValue($context->getOriginalContext(), $this->getField());
				$transformedValue = array_map(function ($val) use ($sameAsCurrentValue) {
					return $val === self::SAME_AS_CURRENT_FILE ? $sameAsCurrentValue : $val;
				}, $this->getValue());
			}
			else
			{
				throw new \Exception('Cannot use SAME_AS_CURRENT_FILE when there is no original context available.');
			}
		}

		return $transformedValue;
	}

	public function serialize(): array
	{
		return [
			'id'       => $this->getId(),
			'group_id' => $this->getGroupId(),
			'type'     => $this->getTargetType()->value,
			'target'   => $this->getField(),
			'operator' => $this->getOperator()->value,
			'value'    => $this->getValue(),
		];
	}
}
