<?php
/**
 * @package     Tchooz\Entities\Fabrik
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Fabrik;

use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;

class FabrikGroupEntity
{
	private int $id;

	private string $name;

	private string $css;

	private string $label;

	private bool $published = true;

	private \DateTime $created;

	private User $createdBy;

	private ?\DateTime $modified = null;

	private ?User $modifiedBy = null;

	private bool $isJoin = false;

	private int $private = 0;

	private ?FabrikGroupParams $params = null;

	private string $paramsRaw = '';

	/**
	 * @var array<FabrikElementEntity>
	 */
	private array $elements = [];

	// TODO: Link FabrikListEntity $list;

	public function __construct(
		int $id,
		string $name,
		string $label,
		\DateTime $created,
		User      $createdBy,
		\DateTime $modified,
		?User     $modifiedBy = null,
		bool      $isJoin = false,
		int       $private = 0,
		string    $paramsRaw = '',
	)
	{
		$this->id         = $id;
		$this->name       = $name;
		$this->label      = $label;
		$this->created    = $created;
		$this->createdBy  = $createdBy;
		$this->modified   = $modified;
		$this->modifiedBy = $modifiedBy;
		$this->isJoin     = $isJoin;
		$this->private    = $private;
		$this->paramsRaw  = $paramsRaw;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): void
	{
		$this->name = $name;
	}

	public function getCss(): string
	{
		return $this->css;
	}

	public function setCss(string $css): void
	{
		$this->css = $css;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function setLabel(string $label): void
	{
		$this->label = $label;
	}

	public function isPublished(): bool
	{
		return $this->published;
	}

	public function setPublished(bool $published): void
	{
		$this->published = $published;
	}

	public function getCreated(): \DateTime
	{
		return $this->created;
	}

	public function setCreated(\DateTime $created): void
	{
		$this->created = $created;
	}

	public function getCreatedBy(): User
	{
		return $this->createdBy;
	}

	public function setCreatedBy(User $createdBy): void
	{
		$this->createdBy = $createdBy;
	}

	public function getModified(): ?\DateTime
	{
		return $this->modified;
	}

	public function setModified(?\DateTime $modified): void
	{
		$this->modified = $modified;
	}

	public function getModifiedBy(): ?User
	{
		return $this->modifiedBy;
	}

	public function setModifiedBy(?User $modifiedBy): void
	{
		$this->modifiedBy = $modifiedBy;
	}

	public function isJoin(): bool
	{
		return $this->isJoin;
	}

	public function setIsJoin(bool $isJoin): void
	{
		$this->isJoin = $isJoin;
	}

	public function getPrivate(): int
	{
		return $this->private;
	}

	public function setPrivate(int $private): void
	{
		$this->private = $private;
	}

	public function getParams(): ?FabrikGroupParams
	{
		return $this->params;
	}

	public function setParams(?FabrikGroupParams $params): void
	{
		$this->params = $params;
	}

	public function getParamsRaw(): string
	{
		return $this->paramsRaw;
	}

	public function setParamsRaw(string $paramsRaw): void
	{
		$this->paramsRaw = $paramsRaw;
	}

	public function getElements(): array
	{
		return $this->elements;
	}

	public function setElements(array $elements): void
	{
		$this->elements = $elements;
	}

	public function __serialize(): array
	{
		$serialized = [
			'id'          => $this->id,
			'name'        => $this->name,
			'label'       => strip_tags(Text::_($this->label)),
			'published'   => $this->published,
		];

		if(!empty($this->elements))
		{
			foreach($this->elements as $element)
			{
				assert($element instanceof FabrikElementEntity);
				$serialized['elements'][] = $element->toArray();
			}
		}

		return $serialized;
	}
}