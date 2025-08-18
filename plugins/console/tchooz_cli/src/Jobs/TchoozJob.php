<?php
/**
 * @package     Emundus\Plugin\Console\Tchooz\CliCommand
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Emundus\Plugin\Console\Tchooz\Jobs;

use Joomla\CMS\Log\Log;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class TchoozJob
{
	protected bool $allowFailure = false;

	protected array $colors = [
		'blue' => "\e[34m",
		'green' => "\e[32m",
		'red' => "\e[31m",
		'yellow' => "\e[33m",
		'reset' => "\e[0m",
		'bold' => "\e[1m"
	];

	public function __construct(object $logger) {
		Log::addLogger($logger->options,Log::ALL, [$logger->jobName]);
	}

	public function execute(InputInterface $input, OutputInterface $output): void {
		//return;
	}

	public static function getJobName(): string {
		return self::class;
	}

	public static function getJobDescription(): ?string {
		return null;
	}

	public function isAllowFailure(): bool {
		return $this->allowFailure;
	}
}