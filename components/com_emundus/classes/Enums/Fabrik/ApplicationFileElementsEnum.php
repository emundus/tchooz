<?php

namespace Tchooz\Enums\Fabrik;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

enum ApplicationFileElementsEnum: string
{
	case CAMPAIGN = 'campaign';

	case PROGRAM = 'program';

	public function getLabel(): string
	{
		return match ($this)
		{
			self::CAMPAIGN => Text::_('COM_EMUNDUS_CAMPAIGN'),
			self::PROGRAM => Text::_('COM_EMUNDUS_PROGRAMME'),
		};
	}

	public function getTableName(): string
	{
		return match ($this)
		{
			self::CAMPAIGN => '#__emundus_setup_campaigns',
			self::PROGRAM => '#__emundus_setup_programmes'
		};
	}
}
