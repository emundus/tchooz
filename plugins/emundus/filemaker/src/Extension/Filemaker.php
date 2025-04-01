<?php
namespace Joomla\Plugin\Emundus\Filemaker\Extension;

use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\UserFactoryAwareTrait;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Event\SubscriberInterface;
use classes\api\FileMaker as FileMakerAPI;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Joomla! Users Actions Logging Plugin.
 *
 * @since  3.9.0
 */
final class Filemaker extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;
	use UserFactoryAwareTrait;

	private DatabaseInterface $db;

	public function __construct(DispatcherInterface $dispatcher, array $config)
	{
		parent::__construct($dispatcher, $config);

		Log::addLogger(['text_file' => 'com_emundus.filemaker.php'], Log::ALL, 'com_emundus');
	}

	public static function getSubscribedEvents(): array
	{
		return [
			'onWebhookCallbackProcess' => 'processFilemaker',
		];
	}

	public function processFilemaker(GenericEvent $event): array
	{
		$args = $event->getArguments();

		$debugMode    = (int) $this->params->get('debug_mode', 0);
		$importTag    = (int) $this->params->get('import_tag', 6);

		if(!class_exists('FileMaker'))
		{
			require_once(JPATH_SITE . '/components/com_emundus/classes/api/FileMaker.php');
		}
		$file_maker_api = new FileMakerAPI(false);

		$webhook_datas = $args['webhook_datas'];
		$webhook_type = $args['type'];
		$webhook_status = false;
		$webhook_message = null;
		$webhook_code = 200;

		$this->db = $this->getDatabase();

		$fnums = [];

		if($debugMode === 1)
		{
			Log::add('JSON datas : ' . json_encode($webhook_datas), Log::DEBUG, 'com_emundus');
		}

		switch ($webhook_type) {
			case 'filemaker_create':
			case 'filemaker_update':
				$datas = $webhook_datas['DATA'][0]['data'];

				try {
					$admin_step = $datas['fieldData']['Admin_Step'];
					$mapped_columns = $file_maker_api->retrieveMappingColumnsData($admin_step);

					$fnums = $file_maker_api->createFiles([$datas], $mapped_columns);
					if (!empty($fnums)) {
						$webhook_status = true;
						if ($webhook_type == 'filemaker_create')
							$webhook_message = 'Le dossier a été créé avec succès';
						else
							$webhook_message = 'Le dossier a été mis à jour avec succès';
					}
				} catch (\Exception $e) {
					$webhook_code = $e->getCode();
					$webhook_message = $e->getMessage();
					Log::add('onWebhookCallbackProcess: error while trying to update filemaker '.$webhook_code.' -> '.$webhook_message, Log::ERROR, 'com_emundus');
				}
				break;
			case 'filemaker_import':
				$datas = $webhook_datas['DATA'][0];

				$emundus_file = $file_maker_api->getEmundusFile($datas['uuid'], $datas['Emundus_NumDossier']);

				try {
					if (!empty($emundus_file)) {
						if ($datas['Emundus_StatutFileMaker'] == 1 || $datas['Emundus_StatutFileMaker'] == 'reçu') {
							$update_data = [
								'id' => $emundus_file->id,
								'filemaker_update_send' => -1
							];

							$update_data = (object)$update_data;
							$this->db->updateObject('#__emundus_campaign_candidature', $update_data, 'id');

							if(!class_exists('EmundusModelFiles'))
							{
								require_once(JPATH_SITE . '/components/com_emundus/models/files.php');
							}
							$m_files = new \EmundusModelFiles();
							if ($m_files->tagFile([$emundus_file->fnum], [$importTag], 62)) {
								$webhook_status = true;
								$webhook_message = 'Le dossier a été importé avec succès';
							} else {
								$webhook_code = 500;
								$webhook_message = 'Erreur lors de l\'importation du dossier';
							}
						} else if ($datas['Emundus_StatutFileMaker'] == 0) {
							$update_data = [
								'id' => $emundus_file->id,
								'filemaker_update_send' => 1
							];

							$update_data = (object)$update_data;
							$this->db->updateObject('#__emundus_campaign_candidature', $update_data, 'id');

							$query = $this->db->getQuery(true);
							$query->clear()
								->delete('#__emundus_tag_assoc')
								->where('fnum = ' . $this->db->quote($emundus_file->fnum))
								->where('id_tag = ' . $importTag);
							$this->db->setQuery($query);
							if ($this->db->execute()) {
								$webhook_status = true;
								$webhook_message = 'Le dossier a été réouvert avec succès';
							} else {
								$webhook_code = 500;
								$webhook_message = 'Erreur lors de la réouverture du dossier';
							}
						}
					} else {
						$webhook_code = 404;
						$webhook_message = 'Le dossier n\'existe pas';
					}
				} catch (\Exception $e) {
					$webhook_code = $e->getCode();
					$webhook_message = $e->getMessage();
					Log::add('onWebhookCallbackProcess: error while trying to update filemaker '.$webhook_code.' -> '.$webhook_message, Log::ERROR, 'com_emundus');
				}

				break;
			default:
				$webhook_code = 400;
				$webhook_message = 'Type de webhook inconnu';
				break;
		}

		return [
			'type' => $webhook_type,
			'status' => $webhook_status,
			'code' => $webhook_code,
			'message' => $webhook_message,
			'error' => null,
			'Emundus_NumDossier' => implode(',', $fnums),
			'datas_sent' => $webhook_datas,
		];
	}
}