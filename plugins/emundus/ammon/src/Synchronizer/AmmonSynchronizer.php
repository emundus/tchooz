<?php

namespace Joomla\Plugin\Emundus\Ammon\Synchronizer;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseDriver;
use Joomla\Plugin\Emundus\Ammon\Entities\CompanyEntity;
use Joomla\Plugin\Emundus\Ammon\Entities\RegistrationEntity;
use Joomla\Plugin\Emundus\Ammon\Entities\UserEntity;
use Joomla\CMS\Log\Log;

require_once(JPATH_SITE . '/components/com_emundus/models/sync.php');
require_once(JPATH_SITE . '/components/com_emundus/helpers/filters.php');

class AmmonSynchronizer {
	private \EmundusModelSync $sync_model;
	private $api;

	private DatabaseDriver $db;

	public function __construct($api)
	{
		$this->sync_model = new \EmundusModelSync();
		$this->api = $api;
		$this->db = Factory::getContainer()->get('DatabaseDriver');
	}

	public function getCompany(string $siret)
	{
		$ammon_company = null;

		if (!empty($siret)) {
			$query = 'SELECT TOP 50 CNOME,CSIRET,CCDP,CBURD,EnTREP.SENTITE ,ENTREP.CENTEXT, ADRESSE.SADRESSE,CENTEXT,ADRESSE.CDESTI, ADRESSE.CADR1,ADRESSE.CADR2,ADRESSE.CADR3,ADRESSE.CADR4,ADRESSE.CCP,ADRESSE.CVILLE, ADRESSE.CPAYS,ADRESSE.CTEL,ADRESSE.CEMAIL,ADRESSE.CADRNATURE,ADRESSE.SADRNATURE
				FROM ENTREP with(nolock)
				JOIN ADRESSE with(nolock) ON ADRESSE.SENTITE=ENTREP.SENTITE
				WHERE CSIRET = \''. $siret . '\'';

			$response = $this->sync_model->callApi($this->api, 'queries/execute', 'post', $query, false);

			if ($response['status'] == 200 && !empty($response['data']->results))
			{
				$ammon_company = $response['data']->results[0];
			}
		}

		return $ammon_company;
	}

	public function createCompany(CompanyEntity $company): bool
	{
		$created = false;

		$body_params = ["ignoreExistingRows" => false, "clearMissingData" => false, "rows" => [json_decode(json_encode($company), true)]];
		$response = $this->sync_model->callApi($this->api, 'companies/bulk/import', 'post', $body_params);

		if ($response['status'] && $response['data']['status'] == 'success') {
			$created = true;
		}

		return $created;
	}

	public function getUser($lastname, $firstname, $birthdate, bool $force_new_user_if_not_found = false)
	{
		$user = null;

		if (!empty($lastname) && !empty($firstname) && !empty($birthdate)) {
			$get_query = "DECLARE @XNAISS DateTime='$birthdate'
			SELECT PERSONNE.XNAISS, PERSONNE.SENTITE, PERSONNE.CENTEXT, PERSONNE.CNOM, PERSONNE.CPRENOM, PERSONNE.XNAISS, PERSONNE.SADR1 FROM PERSONNE WHERE PERSONNE.XNAISS = @XNAISS;";

			$response = $this->sync_model->callApi($this->api, 'queries/execute', 'post', $get_query, false);

			$current_user_name = $lastname . ' ' . $firstname;
			if (!empty($response) && $response['status'] == 200 && !empty($response['data']->results)) {
				$found_names = array_map(function($result) {
					$result->cnom = trim($result->cnom);
					$result->cprenom = trim($result->cprenom);

					return $result->cnom . ' ' . $result->cprenom;
				}, $response['data']->results);

				$match = \EmundusHelperFilters::searchClosestWord($current_user_name, $found_names);

				if ($match['lev'] == 0) {
					$user = $response['data']->results[$match['position']];
				} else if (!$force_new_user_if_not_found && $match['lev'] > 0 && $match['lev'] < 3) {
					Log::add('User ' . $current_user_name . ' has a similar name to ' . $found_names[$match['position']] . ' in ammon api. Need a manual check', Log::ERROR, 'plugin.emundus.ammon');
					throw new \Exception('[SHORT_LEV_DISTANCE] User ' . $current_user_name . ' has a similar name to ' . $found_names[$match['position']] . ' in ammon api. Need a manual check');
				} else {
					// No match found, need to create a new user
				}
			}
		}

		return $user;
	}

	public function getUserFromName($lastname, $firstname) {
		$user = null;

		if (!empty($lastname) && !empty($firstname)) {
			$get_query = "SELECT PERSONNE.XNAISS, PERSONNE.SENTITE, PERSONNE.CENTEXT, PERSONNE.CNOM, PERSONNE.CPRENOM, PERSONNE.XNAISS, PERSONNE.SADR1 FROM PERSONNE WHERE PERSONNE.CNOM = '$lastname' AND PERSONNE.CPRENOM = '$firstname';";

			$response = $this->sync_model->callApi($this->api, 'queries/execute', 'post', $get_query, false);

			if (!empty($response) && $response['status'] == 200 && !empty($response['data']->results)) {
				$user = $response['data']->results[0];
			}
		}

		return $user;
	}

	public function createUser(UserEntity $user): bool
	{
		$created = false;

		$body_params = ["ignoreExistingRows" => false, "clearMissingData" => false, "rows" => [json_decode(json_encode($user), true)]];
		$response = $this->sync_model->callApi($this->api, 'persons/bulk/import', 'post', $body_params);

		if ($response['status'] == 200 && $response['data']->status == 'success' && !empty($response['data']->results)) {
			$created = true;
		} else {
			Log::add('Failed to create user ' . $body_params['rows'], Log::ERROR, 'com_emundus.error');
		}

		return $created;
	}

	/**
	 * @param   string  $fnum
	 * @param   int     $ammon_session_id
	 *
	 * @return bool
	 */
	public function isRegistered(string $fnum, int $ammon_session_id): bool
	{
		$registered = false;

		if (!empty($fnum) && !empty($ammon_session_id)) {
			$response = $this->sync_model->callApi($this->api, 'courses/' . $ammon_session_id . '/enrolments', 'get', []);

			if (!empty($response) && $response['data']->status == 'success') {
				foreach ($response['data']->results as $enrolment) {
					if ($enrolment->externalReference == $fnum) {
						$registered = true;
						break;
					}
				}
			}
		}

		return $registered;
	}

	public function createRegistration(RegistrationEntity $registration): bool
	{
		$created = false;

		if (!$this->isRegistered($registration->ExternalReference, $registration->CourseId)) {
			$body_params = ["ignoreExistingRows" => false, "clearMissingData" => false, "rows" => [json_decode(json_encode($registration), true)]];
			$response    = $this->sync_model->callApi($this->api, 'enrolments/bulk/import', 'post', $body_params);

			if (!empty($response) && $response['status'] == 200 && $response['data']->status == 'success') {
				$created = true;
			} else {
				Log::add('Failed to create registration ' . $body_params['rows'], Log::ERROR, 'com_emundus.error');
			}
		} else {
			Log::add('Registration for fnum ' . $registration->ExternalReference . ' and session ' . $registration->CourseId . ' already exists', Log::INFO, 'com_emundus.error');
			$created = true;
		}


		return $created;
	}
}
