<?php
/**
 * @package     Tchooz\Services\Import\Referential\Source
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Import\Referential\Source;

use Joomla\CMS\Language\Text;
use Tchooz\Repositories\Contacts\OrganizationRepository;
use Tchooz\Services\Import\Referential\AbstractReferentialProvider;

/**
 * Referential of existing organizations, keyed by id and labeled by name.
 */
final class OrganizationReferentialSource extends AbstractReferentialProvider
{
	public const KEY = 'organizations';

	public static function create(): self
	{
		return new self();
	}

	public function getKey(): string
	{
		return self::KEY;
	}

	public function getLabel(): string
	{
		return Text::_('COM_EMUNDUS_IMPORT_REFERENTIAL_ORGANIZATIONS');
	}

	protected function loadEntries(): array
	{
		return array_map(
			static fn($organization): array => [
				'value' => (string) $organization->getId(),
				'label' => $organization->getName(),
			],
			(new OrganizationRepository(false))->getAll()
		);
	}
}