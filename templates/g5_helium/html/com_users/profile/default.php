<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_users
 *
 * @copyright   (C) 2009 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

Factory::getApplication()->redirect('index.php');
?>
<div class="profile<?php echo $this->pageclass_sfx; ?>">
	<?php if ($this->params->get('show_page_heading')) : ?>
		<div class="page-header">
			<h1>
				<?php echo $this->escape($this->params->get('page_heading')); ?>
			</h1>
		</div>
	<?php endif; ?>

      <?php if ($this->getCurrentUser()->id == $this->data->id) : ?>		<?php echo $this->loadTemplate('params'); ?>
        <ul class="com-users-profile__edit btn-toolbar float-end">		<?php echo $this->loadTemplate('custom'); ?>
            <li class="btn-group">	
                <a class="btn btn-primary" href="<?php echo Route::_('index.php?option=com_users&task=profile.edit&user_id=' . (int) $this->data->id); ?>">	
                    <span class="icon-user-edit" aria-hidden="true"></span> <?php echo Text::_('COM_USERS_EDIT_PROFILE'); ?>	
                </a>	
            </li>	
        </ul>	
    <?php endif; ?>

	<?php echo $this->loadTemplate('core'); ?>
	<?php echo $this->loadTemplate('params'); ?>
	<?php echo $this->loadTemplate('custom'); ?>
</div>
