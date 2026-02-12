<?php
/**
 * @package     Tchooz\Services\Export
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Export;

use Joomla\CMS\Cache\CacheController;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Tchooz\Entities\Export\ExportEntity;

class ExportRegistry
{
	private CONST EXPORTS_DIRECTORY = JPATH_ROOT . '/components/com_emundus/classes/Services/Export';

	private array $exportServices = [];

	private CacheController $cache;

	public function __construct()
	{
		$this->cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
			->createCacheController('output', ['defaultgroup' => 'com_emundus']);
		$this->autoRegisterExports();
	}

	private function autoRegisterExports(): void
	{
		$exportServices = $this->cache->get('export_services');

		if (empty($exportServices)) {
			$directories = glob(self::EXPORTS_DIRECTORY . '/*', GLOB_ONLYDIR);
			if(!empty($directories)) {
				foreach ($directories as $directory) {
					$files = glob($directory . '/*Service.php');
					if ($files) {
						foreach ($files as $file) {
							$className = 'Tchooz\\Services\\Export\\' . pathinfo(basename($directory), PATHINFO_FILENAME) . '\\' . pathinfo($file, PATHINFO_FILENAME);
							$this->register($className);
						}
					}
				}

				$this->cache->store($this->exportServices, 'export_services');
			}
		} else {
			$this->exportServices = $exportServices;
		}
	}

	private function register(string $className): void
	{
		if (class_exists($className)) {
			$reflection = new \ReflectionClass($className);
			if (!$reflection->isAbstract() && $reflection->implementsInterface(ExportInterface::class)) {
				$this->exportServices[$className::getType()] = $className;
			}
		}
	}

	public function getExportServiceInstance(string $type, array $fnums, User $user, ?array $options = null, ExportEntity $exportEntity = null): ?ExportInterface
	{
		if (isset($this->exportServices[$type])) {
			$className = $this->exportServices[$type];
			return new $className($fnums, $user, $options, $exportEntity);
		}
		return null;
	}
}