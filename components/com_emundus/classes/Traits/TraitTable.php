<?php
/**
 * @package     Tchooz\Traits
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Traits;

trait TraitTable
{
	private static array $__tableAttributeCache = [];
	private static ?array $__tableGeneratedMap = null;

	private static function loadGeneratedMap(): array
	{
		if (self::$__tableGeneratedMap !== null) {
			return self::$__tableGeneratedMap;
		}

		$generatedFile = JPATH_CACHE.'/autoload_tables.php';
		if (file_exists($generatedFile)) {
			$map = require $generatedFile;
			if (is_array($map)) {
				self::$__tableGeneratedMap = $map;
				return $map;
			}
		}

		self::$__tableGeneratedMap = [];
		return [];
	}

	private static function loadTableAttribute(string $class): ?object
	{
		$map = self::loadGeneratedMap();
		if (isset($map[$class])) {
			return (object) $map[$class];
		}

		if (array_key_exists($class, self::$__tableAttributeCache)) {
			return self::$__tableAttributeCache[$class];
		}

		try {
			$reflection = new \ReflectionClass($class);
			$attrs = $reflection->getAttributes('Tchooz\Attributes\TableAttribute');
			if (count($attrs) === 0) {
				self::$__tableAttributeCache[$class] = null;
				return null;
			}

			$instance = $attrs[0]->newInstance();
			self::$__tableAttributeCache[$class] = $instance;
			return $instance;
		} catch (\ReflectionException $e) {
			self::$__tableAttributeCache[$class] = null;
			return null;
		}
	}

	public function getTableName(string $class): string
	{
		$attr = self::loadTableAttribute($class);
		return $attr?->table ?? '';
	}

	public function getTn(string $class): string
	{
		return $this->getTableName($class);
	}

	public function getTableAlias(string $class): string
	{
		$attr = self::loadTableAttribute($class);
		if ($attr === null) return '';
		return $attr->alias !== '' ? $attr->alias : $attr->table;
	}

	public function getTa(string $class): string
	{
		return $this->getTableAlias($class);
	}

	public function getTableColumns(string $class): array
	{
		$cacheKey = $class . ':columns';
		if (array_key_exists($cacheKey, self::$__tableAttributeCache)) {
			return self::$__tableAttributeCache[$cacheKey];
		}

		$attr = self::loadTableAttribute($class);
		if ($attr === null || empty($attr->columns)) {
			self::$__tableAttributeCache[$cacheKey] = [];
			return [];
		}

		$alias = $this->getTableAlias($class);
		$prefixed = [];
		foreach ($attr->columns as $col) {
			$prefixed[] = $alias . '.' . $col;
		}

		self::$__tableAttributeCache[$cacheKey] = $prefixed;
		return $prefixed;
	}

	public static function clearTableAttributeCache(?string $class = null): void
	{
		if ($class === null) {
			self::$__tableAttributeCache = [];
			self::$__tableGeneratedMap = null;
			return;
		}
		unset(self::$__tableAttributeCache[$class], self::$__tableAttributeCache[$class . ':columns']);
	}
}