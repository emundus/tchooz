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
use Tchooz\Enums\Fabrik\ElementPluginEnum;

class FabrikElementEntity
{
	private int $id;

	private string $name;

	private int $groupId;

	private ElementPluginEnum $plugin;

	private string $label;

	private \DateTime $created;

	private User $createdBy;

	private \DateTime $modified;

	private ?User $modifiedBy = null;

	private int $width = 0;

	private int $height = 0;

	private string $default = '';

	private int $hidden = 0;

	private int $eval = 0;

	private int $ordering = 0;

	private int $showInListSummary = 0;

	private string $filterType = '';

	private int $filterExactMatch = 0;

	private bool $published = true;

	private int $linkToDetail = 0;

	private int $primaryKey = 0;

	private int $autoIncrement = 0;

	private int $access = 0;

	private int $useInPageTitle = 0;

	private int $parentId = 0;

	private string $paramsRaw = '';

	private string $dbTableName = '';

	private string $tableJoin = '';

	private string $groupParamsRaw = '';

	private string $alias = '';

	public function __construct(
		int               $id,
		string            $name,
		int               $groupId,
		ElementPluginEnum $plugin,
		string            $label,
		\DateTime         $created,
		User              $createdBy,
		string            $params = '',
		string            $dbTableName = '',
		string            $tableJoin = '',
		string            $groupParams = '',
		string            $alias = '',
		string            $default = '',
		int               $eval = 0
	)
	{
		$this->id             = $id;
		$this->name           = $name;
		$this->groupId        = $groupId;
		$this->plugin         = $plugin;
		$this->label          = $label;
		$this->created        = $created;
		$this->createdBy      = $createdBy;
		$this->paramsRaw      = $params;
		$this->dbTableName    = $dbTableName;
		$this->tableJoin      = $tableJoin;
		$this->groupParamsRaw = $groupParams;
		$this->alias          = $alias;
		$this->default        = $default;
		$this->eval           = $eval;
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

	public function getGroupId(): int
	{
		return $this->groupId;
	}

	public function setGroupId(int $groupId): void
	{
		$this->groupId = $groupId;
	}

	public function getPlugin(): ElementPluginEnum
	{
		return $this->plugin;
	}

	public function setPlugin(ElementPluginEnum $plugin): void
	{
		$this->plugin = $plugin;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function setLabel(string $label): void
	{
		$this->label = $label;
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

	public function getModified(): \DateTime
	{
		return $this->modified;
	}

	public function setModified(\DateTime $modified): void
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

	public function getWidth(): int
	{
		return $this->width;
	}

	public function setWidth(int $width): void
	{
		$this->width = $width;
	}

	public function getHeight(): int
	{
		return $this->height;
	}

	public function setHeight(int $height): void
	{
		$this->height = $height;
	}

	public function getDefault(): string
	{
		return $this->default;
	}

	public function setDefault(string $default): void
	{
		$this->default = $default;
	}

	public function getHidden(): int
	{
		return $this->hidden;
	}

	public function setHidden(int $hidden): void
	{
		$this->hidden = $hidden;
	}

	public function getEval(): int
	{
		return $this->eval;
	}

	public function setEval(int $eval): void
	{
		$this->eval = $eval;
	}

	public function getOrdering(): int
	{
		return $this->ordering;
	}

	public function setOrdering(int $ordering): void
	{
		$this->ordering = $ordering;
	}

	public function getShowInListSummary(): int
	{
		return $this->showInListSummary;
	}

	public function setShowInListSummary(int $showInListSummary): void
	{
		$this->showInListSummary = $showInListSummary;
	}

	public function getFilterType(): string
	{
		return $this->filterType;
	}

	public function setFilterType(string $filterType): void
	{
		$this->filterType = $filterType;
	}

	public function getFilterExactMatch(): int
	{
		return $this->filterExactMatch;
	}

	public function setFilterExactMatch(int $filterExactMatch): void
	{
		$this->filterExactMatch = $filterExactMatch;
	}

	public function isPublished(): bool
	{
		return $this->published;
	}

	public function setPublished(bool $published): void
	{
		$this->published = $published;
	}

	public function getLinkToDetail(): int
	{
		return $this->linkToDetail;
	}

	public function setLinkToDetail(int $linkToDetail): void
	{
		$this->linkToDetail = $linkToDetail;
	}

	public function getPrimaryKey(): int
	{
		return $this->primaryKey;
	}

	public function setPrimaryKey(int $primaryKey): void
	{
		$this->primaryKey = $primaryKey;
	}

	public function getAutoIncrement(): int
	{
		return $this->autoIncrement;
	}

	public function setAutoIncrement(int $autoIncrement): void
	{
		$this->autoIncrement = $autoIncrement;
	}

	public function getAccess(): int
	{
		return $this->access;
	}

	public function setAccess(int $access): void
	{
		$this->access = $access;
	}

	public function getUseInPageTitle(): int
	{
		return $this->useInPageTitle;
	}

	public function setUseInPageTitle(int $useInPageTitle): void
	{
		$this->useInPageTitle = $useInPageTitle;
	}

	public function getParentId(): int
	{
		return $this->parentId;
	}

	public function setParentId(int $parentId): void
	{
		$this->parentId = $parentId;
	}

	public function getParams(): object
	{
		return json_decode($this->paramsRaw) ?? new \stdClass();
	}

	public function getParamsArray(): array
	{
		return json_decode($this->paramsRaw, true) ?? [];
	}

	public function getParamsRaw(): string
	{
		return $this->paramsRaw;
	}

	public function setParamsRaw(string $paramsRaw): void
	{
		$this->paramsRaw = $paramsRaw;
	}

	public function getDbTableName(): string
	{
		return $this->dbTableName;
	}

	public function setDbTableName(string $dbTableName): void
	{
		$this->dbTableName = $dbTableName;
	}

	public function getTableJoin(): string
	{
		return $this->tableJoin;
	}

	public function setTableJoin(string $tableJoin): void
	{
		$this->tableJoin = $tableJoin;
	}

	public function getGroupParams(): object
	{
		return json_decode($this->groupParamsRaw) ?? new \stdClass();
	}

	public function getGroupParamsArray(): array
	{
		return json_decode($this->groupParamsRaw, true) ?? [];
	}

	public function getGroupParamsRaw(): string
	{
		return $this->groupParamsRaw;
	}

	public function setGroupParamsRaw(string $groupParamsRaw): void
	{
		$this->groupParamsRaw = $groupParamsRaw;
	}

	public function getAlias(): string
	{
		return $this->alias;
	}

	public function setAlias(string $alias): void
	{
		$this->alias = $alias;
	}

	public function toArray(bool $translate = true): array
	{
		return [
			'id'            => $this->id,
			'created'       => $this->created->format('Y-m-d H:i:s'),
			'created_by'    => $this->createdBy->id,
			'name'          => $this->name,
			'group_id'      => $this->groupId,
			'plugin'        => $this->plugin->value,
			'plugin_name'   => $translate ? Text::_($this->plugin->getLabel()) : $this->plugin->getLabel(),
			'label'         => $translate ? Text::_($this->label) : $this->label,
			'default'       => $this->default,
			'ordering'      => $this->ordering,
			'published'     => $this->published,
			'parent_id'     => $this->parentId,
			'params'        => $this->paramsRaw,
			'db_table_name' => $this->dbTableName,
			'table_join'    => $this->tableJoin,
			'group_params'  => $this->groupParamsRaw,
			'alias'         => $this->alias,
		];
	}
}