<?php

namespace Tchooz\Factories\Upload;

use DateTimeImmutable;
use Tchooz\Entities\Upload\UploadEntity;
use Tchooz\Enums\Upload\UploadValidationStatusEnum;

class UploadFactory
{
	/**
	 * @param   array  $dbObjects
	 *
	 * @return array<UploadEntity>
	 */
	public function fromDbObjects(array $dbObjects): array
	{
		$entities = [];

		foreach ($dbObjects as $dbObject) {
			$entity = new UploadEntity(
				$dbObject->id,
				$dbObject->user_id,
				$dbObject->fnum,
				$dbObject->attachment_id,
				$dbObject->filename,
				$dbObject->description,
				$dbObject->local_filename,
				$dbObject->campaign_id,
				$dbObject->size,
				!is_null($dbObject->is_validated) ? UploadValidationStatusEnum::from($dbObject->is_validated) : UploadValidationStatusEnum::TO_BE_VALIDATED,
				(bool) $dbObject->signed_file,
				$dbObject->thumbnail,
				(bool) $dbObject->can_be_deleted,
				(bool) $dbObject->can_be_viewed
			);

			$entity->setTimedate(isset($dbObject->timedate) ? new DateTimeImmutable($dbObject->timedate) : new \DateTimeImmutable());
			$entity->setModified(isset($dbObject->modified) ? new DateTimeImmutable($dbObject->modified) : null);
			$entity->setModifiedBy($dbObject->modified_by ?? 0);

			$entities[] = $entity;
		}

		return $entities;
	}
}