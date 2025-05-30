<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.5
 * @author	hikashop.com
 * @copyright	(C) 2010-2025 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><div class="iframedoc" id="iframedoc"></div>
<form action="<?php echo hikashop_completeLink('report'); ?>" method="post"  name="adminForm" id="adminForm">
<div class="hk-row-fluid">
	<div class="hkc-md-6 hika_j4_search">
<?php
	echo $this->loadHkLayout('search', array());
?>
	</div>
	<div class="hkc-md-6 hikashop_listing_filters">
	</div>
</div>
<?php 
	echo $this->loadHkLayout('columns', array()); 
?>
	<table id="hikashop_report_listing" class="adminlist table table-striped table-hover" cellpadding="1">
		<thead>
			<tr>
				<th class="title titlenum">
					<?php echo JText::_( 'HIKA_NUM' );?>
				</th>
				<th class="title titlebox">
					<input type="checkbox" name="toggle" value="" onclick="hikashop.checkAll(this);" />
				</th>
				<th class="title">
					<?php echo JHTML::_('grid.sort', JText::_('HIKA_TITLE'), 'a.widget_name', $this->pageInfo->filter->order->dir,$this->pageInfo->filter->order->value ); ?>
				</th>
				<?php  ?>
				<th class="title titleorder">
				<?php echo JHTML::_('grid.sort', JText::_( 'HIKA_ORDER' ), 'a.widget_ordering',$this->pageInfo->filter->order->dir, $this->pageInfo->filter->order->value ); ?>
					<?php if ($this->order->ordering) {
						$keys = array_keys($this->rows);
						$rows_nb = end($keys);
						$href = "javascript:saveorder(".$rows_nb.", 'saveorder')";
						?><a href="<?php echo $href; ?>" rel="tooltip" class="saveorder btn btn-sm btn-secondary float-end" title="Save Order">
							<button class="button-apply btn btn-success" type="button">
<!--							<span class="icon-apply" aria-hidden="true"></span> -->
								<i class="fas fa-save"></i>
							</button>
						</a><?php
					} ?>
				</th>
				<th class="title titletoggle">
					<?php echo JHTML::_('grid.sort',   JText::_('HIKA_PUBLISHED'), 'a.widget_published', $this->pageInfo->filter->order->dir, $this->pageInfo->filter->order->value ); ?>
				</th>
				<th class="title">
					<?php echo JHTML::_('grid.sort',   JText::_( 'ID' ), 'a.widget_id', $this->pageInfo->filter->order->dir, $this->pageInfo->filter->order->value ); ?>
				</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="8">
					<?php echo $this->pagination->getListFooter(); ?>
				</td>
			</tr>
		</tfoot>
		<tbody>
			<?php
				$k = 0;
				$a = count($this->rows);
				if($a){
					for($i = 0;$i<$a;$i++){
						$row =& $this->rows[$i];
						$publishedid = 'widget_published-'.$row->widget_id;
				?>
					<tr class="<?php echo "row$k"; ?>">
						<td class="hk_center">
						<?php echo $this->pagination->getRowOffset($i); ?>
						</td>
						<td class="hk_center">
							<?php echo JHTML::_('grid.id', $i, $row->widget_id ); ?>
						</td>
						<td>
							<?php if($this->viewAccess){ ?>
								<a href="<?php echo hikashop_completeLink('report&task=edit&cid[]='.$row->widget_id); ?>">
							<?php } ?>
									<?php echo $row->widget_name; ?>
							<?php if($this->viewAccess){ ?>
								</a>
							<?php } ?>
						</td>

						<td class="order column_move">
							<?php if($this->manage){ ?>
								<span class="move_icon"><img src="../media/com_hikashop/images/move.png" alt=""></span>
								<input type="text" name="order[]" size="5" <?php if(!$this->order->ordering) echo 'disabled="disabled"'?> value="<?php echo $row->widget_ordering; ?>" class="text_area" style="text-align: center" />
							<?php }else{ echo $row->widget_ordering; } ?>
						</td>
						<td class="hk_center">
							<?php if($this->manage){ ?>
								<span id="<?php echo $publishedid ?>" class="spanloading"><?php echo $this->toggleClass->toggle($publishedid,(int) $row->widget_published,'report') ?></span>
							<?php }else{ echo $this->toggleClass->display('activate',$row->widget_published); } ?>
						</td>
						<td width="1%" class="hk_center">
							<?php echo $row->widget_id; ?>
						</td>
					</tr>
				<?php
						$k = 1-$k;
					}
				}
			?>
		</tbody>
	</table>
	<input type="hidden" name="option" value="<?php echo HIKASHOP_COMPONENT; ?>" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="ctrl" value="<?php echo hikaInput::get()->getCmd('ctrl'); ?>" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="filter_order" value="<?php echo $this->pageInfo->filter->order->value; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $this->pageInfo->filter->order->dir; ?>" />
	<?php echo JHTML::_( 'form.token' ); ?>
</form>
