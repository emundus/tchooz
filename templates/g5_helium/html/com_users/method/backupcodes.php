<?php

/**
 * @package         Joomla.Site
 * @subpackage      com_users
 *
 * @copyright   (C) 2022 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

// Prevent direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Component\Users\Site\View\Method\HtmlView;

/** @var  HtmlView $this */

HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');

$cancelURL = Route::_('index.php?option=com_users&task=methods.display&user_id=' . $this->user->id);

if (!empty($this->returnURL))
{
	$cancelURL = $this->escape(base64_decode($this->returnURL));
}

if ($this->record->method != 'backupcodes')
{
	throw new RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
}

if($this->record->already_seen === 1)
{
    // Redirect to the methods list if the user has already seen the backup codes
    Factory::getApplication()->redirect(Route::_('index.php?option=com_users&view=profile&layout=edit'));
}

$db = Factory::getContainer()->get('DatabaseDriver');
$query = $db->createQuery();

$query->update('#__user_mfa')
    ->set('already_seen = 1')
    ->where('user_id = ' . (int) $this->user->id)
    ->where('method = ' . $db->quote($this->record->method));
$db->setQuery($query);
try
{
    $db->execute();
}
catch (Exception $e)
{
    // Do nothing
}

?>
<div class="tw-mt-6 tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-white tw-p-6 tw-shadow-card">
    <h2>
		<?php echo Text::_('COM_USERS_USER_BACKUPCODES') ?>
    </h2>

    <div class="alert alert-warning mt-1 w-100">
        <p><?php echo Text::_('COM_USERS_MFA_BACKUPCODES_ONLY_ONCE') ?></p>
    </div>

    <div class="alert alert-info tw-mt-2">
		<?php echo Text::_('COM_USERS_USER_BACKUPCODES_DESC') ?>
    </div>

    <table class="table table-striped">
		<?php for ($i = 0; $i < (count($this->backupCodes) / 2); $i++) : ?>
            <tr>
                <td>
					<?php if (!empty($this->backupCodes[2 * $i])) : ?>
						<?php // This is a Key emoji; we can hide it from screen readers ?>
                        <span aria-hidden="true">&#128273;</span>
						<?php echo $this->backupCodes[2 * $i] ?>
					<?php endif; ?>
                </td>
                <td>
					<?php if (!empty($this->backupCodes[1 + 2 * $i])) : ?>
						<?php // This is a Key emoji; we can hide it from screen readers ?>
                        <span aria-hidden="true">&#128273;</span>
						<?php echo $this->backupCodes[1 + 2 * $i] ?>
					<?php endif; ?>
                </td>
            </tr>
		<?php endfor; ?>
    </table>

<!--    <p>
		<?php echo Text::_('COM_USERS_MFA_BACKUPCODES_RESET_INFO'); ?>
    </p>

    <div class="tw-w-full tw-flex tw-justify-end tw-mt-4">
        <a class="tw-btn tw-btn-secondary tw-w-fit"
           href="<?php echo Route::_(sprintf("index.php?option=com_users&task=method.regenerateBackupCodes&user_id=%s&%s=1%s", $this->user->id, Factory::getApplication()->getFormToken(), empty($this->returnURL) ? '' : '&returnurl=' . $this->returnURL)) ?>">
		    <?php echo Text::_('COM_USERS_MFA_BACKUPCODES_RESET'); ?>
        </a>
    </div>-->

    <div class="tw-w-full tw-flex tw-justify-end tw-mt-4">
        <a class="tw-btn tw-btn-secondary tw-w-fit"
           href="<?php echo Route::_('index.php?option=com_users&view=profile&layout=edit') ?>">
			<?php echo Text::_('COM_USERS_MFA_VALIDATE'); ?>
        </a>
    </div>
</div>
