<?php

namespace Tchooz\Services\Integrations;

use Tchooz\Entities\Fields\Field;

abstract class EmundusIntegrationConfiguration
{
	/**
	 * @return array<Field>
	 */
	abstract public function getParameters(): array;

	/**
	 * @param   object  $data
	 *
	 * @return object
	 */
	public function beforeSaveConfiguration(object $data): object
	{
		if (!empty($this->getParameters()))
		{
			foreach ($this->getParameters() as $parameter)
			{
				if ($parameter->isRequired())
				{

					if ($parameter->getGroup() !== null)
					{
						$groupName = $parameter->getGroup()->getName();
						if (!isset($data[$groupName])  || !isset($data[$groupName][$parameter->getName()]) || empty($data[$groupName][$parameter->getName()]))
						{
							throw new \InvalidArgumentException('The parameter ' . $parameter->getName() . ' in group ' . $groupName . ' is required and cannot be empty.');
						}
					}
					else
					{
						if (!isset($data[$parameter->getName()]) || empty($data[$parameter->getName()]))
						{
							throw new \InvalidArgumentException('The parameter ' . $parameter->getName() . ' is required and cannot be empty.');
						}
					}
				}
			}
		}

		return $data;
	}

	/**
	 * @param   string  $name
	 *
	 * @return Field|null
	 */
	public function getParameter(string $name): ?Field
	{
		foreach ($this->getParameters() as $parameter)
		{
			if ($parameter->getName() === $name)
			{
				return $parameter;
			}
		}

		return null;
	}
}