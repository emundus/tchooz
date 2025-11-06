<?php
/**
 * @package     Tchooz\Repositories
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;

class EmundusRepository
{
	protected bool $withRelations;
	protected array $exceptRelations = [];

	protected DatabaseInterface $db;

	public function __construct($withRelations = true, $exceptRelations = [], $name = '')
	{
		$this->db              = Factory::getContainer()->get('DatabaseDriver');
		$this->withRelations   = $withRelations;
		$this->exceptRelations = $exceptRelations;

		if (!empty($name))
		{
			Log::addLogger(['text_file' => "com_emundus.repository.{$name}.php"], Log::ALL, ["com_emundus.repository.{$name}"]);
		}
	}
}