<?php

namespace Tchooz\Enums\Workflow;

enum WorkflowStepDatesRelativeUnitsEnum: string
{
	case DAY = 'day';
	case WEEK = 'week';
	case MONTH = 'month';
	case YEAR = 'year';
}