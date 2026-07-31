<?php

namespace Tchooz\Enums\Workflow;

enum StepStateEnum: int
{
	case PUBLISHED = 1;
	case ARCHIVED = 0;
	case DELETED = -1;
}
