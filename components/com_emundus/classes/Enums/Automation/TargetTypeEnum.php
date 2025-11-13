<?php

namespace Tchooz\Enums\Automation;

use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\TargetPredefinitions\ApplicantCurrentFilePredefinition;

enum TargetTypeEnum: string
{
	case USER = 'user';
	case FILE = 'file';
	case GROUP = 'group';

	public function getTable()
	{
		return match ($this) {
			self::USER => '#__users',
			self::FILE => '#__emundus_campaign_candidature',
			self::GROUP => '#__emundus_setup_groups',
		};
	}

	public function getTableAlias()
	{
		return match ($this) {
			self::USER => 'u',
			self::FILE => 'ecc',
			self::GROUP => 'esg',
		};
	}

	public function getPrimaryField()
	{
		return match ($this) {
			self::USER => 'id',
			self::FILE => 'fnum',
			self::GROUP => 'id',
		};
	}
}
