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

		$user  = Factory::getApplication()->getIdentity();
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();

		$cid = Factory::getApplication()->getInput()->getInt('cid', 0);

        $query->clear()
            ->select('esp.opened_to_btob')
            ->from($db->quoteName('#__emundus_setup_campaigns', 'esc'))
            ->leftJoin($db->quoteName('#__emundus_setup_programmes', 'esp') . ' ON ' . $db->quoteName('esp.code') . ' = ' . $db->quoteName('esc.training'))
            ->where('esc.id = :cid')
            ->bind(':cid', $cid, ParameterType::INTEGER);
        $db->setQuery($query);
        $opened_to_btob = $db->loadResult();

        if ($opened_to_btob == 1) {
            $query->clear()
                ->select('esp.btob_max_applicants')
                ->from($db->quoteName('#__emundus_setup_campaigns', 'esc'))
                ->leftJoin($db->quoteName('#__emundus_setup_programmes', 'esp') . ' ON ' . $db->quoteName('esp.code') . ' = ' . $db->quoteName('esc.training'))
                ->where('esc.id = :cid')
                ->bind(':cid', $cid, ParameterType::INTEGER);
            $db->setQuery($query);
            $max_files_per_session = $db->loadResult();
            if (empty($max_files_per_session))
            {
                $max_files_per_session = $this->getParams()->get('btob_form_max_file_per_session', 4);
            }

            $nb_dossiers          = 0;
            $nb_dossiers_possible = $max_files_per_session;
            if (!empty($user->id))
            {
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
        } else {
            $app = Factory::getApplication();
            $app->enqueueMessage(Text::_('PLG_FABRIK_FORM_BTOBFORM_CAMPAIGN_NOT_OPEN_TO_BTOB'), 'error');
            $app->redirect('index.php');
        }
	}

	public function onBeforeProcess()
	{
		require_once JPATH_SITE . '/components/com_emundus/helpers/menu.php';
		require_once JPATH_SITE . '/components/com_emundus/helpers/fabrik.php';
		$app  = Factory::getApplication();
		$user = $app->getIdentity();

		$formModel = $this->getModel();
		$data      = $formModel->formData;

		$applicants  = $data['jos_emundus_btob_1237_repeat___id'];
		$campaign_id = $data['jos_emundus_btob___campaign_id_raw'];
		if (is_array($campaign_id))
		{
			$campaign_id = $campaign_id[0];
		}

		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();

		$query->clear()
			->select('esp.btob_max_applicants')
			->from($db->quoteName('#__emundus_setup_campaigns', 'esc'))
			->leftJoin($db->quoteName('#__emundus_setup_programmes', 'esp') . ' ON ' . $db->quoteName('esp.code') . ' = ' . $db->quoteName('esc.training'))
			->where('esc.id = :cid')
			->bind(':cid', $campaign_id, ParameterType::INTEGER);
		$db->setQuery($query);
		$max_files_per_session = $db->loadResult();
		if (empty($max_files_per_session))
		{
			$max_files_per_session = $this->getParams()->get('btob_form_max_file_per_session', 4);
		}

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

		if ($nb_dossiers_possible < count($applicants))
		{
			$app->enqueueMessage(Text::sprintf('PLG_FABRIK_FORM_BTOBFORM_MAX_FILES_EXCEEDED', $nb_dossiers, count($applicants)), 'error');
			$formModel->errors['jos_emundus_btob_1237_repeat___id'][0][] = Text::sprintf('PLG_FABRIK_FORM_BTOBFORM_MAX_FILES_EXCEEDED', $nb_dossiers, count($applicants));

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
			if (is_array($financement_entreprise))
			{
				$financement_entreprise = $financement_entreprise['rowInputValueFront'];
			}
			else
			{
				$financement_entreprise = str_replace('€', '', $financement_entreprise);
				$financement_entreprise = str_replace(' ', '', $financement_entreprise);
			}
			$financement_entreprise = (float) $financement_entreprise;

			$financement_organisme = $data['jos_emundus_btob_1237_repeat___financement_organisme_raw'][$key];
			if (is_array($financement_organisme))
			{
				$financement_organisme = $financement_organisme['rowInputValueFront'];
			}
			else
			{
				$financement_organisme = str_replace('€', '', $financement_organisme);
				$financement_organisme = str_replace(' ', '', $financement_organisme);
			}

			$financement_total = $financement_entreprise + $financement_organisme;
			if ($financement_total != $formation_price)
			{
				$app->enqueueMessage(Text::_('PLG_FABRIK_FORM_BTOBFORM_BALANCE_NOT_OK'), 'error');
				$formModel->errors['jos_emundus_btob_1237_repeat___financement_entreprise'][$key][] = Text::_('PLG_FABRIK_FORM_BTOBFORM_BALANCE_NOT_OK');
				$formModel->errors['jos_emundus_btob_1237_repeat___financement_organisme'][$key][]  = Text::_('PLG_FABRIK_FORM_BTOBFORM_BALANCE_NOT_OK');

				return false;
			}
		}
	}

	public function onAfterProcess()
	{
		$formModel = $this->getModel();
		$data      = $this->getProcessData();

		$cid = $data['jos_emundus_btob___campaign_id_raw'];
		if (is_array($cid))
		{
			$cid = $cid[0];
		}

		$app  = Factory::getApplication();
		$user = $app->getIdentity();

		require_once JPATH_SITE . '/components/com_emundus/models/files.php';
		require_once JPATH_SITE . '/components/com_emundus/models/evaluation.php';
		require_once JPATH_SITE . '/components/com_emundus/models/workflow.php';
		$m_workflow = new EmundusModelWorkflow();
		$m_files      = new EmundusModelFiles();
		$m_evaluation = new EmundusModelEvaluation();

		$id         = $data['jos_emundus_btob___id'];
		$applicants = $data['jos_emundus_btob_1237_repeat___id'];
		$campaign_id = $data['jos_emundus_btob___campaign_id_raw'];
		if (is_array($campaign_id))
		{
			$campaign_id = $campaign_id[0];
		}

		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();

		$profiles = [];
		$query->clear()
			->select('profile_id')
			->from('#__emundus_setup_campaigns')
			->where('id = :cid')
			->bind(':cid', $campaign_id, ParameterType::INTEGER);
		$db->setQuery($query);
		$profiles[] = $db->loadResult();

		$steps = $m_workflow->getCampaignSteps($campaign_id);
		foreach ($steps as $step)
		{
			if (!empty($step->profile_id))
			{
				$profiles[] = $step->profile_id;
			}
		}

		$forms = [];
		$forms_ids = [];
		foreach ($profiles as $profile_id)
		{
			$forms_by_profile = EmundusHelperMenu::buildMenuQuery($profile_id);
			foreach ($forms_by_profile as $form) {
				if(in_array($form->form_id, $forms_ids)) {
					continue;
				}
				$forms_ids[] = $form->form_id;
				$forms[] = $form;
			}
		}

		foreach ($applicants as $key => $applicant)
		{
			$fnum = $m_files->createFile($cid, $user->id);

			$query->clear()
				->update('#__emundus_campaign_candidature')
				->set('date_submitted = ' . $db->quote(date('Y-m-d H:i:s')))
				->where('fnum LIKE ' . $db->quote($fnum));
			$db->setQuery($query);
			$db->execute();

			if (!empty($fnum))
			{
				// Update the fnum in the repeat table
				$query->clear()
					->update('#__emundus_btob_1237_repeat')
					->set('fullname = ' . $db->quote($data['jos_emundus_btob_1237_repeat___lastname'][$key] . ' ' . $data['jos_emundus_btob_1237_repeat___firstname'][$key]))
					->set('fnum = ' . $db->quote($fnum))
					->where('id = ' . $applicant);
				$db->setQuery($query);
				$db->execute();

				$tag = $this->getParams()->get('btob_form_tag', 7);
				$m_files->tagFile([$fnum], [$tag], $user->id);

				// Get informations from profile for filling the form
				$columns = [
					'siret_profil as siret',
					'raison_sociale_profil as raison_sociale',
					'adresse_postale_ligne_1_profil',
					'adresse_postale_ligne_2_profil',
					'code_postal_profil',
					'ville',
					'pays_profil',
					'adresse_facturation',
					'pays_facturation',
					'field_adresse_facturation',
					'complement_adresse_facturation',
					'code_postal_facturation',
					'ville_facturation',
					'civilite_responsable',
					'nom_responsable',
					'prenom_manager',
					'fonction_responsable',
					'adresse_e_mail_responsable',
					'telephone_responsable',
					'meme_adresse_entreprise',
					'pays_responsable',
					'adresse_postale_responsable',
					'complement_adresse_responsable',
					'code_postal_manager',
					'ville_responsable',
					'charge_gestion_admin',
					'civilite_admin',
					'nom_admin',
					'prenom_admin',
					'fonction_admin',
					'adresse_e_mail',
					'telephone_admin'
				];
				$query->clear()
					->select($columns)
					->from('#__emundus_users')
					->where('user_id = ' . $user->id);
				$db->setQuery($query);
				$profile = $db->loadObject();

				$financement_entreprise = $data['jos_emundus_btob_1237_repeat___financement_entreprise_raw'][$key];
				if (is_array($financement_entreprise))
				{
					$financement_entreprise = $financement_entreprise['rowInputValueFront'];
				}
				else
				{
					$financement_entreprise = str_replace('€', '', $financement_entreprise);
					$financement_entreprise = str_replace(' ', '', $financement_entreprise);
				}
				$financement_entreprise = (float) $financement_entreprise;

				$financement_organisme = $data['jos_emundus_btob_1237_repeat___financement_organisme_raw'][$key];
				if (is_array($financement_organisme))
				{
					$financement_organisme = $financement_organisme['rowInputValueFront'];
				}
				else
				{
					$financement_organisme = str_replace('€', '', $financement_organisme);
					$financement_organisme = str_replace(' ', '', $financement_organisme);
				}

				$alias_to_fills = [
					'registration_civility' => $data['jos_emundus_btob_1237_repeat___civility_raw'][$key],
					'registration_common_name' => $data['jos_emundus_btob_1237_repeat___lastname'][$key],
					'registration_birth_name' => $data['jos_emundus_btob_1237_repeat___lastname'][$key],
					'registration_first_name' => $data['jos_emundus_btob_1237_repeat___firstname'][$key],
					'registration_function' => $data['jos_emundus_btob_1237_repeat___registration_function_raw'][$key],
					'accomodation_yesno' => !empty($data['jos_emundus_btob_1237_repeat___btob_amenagements'][$key]) ? 1 : 0,
					'accomodation_specify' => $data['jos_emundus_btob_1237_repeat___amenagements_details'][$key],
					'registration_email' => $data['jos_emundus_btob_1237_repeat___email'][$key],
					'correspondence_different_contact' => 0,
					'correspondence_different_email' => '',
					'correspondence_phone' => $profile->telephone_responsable,
					'correspondence_different_phone' => '',
					'registration_company_price' => $financement_entreprise . '€',
					'registration_organism_price' => $financement_organisme . '€',
					'registration_candidate_price' => '0€',
					'company_country' => $profile->pays_profil,
					'company_siret' => $profile->siret,
					'registration_company_name' => $profile->raison_sociale,
					'company_address' => $profile->adresse_postale_ligne_1_profil,
					'company_additional_address' => $profile->adresse_postale_ligne_2_profil,
					'company_postal_code' => $profile->code_postal_profil,
					'company_city' => $profile->ville,
					'meme_adresse_facturation' => $profile->adresse_facturation,
					'pays_facturation' => $profile->pays_facturation,
					'adresse_postale_facturation' => $profile->field_adresse_facturation,
					'complement_facturation' => $profile->complement_adresse_facturation,
					'code_postal_facturation' => $profile->code_postal_facturation,
					'ville_facturation' => $profile->ville_facturation,
					'manager_civility' => $profile->civilite_responsable,
					'manager_last_name' => $profile->nom_responsable,
					'manager_first_name' => $profile->prenom_manager,
					'manager_function' => $profile->fonction_responsable,
					'manager_email' => $profile->adresse_e_mail_responsable,
					'manager_phone' => $profile->telephone_responsable,
					'same_company_address' => !empty($profile->meme_adresse_entreprise) ? 1 : 0,
					'manager_country' => $profile->pays_responsable,
					'manager_address' => $profile->adresse_postale_responsable,
					'manager_additional_address' => $profile->complement_adresse_responsable,
					'manager_postal_code' => $profile->code_postal_manager,
					'manager_city' => $profile->ville_responsable,
					'different_admin' => !empty($profile->charge_gestion_admin) ? 1 : 0
				];

				if ($alias_to_fills['different_admin'] != 0) {
					$alias_to_fills['admin_civility'] = $profile->civilite_admin;
					$alias_to_fills['admin_last_name'] = $profile->nom_admin;
					$alias_to_fills['admin_first_name'] = $profile->prenom_admin;
					$alias_to_fills['admin_function'] = $profile->fonction_admin;
					$alias_to_fills['admin_email'] = $profile->adresse_e_mail;
					$alias_to_fills['admin_phone_number'] = $profile->telephone_admin;
				}

				$datas_to_fills = [];
				foreach ($alias_to_fills as $alias => $value)
				{
					foreach ($forms as $form) {
						$element = EmundusHelperFabrik::getElementsByAlias($alias, $form->form_id);
						if (!empty($element)) {
							if(empty($datas_to_fills[$element[0]->db_table_name])) {
								$datas_to_fills[$element[0]->db_table_name] = [];
							}
							$datas_to_fills[$element[0]->db_table_name][$element[0]->name] = $value;
						}
					}
				}

				foreach ($datas_to_fills as $table => $fields) {
					$insert_fields = [];
					$insert_fields['fnum'] = $fnum;
					$insert_fields['user'] = $user->id;
					$insert_fields['time_date'] = date('Y-m-d H:i:s');
					foreach ($fields as $key => $value) {
						$insert_fields[$key] = $value;
					}

					$insert_fields = (object) $insert_fields;
					$db->insertObject($table, $insert_fields);
				}

				$attachment_id = $this->getParams()->get('btob_attachment_to_generate', 43);
				$m_evaluation->generateLetters($fnum, [$attachment_id], 1, 2, 0);
			}
		}

		$app->redirect('/bulletin-dinscription?btob=' . $id . '&rowid=0');
	}
}
