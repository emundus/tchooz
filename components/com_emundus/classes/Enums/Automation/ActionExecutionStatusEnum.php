<?php

namespace Tchooz\Enums\Automation;

enum ActionExecutionStatusEnum: string
{
	case PENDING = 'pending';
	case IN_PROGRESS = 'in_progress';
	case COMPLETED = 'completed';
	case FAILED = 'failed';
}
