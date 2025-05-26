<?php
/**
 * @package     Tchooz\Repositories\Attachments
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories\Attachments;

use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Attachments\AttachmentType;
use Tchooz\Traits\TraitTable;
use Joomla\Database\DatabaseInterface;

#[TableAttribute(table: '#__emundus_setup_attachments')]
readonly class AttachmentTypeRepository
{
	use TraitTable;

	public function __construct(private DatabaseInterface $db)
	{}

	public function flush(): int
	{}

	public function loadAttachmentTypeById(int $id): ?AttachmentType
	{
		$query = $this->db->createQuery();

		$query->select('*')
			->from($this->getTableName(self::class))
			->where('id = ' . $id);
		$this->db->setQuery($query);
		$attachment_type_object = $this->db->loadObject();

		if(!empty($attachment_type_object))
		{
			$attachment_type = new AttachmentType();
			$attachment_type->setId($attachment_type_object->id);
			$attachment_type->setLbl($attachment_type_object->lbl);
			$attachment_type->setName($attachment_type_object->value);
			$attachment_type->setDescription($attachment_type_object->description);
			$attachment_type->setAllowedTypes($attachment_type_object->allowed_types);
			$attachment_type->setNbMax($attachment_type_object->nbmax);
			$attachment_type->setOrdering($attachment_type_object->ordering);
			$attachment_type->setPublished($attachment_type_object->published);
			$attachment_type->setCategory($attachment_type_object->category);

			return $attachment_type;
		}

		return null;
	}
}