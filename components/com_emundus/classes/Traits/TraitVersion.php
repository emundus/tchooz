<?php
/**
 * @package     Tchooz\Traits
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Traits;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

trait TraitVersion
{
	public function createVersion(DatabaseInterface $db, string $version, ?string $date): bool
	{
		if(empty($date))
		{
			$date = Factory::getDate()->toSql();
		}

		$insert = (object) [
			'version' => $version,
			'update_date' => $date,
		];
		return $db->insertObject('#__emundus_version', $insert, 'version');
	}

	public function updateVersion(DatabaseInterface $db, string $version, ?string $date): bool
	{
		if(empty($date))
		{
			$date = Factory::getDate()->toSql();
		}

		$update = (object) [
			'version' => $version,
			'update_date' => $date,
		];
		return $db->updateObject('#__emundus_version', $update, 'version');
	}

	public function getVersion(DatabaseInterface $db, string $version): ?object
	{
		$query = $db->getQuery(true)
			->select($db->quoteName(['version', 'update_date']))
			->from($db->quoteName('#__emundus_version'))
			->where($db->quoteName('version') . ' = ' . $db->quote($version));

		$db->setQuery($query);

		return $db->loadObject();
	}

	public function getVersionDate(DatabaseInterface $db, string $version): ?string
	{
		$versionObj = $this->getVersion($db, $version);

		if($versionObj)
		{
			return $versionObj->update_date;
		}

		return null;
	}
}