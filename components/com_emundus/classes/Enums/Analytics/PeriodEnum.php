<?php
/**
 * @package     Tchooz\Enums\Analytics
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums\Analytics;

enum PeriodEnum: string
{
	case ALL_TIME = 'all_time';
	case YEARLY = 'yearly';
	CASE MONTHLY = 'monthly';
	case WEEKLY = 'weekly';
	case DAILY = 'daily';

	public function getPeriodDates(): array
	{
		return match($this)
		{
			self::ALL_TIME => ['start_date' => null, 'end_date' => null],
			self::YEARLY   => ['start_date' => (new \DateTime())->modify('-1 year')->setDate((new \DateTime())->format('Y'), 1, 1)->setTime(0, 0, 0),
			                   'end_date'   => (new \DateTime())->setDate((new \DateTime())->format('Y'), 12, 31)->setTime(23, 59, 59)],
			self::MONTHLY  => ['start_date' => (new \DateTime())->modify('-1 month')->setDate((new \DateTime())->format('Y'), (new \DateTime())->format('m'), 1)->setTime(0, 0, 0),
			                   'end_date'   => (new \DateTime())->setDate((new \DateTime())->format('Y'), (new \DateTime())->format('m'), (new \DateTime())->format('t'))->setTime(23, 59, 59)],
			self::WEEKLY   => ['start_date' => (new \DateTime())->modify('-1 week')->setTime(0, 0, 0),
			                   'end_date'   => (new \DateTime())->setTime(23, 59, 59)],
			self::DAILY    => ['start_date' => (new \DateTime())->setTime(0, 0, 0),
			                   'end_date'   => (new \DateTime())->setTime(23, 59, 59)],
		};
	}
}
