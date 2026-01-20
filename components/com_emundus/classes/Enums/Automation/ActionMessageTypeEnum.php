<?php

namespace Tchooz\Enums\Automation;

enum ActionMessageTypeEnum: string
{
	case INFO = 'info';
	case WARNING = 'warning';
	case ERROR = 'error';
}