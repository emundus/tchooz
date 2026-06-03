<?php

namespace Tchooz\Factories\ApplicationFile;

use Tchooz\Entities\ApplicationFile\ApplicationFileAccessEntity;

class ApplicationFileAccessFactory
{
	/**
	 * @param   array  $dbObjects
	 *
	 * @return array<ApplicationFileAccessEntity>
	 * @throws \Exception
	 */
	public static function fromDbObjects(array $dbObjects): array
	{
		$access = array();

		if (!empty($dbObjects))
		{
			foreach ($dbObjects as $dbObject)
			{
				$access[] = new ApplicationFileAccessEntity(
					$dbObject->id,
					$dbObject->ccid,
					$dbObject->token,
					!empty($dbObject->expiration_date) ? new \DateTimeImmutable($dbObject->expiration_date) : new \DateTimeImmutable('- 30 days'),
				);
			}
		}

		return $access;
	}
}