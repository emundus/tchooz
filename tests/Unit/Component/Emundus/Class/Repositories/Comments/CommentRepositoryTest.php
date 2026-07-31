<?php
/**
 * @package     Unit\Component\Emundus\Class
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Repositories\Comments;

use DateTime;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Comments\CommentEntity;
use Tchooz\Enums\Comments\CommentTargetTypeEnum;
use Tchooz\Repositories\Comments\CommentRepository;
use Tchooz\Repositories\Contacts\ContactRepository;
use Tchooz\Entities\Contacts\ContactEntity;

/**
 * @package     Unit\Component\Emundus\Class\Repositories\Comments
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\Comments\CommentRepository
 */
class CommentRepositoryTest extends UnitTestCase
{
	private array $commentFixtures = [];

	private array $contactFixtures = [];

	private ContactRepository $contactRepository;

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct();
		$this->initDataSet();

		$this->model = new CommentRepository();
		$this->contactRepository = new ContactRepository();
	}

	public function createFixtures(): void
	{
		$contactEntity = $this->contactRepository->getByEmail('comment-target-unit-tests@emundus.fr');
		if ($contactEntity && !empty($contactEntity->getId())) {
			$this->contactRepository->delete($contactEntity->getId());
		}
		$contactEntity = new ContactEntity(
			email: 'comment-target-unit-tests@emundus.fr',
			lastname: 'Target',
			firstname: 'Comment',
			user_id: $this->dataset['coordinator']
		);
		$this->contactRepository->flush($contactEntity);
		$this->contactFixtures[] = $contactEntity;

		$comment1 = new CommentEntity(
			id: 0,
			targetType: CommentTargetTypeEnum::CONTACT,
			targetId: $contactEntity->getId(),
			content: 'Premier commentaire de test',
			createdBy: $this->dataset['coordinator'],
			createdAt: new DateTime(),
			isPublic: 1
		);
		$this->model->flush($comment1);
		$this->commentFixtures[] = $comment1;

		$comment2 = new CommentEntity(
			id: 0,
			targetType: CommentTargetTypeEnum::CONTACT,
			targetId: $contactEntity->getId(),
			content: 'Deuxième commentaire privé',
			createdBy: $this->dataset['coordinator'],
			createdAt: new DateTime(),
			isPublic: 0
		);
		$this->model->flush($comment2);
		$this->commentFixtures[] = $comment2;

		$reply = new CommentEntity(
			id: 0,
			targetType: CommentTargetTypeEnum::CONTACT,
			targetId: $contactEntity->getId(),
			content: 'Réponse au premier commentaire',
			createdBy: $this->dataset['coordinator'],
			createdAt: new DateTime(),
			isPublic: 1,
			parentId: $comment1->getId()
		);
		$this->model->flush($reply);
		$this->commentFixtures[] = $reply;
	}

	public function clearFixtures(): void
	{
		if (!empty($this->commentFixtures)) {
			foreach ($this->commentFixtures as $comment) {
				$this->model->delete($comment->getId());
			}
			$this->commentFixtures = [];
		}

		if (!empty($this->contactFixtures)) {
			foreach ($this->contactFixtures as $contact) {
				$this->contactRepository->delete($contact->getId());
			}
			$this->contactFixtures = [];
		}
	}

	/**
	 * @covers \Tchooz\Repositories\Comments\CommentRepository::flush
	 * @return void
	 */
	public function testFlush()
	{
		$contactEntity = $this->contactRepository->getByEmail('flush-target@emundus.fr');
		if ($contactEntity && !empty($contactEntity->getId())) {
			$this->contactRepository->delete($contactEntity->getId());
		}
		$contactEntity = new ContactEntity(
			email: 'flush-target@emundus.fr',
			lastname: 'FlushTarget',
			firstname: 'Test',
			user_id: $this->dataset['coordinator']
		);
		$this->contactRepository->flush($contactEntity);

		$comment1 = new CommentEntity(
			id: 0,
			targetType: CommentTargetTypeEnum::CONTACT,
			targetId: $contactEntity->getId(),
			content: 'Commentaire à insérer',
			createdBy: $this->dataset['coordinator'],
			createdAt: new DateTime(),
			isPublic: 1
		);
		$result = $this->model->flush($comment1);
		$this->assertTrue($result, 'The result should be true on insert');
		$this->assertGreaterThan(0, $comment1->getId(), 'The comment has been created with an ID greater than 0');

		$reply = new CommentEntity(
			id: 0,
			targetType: CommentTargetTypeEnum::CONTACT,
			targetId: $contactEntity->getId(),
			content: 'Réponse',
			createdBy: $this->dataset['coordinator'],
			createdAt: new DateTime(),
			isPublic: 1,
			parentId: $comment1->getId()
		);
		$result = $this->model->flush($reply);
		$this->assertTrue($result, 'The reply has been inserted');
		$this->assertGreaterThan(0, $reply->getId(), 'The reply has an ID greater than 0');
		$this->assertEquals($comment1->getId(), $reply->getParentId(), 'The reply parent_id matches the parent comment');

		$comment1->setContent('Contenu modifié');
		$comment1->setIsPublic(false);
		$comment1->setUpdatedAt(new DateTime());
		$comment1->setUpdatedBy($this->dataset['coordinator']);

		$result = $this->model->flush($comment1);
		$this->assertTrue($result, 'The result should be true on update');

		$updatedComment = $this->model->getById($comment1->getId());
		$this->assertInstanceOf(CommentEntity::class, $updatedComment);
		$this->assertEquals('Contenu modifié', $updatedComment->getContent(), 'The content has been updated');
		$this->assertEquals(0, $updatedComment->isPublic(), 'The is_public has been updated');
		$this->assertNotNull($updatedComment->getUpdatedAt(), 'The updated_at has been set');

		$this->model->delete($reply->getId());
		$this->model->delete($comment1->getId());
		$this->contactRepository->delete($contactEntity->getId());
	}

	/**
	 * @covers \Tchooz\Repositories\Comments\CommentRepository::delete
	 * @return void
	 */
	public function testDelete()
	{
		$this->createFixtures();

		foreach ($this->commentFixtures as $comment) {
			$result = $this->model->delete($comment->getId());
			$this->assertTrue($result, 'The comment has been deleted');
		}

		$this->commentFixtures = [];

		$result = $this->model->delete(0);
		$this->assertFalse($result, 'Delete with id 0 should return false');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Comments\CommentRepository::getById
	 * @return void
	 */
	public function testGetById()
	{
		$this->createFixtures();

		$comment = $this->model->getById($this->commentFixtures[0]->getId());

		$this->assertInstanceOf(CommentEntity::class, $comment, 'The result is an instance of CommentEntity');
		$this->assertNotEmpty($comment->getId());
		$this->assertEquals('Premier commentaire de test', $comment->getContent());
		$this->assertEquals($this->dataset['coordinator'], $comment->getCreatedBy());
		$this->assertEquals(1, $comment->isPublic());
		$this->assertNotNull($comment->getCreatedAt());
		$this->assertInstanceOf(DateTime::class, $comment->getCreatedAt());
		$this->assertEquals(CommentTargetTypeEnum::CONTACT, $comment->getTargetType());

		$reply = $this->model->getById($this->commentFixtures[2]->getId());
		$this->assertInstanceOf(CommentEntity::class, $reply);
		$this->assertEquals($this->commentFixtures[0]->getId(), $reply->getParentId(), 'The reply has the correct parent_id');

		$nonExistent = $this->model->getById(999999999);
		$this->assertNull($nonExistent, 'getById with non-existent id should return null');

		$this->clearFixtures();
	}

	/**
	 * @covers \Tchooz\Repositories\Comments\CommentRepository::get
	 * @return void
	 */
	public function testGet()
	{
		$this->createFixtures();

		$targetId = $this->contactFixtures[0]->getId();

		$comments = $this->model->get(['target_type' => CommentTargetTypeEnum::CONTACT->value, 'target_id' => $targetId]);
		$this->assertIsArray($comments, 'The result should be an array');
		$this->assertCount(3, $comments, 'There should be 3 comments for the target contact');
		$this->assertInstanceOf(CommentEntity::class, $comments[0], 'Each item should be a CommentEntity');

		$comments = $this->model->get(['target_type' => CommentTargetTypeEnum::CONTACT->value]);
		$this->assertIsArray($comments);
		$this->assertGreaterThanOrEqual(3, count($comments), 'Should find at least the 3 created comments');

		$comments = $this->model->get(['target_id' => $targetId]);
		$this->assertIsArray($comments);
		$this->assertCount(3, $comments, 'Should find the 3 comments by target_id');

		$comments = $this->model->get();
		$this->assertIsArray($comments);
		$this->assertGreaterThanOrEqual(3, count($comments), 'Should find at least the 3 created comments without filter');

		$comments = $this->model->get(['target_type' => CommentTargetTypeEnum::CONTACT->value, 'target_id' => 999999999]);
		$this->assertIsArray($comments);
		$this->assertEmpty($comments, 'Should return empty array when no comment matches');

		$comments = $this->model->get(['target_type' => CommentTargetTypeEnum::ORGANIZATION->value, 'target_id' => $targetId]);
		$this->assertIsArray($comments);
		$this->assertEmpty($comments, 'Should return empty array when target_type does not match');

		$this->clearFixtures();
	}
}