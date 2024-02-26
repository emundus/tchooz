<?php
/**
 * Admin Home Bootstrap Tmpl
 *
 * @package     Joomla.Administrator
 * @subpackage  Fabrik
 * @copyright   Copyright (C) 2005-2020  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @since       3.0
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;

HTMLHelper::stylesheet('media/com_fabrik/css/admin.css');
ToolBarHelper::title(Text::_('COM_FABRIK_WELCOME'), 'fabrik.png');
$user = Factory::getUser();
$is_suadmin = $user->authorise('core.admin');
?>

<div id="j-main-container">
	<div class="row">
		<div class="d-flex">
			<div style="margin:0 0 25px;width:250px;"><?php echo HTMLHelper::image('media/com_fabrik/images/logo.png', 'Fabrik'); ?></div>
		
		<?php 
			$xml = file_get_contents(COM_FABRIK_BACKEND. '/fabrik.xml');
			$xmltags = json_decode(json_encode(simplexml_load_string($xml)),true);
			echo '<div><br><h4>
			Version: '.$xmltags['version'];
			if (array_key_exists('gitdate',$xmltags) && is_string($xmltags['gitdate'])) echo ' (GitDate: ' . $xmltags['gitdate'] . ')';
			echo '<br><br></h4>' . $xmltags['creationDate'] . '<br>';
			echo $xmltags['license'] . '<br>';
			echo $xmltags['copyright'] . '<br>';
			echo '</div>';
		?>
		</div>
		<div class="col-sm-12">
			<ul class="nav nav-tabs" id="Fab_Home_Nav" role="tablist">
				<li class="nav-item" role="">
					<button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home-about" type="button" role="tab" aria-controls="" aria-selected="true">
						<?php echo Text::_('COM_FABRIK_HOME_ABOUT'); ?>
					</button>
				</li>

				<li class="nav-item" role="">
					<button class="nav-link" id="news-tab" data-bs-toggle="tab" data-bs-target="#home-news" type="button" role="tab" aria-controls="" aria-selected="true">
						<?php echo Text::_('COM_FABRIK_HOME_NEWS'); ?>
					</button>
				</li>

				<?php if ($is_suadmin):?>
				<li class="nav-item" role="">
					<button class="nav-link" id="logs-tab" data-bs-toggle="tab" data-bs-target="#home-logs" type="button" role="tab" aria-controls="" aria-selected="true">
						<?php echo Text::_('COM_FABRIK_HOME_STATS'); ?>
					</button>
				</li>
				<?php endif?>

				<li class="nav-item" role="">
					<button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#home-tools" type="button" role="tab" aria-controls="" aria-selected="false">
						<?php echo Text::_('COM_FABRIK_HOME_TOOLS')?>
					</button>
				</li>
			</ul>

			<div class="tab-content">
				<div class="tab-pane active pt-5" id="home-about">
					
					<?php 
					echo Text::_('COM_FABRIK_HOME_ABOUT_TEXT'); 
					?>
					<br>
					
					<div style="display:flex;margin-top:30px;">
						<div class="p-3">
							<a href="https://fabrikar.com">
								<?php echo HTMLHelper::image('media/com_fabrik/images/box.png', 'Fabrik'); ?>
							</a>
						</div>
						<div class="p-4 lh-lg" >
							<ul class="list-unstyled">
							<li><a href="https://fabrikar.com/download" target="_blank">Downloads</a></li>
							<li><a href="https://fabrikar.com/forums/index.php?wiki/index/" target="_blank">Documentation WIKI</a></li>
							<li><a href="https://fabrikar.com/forums/index.php" target="_blank">Forums</a></li>
							<li><a href="https://fabrikar.com/subscribe" target="_blank">Create Account</a></li>
							<li><a href="https://fabrikar.com/forums/index.php?misc/contact" target="_blank">Contact</a></li></ul>
						</div>
					</div>
				</div>
				
				<div class="tab-pane" id="home-news">
					<div class="p-4 fw-bold lh-lg" >
						<ul class="list-unstyled">
							<li><a href="https://fabrikar.com/blog" target="_blank">Fabrik: Joomla Custom Website Application Builder - Blog</a></li>
							<li><a href="https://fabrikar.com/forums/index.php?forums/announcements.106/" target="_blank">Fabrik Forum: Fabrik 4 - Announcements</a></li>
						</ul>
					</div>
				</div>
				
				<?php if ($is_suadmin):?>
				<div class="tab-pane" id="home-logs">
					<br>
					<table class='adminlist'>
						<thead>
							<tr>
								<th style="width:20%"><?php echo Text::_('COM_FABRIK_HOME_DATE')?></th>
								<th><?php echo Text::_('COM_FABRIK_HOME_ACTION')?></th>
								<th><?php echo Text::_('MESSAGE')?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($this->logs as $log) :?>
							<tr>
								<td>
								<?php echo $log->timedate_created;?>
								</td>
								<td>
									<?php echo $log->message_type . '&nbsp;&nbsp;&nbsp;' ;?>
								</td>
								<td>
									<?php echo $log->message;?>
								</td>
							</tr>
							<?php
							endforeach;?>
						</tbody>
					</table>
				</div>
				<?php endif?>
				
				<div class="tab-pane" id="home-tools">
				<?php if ($is_suadmin):?>
					<div class=" alert alert-danger ">
					<?php echo Text::_('COM_FABRIK_HOME_RESET_FABRIK'); ?>
						<h4><?php echo Text::_('COM_FABRIK_HOME_CONFIRM_WIPE', true);?><h4>
						<a class="btn btn-danger" onclick="return confirm('<?php echo Text::_('COM_FABRIK_HOME_CONFIRM_WIPE', true);?>')" href="index.php?option=com_fabrik&task=home.reset">
						<?php echo Text::_('COM_FABRIK_HOME_RESET_FABRIK') ?></a>
					</div>
				<?php endif?>
				</div>
			</div>
		</div>
	</div>
</div>
