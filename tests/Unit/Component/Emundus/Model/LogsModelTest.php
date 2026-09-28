<?php
/**
 * @package     Unit\Component\Emundus\Model
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Enums\Actions\ActionEnum;
use Tchooz\Repositories\Actions\ActionRepository;

/**
 * @package     Unit\Component\Emundus\Model
 *
 * @covers      EmundusModelLogs
 */
class LogsModelTest extends UnitTestCase
{
	private int $fileActionId;

	private string $fileActionLabel;

	private const MESSAGE = 'COM_EMUNDUS_ACCESS_FILE_UPDATE';

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct('logs', $data, $dataName, 'EmundusModelLogs');
	}

	protected function setUp(): void
	{
		parent::setUp();

		$fileAction            = (new ActionRepository())->getByName(ActionEnum::FILE->value);
		$this->fileActionId    = $fileAction->getId();
		$this->fileActionLabel = $fileAction->getLabel();
	}

	protected function tearDown(): void
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__emundus_logs'))
			->where($db->quoteName('user_id_from') . ' = ' . (int) $this->dataset['coordinator'])
			->where($db->quoteName('message') . ' = ' . $db->quote(self::MESSAGE));
		$db->setQuery($query);
		$db->execute();

		parent::tearDown();
	}

	private function getLastWritten(): object
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select($db->quoteName(['user_id_to', 'fnum_to', 'params']))
			->from($db->quoteName('#__emundus_logs'))
			->where($db->quoteName('user_id_from') . ' = ' . (int) $this->dataset['coordinator'])
			->where($db->quoteName('message') . ' = ' . $db->quote(self::MESSAGE))
			->order($db->quoteName('id') . ' DESC');
		$db->setQuery($query, 0, 1);

		return $db->loadObject();
	}

	/**
	 * log() and logs() must agree on who an entry is addressed to.
	 *
	 * @covers EmundusModelLogs::log
	 * @covers EmundusModelLogs::logs
	 */
	public function testLogAndLogsAddressTheApplicantWhenNoRecipientIsGiven(): void
	{
		$this->assertTrue(\EmundusModelLogs::log($this->dataset['coordinator'], null, $this->dataset['fnum'], $this->fileActionId, 'u', self::MESSAGE));
		$this->assertEquals($this->dataset['applicant'], $this->getLastWritten()->user_id_to);

		$this->assertTrue(\EmundusModelLogs::logs($this->dataset['coordinator'], [$this->dataset['fnum']], $this->fileActionId, 'u', self::MESSAGE));
		$this->assertEquals($this->dataset['applicant'], $this->getLastWritten()->user_id_to);
	}

	/**
	 * @covers EmundusModelLogs::log
	 * @covers EmundusModelLogs::logs
	 */
	public function testLogAndLogsKeepAnExplicitRecipient(): void
	{
		$this->assertTrue(\EmundusModelLogs::log($this->dataset['coordinator'], $this->dataset['coordinator'], $this->dataset['fnum'], $this->fileActionId, 'u', self::MESSAGE));
		$this->assertEquals($this->dataset['coordinator'], $this->getLastWritten()->user_id_to);

		$this->assertTrue(\EmundusModelLogs::logs($this->dataset['coordinator'], [$this->dataset['fnum']], $this->fileActionId, 'u', self::MESSAGE, '', $this->dataset['coordinator']));
		$this->assertEquals($this->dataset['coordinator'], $this->getLastWritten()->user_id_to);
	}

	/**
	 * Entries about a user rather than a file (login, account edition) carry no fnum.
	 *
	 * @covers EmundusModelLogs::log
	 */
	public function testLogWritesAnEntryWithoutFile(): void
	{
		$this->assertTrue(\EmundusModelLogs::log($this->dataset['coordinator'], $this->dataset['applicant'], '', $this->fileActionId, 'u', self::MESSAGE));

		$written = $this->getLastWritten();
		$this->assertEquals($this->dataset['applicant'], $written->user_id_to);
		$this->assertEmpty($written->fnum_to);
	}

	/**
	 * @covers EmundusModelLogs::log
	 */
	public function testLogKeepsPlainTextParams(): void
	{
		$this->assertTrue(\EmundusModelLogs::log($this->dataset['coordinator'], null, $this->dataset['fnum'], $this->fileActionId, 'u', self::MESSAGE, 'Error updating payment infos'));
		$this->assertEquals('Error updating payment infos', json_decode($this->getLastWritten()->params, true)['raw'] ?? null);
	}

	/**
	 * An entry written by an automation names it, so a reader can tell which automation acted.
	 *
	 * @covers EmundusModelLogs::setActionDetails
	 */
	public function testActionNameIsTheAutomationLabelWhenOneWrote(): void
	{
		$params = json_encode([
			'action_type' => 'update_file_data',
			'automation'  => ['id' => 12, 'label' => 'Passage en revue'],
			'updated'     => [['old' => 'a', 'new' => 'b']]
		]);

		$details = $this->model->setActionDetails($this->fileActionId, 'u', $params, 'COM_EMUNDUS_ACCESS_FILE_UPDATE');

		$this->assertEquals(
			Text::_('COM_EMUNDUS_LOGS_AUTOMATION_ORIGIN') . ' - Passage en revue',
			strip_tags($details['action_name']),
			'The origin prefix should make clear an automation wrote the entry.'
		);
	}

	/**
	 * @covers EmundusModelLogs::setActionDetails
	 */
	public function testActionNameFallsBackToTheStoredMessage(): void
	{
		$params = json_encode(['updated' => [['old' => 'a', 'new' => 'b']]]);

		$details = $this->model->setActionDetails($this->fileActionId, 'u', $params, 'COM_EMUNDUS_ACCESS_FILE_UPDATE');

		$this->assertEquals(Text::_('COM_EMUNDUS_ACCESS_FILE_UPDATE'), $details['action_name']);
	}

	/**
	 * Callers that predate the message argument must keep the derived key.
	 *
	 * @covers EmundusModelLogs::setActionDetails
	 */
	public function testActionNameFallsBackToTheDerivedKeyWithoutAMessage(): void
	{
		$params = json_encode(['updated' => [['old' => 'a', 'new' => 'b']]]);

		$details = $this->model->setActionDetails($this->fileActionId, 'u', $params);

		// The key is derived from the action label held by the referential, which is platform dependent.
		$this->assertEquals(Text::_($this->fileActionLabel . '_UPDATE'), $details['action_name']);
	}

	/**
	 * The label is author-supplied and reaches templates that echo action_name unescaped.
	 *
	 * @covers EmundusModelLogs::setActionDetails
	 */
	public function testTheAutomationLabelIsEscaped(): void
	{
		$params = json_encode([
			'automation' => ['id' => 12, 'label' => '<img src=x onerror=alert(1)>'],
			'updated'    => [['old' => 'a', 'new' => 'b']]
		]);

		$details = $this->model->setActionDetails($this->fileActionId, 'u', $params, 'COM_EMUNDUS_ACCESS_FILE_UPDATE');

		$this->assertStringNotContainsString('<img', $details['action_name']);
		$this->assertStringContainsString('&lt;img', $details['action_name']);
	}
}
