<?php
/**
 * @version 2: emundusconfirmpost 2018-09-06 Hugo Moracchini
 * @package Fabrik
 * @copyright Copyright (C) 2018 emundus.fr. All rights reserved.
 * @license GNU/GPL, see LICENSE.php
 * Joomla! is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See COPYRIGHT.php for copyright notices and details.
 * @description Valide l'envoie d'un dossier de candidature et change le statut.
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-form.php';

/**
 * Create a Joomla user from the forms data
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.form.juseremundus
 * @since       3.0
 */

class PlgFabrik_FormEmundusencryptdatas extends plgFabrik_Form
{

	/**
	 * Decrypt datas before display them in applicant form
	 *
	 * @since version
	 */
	public function onLoad() {
		if(!class_exists('EmundusHelperFabrik')) {
			require_once JPATH_ROOT.'/components/com_emundus/helpers/fabrik.php';
		}

		$formModel = $this->getModel();
		$datas = $formModel->data;

		$elements = array();
		$groups = $formModel->getGroupsHiarachy();
		foreach ($groups as $group)
		{
			$elements = array_merge($group->getPublishedElements(),$elements);
		}

		foreach ($elements as $element)
		{
			$elt_fullname = $element->getFullName();
			if (strpos($elt_fullname, '[]') !== false)
			{
				$elt_fullname = str_replace('[]', '', $elt_fullname);
			}

			$data = $datas[$elt_fullname];
			if (empty($data) || is_array($data) && count(array_filter($data)) === 0)
			{
				$data = $datas[$elt_fullname . '_raw'];
			}

			if (is_string($data))
			{
				$decoded_value = json_decode($data, true);
			}
			elseif (is_array($data))
			{
				$decoded_value = $data;
			}
			else
			{
				$decoded_value = null;
			}


			if (!empty($decoded_value)) {
				$all_decrypted_data = [];
				foreach ($decoded_value as $decoded_sub_value) {
					$all_decrypted_data[] = EmundusHelperFabrik::decryptDatas($decoded_sub_value);
				}

				$decrypted_data = $all_decrypted_data;
			}
			else
			{
				$decrypted_data = EmundusHelperFabrik::decryptDatas($data, null, 'aes-128-cbc', $element->getElement()->plugin);
			}

			if(!empty($decrypted_data))
			{
				$formModel->data[$elt_fullname] = $decrypted_data;
				$formModel->data[$elt_fullname.'_raw'] = $decrypted_data;
			}
		}
	}

	/**
	 * Encrypt datas before store them in DB using aes encryption
	 *
	 * @return bool|void
	 *
	 * @since version
	 */
	public function onBeforeProcess() {
		if(!class_exists('EmundusHelperFabrik')) {
			require_once JPATH_ROOT.'/components/com_emundus/helpers/fabrik.php';
		}

		$formModel = $this->getModel();
		$datas = $formModel->formData;

		$elements = array();
		$groups = $formModel->getGroupsHiarachy();
		foreach ($groups as $group)
		{
			$elements = array_merge($group->getPublishedElements(),$elements);
		}

		foreach ($elements as $element) {
			$elt_fullname = $element->getFullName();
			if (strpos($elt_fullname, '[]') !== false)
			{
				$elt_fullname = str_replace('[]', '', $elt_fullname);
			}

			if (strpos($elt_fullname,'jos_emundus') !== false && strpos($elt_fullname,'fnum') === false && strpos($elt_fullname,'id') === false && strpos($elt_fullname,'user') === false && strpos($elt_fullname,'time_date') === false)
			{
				$data = $datas[$elt_fullname.'_raw'];
				if (empty($data))
				{
					$data = $datas[$elt_fullname];
				}

				$skip = false;
				switch($element->getElement()->plugin) {
					case 'emundus_phonenumber':
						$data = $data['country'].$data['country_code'].$data['num_tel'];
						break;
					// Iban can be encrypted by default on any form so we don't need to encrypt it again
					case 'iban':
						if (!empty($element->getElement()->params))
						{
							$params = json_decode($element->getElement()->params);
							if ($params->encrypt_datas == 1)
							{
								$skip = true;
							}
						}
						break;
					case 'birthday':
						$data = $datas[$elt_fullname];
						break;
				}

				if ($data !== '' && !$skip)
				{
					if (!is_array($data))
					{
						$encrypted_data = EmundusHelperFabrik::encryptDatas($data);
						$formModel->updateFormData($elt_fullname, $encrypted_data);
						$formModel->updateFormData($elt_fullname . '_raw', $encrypted_data);
					}
					else
					{
						// If the data is an array, we need to encrypt each value
						$encrypted_data = array();
						foreach ($data as $key => $value)
						{
							if ($value !== '')
							{
								$encrypted_data[$key] = EmundusHelperFabrik::encryptDatas($value);
							}
							else
							{
								$encrypted_data[$key] = '';
							}
						}

						$formModel->updateFormData($elt_fullname, $encrypted_data);
						$formModel->updateFormData($elt_fullname . '_raw', $encrypted_data);
					}
				}
			}
		}
	}
}
