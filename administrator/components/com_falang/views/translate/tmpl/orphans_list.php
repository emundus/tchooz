<?php
/**
 * @package     Falang for Joomla!
 * @author      Stéphane Bouey <stephane.bouey@faboba.com> - http://www.faboba.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @copyright   Copyright (C) 2010-2023. Faboba.com All rights reserved.
 */

// No direct access to this file
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

$user = Factory::getUser();
$db = Factory::getDBO();

$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
$wa->useStyle('searchtools');

//manage filter bar
$filterOptions = '<div id="filter-bar" class="js-stools-container-bar">';
$filterOptions .= ' <div class="btn-toolbar">';
if (isset($this->filterlist) && count($this->filterlist)>0){
    foreach ($this->filterlist as $fl){
        if (is_array($fl) && !empty($fl['position']) && $fl['position'] != 'sidebar')		$filterOptions .= "<div class='filter-search-bar btn-group'>".$fl["html"]."</div>";
   }
}
$filterOptions .= '   <div class="filter-search-actions btn-group">';
$filterOptions .= '     <div class="js-stools-field-list">'.$this->clist.'</div>';
$filterOptions .= '     <div class="js-stools-field-list">'.$this->langlist.'</div>';
$filterOptions .= '   </div>';
$filterOptions .= ' </div>';
$filterOptions .= '</div>';


