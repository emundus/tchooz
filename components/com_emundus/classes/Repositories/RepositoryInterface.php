<?php
/**
 * @package     Tchooz\Repositories
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories;

interface RepositoryInterface
{
	public function __construct($withRelations = true, $exceptRelations = []);
	public function delete(int $id): bool;
	public function getById(int $id): mixed;
}