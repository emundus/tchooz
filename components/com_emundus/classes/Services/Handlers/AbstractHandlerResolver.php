<?php
/**
 * @package     Tchooz\Services\Handlers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Handlers;

class AbstractHandlerResolver
{
	protected string $basePath;

	protected ?string $namespacePrefix;

	public function __construct(string $basePath = '', string $namespacePrefix = null)
	{
		$this->basePath = $basePath;
		$this->namespacePrefix = $namespacePrefix;
	}

	public function getBasePath(): string
	{
		return $this->basePath;
	}

	public function getNamespacePrefix(): ?string
	{
		return $this->namespacePrefix;
	}
}