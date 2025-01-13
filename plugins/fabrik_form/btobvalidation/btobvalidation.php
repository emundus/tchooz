<?php

use Joomla\CMS\Factory;
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
class PlgFabrik_FormBtobValidation extends plgFabrik_Form
{
	public function onBeforeCalculations()
	{
		$params   = $this->getParams();
		$group_id = $params->get('btob_joomla_group', 16);
		$email_id = $params->get('btob_email_id', 125);

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

		if ($btob_status === 2)
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
			}
		}
	}
}
