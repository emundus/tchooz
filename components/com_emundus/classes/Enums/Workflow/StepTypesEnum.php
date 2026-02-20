<?php

namespace Tchooz\Enums\Workflow;

enum StepTypesEnum: string
{
	case APPLICANT = 'applicant';
	case EVALUATOR = 'evaluator';
	case PAYMENT = 'payment';
	case CHOICES = 'choices';


}
