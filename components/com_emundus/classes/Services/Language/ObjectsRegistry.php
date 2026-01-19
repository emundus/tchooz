<?php
/**
 * @package     Tchooz\Services\Language
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Language;

use EmundusHelperCache;
use Tchooz\Services\Language\Objects\ObjectInterface;

class ObjectsRegistry
{
	private CONST OBJECTS_DIRECTORY = JPATH_ROOT . '/components/com_emundus/classes/Services/Language/Objects';

	private array $objects = [];

	private \EmundusHelperCache $cache;

	public function __construct()
	{
		$this->cache = new EmundusHelperCache();
		$this->autoRegisterObjects();
	}

	private function autoRegisterObjects(): void
	{
		$objects = $this->cache->get('translations_objects');

		if (empty($actions)) {
			$files = glob(self::OBJECTS_DIRECTORY . '/Object*.php');
			if ($files) {
				foreach ($files as $file) {
					$className = 'Tchooz\\Services\\Language\\Objects\\' . pathinfo($file, PATHINFO_FILENAME);
					$this->register($className);
				}

				$this->cache->set('translations_objects', $this->objects);
			}
		} else {
			$this->objects = $objects;
		}
	}

	private function register(string $className): void
	{
		if (class_exists($className)) {
			$reflection = new \ReflectionClass($className);
			if (!$reflection->isAbstract() && $reflection->implementsInterface(ObjectInterface::class)) {
				$instance = $reflection->newInstance();
				$this->objects[$instance->getType()] = $instance;
			}
		}
	}

	public function getObjectByType(string $type): ?ObjectInterface
	{
		return $this->objects[$type] ?? null;
	}

	/**
	 *
	 * @return array<ObjectInterface>
	 *
	 * @since version
	 */
	public function getObjects(): array
	{
		return $this->objects;
	}
}