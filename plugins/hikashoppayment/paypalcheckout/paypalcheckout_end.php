<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.0.3
 * @author	hikashop.com
 * @copyright	(C) 2010-2024 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><script src="https://www.paypal.com/sdk/js?<?php echo http_build_query($this->params); ?>" data-partner-attribution-id="<?php echo $this->bncode; ?>"></script>
<div class="hikashop_paypalcheckout_end" id="hikashop_paypalcheckout_end">
<div id="paypal-select-message"><?php echo JText::_('PLEASE_SELECT_A_PAYMENT_METHOD'); ?></div>
<div id="paypal-button-container"></div>
<script>
if(!window.Oby.extractJSON) {
	window.Oby.extractJSON = function(str) {
		var firstOpen, firstClose, candidate;
		firstOpen = str.indexOf('{', firstOpen + 1);
		do {
			firstClose = str.lastIndexOf('}');
			if(firstClose <= firstOpen) {
				return null;
			}
			do {
				candidate = str.substring(firstOpen, firstClose + 1);
				try {
					var res = JSON.parse(candidate);
					return res;
				}
				catch(e) {
					console.log('failed parsing JSON in string below:');
					console.log(candidate);
				}
				firstClose = str.substr(0, firstClose).lastIndexOf('}');
			} while(firstClose > firstOpen);
			firstOpen = str.indexOf('{', firstOpen + 1);
		} while(firstOpen != -1);
		return null;
	};
}
paypal.Buttons(
	{
		style: {
			layout: '<?php echo $this->payment_params->layout; ?>',
			color: '<?php echo $this->payment_params->color; ?>',
			shape: '<?php echo $this->payment_params->shape; ?>',
			label: '<?php echo $this->payment_params->label; ?>',
<?php if($this->payment_params->tagline) { ?>
			tagline: '<?php echo $this->payment_params->tagline; ?>',
<?php } ?>
		},
		createOrder: function(data, actions) {
			return actions.order.create(<?php echo json_encode($this->orderData, JSON_PRETTY_PRINT); ?>);
		},
		onApprove: function (data, actions) {
<?php if(!empty($this->payment_params->capture)) { ?>
			return actions.order.capture().then(function (details) {
<?php } ?>
				window.location.href = "<?php echo $this->notify_url; ?>&paypal_id="+details.id;
<?php if(!empty($this->payment_params->capture)) { ?>
			});
<?php } ?>
		},
		onError: function (err) {

			var errormsg = "<?php echo JText::sprintf('PAYMENT_REQUEST_REFUSED_BY_PAYPAL_CANCEL_URL', $this->cancel_url); ?>";

			var data = window.Oby.extractJSON(err.message);
			if(data) {
				console.log(data);
				for(var i = 0; i < data.body.details.length; i++) {
					var details = data.body.details[i];
					var msg = '';
					if(details.issue)
						msg+='['+details.issue+'] ';
					if(details.description)
						msg+=details.description;
					if(msg.length)
						errormsg+='<br/>'+msg;
				}
			} else {
				console.log(err);
			}
			Joomla.renderMessages({"error":[errormsg]});
			var errDiv = document.getElementById('system-message-container');
			if(errDiv)
				errDiv.scrollIntoView();
		},
<?php
if(!empty($this->payment_params->cancel_url)) {
	?>
		onCancel: function (data) {
			window.location.href = "<?php echo $this->cancel_url; ?>";
		},
	<?php
}
?>
	}
).render('#paypal-button-container');
</script>
<style>
#paypal-button-container {
    text-align: center;
	max-width: 200px;
	margin: auto;
}
div#paypal-select-message {
    text-align: center;
    margin: 5px;
    font-weight: bold;
}
</style>
</div>
