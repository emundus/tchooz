<?php
/**
 * Layout: emundus.application.applicant-header
 *
 * Données attendues dans $displayData :
 *  - applicant        object   Objet candidat (profile_picture, lastname, firstname, is_anonym). Obligatoire.
 *  - applicationFile  object   ApplicationFileEntity (getFnum(), getId(), isAnonymous(), getShortReference()). Obligatoire.
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Providers\DateProvider;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\Reference\InternalReferenceRepository;
use Tchooz\Services\Reference\InternalReferenceService;

defined('_JEXEC') or die;

require_once JPATH_BASE . '/components/com_emundus/helpers/access.php';

$displayData = $displayData ?? [];

$applicant       = $displayData['applicant'] ?? null;
$applicationFile = $displayData['applicationFile'] ?? null;

if (empty($applicant) || empty($applicationFile) || !$applicationFile instanceof ApplicationFileEntity)
{
	return;
}

$userId = Factory::getApplication()->getIdentity()->id;

// Garde d'anonymisation commune aux deux vues.
if (EmundusHelperAccess::isDataAnonymized($userId) || !empty($applicant->is_anonym) || $applicationFile->isAnonymous())
{
	return;
}

// Calcul de la visibilité et récupération de la référence interne, centralisés ici.
$internalReferenceService = new InternalReferenceService(new DateProvider(), new ApplicationFileRepository());
$showReference            = $internalReferenceService->getCustomReferenceFormatEntity()->isShowInFiles();

$reference = null;
if ($showReference)
{
	$reference = (new InternalReferenceRepository())->getActiveReference($applicationFile->getId());
}

$shortReference = (string) $applicationFile->getShortReference();

$profilePicture = !empty($applicant->profile_picture)
	? Uri::base() . '/' . $applicant->profile_picture
	: Uri::base() . '/media/com_emundus/images/profile/default-profile.jpg';

$copyValue = !empty($reference) ? ($reference->getReference() . '#' . $shortReference) : $shortReference;
?>
<div class="tw-flex tw-flex-row tw-items-center em-mt-16">
    <div class="tw-flex tw-flex-row em-small-flex-column em-small-align-items-start">
        <div class="em-profile-picture-big no-hover"
             style="background-image:url(<?php echo htmlspecialchars($profilePicture, ENT_QUOTES, 'UTF-8'); ?>)">
        </div>
    </div>
    <div class="tw-ml-4">
        <p class="em-font-weight-500">
			<?php echo htmlspecialchars($applicant->lastname . ' ' . $applicant->firstname, ENT_QUOTES, 'UTF-8'); ?>
        </p>
        <p><?php echo htmlspecialchars($applicationFile->getFnum(), ENT_QUOTES, 'UTF-8'); ?></p>
		<?php if ($showReference) : ?>
            <div class="tw-flex tw-items-end tw-gap-1">
				<?php if (!empty($reference)) : ?>
                    <label class="tw-mb-0"><?php echo htmlspecialchars($reference->getReference(), ENT_QUOTES, 'UTF-8'); ?></label>
				<?php endif; ?>
				<?php if ($shortReference !== '') : ?>
                    <span class="tw-text-sm tw-text-neutral-500">#<?php echo htmlspecialchars($shortReference, ENT_QUOTES, 'UTF-8'); ?></span>
				<?php endif; ?>
                <span class="material-symbols-outlined !tw-text-base tw-cursor-pointer" onclick="copyReference()">content_copy</span>
            </div>
		<?php endif; ?>
    </div>
</div>

<?php if ($showReference) : ?>
    <script>
        function copyReference() {
            const reference = <?php echo json_encode($copyValue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

            navigator.clipboard.writeText(reference);
            Swal.fire({
                title: <?php echo json_encode(Text::_('COM_EMUNDUS_REFERENCE_COPIED_TO_CLIPBOARD'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
                icon: 'success',
                showConfirmButton: false,
                customClass: {
                    title: 'em-swal-title',
                    actions: 'em-swal-single-action',
                },
                timer: 1500,
            });
        }
    </script>
<?php endif; ?>


