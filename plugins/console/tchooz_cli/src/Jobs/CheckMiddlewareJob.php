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

class CheckMiddlewareJob extends TchoozJob
{
	public function __construct(
		private readonly object $logger,
		private readonly DatabaseService $databaseService
	)
	{
		$this->allowFailure = true;

		parent::__construct($logger);
	}

	public function execute() {
		$phpVersion = phpversion();
		Log::add('PHP version: ' . $phpVersion, Log::INFO, self::getJobName());
		if(version_compare($phpVersion, '8.2', '<')) {
			throw new \Exception('You need PHP 8.2 or higher to migrate to Tchooz v2.');
		}

		$engine = $this->databaseService->getDatabaseEngine();
		Log::add('Database engine: ' . $engine, Log::INFO, self::getJobName());
		if($engine !== 'InnoDB') {
			throw new \Exception('We will convert all tables to InnoDB as default engine in MySQL to migrate to Tchooz v2.');
		}

		$charsetCollation = $this->databaseService->getDefaultCharsetCollation();
		Log::add('Database charset: ' . $charsetCollation->charset . ', database collation: ' . $charsetCollation->collation, Log::INFO, self::getJobName());
		if ($charsetCollation->charset !== 'utf8mb4' || $charsetCollation->collation !== 'utf8mb4_0900_ai_ci') {
			throw new \Exception('We recommend you to set utf8mb4 as charset and utf8mb4_unicode_ci as collation in MySQL to migrate to Tchooz v2.');
		}
	}

	public static function getJobName(): string {
		return 'Middleware';
	}

	public static function getJobDescription(): ?string {
		return 'Check if the middleware is correctly configured. Needed PHP 8.2, InnoDB as default engine in MySQL';
	}
}