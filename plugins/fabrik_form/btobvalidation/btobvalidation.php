<?php

use Joomla\CMS\Factory;
use Joomla\Database\ParameterType;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Language\Text;

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
class PlgFabrik_FormBtobValidation extends plgFabrik_Form
{
	public function onBeforeCalculations()
	{
		$params   = $this->getParams();
		$group_id = $params->get('btob_joomla_group', 16);
		$email_id = $params->get('btob_email_id', 125);
		$keep_files_at_status = $params->get('btob_keep_files_at_status', '');
		$keep_files_at_status = !empty($keep_files_at_status) ? explode(',', $keep_files_at_status) : [];

		$formModel = $this->getModel();
		$data      = $formModel->getData();
		$origData  = $formModel->getOrigData()[0];

		$old_btob_status = $origData->jos_emundus_users___statut_du_compte_btob_raw;
		$btob_status     = $data['jos_emundus_users___statut_du_compte_btob_raw'];
		if (is_array($btob_status) && count($btob_status) > 0)
		{
			$btob_status = $btob_status[0];
		}
		$btob_status     = (int) $btob_status;
		$old_btob_status = (int) $old_btob_status;

		if ($old_btob_status !== $btob_status && $btob_status === 2)
		{
			$user_id = $data['jos_emundus_users___user_id_raw'];

			if (!empty($user_id))
			{
				$db    = Factory::getContainer()->get('DatabaseDriver');
				$query = $db->createQuery();

				$query->clear()
					->select('email')
					->from($db->quoteName('#__users'))
					->where($db->quoteName('id') . ' = ' . $db->quote($user_id));
				$db->setQuery($query);
				$applicant_email = $db->loadResult();

				if (!empty($applicant_email))
				{
					$post = [
						'NAME' => $data['jos_emundus_users___lastname_raw'] . ' ' . $data['jos_emundus_users___firstname_raw'],
					];
					require_once JPATH_SITE . '/components/com_emundus/models/emails.php';
					$m_emails = new EmundusModelEmails();
					$m_emails->sendEmailNoFnum($applicant_email, $email_id, $post);
				}

				$query->clear()
					->select('user_id')
					->from('#__user_usergroup_map')
					->where('user_id = :id')
					->where('group_id = :group_id')
					->bind(':id', $user_id, ParameterType::INTEGER)
					->bind(':group_id', $group_id, ParameterType::INTEGER);
				$db->setQuery($query);
				$associated = $db->loadResult();

				if (empty($associated))
				{
					$query->clear()
						->insert('#__user_usergroup_map')
						->columns('user_id, group_id')
						->values($user_id . ', ' . $group_id);
					$db->setQuery($query);
					$db->execute();
				}

				$group_to_remove = 11;
				$query->clear()
					->delete('#__user_usergroup_map')
					->where('user_id = :id')
					->where('group_id = :group_id')
					->bind(':id', $user_id, ParameterType::INTEGER)
					->bind(':group_id', $group_to_remove, ParameterType::INTEGER);
				$db->setQuery($query);
				$db->execute();

				if (!empty($keep_files_at_status)) {
					$moved = $this->moveFilesToBtobList($keep_files_at_status, $user_id);

					if (!$moved) {
						// Log the error if files were not moved successfully
						Log::add('Error moving files to BtoB list for user ID: ' . $user_id, Log::WARNING, 'com_emundus.error');
						Factory::getApplication()->enqueueMessage(Text::_('COM_EMUNDUS_ERROR_MOVING_BTOB_FILES'), 'error');
					}
				}
			}
		}
	}

