<?php
/**
 * @package     Tchooz\Services\Import\Referential
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Import\Referential;

/**
 * Maps a referential key ("organizations", "countries", ...) to its provider.
 *
 * Use ReferentialRegistry::default() to get a registry auto-scanned once per
 * request; the returned providers are shared, so each referential loads a single
 * time across every field and importer that references it.
 */
final class ReferentialRegistry
{
	/** @var array<string, ReferentialProviderInterface> */
	private array $providers = [];

	private static ?self $defaultInstance = null;

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
	 * call re-runs discovery.
	 */
	public static function resetDefault(): void
	{
		self::$defaultInstance = null;
	}

	public function register(ReferentialProviderInterface $provider): void
	{
		$this->providers[$provider->getKey()] = $provider;
	}

	/**
	 * Discovers every concrete ReferentialProviderInterface in $directory and
	 * registers it via its static ::create() factory.
	 *
	 * Convention: sources live under Referential/Source/ (recursively), one class
	 * per file, FQCN following the directory layout under
	 * Tchooz\Services\Import\Referential\Source\.
	 *
	 * @param string|null $directory Defaults to the Source/ folder next to this file.
	 */
	public function registerAll(?string $directory = null): void
	{
		$directory   = $directory ?? __DIR__ . '/Source';
		$baseNs      = 'Tchooz\\Services\\Import\\Referential\\Source';
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
			if (!$reflection->implementsInterface(ReferentialProviderInterface::class))
			{
				continue;
			}

			$factory = [$fqcn, 'create'];
			if (!is_callable($factory))
			{
				continue;
			}

			$provider = $factory();
			if ($provider instanceof ReferentialProviderInterface)
			{
				$this->register($provider);
			}
		}
	}

	public function has(string $key): bool
	{
		return isset($this->providers[$key]);
	}

	public function get(string $key): ReferentialProviderInterface
	{
		if (!isset($this->providers[$key]))
		{
			throw new \InvalidArgumentException(sprintf('No referential registered for key "%s".', $key));
		}

		return $this->providers[$key];
	}

	/**
	 * @return string[]
	 */
	public function getKeys(): array
	{
		return array_keys($this->providers);
	}

	private function resolveFqcn(string $absolutePath, string $baseDir, string $baseNs): ?string
	{
		$relative = substr($absolutePath, strlen($baseDir) + 1);
		if ($relative === '')
		{
			return null;
		}

		$relative = substr($relative, 0, -4);
		$relative = str_replace(['/', '\\'], '\\', $relative);

		return $baseNs . '\\' . $relative;
	}
}