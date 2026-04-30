<?php
/**
 * @package     Tchooz\Entities\Profile
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Entities\Profile;

class ProfileEntity
{
	private int $id;

	private string $label;

	private string $description;

	private bool $published;

	private string $menutype;

	private int $aclAroGroups;

	private string $class;

	/**
	 * @param   int     $id
	 * @param   string  $label
	 * @param   string  $description
	 * @param   bool    $published
	 * @param   string  $menutype
	 * @param   int     $aclAroGroups
	 * @param   string  $class
	 */
	public function __construct(int $id, string $label, string $description, bool $published, string $menutype, int $aclAroGroups, string $class)
	{
		$this->id           = $id;
		$this->label        = $label;
		$this->description  = $description;
		$this->published    = $published;
		$this->menutype     = $menutype;
		$this->aclAroGroups = $aclAroGroups;
		$this->class        = $class;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): ProfileEntity
	{
		$this->id = $id;

		return $this;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function setLabel(string $label): ProfileEntity
	{
		$this->label = $label;

		return $this;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function setDescription(string $description): ProfileEntity
	{
		$this->description = $description;

		return $this;
	}

	public function isPublished(): bool
	{
		return $this->published;
	}

	public function setPublished(bool $published): ProfileEntity
	{
		$this->published = $published;

		return $this;
	}

	public function getMenutype(): string
	{
		return $this->menutype;
	}

	public function setMenutype(string $menutype): ProfileEntity
	{
		$this->menutype = $menutype;

		return $this;
	}

	public function getAclAroGroups(): int
	{
		return $this->aclAroGroups;
	}

	public function setAclAroGroups(int $aclAroGroups): ProfileEntity
	{
		$this->aclAroGroups = $aclAroGroups;

		return $this;
	}

	public function getClass(): string
	{
		return $this->class;
	}

	public function setClass(string $class): ProfileEntity
	{
		$this->class = $class;

		return $this;
	}
}