<?php
/**
 * @package     Joomla\Plugin\Emundus\Parcoursup
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Joomla\Plugin\Emundus\Parcoursup;

use Joomla\Database\DatabaseInterface;
use Joomla\Plugin\Emundus\Parcoursup\Helper\ArrayHelper;

class ParcoursupDataProvider
{
	public function __construct(
		private readonly string      $baseJsonPath = '',
		private readonly bool        $debugMode = false,
		private readonly DatabaseInterface $db,
		private readonly ArrayHelper $arrayHelper,
	)
	{
		$tables = $this->db->getTableList();

		if(!in_array('jos_emundus_campaign_candidature_parcoursup', $tables)) {
			if(!$this->createTable()) {
				throw new \Exception('The table could not be created');
			}
		}
	}

	public function loadFromFile(array $webhookFiles): array
	{
		$dirsToImport = [];
		foreach ($webhookFiles as $webhookFile)
		{
			$filename = $webhookFile['name'];
			$filepath = $webhookFile['tmp_name'];
			$ext      = pathinfo($filename, PATHINFO_EXTENSION);

			if ($ext == 'zip' && !empty($filepath))
			{
				$zip = new \ZipArchive();

				if ($zip->open($filepath) === true)
				{
					$newFilename = date('Y-m-d-H-i') . '_' . rand(0, 1000);
					$tmpPath     = JPATH_ROOT . '/tmp/parcoursup/' . $newFilename;

					if ($zip->extractTo($tmpPath))
					{
						$dirsToImport[] = $tmpPath;
					}

					if ($this->debugMode)
					{
						$debugPath = JPATH_ROOT . '/logs/parcoursup/' . $newFilename;
						$zip->extractTo($debugPath);
					}

					$zip->close();
					unset($zip);
				}
			}
		}

		foreach ($dirsToImport as $dir)
		{
			if (is_dir($dir))
			{
				/*$importPath = JPATH_ROOT . '/images/emundus/import';
				if (!is_dir($importPath))
				{
					mkdir($importPath, 0777, true);
				}
				$parcoursupImportPath = $importPath . '/parcoursup';
				if (!is_dir($parcoursupImportPath))
				{
					mkdir($parcoursupImportPath, 0777, true);
				}
				$parcoursupImportPath = $parcoursupImportPath . '/' . date('Y-m-d-H-i-s') . '_' . rand(0, 1000);
				mkdir($parcoursupImportPath, 0777, true);*/

				$files = glob($dir . '/*');
				foreach ($files as $file)
				{
					// Get name of the file
					$filename = pathinfo($file, PATHINFO_FILENAME);

					// Check if the file is a json file
					if (pathinfo($file, PATHINFO_EXTENSION) == 'json')
					{
						$json = file_get_contents($file);
						// merge the datas
						$jsonDatas = json_decode($json, true);

						if (json_last_error() !== JSON_ERROR_NONE)
						{
							throw new \InvalidArgumentException('The JSON file is not valid');
						}

						/*$importFile = $parcoursupImportPath . '/' . $filename . '.json';
						file_put_contents($importFile, json_encode($jsonDatas, JSON_PRETTY_PRINT));*/
						if (empty($datas))
						{
							$datas = $jsonDatas;
						}
						else
						{
							$tmpDatas       = $this->arrayHelper->getNestedValue($datas, $this->baseJsonPath);
							$tmpDatasToPush = $this->arrayHelper->getNestedValue($jsonDatas, $this->baseJsonPath);
							if (!empty($tmpDatasToPush))
							{
								$tmpDatas[] = $tmpDatasToPush[0];
							}

							$datas = $this->arrayHelper->setNestedValue($datas, $this->baseJsonPath, $tmpDatas, '.');
						}
					}
				}

				if(!$this->deleteDir($dir)) {
					throw new \Exception('The directory could not be deleted');
				}
			}
		}

		return $datas;
	}

	private function createTable(): bool
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_emundus/helpers/update.php';
		$columns = [
			[
				'name' => 'id_parcoursup',
				'type' => 'VARCHAR',
				'null' => 1,
				'length' => 100
			],
			[
				'name' => 'fnum',
				'type' => 'VARCHAR',
				'null' => 1,
				'length' => 28
			],
			[
				'name' => 'campaign_id',
				'type' => 'INT',
				'null' => 1,
				'length' => 11
			],
			[
				'name'   => 'created_at',
				'type'   => 'DATETIME',
				'null'   => 0
			],
			[
				'name'   => 'updated_at',
				'type'   => 'DATETIME',
				'null'   => 1
			],
			[
				'name'   => 'deleted_at',
				'type'   => 'DATETIME',
				'null'   => 1
			],
			[
				'name' => 'json',
				'type' => 'TEXT',
				'null' => 1
			],
			[
				'name' => 'crm_sended_at',
				'type' => 'DATETIME',
				'null' => 1
			]
		];
		
		return \EmundusHelperUpdate::createTable('jos_emundus_campaign_candidature_parcoursup', $columns)['status'];
	}

	private function deleteDir(string $dirPath): bool
	{
		if (!is_dir($dirPath))
		{
			throw new \InvalidArgumentException("$dirPath must be a directory");
		}

		if (!str_contains($dirPath, 'tmp/parcoursup'))
		{
			throw new \InvalidArgumentException("$dirPath must be the tmp/parcoursup directory");
		}

		if (substr($dirPath, strlen($dirPath) - 1, 1) != '/')
		{
			$dirPath .= '/';
		}
		$files = glob($dirPath . '*', GLOB_MARK);
		foreach ($files as $file)
		{
			if (is_dir($file))
			{
				$this->deleteDir($file);
			}
			else
			{
				unlink($file);
			}
		}

		return rmdir($dirPath);
	}
}