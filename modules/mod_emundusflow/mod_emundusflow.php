<?php
/**
 * @version        $Id: mod_emundusflow.php
 * @package        Joomla
 * @copyright      Copyright (C) 2016 emundus.fr. All rights reserved.
 * @license        GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 */

// no direct access
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;

defined('_JEXEC') or die('Restricted access');

$app  = Factory::getApplication();
$user = $app->getSession()->get('emundusUser');

if (isset($user->fnum) && !empty($user->fnum))
{
	require_once(JPATH_SITE . '/components/com_emundus/helpers/menu.php');
	require_once(JPATH_SITE . '/components/com_emundus/helpers/access.php');
	require_once(JPATH_SITE . '/components/com_emundus/models/checklist.php');
	require_once(JPATH_SITE . '/components/com_emundus/models/application.php');
	require_once(JPATH_SITE . '/components/com_emundus/models/files.php');
	require_once(JPATH_SITE . '/components/com_emundus/models/profile.php');
	require_once(JPATH_SITE . '/components/com_emundus/models/emails.php');
	require_once(JPATH_SITE . '/components/com_emundus/models/campaign.php');
	require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');

	// Load Joomla framework classes
	$document = $app->getDocument();
	$wa = $document->getWebAssetManager();
	$jinput   = $app->input;
	$db       = Factory::getContainer()->get('DatabaseDriver');

	// Parameters
	$show_programme     = $params->get('show_programme', 1);
	$show_back_button   = $params->get('show_back_button', 1);
	$show_document_step = $params->get('show_document_step', 1);
	$show_form_step     = $params->get('show_form_step', 1);
	$show_status        = $params->get('show_status', 1);
	$show_hikashop      = $params->get('show_hikashop', 1);
	$show_deadline      = $params->get('show_deadline', 0);
	$admission          = $params->get('admission', 0);
	$layout             = $params->get('layout', 'default');
	$offset             = Factory::getConfig()->get('offset');
	$home_link          = EmundusHelperMenu::getHomepageLink($params->get('home_link', 'index.php'));
	$add_to_cart_icon   = $params->get('add_to_cart_icon', 'large add to cart icon');
	$scholarship_icon   = $params->get('scholarship_icon', 'large student icon');
	$title_override     = Text::_($params->get('title_override', ''));
	$file_tags          = Text::_($params->get('tags', ''));

	// eMundus parameters
	$params_emundus       = ComponentHelper::getParams('com_emundus');
	$applicant_can_renew  = $params_emundus->get('applicant_can_renew', 0);
	$application_fee      = $params_emundus->get('application_fee', 0);
	$scholarship_document = $params_emundus->get('scholarship_document_id', null);
	$id_profiles          = $params_emundus->get('id_profiles', '0');
	$id_profiles          = explode(',', $id_profiles);


	if ($layout != '_:tchooz')
	{
		$wa->registerAndUseStyle("modules/mod_emundusflow/style/emundus.css");
	}

	$header_class = $params->get('header_class', '');
	if (!empty($header_class))
	{
		$wa->registerAndUseStyle("media/com_emundus/lib/Semantic-UI-CSS-master/components/site." . $header_class . ".css");
	}

	// Jinput
	$option = $jinput->get('option');
	$view   = $jinput->get('view');
	if (EmundusHelperAccess::asAccessAction(1, 'c'))
	{
		$applicant_can_renew = 1;
	}
	else
	{
		foreach ($user->emProfiles as $profile)
		{
			if (in_array($profile->id, $id_profiles))
			{
				$applicant_can_renew = 1;
				break;
			}
		}
	}

	// Models
	$m_checklist   = new EmundusModelChecklist();
	$m_application = new EmundusModelApplication();
	$m_files       = new EmundusModelFiles();
	$m_profile     = new EmundusModelProfile();
	$m_emails      = new EmundusModelEmails();
	$m_workflow    = new EmundusModelWorkflow();
	$m_campaign	   = new EmundusModelCampaign();

	$current_application = $m_application->getApplication($user->fnum);

	$campaign_name = $current_application->label;

	$fnumInfos = $m_files->getFnumInfos($user->fnum);

	if (!empty($title_override) && !empty(str_replace(array(' ', "\t", "\n", "\r", "&nbsp;"), '', htmlentities(strip_tags($title_override))))) {
		$m_email = new EmundusModelEmails();
		$emundusUser = $app->getSession()->get('emundusUser');

		$post = array(
			'APPLICANT_ID'   => $user->id,
			'DEADLINE'       => strftime("%A %d %B %Y %H:%M", strtotime($emundusUser->end_date)),
			'CAMPAIGN_LABEL' => $emundusUser->label,
			'CAMPAIGN_YEAR'  => $emundusUser->year,
			'CAMPAIGN_START' => $emundusUser->start_date,
			'CAMPAIGN_END'   => $emundusUser->end_date,
			'CAMPAIGN_CODE'  => $emundusUser->training,
			'FNUM'           => $emundusUser->fnum
		);

		$tags                   = $m_email->setTags($user->id, $post, $emundusUser->fnum, '', $title_override);
		$title_override_display = preg_replace($tags['patterns'], $tags['replacements'], $title_override);
		$title_override_display = $m_email->setTagsFabrik($title_override_display, array($emundusUser->fnum));

		if (!empty($title_override_display)) {
			$campaign_name = $title_override_display;
		}
	}

	if ($layout != '_:tchooz')
	{
		$application_fee = (!empty($application_fee) && !empty($m_profile->getHikashopMenu($user->profile)));
		$paid            = null;

		if ($application_fee)
		{
			$order     = $m_application->getHikashopOrder($fnumInfos);
			$paid      = !empty($order);
			$cart      = $m_application->getHikashopCartUrl($user->profile);
			$cartorder = null;

			if (!$paid || !empty($cart))
			{

				// If students with a scholarship have a different fee.
				// The form ID will be appended to the URL, taking him to a different checkout page.
				if (isset($scholarship_document))
				{

					// See if applicant has uploaded the required scolarship form.
					try
					{

						$query = 'SELECT count(id) FROM #__emundus_uploads
								WHERE attachment_id = ' . $scholarship_document . '
								AND fnum LIKE ' . $db->Quote($user->fnum);

						$db->setQuery($query);
						$uploaded_document = $db->loadResult();

					}
					catch (Exception $e)
					{
						Log::Add('Error in plugin/isApplicationCompleted at SQL query : ' . $query, Log::ERROR, 'plugins');
					}

					// If he hasn't, no discount for him.
					if ($uploaded_document == 0)
					{
						$scholarship_document = null;
					}
					else
					{
						$scholarship = true;
					}

				}
				if (!empty($cart))
				{
					$cartorder    = $m_application->getHikashopCart($fnumInfos);
					$checkout_url = 'cart' . $user->profile;
				}
				elseif (!$paid)
				{
					$orderCancelled = false;

					$checkout_url = $m_application->getHikashopCheckoutUrl($user->profile . $scholarship_document);
					if (strpos($checkout_url, '${') !== false)
					{
						$checkout_url = $m_emails->setTagsFabrik($checkout_url, [$user->fnum]);
					}
					$checkout_url = 'index.php?option=com_hikashop&ctrl=product&task=cleancart&return_url=' . urlencode(base64_encode($checkout_url)) . '&usekey=fnum&rowid=' . $user->fnum;

					$cancelled_orders = $m_application->getHikashopOrder($fnumInfos, true);

					if (!empty($cancelled_orders))
					{
						$orderCancelled = true;
					}
				}

			}
			else
			{
				$checkout_url = 'index.php';
			}
		}

		$attachments     = $m_application->getAttachmentsProgress($user->fnum);
		$attachment_list = !empty($m_profile->getAttachments($user->profile, true));

		$forms     = $m_application->getFormsProgress($user->fnum);
		$form_list = !empty($m_checklist->getFormsList());

		$sent = $m_checklist->getSent();

		$confirm_form_url = $m_checklist->getConfirmUrl() . '&usekey=fnum&rowid=' . $user->fnum;

		$offset = $app->get('offset', 'UTC');
		try
		{
			$dateTime = new DateTime(gmdate("Y-m-d H:i:s"), new DateTimeZone('UTC'));
			$dateTime = $dateTime->setTimezone(new DateTimeZone($offset));
			$now      = $dateTime->format('Y-m-d H:i:s');
		}
		catch (Exception $e)
		{
			echo $e->getMessage() . '<br />';
		}

		if (!empty($user->end_date))
		{
			$is_dead_line_passed = (strtotime(date($now)) > strtotime($user->end_date)) ? true : false;
		}
	}
	$deadline = !empty($admission) ? new JDate($user->fnums[$user->fnum]->admission_end_date) : new JDate($user->end_date);

	$current_phase = $m_workflow->getCurrentWorkflowStepFromFile($user->fnum);
	if (!empty($current_phase) && !empty($current_phase->id))
	{
		if ($current_phase->infinite)
		{
			$show_deadline = false;
		}

		if (!empty($current_phase->end_date))
		{
			$deadline = new JDate($current_phase->end_date);
		}
	}
	
	$lang = Factory::getLanguage();
	$current_lang_tag = $lang->getTag();
	$db = Factory::getContainer()->get('DatabaseDriver');
	$query = $db->getQuery(true);
	$query->select('lang_id')
		->from('#__languages')
		->where('lang_code = ' . $db->quote($current_lang_tag));

	try {
		$db->setQuery($query);
		$current_lang_id = $db->loadResult();
		$campaign_languages = $m_campaign->getCampaignLanguages($user->fnum);
	} catch (Exception $e) {
		$current_lang_id = 0;
		$campaign_languages = [];
	}

	require(ModuleHelper::getLayoutPath('mod_emundusflow', $layout));
}
