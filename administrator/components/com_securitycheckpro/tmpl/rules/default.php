<?php
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\CMS\HTML\HTMLHelper;


Session::checkToken('get') or die('Invalid Token');
?>

<form action="<?php echo Route::_('index.php?option=com_securitycheckpro&controller=rules&view=rules&'. Session::getFormToken() .'=1');?>" style=" margin-left: 10px !important;  margin-right: 10px !important;" method="post" name="adminForm" id="adminForm">

    <?php 
    // Cargamos la navegación
    require JPATH_ADMINISTRATOR.'/components/com_securitycheckpro/helpers/navigation.php';
    ?>
                        
            <div class="card mb-6">
                <div class="card-body">
                    <div>
						<div class="input-group">
							<input type="text" name="filter_acl_search" class="form-control" placeholder="<?php echo Text::_('JSEARCH_FILTER_LABEL'); ?>" id="filter_acl_search" value="<?php echo $this->escape($this->state->get('filter.acl_search')); ?>" title="<?php echo Text::_('JSEARCH_FILTER'); ?>" />
							<button class="btn btn-outline-secondary" type="submit" rel="tooltip" title="<?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>"><i class="icon-search"></i></button>
							<button class="btn btn-outline-secondary" type="button" id="filter_acl_search_button" rel="tooltip" title="<?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>"><i class="icon-remove"></i></button>
							<div class="btn-group margin-left-10">
                                <?php 
									if (!empty($this->pagination)) {
										echo $this->pagination->getLimitBox();
									}
								?>
                            </div>
						</div>			
                        
                        <div class="alert alert-info" class="margin-top-10" role="alert">
							<?php echo Text::_('COM_SECURITYCHECKPRO_RULES_GUEST_USERS'); ?>
                        </div>
        
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="1%" class="nowrap center rules">
                                        <input type="checkbox" name="checkall-toggle" value="" title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
                                    </th>
                                    <th class="rules">
										<?php echo Text::_('COM_SECURITYCHECKPRO_RULES_GROUP_TITLE'); ?>
                                    </th>
                                    <th width="20%" class="rules">
										<?php echo Text::_('COM_SECURITYCHECKPRO_RULES_RULES_APPLIED'); ?>
                                    </th>
                                    <th width="5%" class="rules">
										<?php echo Text::_('JGRID_HEADING_ID'); ?>
                                    </th>
                                    <th width="20%" class="rules">
										<?php echo Text::_('COM_SECURITYCHECKPRO_RULES_LAST_CHANGE'); ?>
                                    </th>
                                </tr>
                            </thead>
        <?php
        $k = 0;
        if (!empty($this->items) ) {    
            foreach ($this->items as &$row) {
                ?>

                                <tr class="row<?php echo $k % 2; ?>">
                                    <td class="center">
                <?php echo HTMLHelper::_('grid.id', $k, $row->id); ?>
                                    </td>
                                    <td>
                <?php echo str_repeat('<span class="gi">|&mdash;</span>', $row->level) ?>
                <?php echo $this->escape($row->title); ?> 
                                    </td>
                                    <td class="rules-logs">
                <?php echo HTMLHelper::_(
                    'jgrid.state', $states = array(
                    0 => array(
                    'task'                => 'apply_rules',
                    'text'                => '',
                    'active_title'        => 'COM_SECURITYCHECKPRO_RULES_NOT_APPLIED_AND_TOGGLE',
                    'inactive_title'    => '',
                    'tip'                => true,
                    'active_class'        => 'unpublish',
                    'inactive_class'    => 'unpublish'
                    ),
                    1 => array(
                    'task'                => 'not_apply_rules',
                    'text'                => '',
                    'active_title'        => 'COM_SECURITYCHECKPRO_RULES_APPLIED_AND_TOGGLE',
                    'inactive_title'    => '',
                    'tip'                => true,
                    'active_class'        => 'publish',
                    'inactive_class'    => 'publish'
                    )
                    ), $row->rules_applied, $k
                ); ?>
                                    </td>
                                    <td class="rules-logs">
                <?php echo (int) $row->id; ?>
                                    </td>
                                    <td class="rules-logs">
                <?php echo $row->last_change; ?>
                                    </td>
                                </tr>

                <?php
                $k = $k+1;
            }
        }
        ?>
                        </table>
        <?php
        if (!empty($this->items) ) {        
            ?>
                        <div>
            <?php echo $this->pagination->getListFooter(); ?>
                        </div>
            <?php
        }
        ?>
                    </div>                    
                </div>
            </div>            
        </div>
</div>

<input type="hidden" name="task" value="" />
<input type="hidden" name="boxchecked" value="1" />
</form>