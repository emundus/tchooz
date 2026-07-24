<?php

namespace Tchooz\Enums\Poll;

enum AnswerTypeEnum: string
{
	case AVAILABLE = 'available';
	case NOT_AVAILABLE = 'not_available';
	case IF_NEEDED = 'if_needed';
	case NOT_ANSWERED = 'not_answered';
}
