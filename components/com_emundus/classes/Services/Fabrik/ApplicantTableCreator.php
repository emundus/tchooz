<?php

namespace Tchooz\Services\Fabrik;

use EmundusHelperFiles;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;

class ApplicantTableCreator implements TableFromReferenceCreatorInterface
{

	public function supports(string $tableName): bool
	{
		if (
			preg_match('/^jos_emundus_\d{4}_\d+$/', $tableName) ||
			// can also match tables like jos_emundus_1001_01_814_repeat
			preg_match('/^jos_emundus_\d{4}_\d+_\d+_repeat$/', $tableName)
		) {
			return true;
		}

		return false;
	}

	public function createTableFromReference(string $tableName, array $args = []): string
	{
		$newTable = '';

		if (!empty($tableName))
		{
			assert(isset($args['profile_id']));
			$profileId = $args['profile_id'];
			$parentTableName = $args['parent_table_name'] ?? '';
			$groupId = $args['group_id'] ?? 0;


			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = 'SHOW CREATE TABLE ' . $db->quoteName($tableName);

			try {
				$db->setQuery($query);
				$result = $db->loadAssoc();
			}
			catch (\Exception $e) {
				$result = array();
			}

			if (!empty($result['Create Table'])) {
				$createTableQuery = $result['Create Table'];

				if (empty($parentTableName)) {
					$increment      = 0;
					$newTable = 'jos_emundus_' . $profileId . '_' . str_pad($increment, 2, '0', STR_PAD_LEFT);

					$h_files = new EmundusHelperFiles();
					while ($h_files->tableExists($newTable)) {
						$increment++;
						$newTable = 'jos_emundus_' . $profileId . '_' . str_pad($increment, 2, '0', STR_PAD_LEFT);
					}
				}
				else
				{
					$newTable = $parentTableName . '_' . $groupId . '_repeat';
				}

				$createTableQuery = str_replace($tableName, $newTable, $createTableQuery);

				try {
					$db->setQuery($createTableQuery);
					$created = $db->execute();

					if (!$created) {
						return '';
					}
				}
				catch (\Exception $e) {
					Log::add('component/com_emundus/models/formbuilder | Error at create a table from the template ' . $tableName . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.formbuilder');
				}
			}
		}

		return $newTable;
	}
}