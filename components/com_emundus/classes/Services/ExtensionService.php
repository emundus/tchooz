<?php
/**
 * @package     Tchooz\Services
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services;

use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Cache\Exception\CacheExceptionInterface;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;

class ExtensionService
{
	public static function updateExtensionParam(
		string $extension = 'com_emundus',
		string $paramName = '',
		mixed $paramValue = null
	): bool
	{
		if(empty($extension))
		{
			throw new \InvalidArgumentException('Extension name cannot be empty');
		}

		if(is_null($paramValue))
		{
			throw new \InvalidArgumentException('Parameter value cannot be null');
		}

		$parameters = ComponentHelper::getParams($extension);
		if (!$parameters->exists($paramName))
		{
			throw new \InvalidArgumentException(sprintf('Parameter %s does not exist for extension %s', $paramName, $extension));
		}

		$parameters->set($paramName, $paramValue);

		$componentid = ComponentHelper::getComponent($extension)->id;
		$db          = Factory::getContainer()->get('DatabaseDriver');
		$query       = $db->getQuery(true);


		$query->update('#__extensions')
			->set($db->quoteName('params') . ' = ' . $db->quote($parameters->toString()))
			->where($db->quoteName('extension_id') . ' = ' . $db->quote($componentid));
		$db->setQuery($query);
		if (!$db->execute())
		{
			throw new \RuntimeException($db->getErrorMsg());
		}

		// Clear cache to ensure new params are loaded
		// TODO: Replace by EmundusHelperCache after MR #1046 is merged
		Factory::getCache('_system')->clean();
		return true;
	}

	public static function getParamValue(
		string $extension = 'com_emundus',
		string $paramName = '',
		mixed  $defaultValue = ''
	): mixed
	{
		$value = null;

		$parameters = ComponentHelper::getParams($extension);
		if ($parameters->exists($paramName))
		{
			$value = $parameters->get($paramName, $defaultValue);
		}

		return $value;
	}
	
	private static array $extensionIdCache = [];

	public static function getExtensionId(string $component = 'com_emundus'): int
	{
		if (isset(self::$extensionIdCache[$component]))
		{
			return self::$extensionIdCache[$component];
		}

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();

		$query->clear()
			->select('extension_id')
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('type') . ' = ' . $db->quote('component'))
			->andWhere($db->quoteName('element') . ' = ' . $db->quote($component));
		$db->setQuery($query);
		$component_id = (int) $db->loadResult();

		if (empty($component_id))
		{
			$component_id = (int) ComponentHelper::getComponent($component)->id;
		}

		self::$extensionIdCache[$component] = $component_id;

		return $component_id;
	}

	/**
	 * Clears ComponentHelper caches so getComponent()/getParams() reflect the current #__extensions state.
	 *
	 * ComponentHelper keeps two layers: an in-memory static array (protected, no public reset) primed once
	 * per request, and the persistent "_system" callback cache group. Both must be cleared, otherwise a
	 * stale entry can make getComponent()->id return 0 right after an install/update.
	 *
	 * @return void
	 * @since version 2.3.0
	 */
	public static function clearComponentHelperCache(): void
	{
		// 1. In-memory static array (ComponentHelper::$components) — reset via reflection, no public API exists.
		try
		{
			$componentsProperty = new \ReflectionProperty(ComponentHelper::class, 'components');
			$componentsProperty->setAccessible(true);
			$componentsProperty->setValue(null, []);
		}
		catch (\ReflectionException $e)
		{
			Log::add('Unable to reset ComponentHelper static cache: ' . $e->getMessage(), Log::WARNING, 'com_emundus');
		}

		// 2. Persistent "_system" callback cache group used by ComponentHelper::load().
		try
		{
			Factory::getContainer()->get(CacheControllerFactoryInterface::class)
				->createCacheController('callback', ['defaultgroup' => '_system'])
				->clean();
		}
		catch (CacheExceptionInterface $e)
		{
			Log::add('Unable to clean _system cache group: ' . $e->getMessage(), Log::WARNING, 'com_emundus');
		}

		// Also drop our own memoized ids so they get re-resolved after the cache reset.
		self::$extensionIdCache = [];
	}
}