<?php

namespace Tchooz\Enums\Automation;

enum ConditionMatchModeEnum: string
{
	case ANY = 'any'; // au moins une valeur correspond
	case ALL = 'all'; // toutes les valeurs configurées doivent être présentes
	case EXACT = 'exact'; // correspondance exacte des ensembles
}
