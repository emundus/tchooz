<?php
/**
 * @package     Tchooz\Enums\Export
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Enums\Export;

enum PivotScopeEnum: string
{
	// Order matters — the UI renders scopes in declaration order (Element > Section > Form).
	case ELEMENT    = 'element';
	case GROUP      = 'group';
	// Kept as `evaluation` internally (a "form" pivot targets a multiple evaluation
	// workflow step), but shown to the user as "Formulaire" — see getLabel().
	case EVALUATION = 'evaluation';

	public function getLabel(): string
	{
		return match ($this)
		{
			PivotScopeEnum::ELEMENT    => 'COM_EMUNDUS_EXPORT_PIVOT_SCOPE_ELEMENT',
			PivotScopeEnum::GROUP      => 'COM_EMUNDUS_EXPORT_PIVOT_SCOPE_GROUP',
			PivotScopeEnum::EVALUATION => 'COM_EMUNDUS_EXPORT_PIVOT_SCOPE_EVALUATION',
		};
	}
}
