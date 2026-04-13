<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Tchooz\EmundusResponse;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\Reference\InternalReferenceRepository;

/**
 * @package
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

class EmundusViewReferences extends HtmlView
{
	protected array $references = [];

	function display($tpl = null)
	{
		$app    = Factory::getApplication();
		$layout = $app->input->getString('layout', '');

		$user = $app->getIdentity();

		if(!EmundusHelperAccess::asPartnerAccessLevel($user->id))
		{
			throw new AccessException(
				Text::_('ACCESS_DENIED'),
				EmundusResponse::HTTP_FORBIDDEN
			);
		}
		
		if ($layout == 'history') {
			$fnum = $app->input->getString('fnum', '');
			if(empty($fnum))
			{
				echo Text::_('FNUM_MISSING');
				return;
			}

			$applicationFileRepository = new ApplicationFileRepository();
			$ccid = $applicationFileRepository->getIdByFnum($fnum);
			$internalReferenceRepository = new InternalReferenceRepository();
			$this->references = $internalReferenceRepository->getItemsByField('ccid', $ccid, true);
			usort($this->references, function ($a, $b) {
				return $b->getCreatedAt() >= $a->getCreatedAt();
			});
		}

		parent::display($tpl);
	}
}