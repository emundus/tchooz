<?php
/**
 * @package     Tchooz\Services
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services;

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
}