<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.0
 * @author	hikashop.com
 * @copyright	(C) 2010-2024 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php


namespace Carbon\Traits;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Closure;
use DateTimeImmutable;
use DateTimeInterface;

trait IntervalStep
{
    protected $step;

    public function getStep(): ?Closure
    {
        return $this->step;
    }

    public function setStep(?Closure $step): void
    {
        $this->step = $step;
    }

    public function convertDate(DateTimeInterface $dateTime, bool $negated = false): CarbonInterface
    {

        $carbonDate = $dateTime instanceof CarbonInterface ? $dateTime : $this->resolveCarbon($dateTime);

        if ($this->step) {
            return $carbonDate->setDateTimeFrom(($this->step)($carbonDate->avoidMutation(), $negated));
        }

        if ($negated) {
            return $carbonDate->rawSub($this);
        }

        return $carbonDate->rawAdd($this);
    }

    private function resolveCarbon(DateTimeInterface $dateTime)
    {
        if ($dateTime instanceof DateTimeImmutable) {
            return CarbonImmutable::instance($dateTime);
        }

        return Carbon::instance($dateTime);
    }
}
