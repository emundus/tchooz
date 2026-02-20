<?php

namespace Tchooz\Factories\ExternalReference;

use Tchooz\Entities\ExternalReference\ExternalReferenceEntity;

class ExternalReferenceFactory
{
	public static function fromDbObjects(array $dbObjects): array
	{
		$externalReferences = [];

		foreach ($dbObjects as $dbObject) {
			$externalReferences[] = new ExternalReferenceEntity(
				(int) $dbObject->id,
				(string) $dbObject->column,
				(string) $dbObject->intern_id,
				(string) $dbObject->reference
			);
		}

		return $externalReferences;
	}
}