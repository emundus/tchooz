<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_users
 *
 * @copyright   (C) 2022 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// Prevent direct access
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Users\Site\View\Captive\HtmlView;

/** @var HtmlView $this */

$shownMethods = [];

?>
<div id="com-users-select">
    <h2 id="com-users-select-heading">
        <?php echo Text::_('COM_USERS_MFA_SELECT_PAGE_HEAD'); ?>
    </h2>
    <div id="com-users-select-information">
        <p>
            <?php echo Text::_('COM_USERS_LBL_SELECT_INSTRUCTIONS'); ?>
        </p>
    </div>

    <div class="com-users-select-methods tw-mt-4 tw-grid tw-grid-cols-1 md:tw-grid-cols-2 gap-2">
        <?php foreach ($this->records as $record) :
            if (!array_key_exists($record->method, $this->mfaMethods) && ($record->method != 'backupcodes')) {
                continue;
            }

            $allowEntryBatching = isset($this->mfaMethods[$record->method]) ? $this->mfaMethods[$record->method]['allowEntryBatching'] : false;

            if ($this->allowEntryBatching) {
                if ($allowEntryBatching && in_array($record->method, $shownMethods)) {
                    continue;
                }
                $shownMethods[] = $record->method;
            }

            $methodName = $this->getModel()->translateMethodName($record->method);
	        $icon = 'phonelink_lock';
	        switch ($record->method)
	        {
		        case 'email':
			        $icon = 'mail_lock';
			        break;
		        case 'yubikey':
			        $icon = 'usb';
			        break;
		        case 'backupcodes':
			        $icon = 'file_copy';
			        break;
		        case 'totp':
			        $icon = 'qr_code_2';
			        break;
	        }
            ?>
        <a class="com-users-method tw-flex tw-flex-col flex-wrap text-decoration-none gap-2 text-body tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-white tw-p-6 tw-shadow-card hover:tw-bg-neutral-300 hover:tw-border-neutral-800"
           href="<?php echo Route::_('index.php?option=com_users&view=captive&record_id=' . $record->id)?>">
            <span class="material-symbols-outlined !tw-text-2xl"
                  title="<?php echo $this->escape(strip_tags($record->title)) ?>">
                        <?php echo $icon; ?>
                    </span>
            <?php if (!$this->allowEntryBatching || !$allowEntryBatching) : ?>
                <span class="com-users-method-title flex-grow-1 fs-5 fw-bold">
                    <?php if ($record->method === 'backupcodes') : ?>
                        <?php echo $record->title ?>
                    <?php else : ?>
                        <?php echo $this->escape($record->title) ?>
                    <?php endif; ?>
                </span>
                <small class="com-users-method-name text-muted">
                    <?php echo $methodName ?>
                </small>
            <?php else : ?>
                <span class="com-users-method-title flex-grow-1 fs-5 fw-bold">
                    <?php echo $methodName ?>
                </span>
                <small class="com-users-method-name text-muted">
                    <?php echo $methodName ?>
                </small>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>
