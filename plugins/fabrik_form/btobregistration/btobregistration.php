<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Database\ParameterType;

defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Manage BtoB profile form
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.btobprofile
 * @since       3.0
 */
class PlgFabrik_FormBtobRegistration extends plgFabrik_Form
{
	public function onBeforeLoad()
	{
		require_once JPATH_SITE . '/components/com_emundus/helpers/access.php';

		$formModel = $this->getModel();
		$app  = Factory::getApplication();
		$fnum = $app->getInput()->getString('fnum', '');
		$btob = $app->getInput()->getInt('btob', '');

		$attachment_to_download = $this->getParams()->get('btob_attachment_to_download',43);
		$attachment_id = $this->getParams()->get('btob_attachment_to_upload',42);

		$user = Factory::getApplication()->getIdentity();

		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();

		$fnums = [];

		if (!empty($btob))
		{
			// Get all fnums related to this inscription
			$query->clear()
				->select('fnum')
				->from('#__emundus_btob_1237_repeat')
				->where('parent_id = ' . $btob);
			$db->setQuery($query);
			$fnums = $db->loadColumn();
		}
		elseif (!empty($fnum))
		{
			$fnums = [$fnum];
		}

		if (!empty($fnums))
		{
			foreach ($fnums as $fnum)
			{
				// Check if fnum is mine
				if (EmundusHelperAccess::isFnumMine($user->id, $fnum))
				{
					$formModel->data['jos_emundus_btob_inscription___no_files'] += 1;

					$query->clear()
						->select('id')
						->from('#__emundus_uploads')
						->where('fnum LIKE ' . $db->quote($fnum))
						->where('attachment_id = ' . $attachment_id);
					$db->setQuery($query);
					$already_upload = $db->loadResult();

					if (!empty($already_upload))
					{
						continue;
					}
					else
					{
						$query->clear()
							->select('lastname,firstname')
							->from('#__emundus_btob_1237_repeat')
							->where('fnum LIKE ' . $db->quote($fnum));
						$db->setQuery($query);
						$profile = $db->loadObject();

						$bulletin_inscription = '';
						$query->clear()
							->select('filename')
							->from('#__emundus_uploads')
							->where('fnum LIKE ' . $db->quote($fnum))
							->where('attachment_id = ' . $attachment_to_download);
						$db->setQuery($query);
						$bulletin_inscription = $db->loadResult();

						// Fill registration form
						$formModel->data['jos_emundus_btob_inscription_1244_repeat___id'][]                   = '';
						$formModel->data['jos_emundus_btob_inscription_1244_repeat___parent_id'][]            = '';
						$formModel->data['jos_emundus_btob_inscription_1244_repeat___fnum'][]                 = $fnum;
						$formModel->data['jos_emundus_btob_inscription_1244_repeat___lastname'][]             = $profile->lastname;
						$formModel->data['jos_emundus_btob_inscription_1244_repeat___firstname'][]            = $profile->firstname;
						$formModel->data['jos_emundus_btob_inscription_1244_repeat___bulletin_inscription'][] = '<a href="' . EMUNDUS_PATH_REL . $user->id . '/' . $bulletin_inscription . '" download>' . $bulletin_inscription . '</a>';
					}
				}
			}

			if (empty($formModel->data['jos_emundus_btob_inscription_1244_repeat___fnum']))
			{
				$app->enqueueMessage('Ce/Ces dossiers sont déjà inscrits.', 'error');
				$app->redirect('index.php');
			}
		}
		else
		{
			$app->enqueueMessage('Aucun dossier trouvé.', 'error');
			$app->redirect('index.php');
		}
	}

	public function onAfterProcess()
	{
		$formModel = $this->getModel();
		$data = $this->getProcessData();

		$attachment_id = $this->getParams()->get('btob_attachment_to_upload',42);
		$registration_status = $this->getParams()->get('btob_registration_status',5);

		require_once JPATH_SITE . '/components/com_emundus/helpers/access.php';
		require_once JPATH_SITE . '/components/com_emundus/models/files.php';
		$m_files = new EmundusModelFiles();

		$fnums = $data['jos_emundus_btob_inscription_1244_repeat___fnum_raw'];
		$user  = Factory::getApplication()->getIdentity();

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();

		$fnums_to_update = [];
		foreach ($fnums as $key => $fnum)
		{
			if (EmundusHelperAccess::isFnumMine($user->id, $fnum))
			{
				$bulletin_complet = $data['jos_emundus_btob_inscription_1244_repeat___bulletin_inscription_complet_raw'][$key];

				if (!empty($bulletin_complet))
				{
					$bulletin_complet = explode('/', $bulletin_complet);
					$query->clear()
						->select('campaign_id')
						->from('#__emundus_campaign_candidature')
						->where('fnum LIKE ' . $db->quote($fnum));
					$db->setQuery($query);
					$campaign_id = $db->loadResult();

					$upload = [
						'user_id' => $user->id,
						'fnum'    => $fnum,
						'campaign_id' => $campaign_id,
						'attachment_id' => $attachment_id,
						'filename' => end($bulletin_complet),
						'can_be_deleted' => 0,
						'can_be_viewed' => 1
					];
					$upload = (object) $upload;

					if($db->insertObject('#__emundus_uploads', $upload)) {
						$fnums_to_update[] = $fnum;
					}
				}
			}
		}

		if(!empty($fnums_to_update)) {
			$query->clear()
				->update('#__emundus_campaign_candidature')
				->set('date_submitted = ' . $db->quote(date('Y-m-d H:i:s')))
				->where('fnum IN (' . implode(',', $db->quote($fnums_to_update)) . ')');
			$db->setQuery($query);
			$db->execute();

			$m_files->updateState($fnums_to_update, $registration_status, $user->id);
		}

		Factory::getApplication()->redirect('/candidatures-btob');
	}
}