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

abstract class TchoozJob
{
	protected bool $allowFailure = false;

	public function __construct(object $logger) {
		Log::addLogger($logger->options,Log::ALL, [$logger->jobName]);
	}

	public function execute() {
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