<?php
/**
 * @package     Tchooz\Enums\Menus
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

declare(strict_types=1);

namespace Tchooz\Enums\Menus;

/**
 * Canonical definition of the standard "actions" menu entries.
 *
 * Each case represents a baseline action menu shared across every platform.
 * The enum value is the Joomla menu link used to match existing menu rows
 * in `#__menu` (see the `link` column). Labels, heading and ordering are
 * the source of truth when (re)building the "actions" menu type.
 */
enum ActionMenuEnum: string
{
	case ADD_DOCUMENT       = 'index.php?option=com_fabrik&view=form&formid=67&rowid=&jos_emundus_uploads___user_id[value]={applicant_id}&jos_emundus_uploads___fnum[value]={fnum}&student_id={applicant_id}&jos_emundus_uploads___campaign_id[value]={campaign_id}&tmpl=component&iframe=1';
	case ADD_EVALUATION     = 'index.php?option=com_fabrik&c=form&view=form&formid={formid}&tmpl=component&iframe=1&rowid=&jos_emundus_evaluations___student_id[value]={applicant_id}&jos_emundus_evaluations___campaign_id[value]={campaign_id}&jos_emundus_evaluations___fnum[value]={fnum}&student_id={applicant_id}&tmpl=component&iframe=1&Itemid={Itemid}';
	case ADD_ACCESS         = 'index.php?option=com_emundus&view=files&format=raw&layout=access&users={fnums}';
	case ADD_LETTER         = '/index.php?option=com_emundus&view=files&format=raw&layout=docs&Itemid={Itemid}&fnums={fnums}&format=raw';
	case ADD_TROMBINOSCOPE  = 'index.php?option=com_emundus&view=trombinoscope&tmpl=component&fnums={fnums}';
	case ADD_DECISION       = 'index.php?option=com_fabrik&c=form&view=form&formid={formid}&tmpl=component&iframe=1&rowid=&jos_emundus_final_grade___student_id[value]={applicant_id}&jos_emundus_final_grade___campaign_id[value]={campaign_id}&jos_emundus_final_grade___fnum[value]={fnum}&student_id={applicant_id}&tmpl=component&iframe=1&Itemid={Itemid}';

	case EDIT_TAGS          = 'index.php?option=com_emundus&controller={view}&task=gettags';
	case EDIT_STATUS        = 'index.php?option=com_emundus&controller=files&task=getstate';
	case EDIT_PUBLICATION   = 'index.php?option=com_emundus&controller=files&task=getpublish';
	case EDIT_CAMPAIGN      = 'index.php?option=com_fabrik&view=form&formid=150&rowid=&jos_emundus_campaign_candidature___applicant_id={applicant_id}&jos_emundus_campaign_candidature___copied=1&jos_emundus_campaign_candidature___fnum={fnum}&jos_emundus_campaign_candidature___status=2&tmpl=component&iframe=1';
	case EDIT_CART_PRODUCTS = '/index.php?option=com_emundus&view=payment&layout=affectproducts&format=raw&fnums={fnums}';
	case EDIT_OWNER         = 'index.php?option=com_emundus&view=files&layout=updateowner&format=raw';
	case GENERATE_REFERENCE = 'index.php?option=com_emundus&view=files&layout=generatereference&format=raw';

	case MAIL_APPLICANT     = 'index.php?option=com_emundus&view=email&tmpl=component&fnums={fnums}&fnum={fnum}&sid={applicant_id}&desc=0';
	case MAIL_EXPERT        = 'index.php?option=com_emundus&view=email&tmpl=component&layout=expert&Itemid={Itemid}&desc=3&format=raw';
	case SMS_APPLICANT      = '/index.php?option=com_emundus&view=sms&layout=send&format=raw&fnums={fnums}';

	case EXPORT_PDF         = 'index.php?option=com_emundus&task=export_pdf&fnums={fnums}&user={applicant_id}';
	case EXPORT_EXCEL       = 'index.php?option=com_emundus&controller=files&task=getformelem&Itemid={Itemid}';
	case EXPORT_ZIP         = 'index.php?option=com_emundus&controller=files&task=zip&Itemid={Itemid}&fnums={fnums}';
	case EXPORT             = 'index.php?option=com_emundus&view=files&tmpl=component&layout=export&format=raw';

	public function getLink(): string
	{
		return $this->value;
	}

	public function getLabelFr(): string
	{
		return match ($this)
		{
			self::ADD_DOCUMENT       => 'Ajouter un document',
			self::ADD_EVALUATION     => 'Ajouter une évaluation',
			self::ADD_ACCESS         => 'Ajouter des accès',
			self::ADD_LETTER         => 'Ajouter un courrier',
			self::ADD_TROMBINOSCOPE  => 'Ajouter un trombinoscope',
			self::ADD_DECISION       => 'Ajouter une décision',

			self::EDIT_TAGS          => 'Modifier les étiquettes',
			self::EDIT_STATUS        => 'Modifier le statut',
			self::EDIT_PUBLICATION   => 'Modifier la publication',
			self::EDIT_CAMPAIGN      => 'Modifier la campagne',
			self::EDIT_CART_PRODUCTS => 'Modifier les produits du panier',
			self::EDIT_OWNER         => 'Modifier le propriétaire',
			self::GENERATE_REFERENCE => 'Générer une référence',

			self::MAIL_APPLICANT     => 'E-mail au(x) déposant(s)',
			self::MAIL_EXPERT        => 'E-mail d\'invitation à un expert',
			self::SMS_APPLICANT      => 'SMS au(x) déposant(s)',

			self::EXPORT_PDF         => 'Exporter au format PDF',
			self::EXPORT_EXCEL       => 'Exporter au format Excel',
			self::EXPORT_ZIP         => 'Exporter au format ZIP',
			self::EXPORT             => 'Exporter',
		};
	}

