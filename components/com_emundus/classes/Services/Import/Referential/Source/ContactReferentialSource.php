<?php
/**
 * @package     Tchooz\Services\Import\Referential\Source
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Services\Import\Referential\Source;

use Joomla\CMS\Language\Text;
use Tchooz\Repositories\Contacts\ContactRepository;
use Tchooz\Services\Import\Referential\AbstractReferentialProvider;

/**
 * Referential of contacts, keyed by id and labeled by full name with the email
 * appended for disambiguation.
 */
final class ContactReferentialSource extends AbstractReferentialProvider
{
	public const KEY = 'contacts';

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
		return Text::_('COM_EMUNDUS_IMPORT_REFERENTIAL_CONTACTS');
	}

	protected function loadEntries(): array
	{
		return array_map(
			static fn($contact): array => [
				'value' => (string) $contact->getId(),
				'label' => self::label($contact),
			],
			(new ContactRepository(false))->getAllContacts(lim: 0)['datas']
		);
	}

	private static function label(object $contact): string
	{
		$name  = trim($contact->getFirstname() . ' ' . $contact->getLastname());
		$email = $contact->getEmail();

		if ($name !== '' && $email !== '')
		{
			return sprintf('%s (%s)', $name, $email);
		}

		return $name !== '' ? $name : $email;
	}
}