<?php
/**
 * @package	HikaShop for Joomla!
 * @version	4.7.3
 * @author	hikashop.com
 * @copyright	(C) 2010-2023 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');
?><?php
$button_style = 'style="height: 30px;width: 130px;"';
$config = hikashop_config();
$delay = (int)$config->get('switcher_cookie_retaining_period', 31557600);
$notice = JText::_('HIKA_NOTICE_COLUMNS', true);
?>
<!-- Dropdown columns selector -->
<div class="hkdropdown column_container">	
	<button type="button" data-toggle="hkdropdown" class="btn btn-primary button_column" aria-haspopup="true" aria-expanded="false" <?php echo $button_style; ?>>
		<span class="column_number"></span>
		<span class="columns_separator"> / </span>
		<span class="column_number_total"></span>
		<span class="hkdropdown-text column_text" style="margin: 0 5px;">
			<?php echo JText::_( 'HIKA_SELECT_COLUMNS', true ); ?>
		</span>
		<span class="caret"></span>
	</button>
	<!-- Dropdown columns settings [fill by JS] -->
	<ul class="hika_columns_select hkdropdown-menu"></ul>
</div>

<script type="text/javascript">
if(!window.localPage) window.localPage = {};

function dropdownFill () {
	var elems = document.querySelectorAll('table.adminlist .title');

	var drop_html ='';
	var ref = 1;
	var def_nb = 0;
	var unDisplayed = 0

	const header_name = [];
	for (i = 0; i < elems.length; i++) {
		var status = 'checked';
		if (elems[i].classList.contains('default')) {
			cssProcess(ref,'hide');
			status = '';
			def_nb++;
		}
		header_name[i] = elems[i].textContent.trim();
		if(header_name[i] == '') {
			ref++;
			unDisplayed++;
			continue;
		}
		drop_html += '<li>'+
			'<label href="#" onclick="window.localPage.actionColumns(event, ' + ref + ', \'click\'); return false;">' +
				'<input class="form-check-input me-1" id="columnSelect_' + ref + '" type="checkbox" name="columnSelect" value="' + header_name[i] + '" '+status+'>' +
				'<span class="hika_columns_name">' + header_name[i] + '</span>' +
			'</label>'+
		'</li>';

		ref++;
	}

	var container = document.querySelector('.hika_columns_select');
	container.innerHTML = drop_html;

	if (ref > 30)
		container.style.columns = "50px 2";

	var cookies = cookiesCheck();

	var tot_cookies = '';
	if (cookies != '') {
		tot_cookies = get_total(cookies);
		if (tot_cookies != elems.length) {
			order_columns_notice('');
			resetCookies(cookies);
		}
	}
	elems_nb = elems.length - unDisplayed;
	updateNumbers(elems_nb - def_nb,elems_nb,'');
}

window.localPage.actionColumns = function (event, rank, action) {
	if (event != '') {
		event = event || window.event;
		event.preventDefault();
		event.stopPropagation();
	}
	cssProcess(rank,action);

	checkboxOperation(rank,action);

	var operator = classOperation(rank,action);

	var elems = document.querySelectorAll('table.adminlist .title');

	var unDisplayed = 0;
	for (i = 0; i < elems.length; i++) {
		if(elems[i].textContent.trim() == '')
			unDisplayed++;
	}

	updateNumbers('','',operator);

	if (action == 'click')
		cookiesSave(rank);
}
function cookiesCheck() {
	var list_code = getListKey();

	let name = "cookie_" + list_code + "=";
	let decodedCookie = decodeURIComponent(document.cookie);
	let cookiesAll = decodedCookie.split(';');

	for(let i = 0; i <cookiesAll.length; i++) {
		let cookie = cookiesAll[i];
		while (cookie.charAt(0) == ' ') {
		  cookie = cookie.substring(1);
		}
		if (cookie.indexOf(name) == 0) {
			return cookie.substring(name.length, cookie.length);
		}
	}

	return "";
}
function order_columns_notice() {
	var errormsg = '<?php echo $notice; ?>';

	Joomla.renderMessages({"warning":[errormsg]});
}
function getListKey() {
	var elems = document.querySelector('#hikashop_main_content form table.adminlist');
	var table_id = elems.id;

	var list_code = table_id.substring(9, 12);

	let name_array = table_id. split("_");
	var list_code = name_array[1];

	if (list_code == 'plugins') {
		var form_elem = document.querySelector('#hikashop_main_content form');
		var form_action = form_elem.action;
		let plg_type_array = form_action. split("=");
		list_code = plg_type_array[3];
	}
	return list_code;
}
function cssProcess (rank, action) {
	var css_value_hide = 'display:none;';
	var css_value_hidden = 'display:table-cell;';

	var css_hide = 'table.adminlist tbody td:nth-child(' + rank + '), ' +
	'table.adminlist thead th:nth-child(' + rank + ') {' + css_value_hide + '}';
	var css_unhidden = 'table.adminlist tbody td:nth-child(' + rank + '), ' +
	'table.adminlist thead th:nth-child(' + rank + ') {' + css_value_hidden + '}';

    head = document.head || document.getElementsByTagName('head')[0],
    style = document.createElement('style');
	head.appendChild(style);

	var css = '';
	var elems = document.querySelectorAll('table.adminlist .title');
	var elems_targeted = elems[parseInt(rank) - 1];

	if (action == 'hide')
		css = css_hide;

	if (action == 'display')
		css = css_unhidden;

	if (action == 'click') {
		if(elems_targeted.classList.contains('columns_hide')) {
			css = css_unhidden;
		}
		else {
			css = css_hide;
		}
	}

	style.type = 'text/css';
	if (style.styleSheet) {
		style.styleSheet.cssText = css;
	} else {
		style.appendChild(document.createTextNode(css));
	}
}
function checkboxOperation(rank,action) {
	var string = '';
	var elems = document.querySelectorAll('table.adminlist .title');
	var checkbox = document.getElementById('columnSelect_' + parseInt(rank));
	var elems_targeted = elems[parseInt(rank) - 1];
	var status = '';
	if(action == 'hide')
		status = false;
	if(action == 'display') {
		status = true;
	}
	if(action == 'click') {
		if (elems_targeted.classList.contains('columns_hide'))
			status = true;
		else 
			status = false;
	}
	if (checkbox !== null)
		checkbox.checked = status;
}
function classOperation(rank,action) {
	if(!isNaN(rank)) {
		var operator = '';
		var elems = document.querySelectorAll('table.adminlist .title');
		var elems_targeted = elems[parseInt(rank) - 1];

 		if (elems_targeted !== undefined) {
			if(action == 'hide') {
				if(!elems_targeted.classList.contains('columns_hide'))
					elems_targeted.classList.add('columns_hide');
				operator = '-';
			}
			if(action == 'display') {
				if(elems_targeted.classList.contains('columns_hide'))
					elems_targeted.classList.remove('columns_hide');

				operator = '+';
			}
			if(action == 'click') {
				if(elems_targeted.classList.contains('columns_hide')) {
					elems_targeted.classList.remove('columns_hide');
					operator = '+';
				}
				else {
					elems_targeted.classList.add('columns_hide');
					operator = '-';
				}
			}
		}
		return operator;
	}
}
function updateNumbers (nb,nb_tot,operation) {
	var number_tot = document.querySelector('.column_number_total');
	var number = document.querySelector('.column_number');

	if (nb == '' || nb_tot == '') {
		var nb_str_tot = number_tot.textContent;
		var nb_str = number.textContent;

		nb_tot = parseInt(nb_str_tot);
		nb = parseInt(nb_str);
	}
	if (operation == '-') {nb = nb - 1;}
	if (operation == '+') {nb = nb + 1;}

	number_tot.innerHTML = nb_tot;
	number.innerHTML = nb;
}
function cookiesSave(rank) {
	var rank_ref = parseInt(rank);

	var old_columnRanks = cookiesCheck();
	if (old_columnRanks.includes('tot_') || old_columnRanks == '')
		old_columnRanks = resetCookies(old_columnRanks);

	var new_cookies = '';
	var drift = 0;

	let old_cookies_array = old_columnRanks.split('/');
	for(let i = 0; i < old_cookies_array.length; i++) {
		if (old_cookies_array[i] == '')
			continue;

		if (old_cookies_array[i].includes('=')) {
			let cookies_def_status = old_cookies_array[i].split('=');
			var cookies_status = cookies_def_status[0];
			let cookies_def_array = old_cookies_array[i].split(':');
			var cookies_ref = cookies_def_array[1];
			var cookies_alias = cookies_def_array[0].slice(2,cookies_def_array[0].length);
		}
		else {
			let cookies_def_array = old_cookies_array[i].split(':');
			var cookies_ref = cookies_def_array[1];
			var cookies_status = cookies_def_array[0];
			var cookies_alias = '';
		}
		if (cookies_ref == rank_ref) {
			if (cookies_status == 'h') cookies_status = 'd';
			else cookies_status = 'h';

			if (cookies_alias != '')
				new_cookies += '/' + cookies_status + '='+ cookies_alias + ':' + cookies_ref;
			else
				new_cookies += '/' + cookies_status + ':' + cookies_ref;
		}
		else {
			new_cookies += '/' + old_cookies_array[i];
		}
	}
	var list_code = getListKey();
	new_cookies = new_cookies.slice(1);
	window.hikashop.setCookie("cookie_" + list_code,new_cookies,<?php echo $delay; ?>);
}

function resetCookies(old_columnRanks) {
	var elems = document.querySelectorAll('table.adminlist .title');
	var new_cookies = '';

	if (old_columnRanks != '') {
		let cookiesRef = old_columnRanks.split('/');
		for(i = 0; i <cookiesRef.length; i++) {
			if (cookiesRef[i] == '' || cookiesRef[i].includes('tot_'))
				continue;

			if (cookiesRef[i].includes(':')) {
				let cookies_def_array = cookiesRef[i].split(':');
				var cookies_ref = parseInt(cookies_def_array[1]);
			}
			else
				var cookies_ref = cookiesRef[i];

			if (cookies_ref >= elems.length)
				continue;

			if (cookiesRef[i].includes('=') || cookiesRef[i].includes('d:') || cookiesRef[i].includes('h:'))
				new_cookies += '/' + cookiesRef[i];
			else 
				new_cookies += '/h:' + cookiesRef[i];
		}
	}
	var ref = 0;
	for (let i = 0; i < elems.length; i++) {
		ref++;
		if (elems[i].classList.contains('default')) {
			var alias = elems[i].getAttribute('data-alias');
			var new_add = '/h=' + alias + ':' + ref;
		}
		else 
			var new_add = '/d:' + ref;

		if (new_cookies.includes(':' + ref))
			continue;
		else
			new_cookies += new_add;
	}
	var list_code = getListKey();
	new_cookies = new_cookies.slice(1);
	window.hikashop.setCookie("cookie_" + list_code,new_cookies,<?php echo $delay; ?>);
	return new_cookies;
}
function get_total(cookies) {
	var tot_number = 0;
	let cookies_array = cookies.split('/');
	if (cookies.includes('tot_')) {
		for(let i = 0; i < cookies_array.length; i++) {
			if(cookies_array[i].includes('tot_'))
				tot_number = parseInt(cookies_array[i].slice(4,6));
		}
	}
	else {
		var tot_number = cookies_array.length;
	}
	return tot_number;
}

function afterDisplay() {
	var elems = document.querySelectorAll('table.adminlist .title');
	var unDisplayed = 0;
	var uncheckedNb = 0;

	for (i = 0; i < elems.length; i++) {
		if(elems[i].textContent.trim() == '')
			unDisplayed++;
	}
	var elems_nb = elems.length - unDisplayed;

	var fromCookie =  cookiesCheck();

	if (fromCookie != '') {
		if(fromCookie.includes('tot_'))
		fromCookie = resetCookies(fromCookie);

		var action = '';
		let cookiesAll = fromCookie.split('/');
		for(let i = 0; i <cookiesAll.length; i++) {
			if (cookiesAll[i] == '')
				continue;

			var separator = ':';
			if(cookiesAll[i].includes('='))
				separator = '=';

			let cookies_def_array = cookiesAll[i].split(':');
			var cookies_ref = parseInt(cookies_def_array[1]);

			if (cookiesAll[i].includes('d'+separator))
				action = 'display';
			else {
				action = 'hide';
				uncheckedNb++;
			}
			window.localPage.actionColumns('',cookies_ref,action);
		}
		updateNumbers(elems_nb - uncheckedNb, elems_nb, '');
	}
}
window.hikashop.ready(function(){
	dropdownFill();
	setTimeout(function(){afterDisplay();}, 100);
});
</script>
