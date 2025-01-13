<?php
use Joomla\CMS\Factory;

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
class PlgFabrik_FormBtobProfile extends plgFabrik_Form
{

	public function onBeforeLoad()
	{
		$formModel = $this->getModel();

		if (empty($formModel->data['jos_emundus_users___statut_du_compte_btob_raw']))
		{
			$formModel->data['jos_emundus_users___statut_du_compte_btob']     = 0;
			$formModel->data['jos_emundus_users___statut_du_compte_btob_raw'] = 0;
		}
	}

	public function onElementCanUse($args)
	{
		$formModel    = $this->getModel();
		$elementModel = FArrayHelper::getValue($args, 0, false);
		if ($elementModel)
		{
			if (!empty($formModel->data['jos_emundus_users___statut_du_compte_btob_raw']) && $formModel->data['jos_emundus_users___statut_du_compte_btob_raw'] == 2)
			{
				$elements_to_check = [
					'jos_emundus_users___souhaitez_vous_un_compte_btob_',
					'jos_emundus_users___statut_du_compte_btob',
					'jos_emundus_users___siret_profil',
					'jos_emundus_users___raison_sociale_profil',
					'jos_emundus_users___adresse_postale_ligne_1_profil',
					'jos_emundus_users___adresse_postale_ligne_2_profil',
					'jos_emundus_users___code_postal_profil',
					'jos_emundus_users___ville',
					'jos_emundus_users___pays_profil',
					'jos_emundus_users___adresse_facturation',
					'jos_emundus_users___meme_adresse_entreprise',
					'jos_emundus_users___charge_gestion_admin',
				];
				if (in_array($elementModel->getFullName(), $elements_to_check))
				{
					return false;
				}
			}
		}
	}

	public function onBeforeCalculations()
	{
		$formModel = $this->getModel();

		$origData = $formModel->getOrigData()[0];
		$old_btob = $origData->jos_emundus_users___souhaitez_vous_un_compte_btob__raw;
		$btob     = $data['jos_emundus_users___souhaitez_vous_un_compte_btob__raw'];
		if (is_array($btob) && count($btob) > 0)
		{
			$btob = $btob[0];
		}
		$btob = (int) $btob;

		if ($old_btob !== $btob && $btob === 1)
		{
			// Send mail to users for BtoB account
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			$query->select('u.email')
				->from($db->quoteName('#__emundus_groups', 'eg'))
				->leftJoin($db->quoteName('#__users', 'u') . ' ON ' . $db->quoteName('eg.user_id') . ' = ' . $db->quoteName('u.id'))
				->where($db->quoteName('eg.group_id') . ' = 12');
			$db->setQuery($query);
			$emails = $db->loadColumn();

			if (!empty($emails))
			{
				$post = [
					'APPLICANT_NAME' => $data['jos_emundus_users___firstname_raw'] . ' ' . $data['jos_emundus_users___lastname_raw'],
				];

				foreach ($emails as $email)
				{
					require_once JPATH_SITE . '/components/com_emundus/models/emails.php';
					$m_emails = new EmundusModelEmails();
					$m_emails->sendEmailNoFnum($email, 128, $post);
				}
			}

			$query->clear()
				->update($db->quoteName('#__emundus_users'))
				->set($db->quoteName('statut_du_compte_btob') . ' = 1')
				->where($db->quoteName('id') . ' = ' . $db->quote($data['jos_emundus_users___id']));
			$db->setQuery($query);
			$db->execute();
		}
	}
}
