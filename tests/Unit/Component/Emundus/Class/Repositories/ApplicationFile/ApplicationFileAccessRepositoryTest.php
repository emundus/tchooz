<?php
/**
 * @package     Unit\Component\Emundus\Class\Repositories\ApplicationFile
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Repositories\ApplicationFile;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\ApplicationFile\ApplicationFileAccessEntity;
use Tchooz\Entities\ApplicationFile\ApplicationFileEntity;
use Tchooz\Repositories\ApplicationFile\ApplicationFileAccessRepository;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;

/**
 * @package     Unit\Component\Emundus\Class\Repositories\ApplicationFile
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\ApplicationFile\ApplicationFileAccessRepository
 */
class ApplicationFileAccessRepositoryTest extends UnitTestCase
{
	private ApplicationFileAccessRepository $repository;
	private ApplicationFileRepository $applicationFileRepository;
	private ?ApplicationFileEntity $publicApplicationFile = null;
	private ?string $publicFnum = null;

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct();
	}

	protected function setUp(): void
	{
		parent::setUp();

		$this->repository = new ApplicationFileAccessRepository();
		$this->applicationFileRepository = new ApplicationFileRepository();

		// Create a public application file for access token tests
		$systemUser = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['applicant']);
		$this->publicApplicationFile = new ApplicationFileEntity($systemUser, '', 0, $this->dataset['campaign']);
		$this->publicApplicationFile->generateFnum($this->dataset['campaign'], $this->dataset['applicant']);
		$this->publicApplicationFile->setIsPublic(true);
		$this->applicationFileRepository->flush($this->publicApplicationFile, $this->dataset['applicant']);

		$this->publicFnum = $this->publicApplicationFile->getFnum();
	}

	protected function tearDown(): void
	{
		// Clean up file access entries
		if (!empty($this->publicApplicationFile) && !empty($this->publicApplicationFile->getId()))
		{
			$query = $this->db->getQuery(true)
				->delete($this->db->quoteName('jos_emundus_file_access'))
				->where($this->db->quoteName('ccid') . ' = ' . (int) $this->publicApplicationFile->getId());
			$this->db->setQuery($query);
			$this->db->execute();
		}

		// Clean up the public application file
		if (!empty($this->publicFnum))
		{
			$query = $this->db->getQuery(true)
				->delete($this->db->quoteName('#__emundus_campaign_candidature'))
				->where($this->db->quoteName('fnum') . ' = ' . $this->db->quote($this->publicFnum));
			$this->db->setQuery($query);
			$this->db->execute();
		}

		parent::tearDown();
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileAccessRepository::generateAccessFileToken
	 */
	public function testGenerateAccessFileToken(): void
	{
		$token = $this->repository->generateAccessFileToken($this->publicApplicationFile);

		$this->assertNotEmpty($token, 'Le token généré ne doit pas être vide');
		$this->assertEquals(
			ApplicationFileAccessEntity::TOKEN_LENGTH * 2,
			strlen($token),
			'Le token doit avoir une longueur de ' . (ApplicationFileAccessEntity::TOKEN_LENGTH * 2) . ' caractères (hex de ' . ApplicationFileAccessEntity::TOKEN_LENGTH . ' bytes)'
		);
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileAccessRepository::generateAccessFileToken
	 */
	public function testGenerateAccessFileTokenThrowsForNonPublicFile(): void
	{
		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['applicant']);
		$nonPublicFile = new ApplicationFileEntity($user, $this->dataset['fnum'], 0, $this->dataset['campaign']);
		$nonPublicFile->setIsPublic(false);
		$nonPublicFile->setId($this->dataset['ccid']);

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('Unallowed to create access token for non-public application file');

		$this->repository->generateAccessFileToken($nonPublicFile);
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileAccessRepository::verifyAccessToken
	 */
	public function testVerifyAccessTokenValid(): void
	{
		$token = $this->repository->generateAccessFileToken($this->publicApplicationFile);

		$isValid = $this->repository->verifyAccessToken($token, $this->publicApplicationFile);

		$this->assertTrue($isValid, 'Le token valide doit être vérifié avec succès');
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileAccessRepository::verifyAccessToken
	 */
	public function testVerifyAccessTokenInvalidToken(): void
	{
		$this->repository->generateAccessFileToken($this->publicApplicationFile);

		$isValid = $this->repository->verifyAccessToken('invalid_token_value', $this->publicApplicationFile);

		$this->assertFalse($isValid, 'Un token invalide ne doit pas être vérifié');
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileAccessRepository::verifyAccessToken
	 */
	public function testVerifyAccessTokenExpired(): void
	{
		$token = $this->repository->generateAccessFileToken($this->publicApplicationFile);

		// Force the expiration date to the past
		$access = $this->repository->getByApplicationFile($this->publicApplicationFile);
		$this->assertNotNull($access);

		$access->setExpirationDate(new \DateTimeImmutable('- 1 day'));
		$this->repository->flush($access);

		$isValid = $this->repository->verifyAccessToken($token, $this->publicApplicationFile);

		$this->assertFalse($isValid, 'Un token expiré ne doit pas être vérifié');
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileAccessRepository::getByApplicationFile
	 */
	public function testGetByApplicationFile(): void
	{
		$this->repository->generateAccessFileToken($this->publicApplicationFile);

		$access = $this->repository->getByApplicationFile($this->publicApplicationFile);

		$this->assertNotNull($access, 'L\'accès doit exister après génération du token');
		$this->assertInstanceOf(ApplicationFileAccessEntity::class, $access);
		$this->assertEquals($this->publicApplicationFile->getId(), $access->getApplicationId());
		$this->assertNotEmpty($access->getToken());
		$this->assertTrue($access->getExpirationDate() > new \DateTimeImmutable(), 'La date d\'expiration doit être dans le futur');
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileAccessRepository::getByApplicationFile
	 */
	public function testGetByApplicationFileReturnsNullForNonPublicFile(): void
	{
		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['applicant']);
		$nonPublicFile = new ApplicationFileEntity($user, $this->dataset['fnum'], 0, $this->dataset['campaign']);
		$nonPublicFile->setIsPublic(false);
		$nonPublicFile->setId($this->dataset['ccid']);

		$access = $this->repository->getByApplicationFile($nonPublicFile);

		$this->assertNull($access, 'Aucun accès ne doit être retourné pour un dossier non public');
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileAccessRepository::getByApplicationFile
	 */
	public function testGetByApplicationFileReturnsNullWhenNoToken(): void
	{
		// No token generated yet for the public file
		$access = $this->repository->getByApplicationFile($this->publicApplicationFile);

		$this->assertNull($access, 'Aucun accès ne doit être retourné si aucun token n\'a été généré');
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileAccessRepository::flush
	 */
	public function testFlushInsert(): void
	{
		$encryptedToken = password_hash('test_token', PASSWORD_BCRYPT);
		$accessEntity = new ApplicationFileAccessEntity(
			0,
			$this->publicApplicationFile->getId(),
			$encryptedToken,
			new \DateTimeImmutable('+ 30 days')
		);

		$flushed = $this->repository->flush($accessEntity);

		$this->assertTrue($flushed, 'L\'insertion doit réussir');
		$this->assertGreaterThan(0, $accessEntity->getId(), 'L\'ID doit être assigné après insertion');
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileAccessRepository::flush
	 */
	public function testFlushInsertAlreadyExists(): void
	{
		$encryptedToken = password_hash('test_token', PASSWORD_BCRYPT);
		$accessEntity = new ApplicationFileAccessEntity(
			0,
			$this->publicApplicationFile->getId(),
			$encryptedToken,
			new \DateTimeImmutable('+ 30 days')
		);
		$this->repository->flush($accessEntity);

		$newAccessEntity = new ApplicationFileAccessEntity(
			0,
			$this->publicApplicationFile->getId(),
			$encryptedToken,
			new \DateTimeImmutable('+ 30 days')
		);
		$flushedNew = $this->repository->flush($newAccessEntity);

		$this->assertTrue($flushedNew, 'La mise à jour doit réussir même si un enregistrement existe déjà pour le même ccid');
		$this->assertSame($newAccessEntity->getId(), $accessEntity->getId(), 'L\'ID doit rester le même après mise à jour');
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileAccessRepository::flush
	 */
	public function testFlushUpdate(): void
	{
		$token = $this->repository->generateAccessFileToken($this->publicApplicationFile);
		$access = $this->repository->getByApplicationFile($this->publicApplicationFile);
		$this->assertNotNull($access);

		$originalId = $access->getId();
		$newExpirationDate = new \DateTimeImmutable('+ 60 days');
		$access->setExpirationDate($newExpirationDate);

		$flushed = $this->repository->flush($access);

		$this->assertTrue($flushed, 'La mise à jour doit réussir');
		$this->assertEquals($originalId, $access->getId(), 'L\'ID ne doit pas changer après mise à jour');

		// Verify the update was persisted
		$updatedAccess = $this->repository->getByApplicationFile($this->publicApplicationFile);
		$this->assertEquals(
			$newExpirationDate->format('Y-m-d'),
			$updatedAccess->getExpirationDate()->format('Y-m-d'),
			'La date d\'expiration doit être mise à jour'
		);
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileAccessRepository::flush
	 */
	public function testFlushThrowsExceptionWhenApplicationIdEmpty(): void
	{
		$encryptedToken = password_hash('test_token', PASSWORD_BCRYPT);
		$accessEntity = new ApplicationFileAccessEntity(
			0,
			0,
			$encryptedToken,
			new \DateTimeImmutable('+ 30 days')
		);

		$this->expectException(\Exception::class);

		$this->repository->flush($accessEntity);
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileAccessRepository::flush
	 */
	public function testFlushThrowsExceptionWhenTokenEmpty(): void
	{
		$accessEntity = new ApplicationFileAccessEntity(
			0,
			$this->publicApplicationFile->getId(),
			'',
			new \DateTimeImmutable('+ 30 days')
		);

		$this->expectException(\Exception::class);

		$this->repository->flush($accessEntity);
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileAccessRepository::flush
	 */
	public function testFlushThrowsExceptionWhenTokenNotHashed(): void
	{
		$accessEntity = new ApplicationFileAccessEntity(
			0,
			$this->publicApplicationFile->getId(),
			'plain_text_token_not_hashed',
			new \DateTimeImmutable('+ 30 days')
		);

		$this->expectException(\InvalidArgumentException::class);

		$this->repository->flush($accessEntity);
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileAccessRepository::renewToken
	 */
	public function testRenewToken(): void
	{
		$originalToken = $this->repository->generateAccessFileToken($this->publicApplicationFile);
		$originalAccess = $this->repository->getByApplicationFile($this->publicApplicationFile);
		$originalAccessId = $originalAccess->getId();
		$originalHashedToken = $originalAccess->getToken();

		$plainNewToken = $this->repository->renewToken($this->publicApplicationFile);

		$this->assertNotEmpty($plainNewToken, 'Le nouveau token ne doit pas être vide');
		$this->assertNotEquals($originalToken, $plainNewToken, 'Le nouveau token doit être différent de l\'ancien');
		$this->assertEquals(
			ApplicationFileAccessEntity::TOKEN_LENGTH * 2,
			strlen($plainNewToken),
			'Le nouveau token doit avoir la bonne longueur'
		);

		// Verify the old token no longer works
		$isOldValid = $this->repository->verifyAccessToken($originalToken, $this->publicApplicationFile);
		$this->assertFalse($isOldValid, 'L\'ancien token ne doit plus être valide après renouvellement');

		// Verify the new token works
		$isNewValid = $this->repository->verifyAccessToken($plainNewToken, $this->publicApplicationFile);
		$this->assertTrue($isNewValid, 'Le nouveau token doit être valide');

		// Verify the access entity was updated, not a new one created
		$updatedAccess = $this->repository->getByApplicationFile($this->publicApplicationFile);
		$this->assertEquals($originalAccessId, $updatedAccess->getId(), 'L\'ID de l\'accès ne doit pas changer lors du renouvellement');
		$this->assertNotEquals($originalHashedToken, $updatedAccess->getToken(), 'Le hash du token doit être mis à jour');
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileAccessRepository::renewToken
	 */
	public function testRenewTokenReturnsEmptyForNonPublicFile(): void
	{
		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['applicant']);
		$nonPublicFile = new ApplicationFileEntity($user, $this->dataset['fnum'], 0, $this->dataset['campaign']);
		$nonPublicFile->setIsPublic(false);
		$nonPublicFile->setId($this->dataset['ccid']);

		$token = $this->repository->renewToken($nonPublicFile);

		$this->assertEmpty($token, 'Le renouvellement doit retourner une chaîne vide pour un dossier non public');
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileAccessRepository::renewToken
	 */
	public function testRenewTokenExtendsExpirationDate(): void
	{
		$this->repository->generateAccessFileToken($this->publicApplicationFile);

		// Force expiration to near future
		$access = $this->repository->getByApplicationFile($this->publicApplicationFile);
		$access->setExpirationDate(new \DateTimeImmutable('+ 1 day'));
		$this->repository->flush($access);

		$this->repository->renewToken($this->publicApplicationFile);

		$renewedAccess = $this->repository->getByApplicationFile($this->publicApplicationFile);
		$expectedMinDate = new \DateTimeImmutable('+ 29 days');
		$this->assertTrue($renewedAccess->getExpirationDate() > $expectedMinDate, 'La date d\'expiration doit être étendue d\'au moins 30 jours après le renouvellement');
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileAccessRepository::generateAccessFileToken
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileAccessRepository::verifyAccessToken
	 */
	public function testTokenSecurityBcryptHash(): void
	{
		$token = $this->repository->generateAccessFileToken($this->publicApplicationFile);

		$access = $this->repository->getByApplicationFile($this->publicApplicationFile);
		$storedHash = $access->getToken();

		// Verify the stored token is a bcrypt hash
		$this->assertStringStartsWith('$2y$', $storedHash, 'Le token stocké doit être un hash bcrypt');
		$this->assertNotEquals($token, $storedHash, 'Le token stocké ne doit pas être le token en clair');
		$this->assertTrue(password_verify($token, $storedHash), 'password_verify doit confirmer le token');
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileAccessRepository::verifyAccessToken
	 */
	public function testVerifyAccessTokenReturnsFalseIfEmptyAccessToken(): void
	{
		// First assert: no access record at all → false
		$this->assertFalse($this->repository->verifyAccessToken('fake_token', $this->publicApplicationFile));

		$emptyTokenAccess = new ApplicationFileAccessEntity(
			1,
			$this->publicApplicationFile->getId(),
			'',
			new \DateTimeImmutable('+ 30 days')
		);

		$mockRepository = $this->getMockBuilder(ApplicationFileAccessRepository::class)
			->onlyMethods(['getByApplicationFile'])
			->getMock();

		$mockRepository->method('getByApplicationFile')
			->willReturn($emptyTokenAccess);

		$this->assertFalse($mockRepository->verifyAccessToken('any_token', $this->publicApplicationFile));
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileAccessRepository::generateAccessFileToken
	 */
	public function testGenerateAccessFileTokenCreatesUniqueTokens(): void
	{
		$token1 = $this->repository->generateAccessFileToken($this->publicApplicationFile);

		// Renew to generate a second token for comparison
		$token2 = $this->repository->renewToken($this->publicApplicationFile);

		$this->assertNotEquals($token1, $token2, 'Deux tokens générés successivement doivent être différents');
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileAccessRepository::generateAccessFileToken
	 */
	public function testTokenExpirationDateCannotExceedMaxDuration(): void
	{
		$token = $this->repository->generateAccessFileToken($this->publicApplicationFile);

		$access = $this->repository->getByApplicationFile($this->publicApplicationFile);
		$this->assertNotNull($access, 'L\'accès doit exister après génération du token');

		$maxAllowedDate = new \DateTimeImmutable('+ 366 days');
		$this->assertTrue(
			$access->getExpirationDate() < $maxAllowedDate,
			'La date d\'expiration ne doit pas dépasser 365 jours à partir de maintenant'
		);

		$minExpectedDate = new \DateTimeImmutable('+ 1 day');
		$this->assertTrue(
			$access->getExpirationDate() > $minExpectedDate,
			'La date d\'expiration doit être dans le futur (au moins 1 jour)'
		);
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileAccessRepository::flush
	 */
	public function testFlushThrowsExceptionWhenExpirationDateExceedsMaxDuration(): void
	{
		$encryptedToken = password_hash('test_token', PASSWORD_BCRYPT);
		$excessiveDate = new \DateTimeImmutable('+ 400 days');
		$accessEntity = new ApplicationFileAccessEntity(
			0,
			$this->publicApplicationFile->getId(),
			$encryptedToken,
			$excessiveDate
		);

		$this->expectException(\InvalidArgumentException::class);

		$this->repository->flush($accessEntity);
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileAccessRepository::flush
	 */
	public function testFlushAcceptsExpirationDateAtMaxDuration(): void
	{
		$encryptedToken = password_hash('test_token', PASSWORD_BCRYPT);
		$maxDate = new \DateTimeImmutable('+ 364 days');
		$accessEntity = new ApplicationFileAccessEntity(
			0,
			$this->publicApplicationFile->getId(),
			$encryptedToken,
			$maxDate
		);

		$flushed = $this->repository->flush($accessEntity);

		$this->assertTrue($flushed, 'Le flush doit réussir avec une date d\'expiration dans la limite autorisée');
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileAccessRepository::renewToken
	 */
	public function testRenewTokenWhenNoAccessExistsThrowsException(): void
	{
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage(Text::_('COM_EMUNDUS_FILE_ACCESS_CANNOT_RENEW_TOKEN_NO_ACCESS_RECORD'));

		$this->repository->renewToken($this->publicApplicationFile);
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileAccessRepository::revokeAccess
	 */
	public function testRevokeAccessDeletesExistingAccess(): void
	{
		$this->repository->generateAccessFileToken($this->publicApplicationFile);

		$access = $this->repository->getByApplicationFile($this->publicApplicationFile);
		$this->assertNotNull($access, 'Un accès doit exister avant la révocation');

		$revoked = $this->repository->revokeAccess($this->publicApplicationFile);

		$this->assertTrue($revoked, 'La révocation doit retourner true quand un accès existait');

		$accessAfterRevoke = $this->repository->getByApplicationFile($this->publicApplicationFile);
		$this->assertNull($accessAfterRevoke, 'Aucun accès ne doit exister après la révocation');
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileAccessRepository::revokeAccess
	 */
	public function testRevokeAccessReturnsTrueWhenNoAccessExists(): void
	{
		$revoked = $this->repository->revokeAccess($this->publicApplicationFile);

		$this->assertTrue($revoked, 'La révocation doit retourner true même si aucun accès n\'existait');
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileAccessRepository::revokeAccess
	 */
	public function testRevokeAccessReturnsFalseWhenApplicationFileHasNoId(): void
	{
		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['applicant']);
		$fileWithoutId = new ApplicationFileEntity($user, '', 0, $this->dataset['campaign']);

		$revoked = $this->repository->revokeAccess($fileWithoutId);

		$this->assertFalse($revoked, 'La révocation doit retourner false si le dossier n\'a pas d\'ID');
	}

	/**
	 * @covers \Tchooz\Repositories\ApplicationFile\ApplicationFileAccessRepository::revokeAccess
	 */
	public function testRevokeAccessInvalidatesToken(): void
	{
		$token = $this->repository->generateAccessFileToken($this->publicApplicationFile);

		$isValidBefore = $this->repository->verifyAccessToken($token, $this->publicApplicationFile);
		$this->assertTrue($isValidBefore, 'Le token doit être valide avant la révocation');

		$this->repository->revokeAccess($this->publicApplicationFile);

		$isValidAfter = $this->repository->verifyAccessToken($token, $this->publicApplicationFile);
		$this->assertFalse($isValidAfter, 'Le token ne doit plus être valide après la révocation');
	}
}
