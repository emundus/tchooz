<?php

namespace classes\SMS\Entities;

use classes\SMS\Entities\ReceiverEntity;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Factory;

class SMSEntity {
	/**
	 * @var ReceiverEntity[] $receivers
	 */
	private array $receivers;

	private string $message;

	private int $sender;

	private int $sms_action_id;

	private $date;

	public function __construct(array $receivers, string $message, int $sender)
	{
		Log::addLogger(['text_file' => 'com_emundus.sms.php',], Log::ALL, ['com_emundus.sms']);

		$this->receivers = $this->sanitizeReceivers($receivers);
		if (empty($this->receivers)) {
			throw new \InvalidArgumentException('Invalid receivers');
		}

		$this->message = $this->sanitizeMessage($message);
		if (empty($this->message)) {
			throw new \InvalidArgumentException('Invalid message');
		}

		$this->sms_action_id = $this->getSmsActionId();
		if (empty($this->sms_action_id)) {
			throw new \InvalidArgumentException('Sms action not retreived, check your configuration');
		}

		$this->sender = $sender;
		$this->date = new \DateTime();
	}

	public function getMessage(): string
	{
		return $this->message;
	}

	public function doesMessageContainTags(): bool
	{
		return preg_match('/\[(.*?)]/i', $this->message);
	}

	public function transformMessage(string $message, ReceiverEntity $receiver): string
	{
		require_once(JPATH_ROOT . '/components/com_emundus/models/emails.php');
		$m_emails = new \EmundusModelEmails();
		$post = $m_emails->setTags($this->getSender(), null, $receiver->getFnum(), null, $message);

		return preg_replace($post['patterns'], $post['replacements'], $message);
	}

	public function getSender(): int
	{
		return $this->sender;
	}

	public function getReceivers(): array
	{
		return $this->receivers;
	}

	public function getReceiversPhoneNumbers(): array
	{
		$phoneNumbers = [];

		if (!empty($this->receivers)) {
			foreach ($this->receivers as $receiver) {
				$phoneNumbers[] = $receiver->phone_number;
			}
		}

		return $phoneNumbers;
	}

	private function getSmsActionId(): int
	{
		$action_id = 0;

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select('id')
			->from('#__emundus_setup_actions')
			->where('name like ' . $db->quote('sms'));

		try
		{
			$db->setQuery($query);
			$action_id = (int)$db->loadResult();
		}
		catch (\Exception $e)
		{
			Log::add('Error on get sms action id : ' . $e->getMessage(), Log::ERROR, 'com_emundus.sms');
		}

		return $action_id;
	}

	private function sanitizeReceivers(array $receivers): array
	{
		$sanitizedPhoneNumber = [];

		if (!empty($receivers)) {
			foreach ($receivers as $receiver) {
				if ($receiver instanceof ReceiverEntity) {
					$sanitizedPhoneNumber[] = $receiver;
				}
			}
		}

		return $sanitizedPhoneNumber;
	}

	private function sanitizeMessage(string $rawMessage): string
	{
		$message = '';

		if (!empty($rawMessage)) {
			$message = strip_tags($rawMessage);
		}

		return $message;
	}

	/**
	 * @param $phone_number
	 *
	 * @return \classes\SMS\Entities\ReceiverEntity|null
	 */
	private function getReceiverFromPhoneNumber($phone_number): ?ReceiverEntity
	{
		$receiver = null;

		if (!empty($phone_number)) {
			foreach ($this->receivers as $receiver_entity) {
				if ($receiver_entity->phone_number === $phone_number) {
					$receiver = $receiver_entity;
					break;
				}
			}
		}

		return $receiver;
	}

	/**
	 * Save in logs the sms sent, in order to retrieve it from files, or user
	 *
	 * @param   string  $phone_number
	 * @param   string  $sent_message sent_message is the final transformed message sent to the receiver, it can be different from the original message
	 * @param   string  $message
	 *
	 * @return bool
	 */
	public function save(string $phone_number, string $sent_message, string $message = 'COM_EMUNDUS_SEND_SMS_SUCCESS'): bool
	{
		$saved = false;

		$receiver = $this->getReceiverFromPhoneNumber($phone_number);

		if (empty($receiver)) {
			throw new \InvalidArgumentException('Phone number was not related to this sms.');
		}

		require_once(JPATH_ROOT . '/components/com_emundus/models/logs.php');
		$saved = \EmundusModelLogs::log($this->getSender(), $receiver->getUserId(), $receiver->getFnum(), $this->sms_action_id, 'c', $message, json_encode([
			'message' => $sent_message,
			'phone_number' => $receiver->phone_number,
			'date' => $this->date->format('Y-m-d H:i:s')
		]));

		return $saved;
	}
}
