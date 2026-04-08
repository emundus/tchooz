<?php
/**
 * @package     Tchooz\Services\ApplicationFile
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\ApplicationFile;

use EmundusModelLogs;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\UserFactoryInterface;
use Tchooz\Entities\ApplicationFile\Actions\ApplicationFileAction;
use Tchooz\Entities\ApplicationFile\Actions\GoToHistory;
use Tchooz\Entities\ApplicationFile\Actions\RenameApplication;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Enums\Actions\ActionEnum;
use Tchooz\Enums\CrudEnum;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\Upload\UploadRepository;

class ApplicationFileService
{
	public function __construct(private readonly UploadRepository $uploadRepository = new UploadRepository())
	{}
	
	public function updateOwner(ApplicationFileEntity $applicationFile, int $newOwnerId, int $userId = 0): bool
	{
		$applicationFileRepository = new ApplicationFileRepository();
		$newOwner = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($newOwnerId);
		if(empty($newOwner) || empty($newOwner->id))
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_UPDATE_OWNER_ERROR_OWNER_DOES_NOT_EXIST'));
		}

		if($newOwner->id == $applicationFile->getUser()->id)
		{
			throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_UPDATE_OWNER_ERROR_OWNER_SAME_AS_CURRENT'));
		}

		$oldOwner = $applicationFile->getUser();

		// First check if new owner already has a directory, if not create it
		$newOwnerDir = JPATH_ROOT . "/images/emundus/files/$newOwnerId";
		if (!is_dir($newOwnerDir)) {
			if (!mkdir($newOwnerDir, 0755, true) && !is_dir($newOwnerDir)) {
				throw new \RuntimeException(Text::_('COM_EMUNDUS_UPDATE_OWNER_ERROR_FAILED_TO_UPDATE_OWNER'));
			}
		}

		// Then copy files from old owner directory to new owner directory
		$oldOwnerDir = JPATH_ROOT . "/images/emundus/files/$oldOwner->id";
		if (is_dir($oldOwnerDir)) {
			$files = scandir($oldOwnerDir);
			foreach ($files as $file) {
				// Do not copy . and .. directories
				if ($file !== '.' && $file !== '..' && is_file("$oldOwnerDir/$file")) {

					if(!copy("$oldOwnerDir/$file", "$newOwnerDir/$file"))
					{
						throw new \RuntimeException(Text::_('COM_EMUNDUS_UPDATE_OWNER_ERROR_FAILED_TO_COPY_FILE'));
					}
				}
			}
		}

		$uploads = $this->uploadRepository->getByFnum($applicationFile->getFnum());
		foreach ($uploads as $upload) {
			$upload->setUserId($newOwnerId);
			if (!$this->uploadRepository->flush($upload)) {
				throw new \RuntimeException(Text::_('COM_EMUNDUS_UPDATE_OWNER_ERROR_FAILED_TO_UPDATE_OWNER'));
			}
		}

		// Update the owner of the application file
		$applicationFile->setUser($newOwner);
		if(!$applicationFileRepository->flush($applicationFile, $userId))
		{
			throw new \RuntimeException(Text::_('COM_EMUNDUS_UPDATE_OWNER_ERROR_FAILED_TO_UPDATE_OWNER'));
		}

		// Update evaluations and final grades table column student_id (only for old platforms)
		$this->updateOldEvaluationsDecisions($applicationFile->getFnum(), $newOwner->id);

		if (!class_exists('EmundusModelLogs'))
		{
			require_once (JPATH_ROOT . '/components/com_emundus/models/logs.php');
		}
		$actionRepository = new ActionRepository();
		$updateOwnerAction = $actionRepository->getByName(ActionEnum::UPDATE_OWNER->value);
		$logsModel = new EmundusModelLogs();
		$params = [
			'updated' => [
				[
					'old' => $oldOwner->name,
					'old_id' => $oldOwner->id,
					'new' => $applicationFile->getUser()->name,
					'new_id' => $applicationFile->getUser()->id,
				]
			]
		];
		$logsModel::log($userId, $applicationFile->getUser()->id, $applicationFile->getFnum(), $updateOwnerAction->getId(), CrudEnum::UPDATE->value, $updateOwnerAction->getLabel(), json_encode($params));

		return true;
	}

	private function updateOldEvaluationsDecisions(string $fnum, int $newOwnerId): void
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$exceptionMessage = Text::_('COM_EMUNDUS_UPDATE_OWNER_ERROR_FAILED_TO_UPDATE_OWNER');

		try
		{
			$query->update($db->quoteName('#__emundus_evaluations'))
				->set($db->quoteName('student_id') . ' = ' . $newOwnerId)
				->where($db->quoteName('fnum') . ' = ' . $db->quote($fnum));
			$db->setQuery($query);
			if(!$db->execute())
			{
				throw new \RuntimeException($exceptionMessage);
			}

			$query->clear()
				->update($db->quoteName('#__emundus_final_grade'))
				->set($db->quoteName('student_id') . ' = ' . $newOwnerId)
				->where($db->quoteName('fnum') . ' = ' . $db->quote($fnum));
			$db->setQuery($query);
			if(!$db->execute())
			{
				throw new \RuntimeException($exceptionMessage);
			}
		}
		catch (\Exception $e)
		{
			throw new \RuntimeException($exceptionMessage);
		}
	}

	/**
	 * @param   ApplicationFileEntity  $applicationFile
	 *
	 * @return array<ApplicationFileAction>
	 */
	public function getApplicationFileActions(ApplicationFileEntity $applicationFile): array
	{
		$actions = [];

		// todo: get component parameters, check availability
		$actions[] = new RenameApplication();
		$actions[] = new GoToHistory();

		return $actions;
	}
}