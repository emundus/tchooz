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
class PlgFabrik_FormBtobForm extends plgFabrik_Form
{
	public function onBeforeLoad()
	{
		$formModel = $this->getModel();

		$user = Factory::getApplication()->getIdentity();
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();

		$cid = Factory::getApplication()->getInput()->getInt('cid', 0);

		$max_files_per_session = $this->getParams()->get('btob_form_max_file_per_session', 4);

		$nb_dossiers = 0;
		$nb_dossiers_possible = $max_files_per_session;
		if(!empty($user->id)) {
			$query->clear()
				->select('count(id)')
				->from('#__emundus_campaign_candidature')
				->where('applicant_id = :id')
				->where('campaign_id = :cid')
				->bind(':cid', $cid, ParameterType::INTEGER)
				->bind(':id', $user->id, ParameterType::INTEGER);
			$db->setQuery($query);
			$nb_dossiers = $db->loadResult();

			$nb_dossiers_possible = $max_files_per_session - $nb_dossiers;
		}

		$formModel->data['jos_emundus_btob___nb_dossiers'] = Text::sprintf('PLG_FABRIK_FORM_BTOBFORM_MAX_FILES_AVAILABLE', $nb_dossiers, $nb_dossiers_possible);
	}

	public function onBeforeProcess()
	{
		$app = Factory::getApplication();
		$user = $app->getIdentity();

		$formModel = $this->getModel();
		$data = $formModel->formData;

		$max_files_per_session = $this->getParams()->get('btob_form_max_file_per_session', 4);

		$applicants = $data['jos_emundus_btob_1237_repeat___id'];
		$campaign_id = $data['jos_emundus_btob___campaign_id_raw'];
		if(is_array($campaign_id)) {
			$campaign_id = $campaign_id[0];
		}

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();

		$query->clear()
			->select('count(id)')
			->from('#__emundus_campaign_candidature')
			->where('applicant_id = :id')
			->where('campaign_id = :cid')
			->bind(':cid', $campaign_id, ParameterType::INTEGER)
			->bind(':id', $user->id, ParameterType::INTEGER);
		$db->setQuery($query);
		$nb_dossiers = $db->loadResult();

		$nb_dossiers_possible = $max_files_per_session - $nb_dossiers;

		if($nb_dossiers_possible < count($applicants)) {
			$app->enqueueMessage(Text::sprintf('PLG_FABRIK_FORM_BTOBFORM_MAX_FILES_EXCEEDED',$nb_dossiers,count($applicants)), 'error');
			$formModel->errors['jos_emundus_btob_1237_repeat___id'][0][] = Text::sprintf('PLG_FABRIK_FORM_BTOBFORM_MAX_FILES_EXCEEDED',$nb_dossiers,count($applicants));
			return false;
		}

		foreach ($applicants as $key => $applicant)
		{
			// Check balance with price
			$formation_price = $data['jos_emundus_btob___prix_participant_raw'];
			// Remove € sign
			$formation_price = str_replace('€', '', $formation_price);
			$formation_price = str_replace(' ', '', $formation_price);
			$formation_price = (float) $formation_price;

			$financement_entreprise = $data['jos_emundus_btob_1237_repeat___financement_entreprise_raw'][$key];
			if(is_array($financement_entreprise)) {
				$financement_entreprise = $financement_entreprise['rowInputValueFront'];
			} else {
				$financement_entreprise = str_replace('€', '', $financement_entreprise);
				$financement_entreprise = str_replace(' ', '', $financement_entreprise);
			}
			$financement_entreprise = (float) $financement_entreprise;

			$financement_organisme = $data['jos_emundus_btob_1237_repeat___financement_organisme_raw'][$key];
			if(is_array($financement_organisme)) {
				$financement_organisme = $financement_organisme['rowInputValueFront'];
			} else {
				$financement_organisme = str_replace('€', '', $financement_organisme);
				$financement_organisme = str_replace(' ', '', $financement_organisme);
			}

			$financement_total = $financement_entreprise + $financement_organisme;
			if($financement_total != $formation_price) {
				$app->enqueueMessage(Text::_('PLG_FABRIK_FORM_BTOBFORM_BALANCE_NOT_OK'), 'error');
				$formModel->errors['jos_emundus_btob_1237_repeat___financement_entreprise'][$key][] = Text::_('PLG_FABRIK_FORM_BTOBFORM_BALANCE_NOT_OK');
				$formModel->errors['jos_emundus_btob_1237_repeat___financement_organisme'][$key][] = Text::_('PLG_FABRIK_FORM_BTOBFORM_BALANCE_NOT_OK');
				return false;
			}
		}
	}

