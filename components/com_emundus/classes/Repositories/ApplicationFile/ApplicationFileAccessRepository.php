<?php

namespace Tchooz\Repositories\ApplicationFile;

use Joomla\CMS\Language\Text;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\ApplicationFile\ApplicationFileAccessEntity;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Factories\ApplicationFile\ApplicationFileAccessFactory;
use Tchooz\Repositories\Addons\AddonRepository;
use Tchooz\Repositories\EmundusRepository;

#[TableAttribute(table: 'jos_emundus_file_access', alias: 'efa', columns: [
	'id',
	'ccid',
	'token',
	'expiration_date'
])]
class ApplicationFileAccessRepository extends EmundusRepository
{
	private ApplicationFileAccessFactory $factory;

	public function __construct($withRelations = true, $exceptRelations = [])
	{
		parent::__construct($withRelations, $exceptRelations, 'application_file_access_repository', self::class);

		$this->factory = new ApplicationFileAccessFactory();
	}

	public function getFactory(): ApplicationFileAccessFactory
	{
		return $this->factory;
	}

	/**
	 * @param   ApplicationFileEntity  $applicationFile
	 *
	 * @return ApplicationFileAccessEntity|null
	 */
	public function getByApplicationFile(ApplicationFileEntity $applicationFile): ?ApplicationFileAccessEntity
	{
		$access = null;

		if ($applicationFile->isPublic())
		{
			$access = $this->getByApplicationFileId($applicationFile->getId());
		}

		return $access;
	}

	/**
	 * @param   int  $applicationFileId
	 *
	 * @return ApplicationFileAccessEntity|null
	 */
	public function getByApplicationFileId(int $applicationFileId): ?ApplicationFileAccessEntity
	{
		$access = null;

		if (!empty($applicationFileId))
		{
			$accesses = $this->get(['ccid' => $applicationFileId], 1);

			if (!empty($accesses))
			{
				$access = $accesses[0];
			}
		}

		return $access;
	}

	/**
	 * @param   ApplicationFileAccessEntity  $applicationFileAccessEntity
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function flush(ApplicationFileAccessEntity $applicationFileAccessEntity): bool
	{
		$flushed = false;

		if (empty($applicationFileAccessEntity->getApplicationId()) || empty($applicationFileAccessEntity->getToken()))
		{
			throw new \Exception(Text::_('COM_EMUNDUS_FILE_ACCESS_ENTITY_INVALID'));
		}

		// token is supposed to be an encrypted string, make sure it is valid
		$tokenInfo = password_get_info($applicationFileAccessEntity->getToken());
		if ($tokenInfo['algo'] === null || $tokenInfo['algoName'] === 'unknown') {
			throw new \InvalidArgumentException(
				Text::_('COM_EMUNDUS_FILE_ACCESS_TOKEN_MUST_BE_HASHED')
			);
		}

		// expiration date cannot exceed 365 days from now
		$maxAllowedDate = new \DateTimeImmutable('+ 365 days');
		if ($applicationFileAccessEntity->getExpirationDate() > $maxAllowedDate) {
			throw new \InvalidArgumentException(
				Text::_('COM_EMUNDUS_FILE_ACCESS_EXPIRATION_DATE_TOO_FAR')
			);
		}

		if (empty($applicationFileAccessEntity->getId()))
		{
			$existingAccess = $this->getByApplicationFileId($applicationFileAccessEntity->getApplicationId());
			if ($existingAccess !== null) {
				$applicationFileAccessEntity->setId($existingAccess->getId());
			}
		}


		if (!empty($applicationFileAccessEntity->getId()))
		{
			$update = (object) [
				'id' => $applicationFileAccessEntity->getId(),
				'ccid' => $applicationFileAccessEntity->getApplicationId(),
				'token' => $applicationFileAccessEntity->getToken(),
				'expiration_date' => $applicationFileAccessEntity->getExpirationDate()->format('Y-m-d H:i:s')
			];

			$flushed = $this->db->updateObject($this->tableName, $update, 'id');
		}
		else
		{
			$insert = (object) [
				'ccid' => $applicationFileAccessEntity->getApplicationId(),
				'token' => $applicationFileAccessEntity->getToken(),
				'expiration_date' => $applicationFileAccessEntity->getExpirationDate()->format('Y-m-d H:i:s')
			];

			$flushed = $this->db->insertObject($this->tableName, $insert);

			if ($flushed)
			{
				$applicationFileAccessEntity->setId($this->db->insertid());
			}
		}

		return $flushed;
	}

	/**
	 * @param   ApplicationFileEntity  $applicationFile
	 *
	 * @return string plain text token
	 * @throws \RuntimeException
	 */
	public function generateAccessFileToken(ApplicationFileEntity $applicationFile): string
	{
		$token = '';

		if ($applicationFile->isPublic())
		{
			// completely random token for public application files
			try {
				$token = bin2hex(random_bytes(ApplicationFileAccessEntity::TOKEN_LENGTH));

				// bcrypt the token before storing it in the database for security reasons
				$encryptedToken = password_hash($token, PASSWORD_BCRYPT);

				$addonRepository = new AddonRepository();
				$publicAccessAddon = $addonRepository->getByName('public_session');
				$params = $publicAccessAddon->getParams();
				$days = !empty($params['token_validity_duration']) ? intval($params['token_validity_duration']) : 30;

				if ($days > 365) {
					$days = 365;
				}

				$access = new ApplicationFileAccessEntity(0, $applicationFile->getId(), $encryptedToken, new \DateTimeImmutable('+ ' . $days . ' days'));
				if (!$this->flush($access))
				{
					throw new \RuntimeException('Failed to generate access token for public application file with fnum: ' . $applicationFile->getFnum());
				}
			} catch (\Exception $e)
			{
				throw new \RuntimeException('Error generating access token for public application file: ' . $e->getMessage());
			}
		}
		else
		{
			throw new \RuntimeException('Unallowed to create access token for non-public application file');
		}

		return $token;
	}

