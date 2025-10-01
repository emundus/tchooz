<?php 
/**
 * @Securitycheckpro component
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
 
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\Plugin\System\Trackactions\Model\TrackActionsHelperModel;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;

$document = Factory::getApplication()->getDocument();

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
?>

<form action="<?php echo Route::_('index.php?option=com_securitycheckpro&view=trackactions_logs');?>" class="margin-left-10 margin-right-10" method="post" name="adminForm" id="adminForm">

    <?php 
    // Cargamos la navegación
    require JPATH_ADMINISTRATOR.'/components/com_securitycheckpro/helpers/navigation.php';
    ?>
                        
                   
            <!-- Contenido principal -->
            <div class="row">
            
                <div class="col-lg-12">
                    <div class="card mb-3">                        
                        <div class="card-body">
                            <div id="j-main-container">
                                <div id="editcell">
                                <div class="accordion-group">
                                <div class="card-header text-center">
            <?php echo Text::_('COM_SECURITYCHECKPRO_COLOR_CODE'); ?>
                                </div>
                                <table class="table table-borderless">                                
                                <thead>
                                    <tr>
                                        <td><span class="badge bg-warning"> </span>
                                        </td>
                                        <td class="left">
            <?php echo Text::_('COM_SECURITYCHECKPRO_ADMINISTRATOR_GROUP'); ?>
                                        </td>
                                        <td><span class="badge bg-danger"> </span>
                                        </td>
                                        <td class="left">
            <?php echo Text::_('COM_SECURITYCHECKPRO_SUPER_USERS_GROUP'); ?>
                                        </td>
                                        <td><span class="badge bg-info"> </span>
                                        </td>
                                        <td class="left">
            <?php echo Text::_('COM_SECURITYCHECKPRO_OTHER_GROUPS'); ?>
                                        </td>
                                    </tr>
                                </thead>
                                </table>
                                </div>
                                <br />
                                <?php echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this)); ?>
                                <?php if (empty($this->items)) : ?>
                                    <div class="alert alert-no-items">
                                    <?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
                                    </div>
                                <?php else : ?>
                                    <table class="table table-striped table-hover" id="logsList">
                                        <thead>
                                            <th width="2%">
                                                <?php echo HTMLHelper::_('searchtools.sort', '', 'a.id', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
                                            </th>
                                            <th width="1%">
                                                <input type="checkbox" name="checkall-toggle" value=""
                                                    title="<?php echo Text::_('JGLOBAL_CHECK_ALL'); ?>"
                                                    onclick="Joomla.checkAll(this)" />
                                            </th>
                                            <th>
                                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_SECURITYCHECKPRO_MESSAGE', 'a.message', $listDirn, $listOrder); ?>
                                            </th>
                                            <th>
                                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_SECURITYCHECKPRO_DATE', 'a.log_date', $listDirn, $listOrder); ?>
                                            </th>
                                            <th>
                                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_SECURITYCHECKPRO_EXTENSION', 'a.extension', $listDirn, $listOrder); ?>
                                            </th>
                                            <th>
                                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_SECURITYCHECKPRO_USER', 'a.user_id', $listDirn, $listOrder); ?>
                                            </th>
                                            <th>
                                                <?php echo HTMLHelper::_('searchtools.sort', 'COM_SECURITYCHECKPRO_IP_ADDRESS', 'a.ip_address', $listDirn, $listOrder); ?>
                                            </th>
                                        </thead>
                                        <tfoot>
                                            <tr>
                                                <td colspan="7">
                                                    <?php echo $this->pagination->getListFooter(); ?>
                                                </td>
                                            </tr>
                                        </tfoot>
                                        <tbody>
                                    <?php foreach ($this->items as $i => $item) : ?>
                                                <tr class="row<?php echo $i % 2; ?>">
                                                    <td>
                                                        <span class="sortable-handler inactive tip-top hasTooltip">
                                                            <i class="icon-menu"></i>
                                                        </span>
                                                    </td>
                                                    <td class="center">
                                        <?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
                                                    </td>
                                                    <td>
                                        <?php 
                                        $icono = null;
                                        Factory::getApplication()->triggerEvent('onLogMessagePrepare', array (&$item->message, $item->extension, &$icono)); 
                                        echo $icono;
                                        echo $this->escape($item->message); ?>
                                                    </td>
                                                    <td>
                                        <?php echo $this->escape($item->log_date); ?>
                                                    </td>
                                                    <td>
                                        <?php echo TrackActionsHelperModel::translateExtensionName(strtoupper(strtok($this->escape($item->extension), '.'))); ?>
                                                    </td>
                                                    <td>
                                        <?php 
                                        $user_id = $item->user_id;
                                                        
                                        $db = Factory::getContainer()->get(DatabaseInterface::class);
                                        $query = "SELECT COUNT(*) FROM #__users WHERE id={$user_id}";
                                        $db->setQuery($query);
                                        $db->execute();
                                        $existe_usuario = $db->loadResult();
                                                        
                                        if ($existe_usuario ) {
                                            $user_object = Factory::getApplication()->getIdentity($user_id);
                                            // El usuario pertenece al grupo Super users
                                            if (array_search(8, $user_object->groups) !== false ) {                                    
                                                $span = '<span class="badge bg-danger">';
                                                // El usuario pertenece al grupo Administrators
                                            } else if (array_search(7, $user_object->groups) !== false ) {
                                                $span = '<span class="badge bg-warning">';
                                            } else {
                                                $span = '<span class="badge bg-info">';
                                            }
                                                            echo $span . $user_object->name . "</span>";
                                        } else {
                                            echo "<span class=\"badge bg-info\" data-toggle=\"tooltip\" title=\"" . Text::_('COM_SECURITYCHECKPRO_USER_DONT_EXISTS') . "\">---</span>";
                                                            
                                        }
                                        ?>
                                                    </td>
                                                    <td>
                                        <?php echo Text::_($this->escape($item->ip_address)); ?>
                                                    </td>
                                                </tr>
                                    <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif;?>        
                            </div>
                        </div>
                    </div>
                </div>
            <!-- End contenido principal -->
            </div>                        
        </div>
</div>    

<input type="hidden" name="option" value="com_securitycheckpro" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="boxchecked" value="1" />
<input type="hidden" name="controller" value="trackactions_logs" />
<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
</form>