	public function onAfterProcess()
	{
		$formModel = $this->getModel();
		$data = $this->getProcessData();

		$cid = $data['jos_emundus_btob___campaign_id_raw'];
		if(is_array($cid)) {
			$cid = $cid[0];
		}

		$app = Factory::getApplication();
		$user = $app->getIdentity();

		require_once JPATH_SITE.'/components/com_emundus/models/files.php';
		require_once JPATH_SITE.'/components/com_emundus/models/evaluation.php';
		$m_files = new EmundusModelFiles();
		$m_evaluation = new EmundusModelEvaluation();

		$id = $data['jos_emundus_btob___id'];
		$applicants = $data['jos_emundus_btob_1237_repeat___id'];

		foreach ($applicants as $key => $applicant)
		{
			$fnum = $m_files->createFile($cid, $user->id);

			if(!empty($fnum)) {
				$db = Factory::getContainer()->get('DatabaseDriver');
				$query = $db->createQuery();

				// Update the fnum in the repeat table
				$query->clear()
					->update('#__emundus_btob_1237_repeat')
					->set('fullname = '.$db->quote($data['jos_emundus_btob_1237_repeat___lastname'][$key].' '.$data['jos_emundus_btob_1237_repeat___firstname'][$key]))
					->set('fnum = '.$db->quote($fnum))
					->where('id = '.$applicant);
				$db->setQuery($query);
				$db->execute();


				$tag = $this->getParams()->get('btob_form_tag', 7);
				$m_files->tagFile([$fnum],[$tag], $user->id);

				$personal_details = [
					'fnum' => $fnum,
					'user' => $user->id,
					'time_date' => date('Y-m-d H:i:s'),
					'e_846_8155' => $data['jos_emundus_btob_1237_repeat___lastname'][$key],
					'e_904_8572' => $data['jos_emundus_btob_1237_repeat___lastname'][$key],
					'e_846_8156' => $data['jos_emundus_btob_1237_repeat___firstname'][$key],
				];
				$personal_details = (object) $personal_details;
				$db->insertObject('#__emundus_1005_04', $personal_details);

				$correspondance = [
					'fnum' => $fnum,
					'user' => $user->id,
					'time_date' => date('Y-m-d H:i:s'),
					'e_852_8184' => $user->email,
					'e_994_89001' => 1,
					'e_994_8901' => $data['jos_emundus_btob_1237_repeat___email'][$key]
				];
				$correspondance = (object) $correspondance;
				$db->insertObject('#__emundus_1005_05', $correspondance);

				// Get informations from profile for filling the form
				$query->clear()
					->select('siret_profil as siret,raison_sociale_profil as raison_sociale, adresse_postale_ligne_1_profil, adresse_postale_ligne_2_profil,code_postal_profil,ville,pays_profil')
					->from('#__emundus_users')
					->where('user_id = '.$user->id);
				$db->setQuery($query);
				$profile = $db->loadObject();

				$financement_entreprise = $data['jos_emundus_btob_1237_repeat___financement_entreprise_raw'][$key];
				if(is_array($financement_entreprise)) {
					$financement_entreprise = $financement_entreprise['rowInputValueFront'];
				} else {
					$financement_entreprise = str_replace('€', '', $financement_entreprise);
					$financement_entreprise = str_replace(' ', '', $financement_entreprise);
				}
				$financement_entreprise = (float) $financement_entreprise;

				$financement_organisme = $data['jos_emundus_btob_1237_repeat___financement_organisme_raw'][$key];
				if(is_array($financement_organisme)) {
					$financement_organisme = $financement_organisme['rowInputValueFront'];
				} else {
					$financement_organisme = str_replace('€', '', $financement_organisme);
					$financement_organisme = str_replace(' ', '', $financement_organisme);
				}

				$financement = [
					'fnum' => $fnum,
					'user' => $user->id,
					'time_date' => date('Y-m-d H:i:s'),
					'e_856_8194' => $financement_entreprise . '€',
					'e_856_8195' => $financement_organisme . '€',
					'e_856_8193' => '0€',
					'e_858_8207' => $profile->pays_profil,
					'e_857_8196' => $profile->siret,
					'e_857_8199' => $profile->raison_sociale,
					'e_857_8200' => $profile->adresse_postale_ligne_1,
					'e_857_8201' => $profile->adresse_postale_ligne_2_profil,
					'e_858_8205' => $profile->code_postal_profil,
					'e_858_8206' => $profile->ville,
				];
				$financement = (object) $financement;
				$db->insertObject('#__emundus_1005_06', $financement);

				$attachment_id = $this->getParams()->get('btob_attachment_to_generate',43);
				$m_evaluation->generateLetters($fnum,[$attachment_id],1,2,0);
			}
		}

		$app->redirect('/bulletin-dinscription?btob='.$id.'&rowid=0');
	}
}