<?php

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

if (!empty($this->organizations)) : ?>
	<ul class="em-container-organizations">
		<?php foreach ($this->organizations as $organization) :
			$name = $organization->getName();
			if (empty($name)) {
				$name = Text::_('COM_EMUNDUS_FILE_ORGANIZATION_NO_NAME');
			}
			?>
			<li class="em-mb-4">
				<strong>
					<?php if (!empty($this->canEditOrganizations)) :
						$editUrl = Route::_('/index.php?option=com_emundus&view=crc&layout=organizationform&id=' . $organization->getId()); ?>
						<a
                            href="<?php echo htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8'); ?>"
                            target="_blank"
                            rel="noopener"
                            class="tw-target-blank-links tw-text-profile-full visited:tw-text-profile-full !tw-no-underline hover:!tw-underline hover:!tw-text-profile-full visited:hover:tw-text-profile-full"
                        >
                            <span><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></span>
						</a>
					<?php else: ?>
						<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>
					<?php endif; ?>
				</strong>
			</li>
		<?php endforeach; ?>
	</ul>
<?php else: ?>
	<p><?php echo Text::_('COM_EMUNDUS_FILE_NO_ORGANIZATIONS_ASSOCIATED'); ?></p>
<?php endif;
