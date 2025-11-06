<?php

namespace Tchooz\Factories\Automation;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Tchooz\Entities\Automation\ActionTargetEntity;

class ActionTargetFactory
{

	public static function fromSerialized(array $serialized): ?ActionTargetEntity
	{
		$actionTarget = null;

		if (!empty($serialized) && !empty($serialized['triggeredBy']) && (!empty($serialized['file']) || !empty($serialized['user'])))
		{
			$actionTarget = new ActionTargetEntity(
				Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($serialized['triggeredBy']),
				$serialized['file'] ?? null,
				(int)$serialized['user'] ?? null,
				$serialized['parameters'] ?? [],
				$serialized['custom'] ?? null,
				isset($serialized['originalContext']) ? self::fromSerialized($serialized['originalContext']) : null
			);
		}

		return $actionTarget;
	}

}