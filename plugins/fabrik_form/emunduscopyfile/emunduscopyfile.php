<?php
/**
 * @version     2: emunduscampaign 2019-04-11 Hugo Moracchini
 * @package     Fabrik
 * @copyright   Copyright (C) 2018 emundus.fr. All rights reserved.
 * @license     GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 * @description Création de dossier de candidature automatique.
 */

// No direct access
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\UserFactoryInterface;

defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Create a Joomla user from the forms data
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.emundusattachment
 * @since       3.0
 */
class PlgFabrik_FormEmundusCopyFile extends plgFabrik_Form
{

	public function onBeforeProcess()
	{
		$query = $this->_db->getQuery(true);
		$user  = $this->app->getIdentity();

		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'models' . DS . 'files.php');
		require_once(JPATH_SITE . DS . 'components' . DS . 'com_emundus' . DS . 'helpers' . DS . 'files.php');
		$m_files = new EmundusModelFiles;

		$formModel             = $this->getModel();
		$fnums_from            = explode(',', $formModel->getElementData('jos_emundus_campaign_candidature___fnum', true));
		$campaign_id           = $formModel->getElementData('jos_emundus_campaign_candidature___campaign_id', true);
		$campaign_id           = is_array($campaign_id) ? $campaign_id[0] : $campaign_id;
		$copied                = $formModel->getElementData('jos_emundus_campaign_candidature___copied', true);
		$copied                = is_array($copied) ? $copied[0] : $copied;
		$applicant_id          = $formModel->getElementData('jos_emundus_campaign_candidature___applicant_id', true);
		$status                = $formModel->getElementData('jos_emundus_campaign_candidature___status', true);
		$status                = is_array($status) ? $status[0] : $status;
		$can_delete            = $formModel->getElementData('jos_emundus_campaign_candidature___can_be_deleted', null);
		$copy_attachment       = $formModel->getElementData('jos_emundus_campaign_candidature___copy_attachment', 0);
		$copy_tag              = $formModel->getElementData('jos_emundus_campaign_candidature___copy_tag', 0);
		$move_hikashop_command = $formModel->getElementData('jos_emundus_campaign_candidature___move_hikashop_command', 0);
		$delete_from_file      = $formModel->getElementData('jos_emundus_campaign_candidature___delete_from_file', 0);
		$copyUsersAssoc        = $formModel->getElementData('jos_emundus_campaign_candidature___copy_users_assoc', 0);
		$copyGroupsAssoc       = $formModel->getElementData('jos_emundus_campaign_candidature___copy_groups_assoc', 0);

		foreach ($fnums_from as $fnum_from)
		{
			$fnum_infos   = $m_files->getFnumInfos($fnum_from);
			$applicant_id = $fnum_infos['applicant_id'];

			$fnum_to = EmundusHelperFiles::createFnum($campaign_id, $applicant_id);

			if ($copied == 1)
			{
				try
				{
					$query->clear()
						->select('*')
						->from($this->_db->quoteName('#__emundus_campaign_candidature'))
						->where($this->_db->quoteName('fnum') . ' = ' . $this->_db->quote($fnum_from));
					$this->_db->setQuery($query);
					$application_file = $this->_db->loadAssoc();

					if (!empty($application_file))
					{
						foreach ($application_file as $key => $value)
						{
							// Unset null value
							if (is_null($value))
							{
								unset($application_file[$key]);
							}
						}

						$application_file['fnum']        = $fnum_to;
						$application_file['copied']      = $copied;
						$application_file['user_id']     = $user->id;
						$application_file['campaign_id'] = $campaign_id;
						$application_file['status']      = $status;
						unset($application_file['id']);

						$query->clear()
							->insert($this->_db->quoteName('#__emundus_campaign_candidature'))
							->columns(array_keys($application_file))
							->values(implode(',', $this->_db->quote($application_file)));
						$this->_db->setQuery($query);
						$this->_db->execute();
					}

					// 3. Duplicate file from new fnum
					include_once(JPATH_SITE . '/components/com_emundus/models/application.php');
					require_once(JPATH_SITE . '/components/com_emundus/models/profile.php');
					require_once(JPATH_SITE . '/components/com_emundus/helpers/menu.php');

					$m_application = new EmundusModelApplication;
					$profiles      = new EmundusModelProfile();

					$fnumInfos = $profiles->getFnumDetails($fnum_from);

					$result = $m_application->copyApplication($fnum_from, $fnum_to, null, $copy_attachment, $fnumInfos['campaign_id'], $copy_tag, $move_hikashop_command, $delete_from_file, array(), $copyUsersAssoc, $copyGroupsAssoc);
				}
				catch (Exception $e)
				{
					$error = Uri::getInstance() . ' :: USER ID : ' . $user->id . ' -> ' . $query;
					Log::add($error, Log::ERROR, 'com_emundus');
				}
			}
			elseif ($copied == 2)
			{
				include_once(JPATH_SITE . '/components/com_emundus/models/application.php');
				$m_application = new EmundusModelApplication;

				$m_application->moveApplication($fnum_from, $fnum_to, $campaign_id, $status);
			}
			else
			{
				try
				{
					$insert = [
						'applicant_id'   => $applicant_id,
						'user_id'        => $user->id,
						'campaign_id'    => $campaign_id,
						'submitted'      => 0,
						'date_submitted' => null,
						'cancelled'      => 0,
						'fnum'           => $fnum_to,
						'status'         => $status,
						'published'      => 1,
						'copied'         => 0
					];
					$insert = (object) $insert;
					$this->_db->insertObject('#__emundus_campaign_candidature', $insert);
				}
				catch (Exception $e)
				{
					$error = Uri::getInstance() . ' :: USER ID : ' . $user->id . ' -> ' . $query;
					Log::add($error, Log::ERROR, 'com_emundus');
				}
			}
		}

		if($copied == 1) {
			$message = 'COM_EMUNDUS_COPIED_SUCCESSFULLY';
		} else {
			$message = 'COM_EMUNDUS_MOVED_SUCCESSFULLY';
		}

		if(count($fnums_from) > 1) {
			$message = 'COM_EMUNDUS_COPIED_SUCCESSFULLY_PLURAL';
			if($copied == 2) {
				$message = 'COM_EMUNDUS_MOVED_SUCCESSFULLY_PLURAL';
			}
		}

		echo '<script src="' . Uri::base() . 'media/com_emundus/js/lib/sweetalert/sweetalert.min.js"></script>';

		echo '<style>
.em-swal-title{
  margin: 8px 8px 32px 8px !important;
  font-family: "Maven Pro", sans-serif;
}
</style>';

		die("<script>
     document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
          position: 'top',
          icon: 'success',
          title: '" . Text::_($message) . "',
          showConfirmButton: false,
          timer: 2000,
          customClass: {
            title: 'em-swal-title',
          }
        }).then((result) => {
		  window.parent.postMessage('reloadData', '*');

  		  window.parent.document.querySelector('.em-modal-actions .swal2-close').click();
		});
      });
      </script>");
	}
}
