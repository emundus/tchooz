<?php

namespace Tchooz\Factories\Upload;

use DateTimeImmutable;
use Joomla\CMS\Log\Log;
use Tchooz\Entities\Upload\UploadEntity;
use Tchooz\Enums\Upload\UploadValidationStatusEnum;

class UploadFactory
{
	/**
	 * @param   array  $dbObjects
	 *
	 * @return array<UploadEntity>
	 */
	public static function fromDbObjects(array $dbObjects): array
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
				$dbObject->campaign_id ?? null,
				$dbObject->size ?? 0,
				self::resolveValidationStatus($dbObject->is_validated ?? null, $dbObject->id ?? null),
				(bool) ($dbObject->signed_file ?? false),
				$dbObject->thumbnail ?? null,
				(bool) $dbObject->can_be_deleted,
				(bool) $dbObject->can_be_viewed
			);

			$entity->setTimedate(isset($dbObject->timedate) ? new DateTimeImmutable($dbObject->timedate) : new \DateTimeImmutable());
			$entity->setModified(isset($dbObject->modified) ? new DateTimeImmutable($dbObject->modified) : null);
			$entity->setModifiedBy($dbObject->modified_by ?? null);

			$entities[] = $entity;
		}

		return $entities;
	}

	private static function resolveValidationStatus(mixed $rawValue, mixed $uploadId): UploadValidationStatusEnum
	{
		if (is_null($rawValue))
		{
			return UploadValidationStatusEnum::TO_BE_VALIDATED;
		}

		if (is_int($rawValue) || (is_string($rawValue) && preg_match('/^-?\d+$/', $rawValue)))
		{
			$status = UploadValidationStatusEnum::tryFrom((int) $rawValue);
			if ($status !== null)
			{
				return $status;
			}
		}

		Log::add(
			sprintf('Invalid is_validated value %s for upload id %s, defaulting to TO_BE_VALIDATED', var_export($rawValue, true), var_export($uploadId, true)),
			Log::WARNING,
			'com_emundus'
		);

		return UploadValidationStatusEnum::TO_BE_VALIDATED;
	}
}