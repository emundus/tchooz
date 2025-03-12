<?php
/**
 * @package     Emundus\Plugin\Console\Tchooz\Jobs
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Emundus\Plugin\Console\Tchooz\Jobs;

use Emundus\Plugin\Console\Tchooz\Jobs\TchoozJob;
use Emundus\Plugin\Console\Tchooz\Services\DatabaseService;
use Emundus\Plugin\Console\Tchooz\Services\StorageService;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Log\Logger;
use Symfony\Component\Console\Output\OutputInterface;

class CheckVersionJob extends TchoozJob
{
	const BASE_VERSION = '1.39.20';

	public function __construct(
		private readonly object          $logger,
		private readonly DatabaseService $databaseService,
		private readonly string          $projectToMigrate
	)
	{
		parent::__construct($logger);
	}

	public function execute()
	{
		$diffs = exec('git status --porcelain');
		if(!empty($diffs)) {
			file_put_contents(JPATH_SITE.'/logs/migration.git.log', $diffs);
			throw new \Exception('You have uncommitted changes in your project. Please commit them before migrating to Tchooz v2.');
		}

		$schema_version = $this->databaseService->getSchemaVersion();
		Log::add('Schema version: ' . $schema_version, Log::INFO, self::getJobName());

		if (is_file($this->projectToMigrate . '/administrator/components/com_emundus/emundus.xml'))
		{
			$xml     = simplexml_load_file($this->projectToMigrate . '/administrator/components/com_emundus/emundus.xml');
			$version = (string) $xml->version;
		}
		if (empty($version))
		{
			throw new \Exception('The version of the project to migrate is not defined.');
		}
		Log::add('File version: ' . $version, Log::INFO, self::getJobName());

		if (version_compare($schema_version, $version, '<'))
		{
			throw new \Exception('You have to update the database schema to the latest version before migrating to Tchooz v2.');
		}
	}

	public static function getJobName(): string
	{
		return 'Versions';
	}

	public static function getJobDescription(): ?string
	{
		return 'Check of the versions';
	}
}