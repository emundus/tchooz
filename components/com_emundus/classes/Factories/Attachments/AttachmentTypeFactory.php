<?php

namespace Tchooz\Factories\Attachments;

use Tchooz\Entities\Attachments\AttachmentType;

class AttachmentTypeFactory
{
	/**
	 * @param array $objects
	 *
	 * @return array<AttachmentType>
	 */
	public static function fromDbObjects(array $objects): array
	{
		$attachmentTypes = array();

		foreach ($objects as $object)
		{
			$attachmentTypes[] = new AttachmentType(
				$object->id,
				$object->lbl,
				$object->value,
				$object->description,
				$object->allowed_types,
				$object->nbmax ?? 0,
				$object->ordering,
				$object->published == 1,
				$object->category
			);
		}

		return $attachmentTypes;
	}
}