	/**
	 * @param   string                 $plainToken
	 * @param   ApplicationFileEntity  $applicationFile
	 *
	 * @return bool
	 */
	public function verifyAccessToken(string $plainToken, ApplicationFileEntity $applicationFile): bool
	{
		$access = $this->getByApplicationFile($applicationFile);

		if (empty($access))
		{
			return false; // No access record found for the application file
		}

		if ($access->getExpirationDate() < new \DateTimeImmutable()) {
			return false; // Token expired
		}

		if (empty($access->getToken()))
		{
			return false; // Token not found or corrupt, thus invalid
		}

		return password_verify($plainToken, $access->getToken());
	}

	/**
	 * @param   ApplicationFileEntity  $applicationFile
	 *
	 * @return string
	 *
	 * @throws \Exception
	 */
	public function renewToken(ApplicationFileEntity $applicationFile): string
	{
		$token = '';

		if ($applicationFile->isPublic())
		{
			$access = $this->getByApplicationFile($applicationFile);

			if (empty($access))
			{
				throw new \Exception(text::_('COM_EMUNDUS_FILE_ACCESS_CANNOT_RENEW_TOKEN_NO_ACCESS_RECORD'));
			}

			$token = bin2hex(random_bytes(ApplicationFileAccessEntity::TOKEN_LENGTH));
			$encryptedToken = password_hash($token, PASSWORD_BCRYPT);

			$addonRepository = new AddonRepository();
			$publicAccessAddon = $addonRepository->getByName('public_session');
			$params = $publicAccessAddon->getParams();
			$days = !empty($params['token_validity_duration']) ? intval($params['token_validity_duration']) : 30;

			if ($days > 365) {
				$days = 365;
			}

			$access->setToken($encryptedToken);
			$access->setExpirationDate(new \DateTimeImmutable('+ ' . $days . ' days'));

			if (!$this->flush($access))
			{
				throw new \Exception('Failed to renew access token for public application file: ' . $applicationFile->getFnum());
			}
		}

		return $token;
	}

	/**
	 * @param   ApplicationFileEntity  $applicationFile
	 *
	 * @return bool
	 */
	public function revokeAccess(ApplicationFileEntity $applicationFile): bool
	{
		$revoked = false;

		if (!empty($applicationFile->getId()))
		{
			$accesses = $this->get(['ccid' => $applicationFile->getId()]);
			if (!empty($accesses))
			{
				$accessIds = array_map(function ($access) {
					return $access->getId();
				}, $accesses);

				$query = $this->db->createQuery();
				$query->delete($this->tableName)
					->where('id IN ('  . implode(',', $accessIds) . ')');

				$this->db->setQuery($query);
				$revoked = $this->db->execute();
			}
			else
			{
				$revoked = true;
			}
		}

		return $revoked;
	}
}