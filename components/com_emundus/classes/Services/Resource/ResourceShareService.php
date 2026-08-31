<?php
/**
 * @package     Tchooz\Services\Resource
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Services\Resource;

use Joomla\CMS\Log\Log;
use Tchooz\Entities\Resource\ResourceShareEntity;
use Tchooz\Repositories\Resource\ResourceShareRepository;

class ResourceShareService
{
	private const CODE_BYTES = 24;

	public function __construct(
		private ResourceShareRepository $repository = new ResourceShareRepository()
	) {
		Log::addLogger(['text_file' => 'com_emundus.service.resource_share.php'], Log::ALL, ['com_emundus.service.resource_share']);
	}

	/**
	 * Generate a cryptographically secure, URL-safe share code.
	 */
	public function generateCode(): string
	{
		return rtrim(strtr(base64_encode(random_bytes(self::CODE_BYTES)), '+/', '-_'), '=');
	}

	public function getByResource(int $resourceId): ?ResourceShareEntity
	{
		return $this->repository->findByResource($resourceId);
	}

	public function getByCode(string $code): ?ResourceShareEntity
	{
		return empty($code) ? null : $this->repository->findByCode($code);
	}

	/**
	 * Create or update the share link of a resource.
	 *
	 * @param   string|null  $plainPassword   null keeps the current password, '' removes it.
	 * @param   string|null  $expirationDate  Y-m-d H:i:s string, or null for no expiration.
	 */
	public function createOrUpdate(int $resourceId, ?string $plainPassword = null, ?string $expirationDate = null): ResourceShareEntity
	{
		if (empty($resourceId))
		{
			throw new \InvalidArgumentException('Resource id is required to create a share link');
		}

		$share = $this->repository->findByResource($resourceId) ?? new ResourceShareEntity(
			resourceId: $resourceId,
			code: $this->generateCode()
		);

		$share->setResourceId($resourceId);

		if ($plainPassword !== null)
		{
			$share->setPasswordHash($plainPassword === '' ? null : password_hash($plainPassword, PASSWORD_DEFAULT));
		}

		$share->setExpirationDate($this->parseExpiration($expirationDate));

		$this->repository->flush($share);

		return $share;
	}

	/**
	 * Validate a public access attempt by code (+ optional password).
	 */
	public function validate(string $code, ?string $plainPassword = null): bool
	{
		if (empty($code))
		{
			return false;
		}

		$share = $this->repository->findByCode($code);
		if ($share === null || $share->isExpired())
		{
			return false;
		}

		if (!$share->hasPassword())
		{
			return true;
		}

		return $plainPassword !== null && password_verify($plainPassword, $share->getPasswordHash());
	}

	public function revoke(int $resourceId): bool
	{
		return $this->repository->deleteByResource($resourceId);
	}

	private function parseExpiration(?string $expirationDate): ?\DateTimeImmutable
	{
		if (empty($expirationDate))
		{
			return null;
		}

		try
		{
			return new \DateTimeImmutable($expirationDate);
		}
		catch (\Exception $e)
		{
			throw new \InvalidArgumentException('Invalid expiration date: ' . $expirationDate);
		}
	}
}
