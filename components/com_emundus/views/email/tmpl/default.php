﻿<?php
defined('_JEXEC') or die('Restricted access'); 

$itemid 	= JFactory::getApplication()->input->get('Itemid', null, 'GET', 'none',0);
$view 		= JFactory::getApplication()->input->get('view', null, 'GET', 'none',0);
$task 		= JFactory::getApplication()->input->get('task', null, 'GET', 'none',0);
$tmpl 		= JFactory::getApplication()->input->get('tmpl', null, 'GET', 'none',0);
 
jimport( 'joomla.utilities.date' );
JHTML::_('behavior.tooltip'); 
JHTML::_('behavior.modal');
?>
<form id="adminForm" name="adminForm" onSubmit="return OnSubmitForm();" method="POST" >
		<div class="emundusraw em-container-email">
			<?php echo $this->email; ?>
		</div>
		<input type="hidden" name="task" value=""/>
</form>
<script type="text/javascript">
function OnSubmitForm() 
{ 
	if(typeof document.pressed !== "undefined") 
	{ 
		document.adminForm.task.value = "";
		var button_name=document.pressed.split("|"); 
	    //alert(button_name[0]);
		switch(button_name[0]) {
            case 'expert':
            break;
			case 'applicant_email': 
				document.adminForm.task.value = "applicantemail";
				document.adminForm.action ="index.php?option=com_emundus&view=files&controller=files&Itemid=<?php echo $itemid; ?>&task=applicantemail";
			break;
			case 'group_email': 
				document.adminForm.task.value = "groupmail";
				document.adminForm.action ="index.php?option=com_emundus&view=files&controller=files&Itemid=<?php echo $itemid; ?>&task=groupmail";
			break;
			default: return false;
		}
		return true;
	}
}
</script>