?>
<form action="index.php" method="post" name="adminForm" id="adminForm">
    <?php if(!empty( $this->sidebar)): ?>
    <div id="j-sidebar-container" class="col-md-2">
        <?php echo $this->sidebar; ?>
    </div>
    <div id="j-main-container" class="j-main-container col-md-10">
        <?php else : ?>
        <div id="j-main-container" class="j-main-container col-md-12">

            <?php endif;?>

            <?php echo $filterOptions; ?>

            <div class="clearfix"> </div>

  <table class="table itemList table-striped" >
  <thead>
    <tr>
        <th width="20"><input type="checkbox" name="checkall-toggle" value="" onclick="Joomla.checkAll(this);" /></th>
      <th class="title" width="20%" align="left"  nowrap="nowrap"><?php echo Text::_('COM_FALANG_TRANSLATE_TITLE_TITLE');?></th>
      <th width="10%" align="left" nowrap="nowrap"><?php echo Text::_('COM_FALANG_TRANSLATE_TITLE_LANGUAGE');?></th>
      <th width="20%" align="left" nowrap="nowrap"><?php echo Text::_('COM_FALANG_TRANSLATE_TITLE_TRANSLATION');?></th>
      <th width="15%" align="left" nowrap="nowrap"><?php echo Text::_('COM_FALANG_TRANSLATE_TITLE_DATECHANGED');?></th>
      <th width="15%" nowrap="nowrap" align="center"><?php echo Text::_('COM_FALANG_TRANSLATE_TITLE_STATE');?></th>
      <th align="center" nowrap="nowrap"><?php echo Text::_('COM_FALANG_TRANSLATE_TITLE_PUBLISHED');?></th>
    </tr>
  </thead>
  <tfoot>
	<tr>
		<td align="left" colspan="7"><?php echo $this->pageNav->getListFooter(); ?></td>
	</tr>
  </tfoot>
	<?php
	if( !isset($this->catid) || $this->catid == ""  ) {
		?>
	<tbody>
		<tr><td colspan="8"><p><?php echo Text::_('COM_FALANG_NOELEMENT_SELECTED');?></p></td></tr>
    </tbody></table>
		<?php
	}
	else {
		?>
	<tbody>
    <?php
    $k=0;
    $i=0;
    foreach ($this->rows as $row ) {
				?>
    <tr class="<?php echo "row$k"; ?>">
      <td width="20">
        <?php		if ($row->checked_out && $row->checked_out != $user->id) { ?>
        &nbsp;
        <?php		} else { ?>
        <input type="checkbox" id="cb<?php echo $i;?>" name="cid[]" value="<?php echo $row->jfc_id."|".$row->jfc_refid."|".$row->language_id; ?>" onclick="Joomla.isChecked(this.checked);" />
        <?php		} ?>
      </td>
      <td>

      	<a href="#detail" onclick="return listItemTask('cb<?php echo $i;?>','translate.orphandetail')"><?php echo $row->title; ?></a>
			</td>
      <td nowrap><?php echo $row->language ? $row->language :  Text::_('COM_FALANG_NOTRANSLATIONYET'); ?></td>
      <td><?php echo $row->titleTranslation ? $row->titleTranslation : '&nbsp;'; ?></td>
	  <td><?php echo $row->lastchanged ? HTMLHelper::_('date', $row->lastchanged, Text::_('DATE_FORMAT_LC2')):"" ;?></td>
				<?php
				switch( $row->state ) {
					case 1:
						$img = 'status_g.png';
						break;
					case 0:
						$img = 'status_y.png';
						break;
					case -1:
					default:
						$img = 'status_r.png';
						break;
				}
				?>
      <td align="center"><img src="components/com_falang/assets/images/<?php echo $img;?>" width="12" height="12" border="0" alt="" /></td>
				<?php
                $href='';
                if( $row->state>=0 ) {
                    $href = HTMLHelper::_('jgrid.published', $row->published, $i, 'translate.');
                }
                else {
                    if (FALANG_J30) {
                        $href = HTMLHelper::_('jgrid.published', $row->published , $i, 'translate.',false);
                    } else {
                        $href = '<a class="jgrid">';
                        $href .= '<span class="state expired"><span class="text">'.Text::_('COM_FALANG_EXPIRED').'</span></span>';
                        $href .= '</a>';
                    }
                }
				?>
      <td align="center"><?php echo $href;?></td>
				<?php
				$k = 1 - $k;
				$i++;
				?>
	</tr>
    <?php }?>
    </tbody>
	</table>
<br />
<table cellspacing="0" cellpadding="4" border="0" align="center">
  <tr align="center">
    <td> <img src="components/com_falang/assets/images/status_g.png" width="12" height="12" border=0 alt="<?php echo Text::_('COM_FALANG_STATE_OK');?>" />
    </td>
    <td> <?php echo Text::_('COM_FALANG_TRANSLATION_UPTODATE');?> |</td>
    <td> <img src="components/com_falang/assets/images/status_y.png" width="12" height="12" border=0 alt="<?php echo Text::_('COM_FALANG_STATE_CHANGED');?>" />
    </td>
    <td> <?php echo Text::_('COM_FALANG_TRANSLATION_INCOMPLETE');?> |</td>
    <td> <img src="components/com_falang/assets/images/status_r.png" width="12" height="12" border=0 alt="<?php echo Text::_('COM_FALANG_STATE_NOTEXISTING');?>" />
    </td>
    <td> <?php echo Text::_('COM_FALANG_TRANSLATION_NOT_EXISTING');?></td>
  </tr>
  <tr align="center">
      <td>
          <?php if (FALANG_J30) { ?>
          <i class="icon-publish"></i>
          <?php } else {
          echo HTMLHelper::_('image.administrator', 'admin/tick.png', NULL, NULL, NULL, Text::_('COM_FALANG_TRANSLATION_PUBLISHED'),array('style' => 'width:12px;height:12px'));
      } ?>
      </td>
      <td> <?php echo Text::_('COM_FALANG_TRANSLATION_PUBLISHED');?>  |</td>
      <td>
          <?php if (FALANG_J30) { ?>
          <i class="icon-unpublish"></i>
          <?php } else {
          echo HTMLHelper::_('image.administrator', 'admin/publish_x.png', NULL, NULL, NULL, Text::_('COM_FALANG_TRANSLATION_NOT_PUBLISHED'),array('style' => 'width:12px;height:12px'));
      } ?>
      </td>
      <td> <?php echo Text::_('COM_FALANG_TRANSLATION_NOT_PUBLISHED');?></td>
      <td>
          <?php if (FALANG_J30) { ?>
          <i class="icon-unpublish disabled"></i>
          <?php } else {
          echo HTMLHelper::_('image.administrator', 'admin/publish_r.png', NULL, NULL, NULL, Text::_('COM_FALANG_STATE_TOGGLE'),array('style' => 'width:12px;height:12px'));
      } ?>
      </td>
      <td> <?php echo Text::_('COM_FALANG_STATE_TOGGLE');?></td>
  </tr>
</table>
<?php } ?>

	<input type="hidden" name="option" value="com_falang" />
	<input type="hidden" name="task" value="translate.orphans" />
	<input type="hidden" name="boxchecked" value="0" />
	<?php echo HTMLHelper::_( 'form.token' ); ?>
</form>
