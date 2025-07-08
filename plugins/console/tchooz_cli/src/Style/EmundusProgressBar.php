<?php
/**
 * @package     Emundus\Plugin\Console\Tchooz\Style
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Emundus\Plugin\Console\Tchooz\Style;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class EmundusProgressBar
{
	private ProgressBar $progressBar;

	public function __construct(private readonly OutputInterface $output, int $max = 1000)
	{
		$this->progressBar = new ProgressBar($output, $max);
		$this->configureProgressBar();
	}

	private function configureProgressBar(): void
	{
		$this->progressBar->setFormat(
			"<fg=white;bg=cyan> %status:-45s%</>\n%current%/%max% [%bar%] %percent:3s%%\n🏁  %estimated:-21s% %memory:21s%"
		);
		$this->progressBar->setBarCharacter('<fg=green>⚬</>');
		$this->progressBar->setEmptyBarCharacter('<fg=red>⚬</>');
		$this->progressBar->setProgressCharacter('<fg=green>➤</>');
		$this->progressBar->setRedrawFrequency(10);
	}

	public function start(): void
	{
		$this->progressBar->start();
	}

	public function advance(int $step = 1): void
	{
		$this->progressBar->advance($step);
	}

	public function setMessage(string $message, string $key = 'status'): void
	{
		$this->progressBar->setMessage($message, $key);
	}

	public function finish(string $message = 'Done :)'): void
	{
		$this->progressBar->setMessage($message . ' ✓', 'status');
		$this->progressBar->finish();
		$this->output->writeln('');
	}
}