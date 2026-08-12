<?php
/**
 * @package     Tchooz\Services\Import
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Import;

/**
 * Maps an entity type ("organization", "contact", ...) to the EntityImporter
 * responsible for it.
 *
 * The registry is the single place where a controller learns which importers
 * exist. Two ways to populate it:
 *   - explicit:   $registry->register(new OrganizationImporter($custom, $deps));
 *   - auto-scan:  $registry->registerAll();   // discovers Services/Import/Entity/
 *
 * Use EntityImporterRegistry::default() to get a registry that is auto-scanned
 * once per request and reused across calls.
 */
final class EntityImporterRegistry
{
	/** @var array<string, EntityImporterInterface> */
	private array $importers = [];

	private static ?self $defaultInstance = null;

	/**
	 * Returns a registry populated by registerAll() once per request.
	 * Subsequent calls reuse the same instance — discovery + reflection only
	 * runs on the first call.
	 */
	public static function default(): self
	{
		if (self::$defaultInstance === null)
		{
			$registry = new self();
			$registry->registerAll();
			self::$defaultInstance = $registry;
		}

		return self::$defaultInstance;
	}

	/**
	 * Test/edge hook: drops the cached default registry so the next default()
	 * call re-runs discovery. Not needed in normal request flow.
	 */
	public static function resetDefault(): void
	{
		self::$defaultInstance = null;
	}

	public function register(EntityImporterInterface $importer): void
	{
		$this->importers[$importer->getType()] = $importer;
	}

	/**
	 * Discovers every concrete EntityImporterInterface in $directory and
	 * registers it via its static ::create() factory.
	 *
	 * Convention: importers live under Services/Import/Entity/ (recursively),
	 * one class per file, and their FQCN follows the directory layout under
	 * the Tchooz\Services\Import\Entity\ namespace.
	 *
	 * @param string|null $directory Defaults to the Entity/ folder next to this file.
	 */
	public function registerAll(?string $directory = null): void
	{
		$directory   = $directory ?? __DIR__ . '/Entity';
		$baseNs      = 'Tchooz\\Services\\Import\\Entity';
		$realBaseDir = realpath($directory);

		if ($realBaseDir === false || !is_dir($realBaseDir))
		{
			return;
		}

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator($realBaseDir, \FilesystemIterator::SKIP_DOTS)
		);

		foreach ($iterator as $file)
		{
			if (!$file->isFile() || strtolower($file->getExtension()) !== 'php')
			{
				continue;
			}

			$fqcn = $this->resolveFqcn($file->getPathname(), $realBaseDir, $baseNs);
			if ($fqcn === null || !class_exists($fqcn))
			{
				continue;
			}

			$reflection = new \ReflectionClass($fqcn);
			if ($reflection->isAbstract() || $reflection->isInterface() || $reflection->isTrait())
			{
				continue;
			}
			if (!$reflection->implementsInterface(EntityImporterInterface::class))
			{
				continue;
			}

			$factory = [$fqcn, 'create'];
			if (!is_callable($factory))
			{
				continue;
			}

			$importer = $factory();
			if ($importer instanceof EntityImporterInterface)
			{
				$this->register($importer);
			}
		}
	}

	public function has(string $type): bool
	{
		return isset($this->importers[$type]);
	}

	public function get(string $type): EntityImporterInterface
	{
		if (!isset($this->importers[$type]))
		{
			throw new \InvalidArgumentException(sprintf('No importer registered for type "%s".', $type));
		}

		return $this->importers[$type];
	}

	/**
	 * @return string[]
	 */
	public function getTypes(): array
	{
		return array_keys($this->importers);
	}

	/**
	 * Builds an FQCN from an absolute file path under $baseDir, mapping the
	 * relative path 1:1 to the namespace below $baseNs.
	 */
	private function resolveFqcn(string $absolutePath, string $baseDir, string $baseNs): ?string
	{
		$relative = substr($absolutePath, strlen($baseDir) + 1);
		if ($relative === '')
		{
			return null;
		}

		// strip .php and convert directory separators to namespace separators
		$relative = substr($relative, 0, -4);
		$relative = str_replace(['/', '\\'], '\\', $relative);

		return $baseNs . '\\' . $relative;
	}
}
