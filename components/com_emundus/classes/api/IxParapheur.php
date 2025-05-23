<?php
/**
 * @package     com_emundus
 * @subpackage  api
 * @author    eMundus.fr
 * @copyright (C) 2022 eMundus SOFTWARE. All rights reserved.
 * @license    GNU/GPLv2 http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Tchooz\api;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Utils;
use JComponentHelper;
use JFactory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Log\Log;

defined('_JEXEC') or die('Restricted access');
class IxParapheur extends Api
{

	private $default_values_dossier = [
		'options' => [
			'confidentiel' => false,
			'circuitModifiable' => false,
			'documentModifiable' => false,
			'annexesSignables' => false,
			'signature' => 0,
			'autoriserRefusAssistant' => false,
			'autoriserDroitRemordSig' => false,
			'ajouterAnnotationPubliqueEtapeSignature' => false,
			'nePasCalculerCircuitHierarchique' => false,
			'remplacerCircuitHierarchiqueParResponsable' => false,
			'autoriserModificationAnnexes' => false,
			'fusionnerEtapesSuccessives' => false,
			'informerPersonnesEvolutionTraitement' => false,
			'informerPersonnesDebutTraitement' => false,
			'informerPersonnesFinTraitement' => false,
		],
		'annotations' => null,
	];

	public function __construct($entities = array())
	{
		parent::__construct();

		$config = ComponentHelper::getParams('com_emundus');
		$baseUrl = $config->get('ixparapheur_api_base_url', '');
		$this->setBaseUrl($baseUrl);

		$this->setClient();
		$this->setAuth();

		$auth = $this->getAuth();
		$headers = array(
			'IXBUS_API' => $auth['app_token']
		);
		$this->setHeaders($headers);
	}

	public function setAuth(): void
	{
		$config = ComponentHelper::getParams('com_emundus');

		$this->auth['app_token'] = $config->get('ixparapheur_api_app_token', '');
	}

	public function getNatures($name = null): array
	{
		$natures = $this->get('nature');

		if($natures['status'] !== 200)
		{
			return array();
		}

		$natures = $natures['data']->payload;
		if(!empty($name)) {
			$natures = array_filter($natures, function($nature) use ($name) {
				return $nature->nom === $name;
			});
		}

		return array_values($natures);
	}

	public function getRedacteursNature($nature, $email = null): array
	{
		$redacteurs = $this->get('nature/'.$nature.'/redacteur');

		if($redacteurs['status'] !== 200)
		{
			return array();
		}

		$redacteurs = $redacteurs['data']->payload;
		if(!empty($email)) {
			$redacteurs = array_filter($redacteurs, function($redacteur) use ($email) {
				return $redacteur->email === $email;
			});
		}

		return $redacteurs;
	}

	public function getViseursNature($nature, $email = null): array
	{
		$viseurs = $this->get('nature/'.$nature.'/viseur');

		if($viseurs['status'] !== 200)
		{
			return array();
		}

		$viseurs = $viseurs['data']->payload;
		if(!empty($email)) {
			$viseurs = array_filter($viseurs, function($viseur) use ($email) {
				return $viseur->email === $email;
			});
		}

		return $viseurs;
	}

	public function getSignatairesNature($nature, $email = null): array
	{
		$signataires = $this->get('nature/'.$nature.'/signataire');

		if($signataires['status'] !== 200)
		{
			return array();
		}

		$signataires = $signataires['data']->payload;
		if(!empty($email)) {
			$signataires = array_filter($signataires, function($signataire) use ($email) {
				return $signataire->email === $email;
			});
		}

		return $signataires;
	}

	public function getServices($name = null, $user = null): array
	{
		$route = 'service';
		if(!empty($user)) {
			$route = 'service?idUtilisateur='.$user;
		}
		$services = $this->get($route);

		if($services['status'] !== 200)
		{
			return array();
		}

		$services = $services['data']->payload;
		if(!empty($name)) {
			$services = array_filter($services, function($service) use ($name) {
				return $service->nom === $name;
			});
		}

		return $services;
	}

	public function getFonctions($name = null): array
	{
		$fonctions = $this->get('fonction');

		if($fonctions['status'] !== 200)
		{
			return array();
		}

		$fonctions = $fonctions['data']->payload;
		if(!empty($name)) {
			$fonctions = array_filter($fonctions, function($fonction) use ($name) {
				return $fonction->nom === $name;
			});
		}

		return $fonctions;
	}

	public function getUtilisateurs($email = null): array
	{
		$users = $this->get('utilisateur');

		if($users['status'] !== 200)
		{
			return array();
		}

		$users = $users['data']->payload;
		if(!empty($email)) {
			$users = array_filter($users, function($user) use ($email) {
				return $user->email === $email;
			});
		}

		return array_values($users);
	}

	public function getModelesCircuits($nature, $service, $name = null): array
	{
		$circuits = $this->get('circuit/'.$nature.'/'.$service);

		if($circuits['status'] !== 200)
		{
			return array();
		}

		$circuits = $circuits['data']->payload;
		if(!empty($name)) {
			$circuits = array_filter($circuits, function($circuit) use ($name) {
				return $circuit->nom === $name;
			});
		}

		return $circuits;
	}

	public function createDossier($dossier, $transmettre = false): array
	{
		if(empty($dossier['nature']) || (empty($dossier['circuit']) && empty($dossier['etapes'])))
		{
			return array();
		}

		$datas = array_merge($this->default_values_dossier, $dossier);

		return $this->post('dossier?transmettre='.$transmettre, json_encode($datas));
	}

	public function updateDossier($idDossier,$datas): array
	{
		return $this->patch('dossier/'.$idDossier, json_encode($datas));
	}

	//TODO: Add filters parameter to get dossier by user, service, state
	public function getDossier($idDossier): array
	{
		return $this->get('dossier/'.$idDossier);
	}

	public function actionDossier($idDossier, $action = 'transmettre'): array
	{
		return $this->post('dossier/'.$idDossier.'/'.$action,json_encode(array()));
	}

	public function addDocument($idDossier, $datas, $filename, $filepath): array
	{
		$response = ['status' => 500, 'message' => '', 'data' => ''];

		$copied = copy($filepath,JPATH_SITE.'/tmp/'.$filename);

		if($copied) {
			$params = [
				'multipart' => [
					[
						'name'     => 'fichier',
						'contents' => \GuzzleHttp\Psr7\Utils::tryFopen(JPATH_SITE . '/tmp/' . $filename, 'r'),
						'filename' => $filepath,
						'headers'  => [
							'Content-Type' => '<Content-type header>'
						]
					],
					[
						'name'     => 'type',
						'contents' => $datas['type']
					],
					[
						'name'     => 'estASigner',
						'contents' => $datas['estASigner']
					],
					[
						'name'     => 'estPublique',
						'contents' => $datas['estPublique']
					],
				]
			];

			$response = $this->postFormData('document/' . $idDossier, $params);

			if($response['status'] === 200) {
				if($response['data']->message === 'Ce dossier possède déjà un document principal') {
					//TODO: Update document content by his id
				}

				unlink(JPATH_SITE . '/tmp/' . $filename);
			}
		}

		return $response;
	}

	public function deleteDocument($idDossier): array
	{
		return $this->delete('document/'.$idDossier);
	}

	public function getDocumentContent($idDocument, $path): array
	{
		$response = ['status' => 200, 'message' => '', 'data' => ''];

		$url = 'document/contenu/'.$idDocument;
		$params = array();

		try
		{
			$url_params = http_build_query($params);
			$url = !empty($url_params) ? $url . '?' . $url_params : $url;
			$request = $this->client->get($this->baseUrl.'/'.$url, ['headers' => $this->getHeaders(),'sink' => $path]);
			$response['status'] = $request->getStatusCode();
			$response['data'] = json_decode($request->getBody());
		}
		catch (\Exception $e)
		{
			if ($this->getRetry()) {
				$this->setRetry(false);
				$this->get($url, $params);
			}

			Log::add('[GET] ' . $e->getMessage(), Log::ERROR, 'com_emundus.api');
			$response['status'] = $e->getCode();
			$response['message'] = $e->getMessage();
		}

		return $response;
	}

	public function updateDocumentContent($idDocument,$file): array
	{
		return $this->patch('document/contenu/'.$idDocument,json_encode($file));
	}

	private function postFormData($url, $params = array())
	{
		$response = ['status' => 200, 'message' => '', 'data' => ''];

		try {
			$request = new Request('POST', $this->baseUrl.'/'.$url, $this->getHeaders());
			$res = $this->client->sendAsync($request, $params)->wait();

			$response['status']         = $res->getStatusCode();
			$response['data']         = json_decode($res->getBody());
		} catch (\Exception $e) {
			Log::add('[POST-multipart] : ' . $e->getMessage(), Log::ERROR, 'com_emundus.ixparapheur');
			$response['status'] = $e->getCode();
			$response['message'] = $e->getMessage();
		}

		return $response;
	}
}