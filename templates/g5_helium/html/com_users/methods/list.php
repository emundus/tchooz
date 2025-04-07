<?php

/**
 * @package         Joomla.Administrator
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
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Users\Administrator\Helper\Mfa as MfaHelper;
use Joomla\Component\Users\Administrator\Model\MethodsModel;
use Joomla\Component\Users\Administrator\View\Methods\HtmlView;

/** @var HtmlView $this */

HTMLHelper::_('bootstrap.tooltip', '.hasTooltip');

/** @var MethodsModel $model */
$model = $this->getModel();

$this->getDocument()->getWebAssetManager()->useScript('com_users.two-factor-list');

$canAddEdit = MfaHelper::canAddEditMethod($this->user);
$canDelete  = MfaHelper::canDeleteMethod($this->user);
?>
<div id="com-users-methods-list-container" class="tw-mt-2 tw-gap-4 tw-grid tw-grid-cols-1 md:tw-grid-cols-2">
	<?php foreach ($this->methods as $methodName => $method) :
        if($method['name'] === 'backupcodes')
        {
	        $alreadySeen = 0;

            $db = Factory::getContainer()->get('DatabaseDriver');
            $query = $db->createQuery();

            $query->select('id,already_seen')
                ->from('#__user_mfa')
                ->where('user_id = ' . (int) $this->user->id)
                ->where('method = ' . $db->quote($method['name']));
            $db->setQuery($query);
            try
            {
                $backup_mfa = $db->loadObject();

	            if($backup_mfa->already_seen === 1)
	            {
		            continue;
	            }
	            else {
		            Factory::getApplication()->redirect(Route::_('index.php?com_users&task=method.edit&id='.$backup_mfa->id.'&user_id='.$this->user->id));
	            }
            }
            catch (Exception $e)
            {
                // Do nothing
            }
        }
        
		$methodClass = 'com-users-methods-list-method-name-' . htmlentities($method['name']) . ($this->defaultMethod == $methodName ? ' com-users-methods-list-method-default' : '');
		$icon = 'phonelink_lock';
		switch ($method['name'])
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
        <div
            class="tw-flex tw-flex-col tw-justify-between tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-white tw-p-6 tw-shadow-card com-users-methods-list-method <?php echo $methodClass ?> <?php echo count($method['active']) ? 'com-users-methods-list-method-active' : '' ?>">
            <div>
                <div class="com-users-methods-list-method-header">
                    <div class="com-users-methods-list-method-image tw-flex tw-items-center tw-justify-between">
                    <span class="material-symbols-outlined !tw-text-2xl"
                          title="<?php echo $this->escape($method['display']) ?>">
                        <?php echo $icon; ?>
                    </span>
						<?php if ($this->defaultMethod == $methodName) : ?>
                            <span id="com-users-methods-list-method-default-tag" class="label label-blue-2">
                                <?php echo Text::_('COM_USERS_MFA_LIST_DEFAULTTAG') ?>
                            </span>
						<?php endif; ?>
                    </div>
                    <div class="com-users-methods-list-method-title">
                        <h3>
                            <label class="me-1 flex-grow-1 tw-font-bold">
								<?php echo $method['display'] ?>
                            </label>
                        </h3>
                    </div>
                </div>

                <div class="com-users-methods-list-method-records-container">
                    <div class="com-users-methods-list-method-info">
						<?php echo $method['shortinfo'] ?>
                    </div>

					<?php if (count($method['active'])) : ?>
                        <div class="com-users-methods-list-method-records pt-2 my-2">
							<?php foreach ($method['active'] as $record) : ?>
                                <div
                                    class="com-users-methods-list-method-record d-flex flex-row flex-wrap justify-content-start border-top py-2 tw-h-full">
                                    <div
                                        class="com-users-methods-list-method-record-info flex-grow-1 d-flex flex-column align-items-start gap-1">
										<?php if ($methodName === 'backupcodes' && $canAddEdit) : ?>
                                            <div class="alert alert-warning mt-1 w-100">
                                                <p><?php echo Text::_('COM_USERS_MFA_BACKUPCODES_ONLY_ONCE') ?></p>
                                                <p><?php echo Text::sprintf('COM_USERS_MFA_BACKUPCODES_PRINT_PROMPT_HEAD', Route::_('index.php?option=com_users&task=method.edit&id=' . (int) $record->id . ($this->returnURL ? '&returnurl=' . $this->escape(urlencode($this->returnURL)) : '') . '&user_id=' . $this->user->id), 'text-decoration-underline') ?></p>
                                            </div>
										<?php else : ?>
                                            <div class="tw-flex tw-justify-between tw-items-center tw-w-full">
                                            <h4 class="com-users-methods-list-method-record-title-container mb-1 fs-3 tw-w-full">
                                            <span class="com-users-methods-list-method-record-title fw-bold">
                                                <?php echo $this->escape($record->title); ?>
                                            </span>
                                            </h4>
											<?php if ($methodName !== 'backupcodes' && ($canAddEdit || $canDelete)) : ?>
                                                <div
                                                    class="com-users-methods-list-method-record-actions my-2 d-flex flex-row flex-wrap tw-justify-end align-content-center align-items-start tw-gap-1 tw-w-full">
													<?php if ($canAddEdit) : ?>
                                                        <a class="com-users-methods-list-method-record-edit tw-btn-primary tw-flex !tw-w-auto tw-items-center"
                                                           href="<?php echo Route::_('index.php?option=com_users&task=method.edit&id=' . (int) $record->id . ($this->returnURL ? '&returnurl=' . $this->escape(urlencode($this->returnURL)) : '') . '&user_id=' . $this->user->id) ?>"
                                                           title="<?php echo Text::_('JACTION_EDIT') ?> <?php echo $this->escape($record->title); ?>">
                                                            <span
                                                                class="material-symbols-outlined popover-toggle-btn tw-cursor-pointer">edit</span>
                                                            <span
                                                                class="visually-hidden"><?php echo Text::_('JACTION_EDIT') ?><?php echo $this->escape($record->title); ?></span>
                                                        </a>
													<?php endif ?>

													<?php if ($method['canDisable'] && $canDelete) : ?>
                                                        <a class="com-users-methods-list-method-record-delete tw-btn-primary tw-flex !tw-w-auto tw-items-center tw-group tw-bg-red-500 tw-border-red-500 tw-text-white hover:tw-bg-white hover:tw-border-red-500 btn-sm"
                                                           href="<?php echo Route::_('index.php?option=com_users&task=method.delete&id=' . (int) $record->id . ($this->returnURL ? '&returnurl=' . $this->escape(urlencode($this->returnURL)) : '') . '&user_id=' . $this->user->id . '&' . Factory::getApplication()->getFormToken() . '=1') ?>"
                                                           title="<?php echo Text::_('JACTION_DELETE') ?> <?php echo $this->escape($record->title); ?>">
                                                            <span
                                                                class="material-symbols-outlined group-hover:tw-text-red-500">delete_outline</span>
                                                            <span
                                                                class="visually-hidden"><?php echo Text::_('JACTION_DELETE') ?><?php echo $this->escape($record->title); ?></span>
                                                        </a>
													<?php endif; ?>
                                                </div>
                                                </div>
											<?php endif; ?>
										<?php endif; ?>

                                        <div
                                            class="com-users-methods-list-method-record-lastused my-1 d-flex gap-5 text-muted w-100">
                                        <span class="com-users-methods-list-method-record-createdon">
                                            <?php echo Text::sprintf('COM_USERS_MFA_LBL_CREATEDON', $model->formatRelative($record->created_on)) ?>
                                        </span>
                                            <span class="com-users-methods-list-method-record-lastused-date">
                                            <?php echo Text::sprintf('COM_USERS_MFA_LBL_LASTUSED', $model->formatRelative($record->last_used)) ?>
                                        </span>
                                        </div>

                                    </div>
                                </div>
							<?php endforeach; ?>
                        </div>
					<?php endif; ?>
                </div>
            </div>

			<?php if ($canAddEdit && (empty($method['active']) || $method['allowMultiple'])) : ?>
                <div class="com-users-methods-list-method-addnew-container tw-mt-3">
                    <a href="<?php echo Route::_('index.php?option=com_users&task=method.add&method=' . $this->escape(urlencode($method['name'])) . ($this->returnURL ? '&returnurl=' . $this->escape(urlencode($this->returnURL)) : '') . '&user_id=' . $this->user->id) ?>"
                       class="com-users-methods-list-method-addnew tw-btn-primary btn-sm tw-w-fit tw-float-right"
                    >
						<?php echo Text::sprintf('COM_USERS_MFA_ADD_AUTHENTICATOR_OF_TYPE', $method['display']) ?>
                    </a>
                </div>
			<?php endif; ?>
        </div>
	<?php endforeach; ?>
</div>
