<?php

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

if (!empty($this->contacts)) : ?>
	<ul class="em-container-contacts">
		<?php foreach ($this->contacts as $contact) :
			$fullname = trim(($contact->getFirstname() ?? '') . ' ' . ($contact->getLastname() ?? ''));
			if (empty($fullname)) {
				$fullname = $contact->getEmail() ?? Text::_('COM_EMUNDUS_FILE_CONTACT_NO_NAME');
			}
			?>
			<li class="em-mb-4">
				<strong>
					<?php if (!empty($this->canEditContacts)) :
						$editUrl = Route::_('/index.php?option=com_emundus&view=crc&layout=contactform&id=' . $contact->getId()); ?>
						<a href="<?php echo htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8'); ?>"
                           target="_blank"
                           rel="noopener"
                           class="tw-target-blank-links tw-text-profile-full visited:tw-text-profile-full !tw-no-underline hover:!tw-underline hover:!tw-text-profile-full visited:hover:tw-text-profile-full"
                        >
                            <span><?php echo htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8'); ?></span>
						</a>
					<?php else: ?>
						<?php echo htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8'); ?>
					<?php endif; ?>
				</strong>
				<?php if (!empty($contact->email)): ?>
					&ndash;
					<a href="mailto:<?php echo htmlspecialchars($contact->getEmail(), ENT_QUOTES, 'UTF-8'); ?>">
						<?php echo htmlspecialchars($contact->getEmail(), ENT_QUOTES, 'UTF-8'); ?>
					</a>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
<?php else: ?>
	<p><?php echo Text::_('COM_EMUNDUS_FILE_NO_CONTACTS_ASSOCIATED'); ?></p>
<?php endif;
