<?php
/**
 * @package     Tchooz\Enums
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums;

enum AccessLevelEnum: int
{
	case ADMINISTRATOR = 8;

	case COORDINATOR = 7;

	case MANAGER = 17;

	case PARTNER = 6;

	case APPLICANT = 4;

	case REGISTERED = 2;

	case PUBLIC = 1;

	public function getMethodName(): string
	{
		return match ($this)
		{
			self::ADMINISTRATOR => 'asAdministratorAccessLevel',
			self::COORDINATOR => 'asCoordinatorAccessLevel',
			self::MANAGER => 'asManagerAccessLevel',
			self::PARTNER => 'asPartnerAccessLevel',
			self::APPLICANT => 'asApplicantAccessLevel',
			self::REGISTERED => 'asRegisteredAccessLevel',
			self::PUBLIC => 'asPublicAccessLevel',
		};
	}
}
