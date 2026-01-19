<?php
/**
 * @package     Tchooz\Entities\Language
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Language;

use Joomla\CMS\User\User;
use Tchooz\Enums\StatusEnum;

class LanguageEntity
{
	private int $id;

	// TODO: Tag will be mandatory only for override type, will be deprecated in future versions to use only references fields
	private string $tag;

	private string $langCode;

	private string $override;

	private string $originalText;

	private string $originalMd5;

	private string $overrideMd5;

	// TODO: Create an enum or a regsitry class for types (override, campaign, status,...)
	private string $type;

	private ?int $referenceId;

	private ?string $referenceTable;

	private ?string $referenceField;

	private StatusEnum $published;

	private ?User $createdBy;

	private ?\DateTime $createdDate;

	private ?User $modifiedBy;

	private ?\DateTime $modifiedDate;

	public function __construct(
		string     $tag,
		string     $langCode,
		string     $override,
		string     $originalText,
		string     $type,
		?User      $createdBy = null,
		?\DateTime $createdDate = null,
		?int       $referenceId = 0,
		?string    $referenceTable = '',
		?string    $referenceField = '',
		?User      $modifiedBy = null,
		?\DateTime $modifiedDate = null,
		int        $id = 0,
		StatusEnum $published = StatusEnum::PUBLISHED,
	)
	{
		$this->tag            = $tag;
		$this->langCode       = $langCode;
		$this->override       = $override;
		$this->overrideMd5    = md5($override);
		$this->originalText   = $originalText;
		$this->originalMd5    = md5($originalText);
		$this->type           = $type;
		$this->referenceId    = $referenceId;
		$this->referenceTable = $referenceTable;
		$this->referenceField = $referenceField;
		$this->createdBy      = $createdBy;
		$this->createdDate    = $createdDate ?: new \DateTime();
		$this->modifiedBy     = $modifiedBy;
		$this->modifiedDate   = $modifiedDate;
		$this->published      = $published;

		$this->id = $id;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getTag(): string
	{
		return $this->tag;
	}

	public function setTag(string $tag): void
	{
		$this->tag = $tag;
	}

	public function getLangCode(): string
	{
		return $this->langCode;
	}

	public function setLangCode(string $langCode): void
	{
		$this->langCode = $langCode;
	}

	public function getShortLangCode(): string
	{
		return substr($this->langCode, 0, 2);
	}

	public function getOverride(): string
	{
		return $this->override;
	}

	public function setOverride(string $override): void
	{
		$this->override = $override;
	}

	public function getOriginalText(): string
	{
		return $this->originalText;
	}

	public function setOriginalText(string $originalText): void
	{
		$this->originalText = $originalText;
	}

	public function getOriginalMd5(): string
	{
		return $this->originalMd5;
	}

	public function setOriginalMd5(string $originalMd5): void
	{
		$this->originalMd5 = $originalMd5;
	}

	public function getOverrideMd5(): string
	{
		return $this->overrideMd5;
	}

	public function setOverrideMd5(string $overrideMd5): void
	{
		$this->overrideMd5 = $overrideMd5;
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function setType(string $type): void
	{
		$this->type = $type;
	}

	public function getReferenceId(): ?int
	{
		return $this->referenceId;
	}

	public function setReferenceId(int $referenceId): void
	{
		$this->referenceId = $referenceId;
	}

	public function getReferenceTable(): ?string
	{
		return $this->referenceTable;
	}

	public function setReferenceTable(string $referenceTable): void
	{
		$this->referenceTable = $referenceTable;
	}

	public function getReferenceField(): ?string
	{
		return $this->referenceField;
	}

	public function setReferenceField(string $referenceField): void
	{
		$this->referenceField = $referenceField;
	}

	public function getPublished(): StatusEnum
	{
		return $this->published;
	}

	public function setPublished(StatusEnum $published): void
	{
		$this->published = $published;
	}

	public function getCreatedBy(): ?User
	{
		return $this->createdBy;
	}

	public function setCreatedBy(User $createdBy): void
	{
		$this->createdBy = $createdBy;
	}

	public function getCreatedDate(): ?\DateTime
	{
		return $this->createdDate;
	}

	public function setCreatedDate(\DateTime $createdDate): void
	{
		$this->createdDate = $createdDate;
	}

	public function getModifiedBy(): ?User
	{
		return $this->modifiedBy;
	}

	public function setModifiedBy(User $modifiedBy): void
	{
		$this->modifiedBy = $modifiedBy;
	}

	public function getModifiedDate(): ?\DateTime
	{
		return $this->modifiedDate;
	}

	public function setModifiedDate(?\DateTime $modifiedDate): void
	{
		$this->modifiedDate = $modifiedDate;
	}

	public function toObject(): \stdClass
	{
		$obj                 = new \stdClass();
		$obj->id             = $this->id;
		$obj->tag            = $this->tag;
		$obj->lang_code       = $this->langCode;
		$obj->override       = $this->override;
		$obj->original_text   = $this->originalText;
		$obj->original_md5    = $this->originalMd5;
		$obj->override_md5    = $this->overrideMd5;
		$obj->type           = $this->type;
		$obj->reference_id    = $this->referenceId;
		$obj->reference_table = $this->referenceTable;
		$obj->reference_field = $this->referenceField;
		$obj->published      = $this->published->value;

		return $obj;
	}
}