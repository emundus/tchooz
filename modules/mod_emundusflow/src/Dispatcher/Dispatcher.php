<?php

namespace Emundus\Module\Emundusflow\Site\Dispatcher;

use EmundusModelCampaign;
use Joomla\CMS\Date\Date;
use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Tchooz\Providers\DateProvider;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\Reference\InternalReferenceRepository;
use Tchooz\Services\Reference\InternalReferenceService;

\defined('_JEXEC') or die;

class Dispatcher extends AbstractModuleDispatcher
{
	protected function getLayoutData(): array
	{
		$data = parent::getLayoutData();

		require_once(JPATH_SITE . '/components/com_emundus/helpers/access.php');
		require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
		require_once(JPATH_ROOT . '/components/com_emundus/models/campaign.php');

		$session = $this->app->getSession()->get('emundusUser');

		if (!empty($session->fnum))
		{
			$applicationFileRepository = new ApplicationFileRepository();
			$applicationFile           = $applicationFileRepository->getByFnum($session->fnum);
			if (empty($applicationFile))
			{
				$this->app->redirect(Route::_('index.php?option=com_users&view=login', false));
			}

			$data['applicationFile']   = $applicationFile;
			$data['campaign_name'] = $applicationFile->getCampaign()->getLabel();

			$document = $this->app->getDocument();
			$wa       = $document->getWebAssetManager();

			$header_class = $data['params']->get('header_class', '');
			if (!empty($header_class))
			{
				$wa->registerAndUseStyle("media/com_emundus/lib/Semantic-UI-CSS-master/components/site." . $header_class . ".css");
			}

			// todo: simplify
			$data['show_back_button']   = $data['params']->get('show_back_button', 1);
			$data['show_document_step'] = $data['params']->get('show_document_step', 1);
			$data['show_form_step']     = $data['params']->get('show_form_step', 1);
			$data['show_status']        = $data['params']->get('show_status', 1);
			$data['show_hikashop']      = $data['params']->get('show_hikashop', 1);
			$data['show_deadline']      = $data['params']->get('show_deadline', 0);
			$data['layout']             = $data['params']->get('layout', 'default');
			$data['offset']             = $this->app->getConfig()->get('offset');
			$data['home_link']          = \EmundusHelperMenu::getHomepageLink($data['params']->get('home_link', 'index.php'));
			$data['add_to_cart_icon']   = $data['params']->get('add_to_cart_icon', 'large add to cart icon');
			$data['scholarship_icon']   = $data['params']->get('scholarship_icon', 'large student icon');
			$data['title_override']     = Text::_($data['params']->get('title_override', ''));
			$data['file_tags']          = Text::_($data['params']->get('tags', ''));
			$data['deadline']           = new Date($session->end_date);

			$internalReferenceService    = new InternalReferenceService(
				new DateProvider(),
				new ApplicationFileRepository()
			);
			$customReferenceFormatEntity = $internalReferenceService->getCustomReferenceFormatEntity();

			$data['isShowToApplicant'] = $customReferenceFormatEntity->isShowToApplicant();
			$internalReferenceRepository = new InternalReferenceRepository();
			$internalReference = $internalReferenceRepository->getActiveReference($applicationFile->getId());
			$data['reference'] = !empty($internalReference) ? $internalReference->getReference() : '';

			if (!empty($data['title_override']) && !empty(str_replace(array(' ', "\t", "\n", "\r", "&nbsp;"), '', htmlentities(strip_tags($data['title_override']))))) {
				require_once(JPATH_SITE . '/components/com_emundus/models/emails.php');
				$m_email = new \EmundusModelEmails();

				$post = array(
					'APPLICANT_ID'   => $session->id,
					'DEADLINE'       => strftime("%A %d %B %Y %H:%M", strtotime($session->end_date)),
					'CAMPAIGN_LABEL' => $session->label,
					'CAMPAIGN_YEAR'  => $session->year,
					'CAMPAIGN_START' => $session->start_date,
					'CAMPAIGN_END'   => $session->end_date,
					'CAMPAIGN_CODE'  => $session->training,
					'FNUM'           => $session->fnum
				);

				$tags                   = $m_email->setTags($session->id, $post, $session->fnum, '', $data['title_override']);
				$title_override_display = preg_replace($tags['patterns'], $tags['replacements'], $data['title_override']);
				$title_override_display = $m_email->setTagsFabrik($title_override_display, array($session->fnum));

				if (!empty($title_override_display)) {
					$data['campaign_name'] = $title_override_display;
				}
			}

			$workflowModel = new \EmundusModelWorkflow();
			$current_phase = $workflowModel->getCurrentWorkflowStepFromFile($applicationFile->getFnum());
			if (!empty($current_phase) && !empty($current_phase->id))
			{
				if ($current_phase->infinite)
				{
					$data['show_deadline'] = false;
				}

				if (!empty($current_phase->end_date))
				{
					$data['deadline'] = new Date($current_phase->end_date);
				}
			}
			$data['current_phase'] = $current_phase;

			$lang = Factory::getLanguage();
			$current_lang_tag = $lang->getTag();
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);
			$query->select('lang_id')
				->from('#__languages')
				->where('lang_code = ' . $db->quote($current_lang_tag));

			try {
				$db->setQuery($query);
				$data['current_lang_id'] = $db->loadResult();

				$campaignModel = new EmundusModelCampaign();
				$data['campaign_languages'] = $campaignModel->getCampaignLanguages($applicationFile->getFnum());
			} catch (\Exception $e) {
				$data['current_lang_id'] = 0;
				$data['campaign_languages'] = [];
			}
		}

		return $data;
	}
}