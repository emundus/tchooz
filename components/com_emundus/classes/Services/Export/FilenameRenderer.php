<?php
/**
 * @package     Tchooz\Services\Export
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Export;

use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Entities\User\EmundusUserEntity;

/**
 * Renders the application_form_name template into a filesystem-safe filename.
 *
 * Centralizes the tag substitution + accent/special-char sanitization that every per-fnum export
 * (PDF, ZIP, …) needs. Callers decide how to handle empty results — this class only renders.
 */
class FilenameRenderer
{
	private \EmundusModelEmails $emails;

	public function __construct(?\EmundusModelEmails $emails = null)
	{
		if ($emails === null && !class_exists('EmundusModelEmails'))
		{
			require_once JPATH_SITE . '/components/com_emundus/models/emails.php';
		}

		$this->emails = $emails ?? new \EmundusModelEmails();
	}

	/**
	 * Render $template for $applicationFile.
	 *
	 * - When the applicant is flagged anonymous (or $forceAnonymize is true), the rendered name is
	 *   `anonym_file_<fnum>` — tags are never expanded.
	 * - Otherwise FNUM / CAMPAIGN_YEAR / PROGRAMME_CODE tokens plus Fabrik tags are substituted,
	 *   the result is stripped of accents, non-alphanum chars and whitespace, then lowercased.
	 *
	 * Returns an empty string if the template renders to nothing after sanitization — the caller
	 * decides the fallback to apply.
	 */
	public function render(
		string                $template,
		ApplicationFileEntity $applicationFile,
		?EmundusUserEntity    $user = null,
		bool                  $forceAnonymize = false
	): string
	{
		$shouldAnonymize = $forceAnonymize || ($user !== null && $user->isAnonym());

		$raw = $shouldAnonymize
			? 'anonym_file_' . $applicationFile->getFnum()
			: $this->substituteTags($template, $applicationFile);

		return $this->sanitize($raw);
	}

	private function substituteTags(string $template, ApplicationFileEntity $applicationFile): string
	{
		$post = [
			'FNUM'           => $applicationFile->getFnum(),
			'CAMPAIGN_YEAR'  => $applicationFile->getCampaign()->getYear(),
			'PROGRAMME_CODE' => $applicationFile->getCampaign()->getProgram()->getCode(),
		];

		$tags    = $this->emails->setTags($applicationFile->getUser()->id, $post, $applicationFile->getFnum(), '', $template);
		$rendered = preg_replace($tags['patterns'], $tags['replacements'], $template);

		return $this->emails->setTagsFabrik($rendered, [$applicationFile->getFnum()]);
	}

	private function sanitize(string $name): string
	{
		$name = $this->emails->stripAccents($name);
		$name = preg_replace('/[^A-Za-z0-9 _.-]/', '', $name);
		$name = preg_replace('/\s/', '', $name);

		return strtolower($name);
	}
}
