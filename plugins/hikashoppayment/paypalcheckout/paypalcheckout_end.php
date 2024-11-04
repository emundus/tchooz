<?php
/**
 * @package	HikaShop for Joomla!
 * @version	5.1.1
 * @author	hikashop.com
 * @copyright	(C) 2010-2024 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><script src="https://www.paypal.com/sdk/js?<?php echo http_build_query($this->params); ?>" data-partner-attribution-id="<?php echo $this->bncode; ?>" <?php echo $this->extraParams; ?>></script>
<div class="hikashop_paypalcheckout_end" id="hikashop_paypalcheckout_end">
<div id="paypal-select-message"><?php echo JText::sprintf('USE_PAYMENT_BUTTON_OR_CREDIT_CARD_FORM_BELOW', $this->total_amount); ?></div>
<div id="paypal-button-container"></div>
<?php if(!empty($this->payment_params->enable_credit_card)) { ?>
<!-- Advanced credit and debit card payments form -->
<div id="card_container" class='card_container'>
	<form id='my-sample-form'>
		<div class="hk-row-fluid">
			<div class="hkc-md-6">
			<h3 class="card_title"><?php echo JText::_('CREDITCARD_PAYMENT'); ?></h3>
				<div id='card-number' class='card_field'></div>
				<div class="hk-row-fluid">
					<div class="hkc-md-6 expiration_date_position_div">
						<div id='expiration-date' class='card_field'></div>
					</div>
					<div class="hkc-md-6 cvv_position_div">
						<div id='cvv' class='card_field'></div>
					</div>
				</div>
				<label for="card-holder-name"><?php echo JText::_('CREDIT_CARD_OWNER'); ?></label>
				<input type='text' class="form-control" id='card-holder-name' name='card-holder-name' value="<?php echo htmlEntities(@$this->orderData->payer->name->given_name.' '.@$this->orderData->payer->name->surname, ENT_QUOTES); ?>" autocomplete='off' placeholder='<?php echo JText::_('CREDIT_CARD_OWNER'); ?>'/>
			</div>
			<div class="hkc-md-6">
				<h3 class="card_title"><?php echo JText::_('CREDIT_CARD_BILLING_ADDRESS'); ?></h3>
				<div>
					<label for="card-billing-address-street"><?php echo JText::_('STREET'); ?></label>
					<input type='text' class="form-control" id='card-billing-address-street' name='card-holder-name' value="<?php echo htmlEntities((string)@$this->orderData->payer->address->address_line_1, ENT_QUOTES); ?>" name='card-billing-address-street' autocomplete='off' placeholder='<?php echo JText::_('STREET'); ?>'/>
				</div>
				<div>
					<label for="card-billing-address-unit"><?php echo JText::_('STREET'); ?></label>
					<input type='text' class="form-control" id='card-billing-address-unit' name='card-billing-address-unit' name='card-holder-name' value="<?php echo htmlEntities((string)@$this->orderData->payer->address->address_line_2, ENT_QUOTES); ?>" autocomplete='off' placeholder=''/>
				</div>
				<div>
					<label for="card-billing-address-city"><?php echo JText::_('CITY'); ?></label>
					<input type='text' class="form-control" id='card-billing-address-city' name='card-billing-address-city' value="<?php echo htmlEntities((string)@$this->orderData->payer->address->admin_area_2, ENT_QUOTES); ?>" autocomplete='off' placeholder='<?php echo JText::_('CITY'); ?>'/>
				</div>
				<div>
					<label for="card-billing-address-state"><?php echo JText::_('STATE'); ?></label>
					<input type='text' class="form-control" id='card-billing-address-state' name='card-billing-address-state' value="<?php echo htmlEntities((string)@$this->orderData->payer->address->admin_area_1, ENT_QUOTES); ?>" autocomplete='off' placeholder='<?php echo JText::_('STATE'); ?>'/>
				</div>
				<div class="hk-row-fluid">
					<div class='hkc-md-6'>
						<label for="card-billing-address-zip"><?php echo JText::_('POST_CODE'); ?></label>
						<input type='text' class="form-control" id='card-billing-address-zip' name='card-billing-address-zip' value="<?php echo htmlEntities((string)@$this->orderData->payer->address->postal_code, ENT_QUOTES); ?>" autocomplete='off' placeholder='<?php echo JText::_('POST_CODE'); ?>'/>
					</div>
					<div class='hkc-md-6'>
						<label for="card-billing-address-country"><?php echo JText::_('COUNTRY'); ?></label>
						<input type='text' class="form-control" id='card-billing-address-country' name='card-billing-address-country' value="<?php echo htmlEntities((string)@$this->orderData->payer->address->country_code, ENT_QUOTES); ?>" pattern="[A-Z]{2}" autocomplete='off' placeholder='<?php echo JText::_('COUNTRY'); ?>' />
					</div>
				</div>
			</div>
		</div>
		<button value='submit' id='paypal_pay_button' class='paypal_pay_button hikabtn hikabtn-success'><?php echo JText::_('PAY_NOW'); ?>  <i class="fa fa-credit-card"></i></button>
	</form>
</div>
<?php } ?>
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
			callbackURL = "<?php echo $this->notify_url; ?>&createorder=1";
			return fetch(callbackURL, {method: "POST"})
			.then(response => response.json())
			.then(resp => {
				console.log(callbackURL);
				console.log(resp);

				if(resp && resp.error) {
					Joomla.renderMessages({"error":[resp.errorMessage]});
					var errDiv = document.getElementById('system-message-container');
					if(errDiv)
						errDiv.scrollIntoView();
					return false;
				} else {
					return resp.id;
				}
			});
		},
		onApprove: function (data, actions) {
			callbackURL = "<?php echo $this->notify_url; ?>&onapprove=1&orderID=" + data.orderID;
			return fetch(callbackURL, {method: "POST"})
			.then(response => response.json())
			.then(resp => {
				console.log(callbackURL);
				console.log(resp);

				if(resp && resp.error) {
					Joomla.renderMessages({"error":[resp.errorMessage]});
					var errDiv = document.getElementById('system-message-container');
					if(errDiv)
						errDiv.scrollIntoView();
					return false;
				} else if(resp.errorMessage == 'restart') {
					return actions.restart();
				}
				document.getElementById('hikashop_paypalcheckout_end').innerHTML = '<?php echo str_replace(array("\n", "\r"), '', hikashop_display(JText::_('THANK_YOU_FOR_PURCHASE', true), 'success', true)); ?>';
				window.location.href = "<?php echo $this->notify_url; ?>&paypal_id="+resp.id;
			});
		},
		onError: function (err) {

			var errormsg = "<?php echo str_replace(array("\n", "\r"), '', str_replace('"','\"',JText::sprintf('PAYMENT_REQUEST_REFUSED_BY_PAYPAL_CANCEL_URL', $this->cancel_url))); ?>";

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

<?php if(!empty($this->payment_params->enable_credit_card)) { ?>
if (paypal.HostedFields.isEligible()) {
	paypal.HostedFields.render({
		createOrder: function () {
			callbackURL = "<?php echo $this->notify_url; ?>&createorder=1";
			return fetch(callbackURL, {method: "POST"})
			.then(response => response.json())
			.then(resp => {
				console.log(callbackURL);
				console.log(resp);

				if(resp && resp.error) {
					Joomla.renderMessages({"error":[resp.errorMessage]});
					var errDiv = document.getElementById('system-message-container');
					if(errDiv)
						errDiv.scrollIntoView();
					return false;
				} else {
					return resp.id;
				}
			});
		},
		styles: {
			'input': {
				'padding': '0.6rem 1rem',
				'font-size': '1rem',
				'font-weight': '400',
				'line-height': '1.5',
				'-webkit-transition': 'border-color 0.15s ease-in-out, -webkit-box-shadow 0.15s ease-in-out',
				'transition': 'border-color 0.15s ease-in-out, -webkit-box-shadow 0.15s ease-in-out',
				'transition': 'border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out',
				'transition': 'border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out, -webkit-box-shadow 0.15s ease-in-out',
			},
			':focus': {
				'color': 'black'
			},
			'.valid': {
				'color': 'green',
			},
			'.invalid': {
				'color': 'red'
			}
		},
		fields: {
			number: {
				selector: '#card-number',
				placeholder: '<?php echo str_replace(array("\n", "\r"), '', JText::_('CREDIT_CARD_NUMBER', true)); ?>'
			},
			cvv: {
				selector: '#cvv',
				placeholder: 'cvv'
			},
			expirationDate: {
				selector: '#expiration-date',
				placeholder: 'MM/YY'
			}
		}
	}).then(function (hf) {
		document.getElementById('my-sample-form').addEventListener('submit', (event) => {
			event.preventDefault();
			document.getElementById('paypal_pay_button').disabled = true;
			document.getElementById('card_container').style.opacity = "0.5";
			hf.submit({
				cardholderName: document.getElementById('card-holder-name').value,
				billingAddress: {
					streetAddress: document.getElementById('card-billing-address-street').value, // address_line_1 - street
					extendedAddress: document.getElementById('card-billing-address-unit').value, // address_line_2 - unit
					region: document.getElementById('card-billing-address-state').value, // admin_area_1 - state
					locality: document.getElementById('card-billing-address-city').value, // admin_area_2 - town / city
					postalCode: document.getElementById('card-billing-address-zip').value, // postal_code - postal_code
					countryCodeAlpha2: document.getElementById('card-billing-address-country').value // country_code - country
				}
<?php if(!empty($this->enable_3dsecure)) { ?>
				,
				contingencies: ['<?php echo $this->enable_3dsecure; ?>']
<?php } ?>
			}).then(function (payload) {
				callbackURL = "<?php echo $this->notify_url; ?>&onapprove=1&orderID=" + payload.orderID;
				return fetch(callbackURL, {method: "POST"})
				.then(response => response.json())
				.then(resp => {
					console.log(callbackURL);
					console.log(resp);

					if(resp && resp.error) {
						Joomla.renderMessages({"error":[resp.errorMessage]});
						var errDiv = document.getElementById('system-message-container');
						if(errDiv)
							errDiv.scrollIntoView();

						if(resp.errorCode == 'stop_and_retry') {
							document.getElementById('paypal_pay_button').disabled = false;
							document.getElementById('card_container').style.opacity = "1";
						}
						return false;
					}
					var mainArea = document.getElementById('hikashop_paypalcheckout_end');
					mainArea.innerHTML = '<?php echo str_replace(array("\n", "\r"), '', hikashop_display(JText::_('THANK_YOU_FOR_PURCHASE', true), 'success', true)); ?>';
					mainArea.scrollIntoView();
					window.location.href = "<?php echo $this->notify_url; ?>&paypal_id="+resp.id;
				});
			}).catch(error => {
				console.log(error);
				Joomla.renderMessages({"error":['<?php echo str_replace(array("\n", "\r"), '', JText::_('PLEASE_FILL_IN_ALL_THE_FIELDS', true)); ?>']});
				var errDiv = document.getElementById('system-message-container');
				if(errDiv)
					errDiv.scrollIntoView();
				document.getElementById('paypal_pay_button').disabled = false;
				document.getElementById('card_container').style.opacity = "1";
			});
		});
	});
} else {
	document.querySelector('.card_container').style.display = 'none'; // hides the advanced credit and debit card payments fields if seller isn't eligible
	document.getElementById('paypal-select-message').innerHTML = '<?php echo str_replace(array("\n", "\r"), '', str_replace("'","\'",JText::sprintf('USE_PAYMENT_BUTTON_BELOW', $this->total_amount))); ?>';
}
<?php } ?>
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
#card-number,
#expiration-date,
#cvv {
	height: 40px;
	width: 100%;
	border: 1px solid hsl(210, 14%, 83%);
	border-radius: 2px;
	background-color: white;
	margin-top: 20px;
}
.card_container {
    background-color: #f2f2f2;
    color: #000;
    padding: 5px 20px 15px 20px;
    border: 1px solid lightgrey;
    border-radius: 3px;
}
.paypal_pay_button {
	width: 100%;
	margin: 3px;
	font-weight: bold;
	font-size: 1.6em;
}
.card_container label{
	display:block;
	margin-top: 20px;
    padding-left: 5px;
}
.hkc-md-6.expiration_date_position_div{
    padding-left: 0;
}
.hkc-md-6.cvv_position_div{
    padding-right: 0;
}
</style>
</div>
