<?php
/**
 * @package     Tchooz\Subscribers
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Subscribers;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseDriver;
use Joomla\Event\SubscriberInterface;

class EmundusSubscriber implements SubscriberInterface
{
	protected DatabaseDriver $db;

	public function __construct(string $name)
	{
		$this->db = Factory::getContainer()->get('DatabaseDriver');

		Log::addLogger(['text_file' => "com_emundus.subscriber{$name}.php"], Log::ALL, array("com_emundus.subscriber{$name}"));
	}

	public static function getSubscribedEvents(): array
	{
		return [];
	}
}