	private function moveFilesToBtobList(array $status, int $user_id): bool
	{
		$moved = false;

		if (!empty($status) && !empty($user_id)) {
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$query->select($db->quoteName('fnum'))
				->from($db->quoteName('#__emundus_campaign_candidature', 'ecc'))
				->where($db->quoteName('ecc.applicant_id') . ' = ' . $user_id)
				->andWhere($db->quoteName('ecc.status') . ' IN (' . implode(',', $status) . ')');

			try {
				$db->setQuery($query);
				$fnums = $db->loadColumn();

				if (!empty($fnums)) {
					$insertions = [];

					if (!class_exists('EmundusHelperFabrik')) {
						require_once(JPATH_ROOT . '/components/com_emundus/helpers/fabrik.php');
					}

					foreach ($fnums as $fnum)
					{
						$query->clear()
							->select('id')
							->from($db->quoteName('#__emundus_btob_1237_repeat'))
							->where($db->quoteName('fnum') . ' = ' . $db->quote($fnum));

						$db->setQuery($query);
						$btob_file_id = $db->loadResult();

						if (empty($btob_file_id)) {
							$query->clear()
								->select('esc.id, esc.training')
								->from($db->quoteName('#__emundus_setup_campaigns', 'esc'))
								->leftJoin($db->quoteName('#__emundus_campaign_candidature', 'ecc'), 'ecc.campaign_id = esc.id')
								->where($db->quoteName('ecc.fnum') . ' = ' . $db->quote($fnum));

							$db->setQuery($query);
							$campaign = $db->loadObject();

							$price = EmundusHelperFabrik::getValueByAlias('registration_candidate_price', $fnum);

							$query->clear()
								->insert('#__emundus_btob')
								->columns('campaign_id, formation, prix_participant')
								->values($campaign->id . ', ' . $db->quote($campaign->training) . ', ' . $db->quote($price['value']));

							$db->setQuery($query);
							$insertions[] = $db->execute();
							$row_id = $db->insertid();

							if (!empty($row_id)) {
								$lastname = EmundusHelperFabrik::getValueByAlias('registration_common_name', $fnum);
								$firstname = EmundusHelperFabrik::getValueByAlias('registration_first_name', $fnum);
								$email = EmundusHelperFabrik::getValueByAlias('correspondence_different_email', $fnum);
								if (empty($email['raw'])) {
									$email = EmundusHelperFabrik::getValueByAlias('registration_email', $fnum);
								}

								$financement_entreprise = EmundusHelperFabrik::getValueByAlias('registration_company_price', $fnum);
								$financement_organisme = EmundusHelperFabrik::getValueByAlias('registration_organism_price', $fnum);
								$fullname = $lastname['value'] . ' ' . $firstname['value'];
								$civility = EmundusHelperFabrik::getValueByAlias('registration_civility', $fnum);
								$btob_amenagements = EmundusHelperFabrik::getValueByAlias('accomodation_yesno', $fnum);

								if (empty($btob_amenagements['raw'])) {
									$btob_amenagements['raw'] = 0;
								}

								$amenagements_details = EmundusHelperFabrik::getValueByAlias('accomodation_specify', $fnum);

								$query->clear()
									->insert('#__emundus_btob_1237_repeat')
									->columns('parent_id, fnum, lastname, firstname, email, financement_entreprise, financement_organisme, fullname, civility, amenagements_details, btob_amenagements')
									->values($row_id . ', ' . $db->quote($fnum) . ', ' . $db->quote($lastname['value']) . ', ' . $db->quote($firstname['value']) . ', ' . $db->quote(strip_tags($email['raw'])) . ', ' . $db->quote($financement_entreprise['value']) . ', ' . $db->quote($financement_organisme['value']) . ', ' . $db->quote($fullname) . ', ' . $db->quote($civility['raw']) . ', ' . $db->quote($amenagements_details['value']) . ', ' . $db->quote($btob_amenagements['raw']));

								$db->setQuery($query);
								$insertions[] = $db->execute();
							}
						}

						// check if file is not already in btob list
						$query->clear()
							->select('id')
							->from($db->quoteName('#__emundus_btob_inscription_1244_repeat'))
							->where($db->quoteName('fnum') . ' = ' . $db->quote($fnum));

						$db->setQuery($query);
						$btob_inscription_file_id = $db->loadResult();

						if (empty($btob_inscription_file_id)) {
							$query->clear()
								->insert('#__emundus_btob_inscription')
								->columns('no_files')
								->values(1);

							$db->setQuery($query);
							$insertions[] = $db->execute();
							$row_id = $db->insertid();

							if (!empty($row_id)) {
								$query->clear()
									->insert('#__emundus_btob_inscription_1244_repeat')
									->columns('parent_id, fnum')
									->values($row_id . ', ' . $db->quote($fnum));

								$db->setQuery($query);
								$insertions[] = $db->execute();
							}
						}
					}

					$moved = !in_array(false, $insertions, true);
				} else {
					$moved = true;
				}
			} catch (Exception $e) {
				Log::add('Error trying to move files after btob validation : ' . $e->getMessage(), Log::ERROR, 'com_emundus.error');
			}
		}

		return $moved;
	}
}