	public function getLabelEn(): string
	{
		return match ($this)
		{
			self::ADD_DOCUMENT       => 'Add a document',
			self::ADD_EVALUATION     => 'Add an evaluation',
			self::ADD_ACCESS         => 'Add access',
			self::ADD_LETTER         => 'Add a letter',
			self::ADD_TROMBINOSCOPE  => 'Add a trombinoscope',
			self::ADD_DECISION       => 'Add a decision',

			self::EDIT_TAGS          => 'Edit stickers',
			self::EDIT_STATUS        => 'Edit status',
			self::EDIT_PUBLICATION   => 'Edit publication',
			self::EDIT_CAMPAIGN      => 'Edit campaign',
			self::EDIT_CART_PRODUCTS => 'Modify cart products',
			self::EDIT_OWNER         => 'Update the owner',
			self::GENERATE_REFERENCE => 'Generate a reference',

			self::MAIL_APPLICANT     => 'E-mail to applicant(s)',
			self::MAIL_EXPERT        => 'Invitation email to an expert',
			self::SMS_APPLICANT      => 'SMS to applicant(s)',

			self::EXPORT_PDF         => 'Export to PDF',
			self::EXPORT_EXCEL       => 'Export to Excel',
			self::EXPORT_ZIP         => 'Export to ZIP',
			self::EXPORT             => 'Export',
		};
	}

	public function getLabel(string $locale): string
	{
		return match (strtolower($locale))
		{
			'en', 'en-gb', 'en-us' => $this->getLabelEn(),
			default                => $this->getLabelFr(),
		};
	}

	public function getHeading(): MenuHeadingEnum
	{
		return match ($this)
		{
			self::ADD_DOCUMENT,
			self::ADD_EVALUATION,
			self::ADD_ACCESS,
			self::ADD_LETTER,
			self::ADD_TROMBINOSCOPE,
			self::ADD_DECISION       => MenuHeadingEnum::ADD,

			self::EDIT_TAGS,
			self::EDIT_STATUS,
			self::EDIT_PUBLICATION,
			self::EDIT_CAMPAIGN,
			self::EDIT_CART_PRODUCTS,
			self::EDIT_OWNER,
			self::GENERATE_REFERENCE => MenuHeadingEnum::EDIT,

			self::MAIL_APPLICANT,
			self::MAIL_EXPERT,
			self::SMS_APPLICANT      => MenuHeadingEnum::SEND,

			self::EXPORT_PDF,
			self::EXPORT_EXCEL,
			self::EXPORT_ZIP,
			self::EXPORT             => MenuHeadingEnum::EXPORT,
		};
	}

	/**
	 * Relative ordering within the "actions" menu.
	 *
	 * Ranges are reserved per heading so entries can be inserted without
	 * re-shuffling the rest:
	 *  - ADD    : 000-099
	 *  - EDIT   : 100-199
	 *  - SEND   : 200-299
	 *  - EXPORT : 300-399
	 */
	public function getOrdering(): int
	{
		return match ($this)
		{
			self::ADD_DOCUMENT       => 0,
			self::ADD_EVALUATION     => 1,
			self::ADD_ACCESS         => 2,
			self::ADD_LETTER         => 3,
			self::ADD_TROMBINOSCOPE  => 4,
			self::ADD_DECISION       => 5,

			self::EDIT_STATUS          => 100,
			self::EDIT_TAGS        => 101,
			self::EDIT_PUBLICATION   => 102,
			self::EDIT_CAMPAIGN      => 103,
			self::EDIT_CART_PRODUCTS => 104,
			self::EDIT_OWNER         => 105,
			self::GENERATE_REFERENCE => 106,

			self::MAIL_APPLICANT     => 200,
			self::MAIL_EXPERT        => 201,
			self::SMS_APPLICANT      => 202,

			self::EXPORT_PDF         => 300,
			self::EXPORT_EXCEL       => 301,
			self::EXPORT_ZIP         => 302,
			self::EXPORT             => 303,
		};
	}

	public static function fromLink(string $link): ?self
	{
		return self::tryFrom($link);
	}

	/**
	 * All action menus sorted by their ordering.
	 *
	 * @return  array<ActionMenuEnum>
	 */
	public static function sorted(): array
	{
		$cases = self::cases();
		usort($cases, fn(self $a, self $b) => $a->getOrdering() <=> $b->getOrdering());

		return $cases;
	}

	/**
	 * All action menus attached to a given heading, sorted by ordering.
	 *
	 * @param   MenuHeadingEnum  $heading
	 *
	 * @return  array<ActionMenuEnum>
	 */
	public static function byHeading(MenuHeadingEnum $heading): array
	{
		return array_values(array_filter(
			self::sorted(),
			fn(self $menu) => $menu->getHeading() === $heading
		));
	}

	/**
	 * All action menus grouped by heading, sorted within each group.
	 *
	 * @return  array<string, array<ActionMenuEnum>>  keyed by heading alias
	 */
	public static function groupedByHeading(): array
	{
		$grouped = [];

		foreach (MenuHeadingEnum::sorted() as $heading)
		{
			$grouped[$heading->getAlias()] = self::byHeading($heading);
		}

		return $grouped;
	}
}
