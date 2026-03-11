<?php

namespace Tchooz\Services\Transformation;

use Tchooz\Entities\Transformation\TransformationEntity;

class TransformationEngine
{
	private mixed $value;

	private array $transformations;

	private TransformationsRegistry $registry;

	/**
	 * @param mixed $value
	 * @param array<TransformationEntity> $transformations
	 */
	public function __construct(mixed $value = null, array $transformations = [])
	{
		$this->value = $value;
		$this->transformations = $transformations;

		$this->registry = new TransformationsRegistry();
	}

	public function setValue(mixed $value): self
	{
		$this->value = $value;

		return $this;
	}

	/**
	 * @param array<TransformationEntity> $transformations
	 * @return self
	 */
	public function setTransformations(array $transformations): self
	{
		foreach ($transformations as $transformation)
		{
			assert($transformation instanceof TransformationEntity);
		}
		$this->transformations = $transformations;

		return $this;
	}

	/**
	 * @param   array  $transformWiths
	 *
	 * @return bool
	 */
	public function transformValue(array $transformWiths = []): bool
	{
		$transformed = false;
		foreach ($this->transformations as $transformation)
		{
			$transformer = $this->registry->getTransformer($transformation->getType());
			if ($transformer)
			{
				$transformer->setParametersValues($transformation->getParameters());

				if (!empty($transformWiths))
				{
					foreach ($transformWiths as $with)
					{
						$transformer->with($with);
					}
				}

				$currentValue = $transformer->transform($this->getValue());
				$this->setValue($currentValue);

				$transformer->reset();
				$transformed = true;
			}
		}

		return $transformed;
	}

	public function getValue(): mixed
	{
		return $this->value;
	}
}