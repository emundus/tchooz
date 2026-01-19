<?php

namespace Tchooz\Services\Automation\Condition;

use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Fields\BooleanField;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Enums\ValueFormatEnum;
use Tchooz\Repositories\Attachments\AttachmentTypeRepository;
use Tchooz\Repositories\Upload\UploadRepository;

class FileAttachmentsDataConditionResolver implements ConditionTargetResolverInterface
{

	public static function getTargetType(): string
	{
		return ConditionTargetTypeEnum::FILEATTACHMENTDATA->value;
	}

	public static function getAllowedActionTargetTypes(): array
	{
		return [TargetTypeEnum::FILE];
	}

	public function getAvailableFields(array $contextFilters): array
	{
		$attachmentTypeRepository = new AttachmentTypeRepository();
		$types = $attachmentTypeRepository->get(['published' => 1], 0);

		$fields = [];
		foreach ($types as $type)
		{
			$fields[] = new BooleanField($type->getId(), $type->getName());
		}

		return $fields;
	}

	public function resolveValue(ActionTargetEntity $context, string $fieldName, ValueFormatEnum $format = ValueFormatEnum::RAW): mixed
	{
		$value = null;

		if (!empty($context->getFile()))
		{
			$attachmentTypeRepository = new AttachmentTypeRepository();
			$types = $attachmentTypeRepository->get(['id' => $fieldName], 1);

			if (!empty($types))
			{
				$uploadRepository = new UploadRepository();
				$uploads = $uploadRepository->getBy([
					'fnum' => $context->getFile(),
					'attachment_id' => $types[0]->getId()
				]);

				if (!empty($uploads))
				{
					$value = $uploads;
				}
			}
		}

		return $value;
	}

	public function getColumnsForField(string $field): array
	{
		return [];
	}

	public function getJoins(string $field): array
	{
		return [];
	}

	public function getJoinsToTable(TargetTypeEnum $targetType): array
	{
		return [];
	}

	public function searchable(): bool
	{
		return false;
	}
}