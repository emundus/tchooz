<?php

namespace Tchooz\Enums\Workflow;

enum WorkflowStepDateRelativeToEnum: string
{
	case STATUS = 'status';
	case CAMPAIGN_START_DATE = 'campaign_start_date';
	case CAMPAIGN_END_DATE = 'campaign_end_date';
}
