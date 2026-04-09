<?php

namespace Tchooz\Enums\Campaigns;

use Joomla\CMS\Language\Text;

enum AnonymizationPolicyEnum: string
{
	case GLOBAL = 'global';
	case FORBIDDEN = 'forbidden';
	case FORCED = 'forced';
	case OPTIONAL = 'optional';

	public function getLabel(): string
	{
		return match ($this)
		{
			self::GLOBAL => Text::_('COM_EMUNDUS_CAMPAIGN_ANONYMISATION_GLOBAL'),
			self::FORBIDDEN => Text::_('COM_EMUNDUS_CAMPAIGN_ANONYMISATION_FORBIDDEN'),
			self::FORCED => Text::_('COM_EMUNDUS_CAMPAIGN_ANONYMISATION_FORCED'),
			self::OPTIONAL => Text::_('COM_EMUNDUS_CAMPAIGN_ANONYMISATION_OPTIONAL'),
		};
	}
}
