<?php

namespace Tchooz\Factories\Synchronizer;

use Tchooz\Entities\Synchronizer\SynchronizerEntity;
use Tchooz\Enums\Synchronizer\SynchronizerContextEnum;

class SynchronizerFactory
{
	/**
	 * @param   array  $dbObjects
	 *
	 * @return array<SynchronizerEntity>
	 */
	public function fromDbObjects(array $dbObjects): array
	{
		$synchronizers = [];

		if (!empty($dbObjects))
		{
			foreach ($dbObjects as $dbObject)
			{
				$synchronizers[] = new SynchronizerEntity(
					$dbObject->id,
					$dbObject->type,
					$dbObject->name,
					$dbObject->description,
					!empty($dbObject->params) ? json_decode($dbObject->params, true) : [],
					!empty($dbObject->config) ? json_decode($dbObject->config, true) : [],
					(bool) $dbObject->published,
					(bool) $dbObject->enabled,
					$dbObject->icon ?? null,
					$dbObject->consumptions ?? null,
					!empty($dbObject->context) ? SynchronizerContextEnum::from($dbObject->context) : null
				);
			}
		}

		return $synchronizers;
	}
}