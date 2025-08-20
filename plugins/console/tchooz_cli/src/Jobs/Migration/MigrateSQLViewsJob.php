<?php
/**
 * @package     Emundus\Plugin\Console\Tchooz\Jobs
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Emundus\Plugin\Console\Tchooz\Jobs\Migration;

use Emundus\Plugin\Console\Tchooz\Jobs\TchoozJob;
use Emundus\Plugin\Console\Tchooz\Services\DatabaseService;
use Emundus\Plugin\Console\Tchooz\Style\EmundusProgressBar;
use Joomla\CMS\Log\Log;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateSQLViewsJob extends TchoozJob
{
	public function __construct(
		private readonly object            $logger,
		private readonly DatabaseService   $databaseServiceSource,
		private readonly DatabaseService   $databaseService
	)
	{
		parent::__construct($logger);
	}

	public function execute(InputInterface $input, OutputInterface $output): void
	{
		$section = $output->section();
		$views = $this->databaseServiceSource->getViews();

		$progressBar = new EmundusProgressBar($section, count($views));
		$progressBar->setMessage('Migrating SQL views');
		$progressBar->start();
		foreach ($views as $view)
		{
			$dump = $this->databaseServiceSource->getViewCreate($view);
			if (empty($dump['Create View']) || !$this->databaseService->createView($dump, $view, $this->databaseServiceSource->getDbName()))
			{
				Log::add('Error while migrating view ' . $view, Log::ERROR, self::getJobName());
				throw new \RuntimeException('Error while migrating view ' . $view);
			}
			$progressBar->advance();
		}
		$progressBar->finish('SQL views migrated');

		Log::add('SQL views migrated', Log::INFO, self::getJobName());
	}

	public static function getJobName(): string
	{
		return 'SQL Views';
	}

	public static function getJobDescription(): ?string
	{
		return 'Migrate SQL views';
	}
}