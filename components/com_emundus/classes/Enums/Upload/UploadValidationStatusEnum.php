<?php

namespace Tchooz\Enums\Upload;

enum UploadValidationStatusEnum: int
{
	case TO_BE_VALIDATED = -2;
	case VALIDATED = 1;
	case INVALID = 0;
}
