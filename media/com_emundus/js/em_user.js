/**
 * Created by yoan on 16/09/14.
 */

var lastIndex = 0;
var loading;

function reloadActions(view) {
	let multi = document.querySelectorAll('.em-check:checked').length;
	let url = window.location.origin+'/index.php?option=com_emundus&view=files&layout=menuactions&format=raw&Itemid=' + itemId + '&display=inline&multi=' + multi;

	fetch(url)
		.then(response => response.text())
		.then(data => {
			let navbar = $('.navbar.navbar-inverse');
			navbar.empty();
			navbar.append(data);
		});
}

function clearchosen(target){
	$(target)[0].sumo.unSelectAll();
}

function getUserCheck() {
	var id = parseInt($('.modal-body').attr('act-id'));
	if ($('#em-check-all-all').is(':checked')) {
		var checkInput = 'all';
	} else {
		var i = 0;
		var myJSONObject = '{';
		$('.em-check:checked').each(function () {
			i = i + 1;
			myJSONObject += '"' + i + '"' + ':"' + $(this).attr('id').split('_')[0] + '",';
		});
		myJSONObject = myJSONObject.substr(0, myJSONObject.length - 1);
		myJSONObject += '}';
		if (myJSONObject.length == 2) {
			alert('SELECT_FILES');
			return;
		} else {
			checkInput = myJSONObject;
		}

	}
	return checkInput;
}

function formCheck(id) {
	let check = true;
	let field = document.querySelector('#' + id);
	let form_group = field.parentElement;
	let help_block = document.querySelector('.em-addUser-detail-info-'+id+' .help-block');
	if(id == 'login') {
		help_block = document.querySelector('.em-addUser-detail-info-id .help-block');
	}

	field.style.border = null;

	if (id === 'login') {
		let same_as_email = document.querySelector('#same_login_email');
		if (same_as_email && same_as_email.checked) {
			check = false;
		}
	}

	if (field.value && field.value.trim().length === 0 && check) {
		if(form_group) {
			form_group.classList.add('has-error');
		}
		field.style.border = '1px solid var(--red-500)';

		if(help_block) {
			help_block.remove();
			field.insertAdjacentHTML('afterend', '<span class="help-block">' + Joomla.JText._('COM_EMUNDUS_USERS_ERROR_NOT_A_VALID_LOGIN_MUST_NOT_CONTAIN_SPECIAL_CHARACTER') + '</span>');
		}

		return false;
	}
	else
	{
		let remail = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-z\-0-9]+\.)+[a-z]{2,}))$/;
		let re = /^[0-9a-zA-Z\_\@\-\.\+]+$/;

		if (id === 'login' && check)
		{
			if(!re.test(field.value)) {
				if(form_group) {
					form_group.classList.add('has-error');
			}
				field.style.border = '1px solid var(--red-500)';

				if(help_block) {
					help_block.remove();
				}

				field.insertAdjacentHTML('afterend', '<span class="help-block">' + Joomla.JText._('COM_EMUNDUS_USERS_ERROR_NOT_A_VALID_LOGIN_MUST_NOT_CONTAIN_SPECIAL_CHARACTER') + '</span>');

			return false;
		}
		}

		if (id === 'mail' && !remail.test(field.value))
		{
			if(form_group) {
				form_group.classList.add('has-error');
			}
			field.style.border = '1px solid var(--red-500)';

			if(help_block) {
				help_block.remove();
			}

			field.insertAdjacentHTML('afterend', '<span class="help-block">' + Joomla.JText._('COM_EMUNDUS_USERS_ERROR_NOT_A_VALID_EMAIL') + '</span>');

			return false;
		}

		if(form_group && form_group.classList.contains('has-error')) {
			form_group.classList.remove('has-error');
		}
		return true;
	}
}

function reloadData(loader = true) {
	loader ? addLoader() : '';

	let url = window.location.origin+'/index.php?option=com_emundus&view=users&format=raw&layout=user&Itemid=' + itemId;
	fetch(url, {
		method: 'GET',
	}).then((response) => {
		loader ? removeLoader() : '';
		if (response.ok) {
			return response.text();
		}
		throw new Error(Joomla.JText._('COM_EMUNDUS_ERROR_OCCURED'));
	}).then((result) => {
		let table = document.querySelector('.col-md-9 .panel.panel-default');
		while(table.firstChild) table.removeChild(table.firstChild)

		$('.col-md-9 .panel.panel-default').append(result);

		reloadActions($('#view').val(), undefined, false);
	});
}

function refreshFilter() {
	let url = window.location.origin+'/index.php?option=com_emundus&view=users&format=raw&layout=filter&Itemid=' + itemId;

	fetch(url, {
		method: 'GET',
	}).then((response) => {
		if (response.ok) {
			return response.text();
		}
		throw new Error(Joomla.JText._('COM_EMUNDUS_ERROR_OCCURED'));
	}).then((result) => {
		$("#em-user-filters .panel-body").empty();
		$("#em-user-filters .panel-body").append(result);
		$('.chzn-select').chosen();
		reloadData();
	});
}

function tableOrder(order) {
	let url = window.location.origin+'/index.php?option=com_emundus&controller=users&task=order';

	let formData = new FormData();
	formData.append('filter_order', order);

	fetch(url, {
		method: 'POST',
		body: formData,
	}).then((response) => {
		if (response.ok) {
			return response.json();
		}
		throw new Error(Joomla.JText._('COM_EMUNDUS_ERROR_OCCURED'));
	}).then((result) => {
		if (result.status) {
			reloadData();
		}
	})
}

function exist(fnum) {
	var exist = false;
	$('.col-md-9.col-xs-16 .panel.panel-default.em-hide').each(function () {
		if (parseInt($(this).attr('id')) == parseInt(fnum)) {
			exist = true;
			return;
		}
	});

	return exist;
}

function search() {

	var quick = [];
	$('div[data-value]').each( function () {
		quick.push($(this).attr('data-value'));
	});
	var inputs = [{
		name: 's',
		value: quick,
		adv_fil: false
	}];

	$('.em_filters_filedset .testSelAll').each(function () {
		inputs.push({
			name: $(this).attr('name'),
			value: $(this).val(),
			adv_fil: false
		});
	});

	$('.em_filters_filedset .search_test').each(function () {
		inputs.push({
			name: $(this).attr('name'),
			value: $(this).val(),
			adv_fil: false
		});
	});

	let url = window.location.origin+'/index.php?option=com_emundus&controller=users&task=setfilters&1';
	let formData = new FormData();
	formData.append('val', JSON.stringify(inputs));
	formData.append('multi', false);
	formData.append('elements', true);

	fetch(url, {
		method: 'POST',
		body: formData,
	}).then((response) => {
		if (response.ok) {
			return response.json();
		}
		throw new Error(Joomla.JText._('COM_EMUNDUS_ERROR_OCCURED'));
	}).then((result) => {
		if (result.status) {
			reloadData($('#view').val());
		}
	})
}

$(document).ready(function () {
	reloadData();
	refreshFilter();
	var lastVal = new Object();
	$(document).on('click', function () {
		if (!$('ul.dropdown-menu.open').hasClass('just-open')) {
			$('ul.dropdown-menu.open').hide();
			$('ul.dropdown-menu.open').removeClass('open');
		}
	});

	$(document).on('change', '.em-filt-select', function (event) {
		if (event.handle !== true) {
			event.handle = true;
			search();
		}
	});

	$(document).on('click', 'input:button', function (e) {

		if (e.event !== true) {
			e.handle = true;
			var name = $(this).attr('name');
			switch (name) {
				case 'clear-search':
					lastVal = new Object();

					let url = window.location.origin+'/index.php?option=com_emundus&controller=users&task=clear';
					fetch(url, {
						method: 'GET',
					}).then((response) => {
						if (response.ok) {
							return response.json();
						}
						throw new Error(Joomla.JText._('COM_EMUNDUS_ERROR_OCCURED'));
					}).then((result) => {
						if (result.status) {
							refreshFilter();
						}
					});

					break;
				case 'search':
					search();
					break;
				default:
					break;
			}
		}
	});
	$(document).on('click', '.pagination.pagination-sm li a', function (e) {
		if (e.handle !== true) {
			e.handle = true;
			var id = $(this).attr('id');

			let url = window.location.origin+'/index.php?option=com_emundus&controller=users&task=setlimitstart';
			let formData = new FormData();
			formData.append('limitstart', id);

			fetch(url, {
				method: 'POST',
				body: formData,
			}).then((response) => {
				if (response.ok) {
					return response.json();
				}
				throw new Error(Joomla.JText._('COM_EMUNDUS_ERROR_OCCURED'));
			}).then((result) => {
				if (result.status) {
					reloadData();
				}
			})
		}
	});
	$(document).on('click', '#em-last-open .list-group-item', function (e) {
		if (e.handle !== true) {
			e.handle = true;
			var fnum = new Object();
			fnum.fnum = $(this).attr('title');
			fnum.sid = parseInt(fnum.fnum.substr(21, 7));
			fnum.cid = parseInt(fnum.fnum.substr(14, 7));
			$('.em-check:checked').prop('checked', false);

			$('#' + fnum.fnum + '_check').prop('checked', true);

			let url = window.location.origin+'/index.php?option=com_emundus&controller=users&task=getfnuminfos&fnum=' + fnum.fnum;
			fetch(url, {
				method: 'GET',
			}).then((response) => {
				if (response.ok) {
					return response.json();
				}
				throw new Error(Joomla.JText._('COM_EMUNDUS_ERROR_OCCURED'));
			}).then((result) => {
				if (result.status) {
					var fnumInfos = result.fnumInfos;
					fnum.name = fnumInfos.name;
					fnum.label = fnumInfos.label;
					openFiles(fnum);
				}
			});
		}
	});

	$(document).on('click', 'button', function (e) {
		if (e.handle != true) {
			e.handle = true;
			var id = $(this).attr('id');
			switch (id) {
				case 'save-filter':
					var filName = prompt(filterName);
					if (filName != '') {

						let url = window.location.origin+'/index.php?option=com_emundus&controller=users&task=savefilters&Itemid=' + itemId;
						let formData = new FormData();
						formData.append('name', filName);

						fetch(url, {
							method: 'POST',
							body: formData,
						}).then((response) => {
							if (response.ok) {
								return response.json();
							}
							throw new Error(Joomla.JText._('COM_EMUNDUS_ERROR_OCCURED'));
						}).then((result) => {
							if (result.status) {
								$('#select_filter').append('<option id="' + result.filter.id + '" selected="">' + result.filter.name + '<option>');
								$("#select_filter").trigger("chosen:updated");
								$('#saved-filter').show();
								setTimeout(function (e) {
									$('#saved-filter').hide();
								}, 600);
							} else {
								$('#error-filter').show();
								setTimeout(function (e) {
									$('#error-filter').hide();
								}, 600);
							}
						});
					} else {
						alert(filterEmpty);
						filName = prompt(filterName, 'name');
					}
					break;
				case 'del-filter':
					var id = $('#select_filter').val();

					if (id != 0) {
						let url = window.location.origin+'/index.php?option=com_emundus&controller=users&task=deletefilters&Itemid=' + itemId;
						let formData = new FormData();
						formData.append('id', id);

						fetch(url, {
							method: 'POST',
							body: formData,
						}).then((response) => {
							if (response.ok) {
								return response.json();
							}
							throw new Error(Joomla.JText._('COM_EMUNDUS_ERROR_OCCURED'));
						}).then((result) => {
							if (result.status) {
								$('#select_filter option:selected').remove();
								$("#select_filter").trigger("chosen:updated");
								$('#deleted-filter').show();
								setTimeout(function () {
									$('#deleted-filter').hide();
								}, 600);
							} else {
								$('#error-filter').show();
								setTimeout(function () {
									$('#error-filter').hide();
								}, 600);
							}
						});
					} else {
						alert(nodelete);
					}
					break;
				case 'add-filter':
					addElement();
					break;
				case 'em-close-file':
				case 'em-mini-file':
					$('.em-open-files').remove();
					$('.em-hide').hide();
					$('#em-last-open').show();
					$('#em-last-open .list-group .list-group-item').removeClass('active');
					$('#em-user-filters').show();
					$('.em-check:checked').prop('checked', false);
					$('.col-md-9 .panel.panel-default').show();
					break;
				case 'em-see-files':
					var fnum = new Object();
					fnum.fnum = $(this).parents('a').attr('href').split('-')[0];
					fnum.fnum = fnum.fnum.substr(1, fnum.fnum.length);
					fnum.sid = parseInt(fnum.fnum.substr(21, 7));
					fnum.cid = parseInt(fnum.fnum.substr(14, 7));
					$('.em-check:checked').prop('checked', false);
					$('#' + fnum.fnum + '_check').prop('checked', true);

					let url = window.location.origin+'/index.php?option=com_emundus&controller=users&task=getfnuminfos&fnum=' + fnum.fnum;
					fetch(url, {
						method: 'GET',
					}).then((response) => {
						if (response.ok) {
							return response.json();
						}
						throw new Error(Joomla.JText._('COM_EMUNDUS_ERROR_OCCURED'));
					}).then((result) => {
						if (result.status) {
							var fnumInfos = result.fnumInfos;
							fnum.name = fnumInfos.name;
							fnum.label = fnumInfos.label;
							openFiles(fnum);
						}
					});

					break;
				case 'em-delete-files':
					var r = confirm(Joomla.JText._('COM_EMUNDUS_CONFIRM_DELETE_FILE'));
					if (r == true) {
						var fnum = $(this).parents('a').attr('href').split('-')[0];
						fnum = fnum.substr(1, fnum.length);

						let url = window.location.origin+'/index.php?option=com_emundus&controller=users&task=deletefile';
						let formData = new FormData();
						formData.append('fnum', fnum);

						fetch(url, {
							method: 'POST',
							body: formData,
						}).then((response) => {
							if (response.ok) {
								return response.json();
							}
							throw new Error(Joomla.JText._('COM_EMUNDUS_ERROR_OCCURED'));
						}).then((result) => {
							if (result.status) {
								if ($('#' + fnum + '-collapse').parent('div').hasClass('panel-primary')) {
									$('.em-open-files').remove();
									$('.em-hide').hide();
									$('#em-last-open').show();
									$('#em-last-open .list-group .list-group-item').removeClass('active');
									$('#em-user-filters').show();
									$('.em-check:checked').prop('checked', false);
									$('.col-md-9.col-xs-16 .panel.panel-default').show();
								}
								$('#em-last-open #' + fnum + '_ls_op').remove();
								$('#' + fnum + '-collapse').parent('div').remove();
							}
						});
					}

					break;

				default:
					break;
			}

		}
	});

	$(document).on('change', '#pager-select', function (e) {
		if (e.handle !== true) {
			e.handle = true;

			let url = window.location.origin+'/index.php?option=com_emundus&controller=users&task=setlimit';
			let formData = new FormData();
			formData.append('limit', $(this).val());

			fetch(url, {
				method: 'POST',
				body: formData,
			}).then((response) => {
				if (response.ok) {
					return response.json();
				}
				throw new Error(Joomla.JText._('COM_EMUNDUS_ERROR_OCCURED'));
			}).then((result) => {
				if (result.status) {
					reloadData();
				}
			});
		}
	});

	$(document).on('change', '#select_filter', function (e) {
		var id = $(this).attr('id');
		var val = $('#' + id).val();

		let url = window.location.origin+'/index.php?option=com_emundus&controller=users&task=setfilters&3';
		let formData = new FormData();
		formData.append('id', $('#' + id).attr('name'));
		formData.append('val', val);
		formData.append('multi', false);

		fetch(url, {
			method: 'POST',
			body: formData,
		}).then((response) => {
			if (response.ok) {
				return response.json();
			}
			throw new Error(Joomla.JText._('COM_EMUNDUS_ERROR_OCCURED'));
		}).then((result) => {
			if (result.status) {

				let url = window.location.origin+'/index.php?option=com_emundus&controller=users&task=loadfilters';
				let formData = new FormData();
				formData.append('id', val);

				fetch(url, {
					method: 'POST',
					body: formData,
				}).then((response) => {
					if (response.ok) {
						return response.json();
					}
					throw new Error(Joomla.JText._('COM_EMUNDUS_ERROR_OCCURED'));
				}).then((result) => {
					if (result.status) {
						refreshFilter();
						reloadData();
					}
				});
			}
		});
	});

	$(document).on('click', '#suppr-filt', function (e) {
		var fId = $(this).parent('fieldset').attr('id');
		var index = fId.split('-');

		var sonName = $('#em-adv-fil-' + index[index.length - 1]).attr('name');

		$('#' + fId).remove();
		let url = window.location.origin+'/index.php?option=com_emundus&controller=users&task=deladvfilter';
		let formData = new FormData();
		formData.append('elem', sonName);
		formData.append('id', index[index.length - 1]);

		fetch(url, {
			method: 'POST',
			body: formData,
		}).then((response) => {
			if (response.ok) {
				return response.json();
			}
			throw new Error(Joomla.JText._('COM_EMUNDUS_ERROR_OCCURED'));
		}).then((result) => {
			if (result.status) {
				reloadData();
			}
		});
	});

	$(document).on('click', '.em-dropdown', function (e) {
		var id = $(this).attr('id');
		$('ul.dropdown-menu.open').hide();
		$('ul.dropdown-menu.open').removeClass('open');
		if ($('ul[aria-labelledby="' + id + '"]').hasClass('open')) {
			$('ul[aria-labelledby="' + id + '"]').hide();
			$('ul[aria-labelledby="' + id + '"]').removeClass('open');
		} else {
			$('ul[aria-labelledby="' + id + '"]').show();
			$('ul[aria-labelledby="' + id + '"]').addClass('open just-open');
		}

		setTimeout(function () {
			$('ul[aria-labelledby="' + id + '"]').removeClass('just-open');
		}, 300);
	});

	/* Button Form actions*/
	$(document).on('click', '.em-actions-form', function (e) {
		var id = parseInt($(this).attr('id'));
		var url = $(this).attr('url');
		console.log(id);
		$('#em-modal-form').modal({
			backdrop: true
		}, 'toggle');



		$('.modal-title').empty();
		$('.modal-title').append($(this).children('a').text());
		$('.modal-body').empty();
		if ($('.modal-dialog').hasClass('modal-lg')) {
			$('.modal-dialog').removeClass('modal-lg');
		}
		$('.modal-body').attr('act-id', id);
		$('.modal-footer').show();


		$('.modal-footer').append('<div>' +
			'<p>' + jtextArray[2] + '</p>' +
			'<img src="' + loadingLine + '" alt="loading"/>' +
			'</div>');

		$('.modal-footer').hide();

		$('.modal-dialog').addClass('modal-lg');
		$(".modal-body").empty();

		$(".modal-body").append('<iframe src="' + url + '" style="width:100%; height:720px; border:none"></iframe>');

	});

	/* Menu action */
	$(document).off('click', '.em-actions');
	$(document).on('click', '.em-actions',async function (e) {

		e.preventDefault();
		var id = parseInt($(this).attr('id').split('|')[3]);

		// Prepare SweetAlert variables
		var title = '';
		var html = '';
		var swal_container_class = '';
		var swal_popup_class = '';
		var swal_actions_class = '';
		var swal_confirm_button = 'COM_EMUNDUS_ONBOARD_OK';
		var preconfirm = '';
		var preconfirm_value
		var swalForm = false;


		var view = $('#view').val();
		var sid = 0;
		if ($('.em-check:checked').length != 0) {
			sid = $('.em-check:checked').attr('id').split('_')[0];
		}

		var url = $(this).children('a').attr('href');

		String.prototype.fmt = function (hash) {
			var string = this,
				key;
			for (key in hash) {
				string = string.replace(new RegExp('\\{' + key + '\\}', 'gm'), hash[key]);
			}
			return string;
		};

		url = url.fmt({
			applicant_id: sid,
			view: view,
			controller: view,
			Itemid: itemId
		});
		url = window.location.origin + url;

		const checkInput = getUserCheck();

		/**
		 * 19: create group
		 * 20: create user
		 * 21: activate
		 * 22: desactivate
		 * 23: affect
		 * 24: edit user
		 * 25: show user rights
		 * 26: delete user
		 * 33: regenerate password
		 * 34: send email
		 */
		switch (id) {
			case 19:
				title = 'COM_EMUNDUS_USERS_CREATE_GROUP';
				preconfirm = "if (!formCheck('gname')) {Swal.showValidationMessage(Joomla.JText._('COM_EMUNDUS_USERS_ERROR_PLEASE_COMPLETE'))}"
				break;
			case 20:
				title = 'COM_EMUNDUS_ONBOARD_PROGRAM_ADDUSER';
				preconfirm = "let checklanme =formCheck('lname');let checkfname =formCheck('fname');let checkmail =formCheck('mail');let checklogin =formCheck('login'); if (!checklanme || !checkfname || !checkmail || !checklogin) {return Swal.showValidationMessage(Joomla.JText._('COM_EMUNDUS_USERS_ERROR_PLEASE_COMPLETE'))}";
				swal_confirm_button = 'COM_EMUNDUS_USERS_CREATE_USER_CONFIRM';
				break;
			case 23:
				title = 'COM_EMUNDUS_USERS_AFFECT_USER';
				swal_confirm_button = 'COM_EMUNDUS_USERS_AFFECT_USER_CONFIRM';
				preconfirm = "if ($('#agroups').val() == null) {Swal.showValidationMessage(Joomla.JText._('COM_EMUNDUS_USERS_AFFECT_GROUP_ERROR'))}"
				break;
		}

		switch (id) {

			case 19:
			case 20:
			case 23:
				swalForm = true;
				html = '<div id="data"></div>';
				addLoader();

				fetch(url, {
					method: 'GET',
				}).then((response) => {
					if (response.ok) {
						return response.text();
					}
					throw new Error(Joomla.JText._('COM_EMUNDUS_ERROR_OCCURED'));
				}).then((result) => {
					$('#data').append(result);

					removeLoader();
				});

				break;
			case 24:
				swalForm = true;
				title = 'COM_EMUNDUS_ACTIONS_EDIT_USER';
				swal_confirm_button = 'COM_EMUNDUS_USERS_EDIT_USER_CONFIRM';
				preconfirm = "let checklanme =formCheck('lname');let checkfname =formCheck('fname');let checkmail =formCheck('mail');let checklogin =formCheck('login'); if (!checklanme || !checkfname || !checkmail || !checklogin) {return Swal.showValidationMessage(Joomla.JText._('COM_EMUNDUS_USERS_ERROR_PLEASE_COMPLETE'))}";

				html = '<div id="data"></div>';

				addLoader();

				fetch(url, {
					method: 'GET',
				}).then((response) => {
					if (response.ok) {
						return response.text();
					}
					throw new Error(Joomla.JText._('COM_EMUNDUS_ERROR_OCCURED'));
				}).then((result) => {
					$('#data').append(result);

					removeLoader();
				});

				break;

			case 21:
				var formData = new FormData();
				formData.append('users', checkInput);
				formData.append('state', 0);

				fetch(url, {
					method: 'POST',
					body: formData,
				}).then((response) => {
					if (response.ok) {
						return response.json();
					}
					throw new Error(Joomla.JText._('COM_EMUNDUS_ERROR_OCCURED'));
				}).then((result) => {
					if (result.status) {
						Swal.fire({
							position: 'center',
							icon: 'success',
							title: result.msg,
							showConfirmButton: false,
							timer: 1500,
							customClass: {
								title: 'w-full justify-center',
							}
						}).then(() => {
							reloadData(false);
						});
					}
				});

				break;

			case 22:
				var formData = new FormData();
				formData.append('users', checkInput);
				formData.append('state', 1);

				fetch(url, {
					method: 'POST',
					body: formData,
				}).then((response) => {
					if (response.ok) {
						return response.json();
					}
					throw new Error(Joomla.JText._('COM_EMUNDUS_ERROR_OCCURED'));
				}).then((result) => {
					if (result.status) {
						Swal.fire({
							position: 'center',
							icon: 'success',
							title: result.msg,
							showConfirmButton: false,
							timer: 1500,
							customClass: {
								title: 'w-full justify-center',
							}
						}).then(() => {
							reloadData(false);
						});
					}
				})

				break;

			case 25:
				addLoader();
				var formData = new FormData();
				formData.append('users', checkInput);

				await fetch(url, {
					method: 'POST',
					body: formData,
				}).then((response) => {
					if (response.ok) {
						return response.text();
					}
					throw new Error(Joomla.JText._('COM_EMUNDUS_ERROR_OCCURED'));
				}).then((result) => {
					removeLoader();

					swalForm = true;
					title = 'COM_EMUNDUS_USERS_SHOW_USER_RIGHTS';
					swal_popup_class = 'em-w-auto';
					html = result
				});

				break;

			case 6:
				addLoader();

				await fetch(url, {
					method: 'POST',
					body: new URLSearchParams({})
				})
					.then((response) => {
						if (!response.ok) {
							throw new Error('Network response was not ok');
						}
						return response.text();
					})
					.then((result) => {
						removeLoader();
						swalForm = true;
						title = 'COM_EMUNDUS_EXPORT_EXCEL';
						swal_confirm_button = 'COM_EMUNDUS_EXPORTS_GENERATE_EXCEL';
						preconfirm = "var atLeastOneChecked = false; $('.form-group input[type=\"checkbox\"], .all-boxes input[type=\"checkbox\"]').each(function() { if ($(this).is(':checked')) { atLeastOneChecked = true; return false; } }); if (!atLeastOneChecked) { Swal.showValidationMessage(Joomla.JText._('COM_EMUNDUS_EXPORTS_SELECT_AT_LEAST_ONE_INFORMATION')); }";
						html = result;
					})
					.catch((error) => {
						removeLoader();
						console.error('Error:', error);
					});
				break;

			case 26:
				Swal.fire({
					title: $(this).children('a').text(),
					text: Joomla.JText._('COM_EMUNDUS_USERS_ARE_YOU_SURE_TO_DELETE_USERS'),
					showCancelButton: true,
					showCloseButton: true,
					confirmButtonText: Joomla.JText._('JACTION_DELETE'),
					cancelButtonText: Joomla.JText._('JCANCEL'),
					reverseButtons: true,
					customClass: {
						title: 'em-swal-title',
						cancelButton: 'em-swal-cancel-button',
						confirmButton: 'em-swal-confirm-button',
					},
				}).then((result) => {
					if (result.value) {
						addLoader();

						var formData = new FormData();
						formData.append('users', checkInput);

						let url = window.location.origin+'/index.php?option=com_emundus&controller=users&task=deleteusers&Itemid=' + itemId;

						fetch(url, {
							method: 'POST',
							body: formData,
						}).then((response) => {
							if (response.ok) {
								return response.json();
							}
							throw new Error(Joomla.JText._('COM_EMUNDUS_ERROR_OCCURED'));
						}).then((result) => {
							removeLoader();

							if (result.status) {
								Swal.fire({
									position: 'center',
									icon: 'success',
									title: result.msg,
									showConfirmButton: false,
									timer: 1500,
									customClass: {
										title: 'w-full justify-center',
									}
								}).then(() => {
									reloadData(false);
								});
							} else {
								Swal.fire({
									position: 'center',
									icon: 'warning',
									title: result.msg,
									customClass: {
										title: 'em-swal-title',
										confirmButton: 'em-swal-confirm-button',
										actions: "em-swal-single-action",
									},
								});
							}
						});
					}
				});

				break;

			case 33:
				Swal.fire({
					title: $(this).children('a').text(),
					text: Joomla.JText._('COM_EMUNDUS_WANT_RESET_PASSWORD'),
					showCancelButton: true,
					showCloseButton: true,
					confirmButtonText: Joomla.JText._('COM_EMUNDUS_MAIL_SEND_NEW'),
					cancelButtonText: Joomla.JText._('JCANCEL'),
					reverseButtons: true,
					customClass: {
						title: 'em-swal-title',
						cancelButton: 'em-swal-cancel-button',
						confirmButton: 'em-swal-confirm-button',
					},
				}).then((result) => {
					if (result.value) {
						addLoader();

						const formData = new FormData();
						formData.append('users', checkInput);

						fetch('/index.php?option=com_emundus&controller=users&task=passrequest&Itemid=' + itemId, {
							method: 'POST',
							body: formData
						}).then((response) => {
							if (response.ok) {
								return response.json();
							}
							throw new Error(Joomla.JText._('COM_EMUNDUS_ERROR_OCCURED'));
						}).then((result) => {
							removeLoader();

							if (result.status) {
								Swal.fire({
									position: 'center',
									icon: 'success',
									title: result.msg,
									showConfirmButton: false,
									timer: 1500,
									customClass: {
										title: 'w-full justify-center',
									}
								});
								reloadData();
							} else {
								Swal.fire({
									position: 'center',
									icon: 'error',
									title: result.msg,
									customClass: {
										title: 'w-full justify-center',
										confirmButton: 'em-swal-confirm-button',
										actions: "em-swal-single-action",
									},
								});
							}
						}).catch(function(error) {
							removeLoader();
							Swal.fire({
								position: 'center',
								icon: 'error',
								title: Joomla.JText._('COM_EMUNDUS_ERROR_OCCURED'),
								customClass: {
									title: 'w-full justify-center',
									confirmButton: 'em-swal-confirm-button',
									actions: "em-swal-single-action",
								},
							});
						});
					}
				});
				break;

			case 34:
				addLoader();

				swalForm = true;
				title = 'COM_EMUNDUS_MAILS_SEND_EMAIL';
				swal_popup_class = 'em-w-100 em-h-100';
				swal_confirm_button = 'COM_EMUNDUS_MAIL_SEND_NEW';
				html = '<div id="data"></div>';

				var formData = new FormData();
				formData.append('users', checkInput);

				fetch(url, {
					method: 'POST',
					body: formData,
				}).then((response) => {
					if (response.ok) {
						return response.text();
					}
					throw new Error(Joomla.JText._('COM_EMUNDUS_ERROR_OCCURED'));
				}).then((result) => {
					removeLoader();

					$('#data').append(result);
				});

				break;
		}

		if(swalForm) {
			Swal.fire({
				title: Joomla.JText._(title),
				html: html,
				allowOutsideClick: false,
				showCancelButton: true,
				showCloseButton: true,
				reverseButtons: true,
				confirmButtonText: Joomla.JText._(swal_confirm_button),
				cancelButtonText: Joomla.JText._('COM_EMUNDUS_ONBOARD_CANCEL'),
				customClass: {
					container: 'em-modal-actions ' + swal_container_class,
					popup: swal_popup_class,
					title: 'em-swal-title',
					cancelButton: 'em-swal-cancel-button',
					confirmButton: 'em-swal-confirm-button btn btn-success',
					actions: swal_actions_class
				},
				preConfirm: () => {
					if (preconfirm !== '') {
						preconfirm_value = new Function(preconfirm)();
					}
				},
			}).then((result) => {
				if (result.value) {
					runAction(id, url, preconfirm_value);
				}
			});

			$('.em-chosen').chosen({width: '100%'});
		}
	});

	function runAction(id, url = '', option = '') {
		var formData = new FormData();

		if ($('#em-check-all-all').is(':checked')) {
			var checkInput = 'all';
		} else {
			var i = 0;
			var myJSONObject = '{';
			$('.em-check:checked').each(function () {
				i = i + 1;
				myJSONObject += '"' + i + '"' + ':"' + $(this).attr('id').split('_')[0] + '",';
			});
			myJSONObject = myJSONObject.substr(0, myJSONObject.length - 1);
			myJSONObject += '}';
			if (myJSONObject.length == 2) {
				alert('SELECT_FILES');
				return;
			} else {
				checkInput = myJSONObject;
			}
		}

		let action = '';

		/**
		 * 6: export
		 * 19: create group
		 * 20: create user
		 * 21: activate
		 * 22: desactivate
		 * 23: affect
		 * 24: edit user
		 * 25: show user rights
		 * 26: delete user
		 * 33: regenerate password
		 * 34: send email
		 */
		switch (id) {
			case 6:
				addLoader();

				let checkedBoxes = {};
				let allChecked = $('#checkbox-all').prop('checked');

				// Verify which checkboxes have been selected
				$('input[type="checkbox"]').each(function () {
					if ($(this).attr('value') !== 'all') {
						let checkboxValue = $(this).attr('value');
						if (allChecked || $(this).prop('checked')) {
							checkedBoxes[checkboxValue] = true;
						}
					}
				});

				formData = new FormData();
				formData.append('users', getUserCheck());
				formData.append('checkboxes', JSON.stringify(checkedBoxes));

				fetch('/index.php?option=com_emundus&controller=users&task=exportusers&Itemid=' + itemId, {
					method: 'POST',
					body: formData
				})
					.then(function (response) {
						if (!response.ok) {
							throw new Error('Network response was not ok');
						}
						return response.json();
					})
					.then(function (result) {
						removeLoader();

						Swal.fire({
							position: 'center',
							icon: 'success',
							title: Joomla.JText._('COM_EMUNDUS_ATTACHMENTS_DOWNLOAD_READY'),
							showCancelButton: true,
							showConfirmButton: true,
							confirmButtonText: Joomla.JText._('COM_EMUNDUS_ATTACHMENTS_DOWNLOAD'),
							cancelButtonText: Joomla.JText._('JCANCEL'),
							reverseButtons: true,
							allowOutsideClick: false,
							customClass: {
								cancelButton: 'em-swal-cancel-button',
								confirmButton: 'em-swal-confirm-button btn btn-success',
								title: 'w-full justify-center',
							},
							preConfirm: function () {
								var link = document.createElement('a');
								link.href = '/tmp/' + result.fileName;
								link.download = result.fileName;
								document.body.appendChild(link);
								link.click();
								document.body.removeChild(link);
							}
						});
					})

					.catch(function (error) {
						removeLoader();
						console.error('Error:', error);
					});

				break;

			case 19:
				action = window.location.origin + '/' + document.querySelector('#em-add-group').getAttribute('action');

				var programs = $('#gprogs');
				var progs = "";
				if (programs.val() != null) {
					for (var i = 0; i < programs.val().length; i++) {
						progs += programs.val()[i];
						progs += ',';
					}
					progs = progs.substr(0, progs.length - 1);
				}

				addLoader();

				formData = new FormData();
				formData.append('gname', $('#gname').val());
				formData.append('gdesc', $('#gdescription').val());
				formData.append('gprog', progs);

				fetch(action, {
					method: 'POST',
					body: formData,
				}).then((response) => {
					if (response.ok) {
						return response.json();
					}
					throw new Error(Joomla.JText._('COM_EMUNDUS_ERROR_OCCURED'));
				}).then((result) => {
					removeLoader();
					if (result.status) {
						Swal.fire({
							position: 'center',
							icon: 'success',
							title: result.msg,
							showConfirmButton: false,
							timer: 1500
						}).then(function() {
							window.location.replace('/index.php?option=com_emundus&view=users&layout=showgrouprights&Itemid=1169&rowid='+result.status);
						});
					} else {
						Swal.fire({
							position: 'center',
							icon: 'warning',
							title: result.msg,
							customClass: {
								title: 'em-swal-title',
								confirmButton: 'em-swal-confirm-button',
								actions: "em-swal-single-action",
							},
						});
					}
				});

				break;

			case 20:
				action = window.location.origin + '/' + document.querySelector('#em-add-user').getAttribute('action');

				var groups = "";
				var campaigns = "";
				var oprofiles = "";

				if ($("#groups").val() != null && $("#groups").val().length > 0) {
					for (var i = 0; i < $("#groups").val().length; i++) {
						groups += $("#groups").val()[i];
						groups += ',';
					}
				}
				if ($("#campaigns").val() && $("#campaigns").val().length > 0) {
					for (var i = 0; i < $("#campaigns").val().length; i++) {
						campaigns += $("#campaigns").val()[i];
						campaigns += ',';
					}
				}
				if ($("#oprofiles").val() && $("#oprofiles").val().length > 0) {
					for (var i = 0; i < $("#oprofiles").val().length; i++) {
						oprofiles += $("#oprofiles").val()[i];
						oprofiles += ',';
					}
				}
				if($('#same_login_email').is(':checked')){
					$('#login').val($('#mail').val());
				}

				var login = $('#login').val();
				var fn = $('#fname').val();
				var ln = $('#lname').val();
				var email = $('#mail').val();
				var profile = $('#profiles').val();

				if (profile == "0") {
					$('#profiles').parent('.form-group').addClass('has-error');
					$('#profiles').after('<span class="help-block">' + Joomla.JText._('SELECT_A_VALUE') + '</span>');
					return false;
				}

				addLoader();

				formData = new FormData();
				formData.append('login', login);
				formData.append('firstname', fn);
				formData.append('lastname', ln);
				formData.append('campaigns', campaigns.substr(0, campaigns.length - 1));
				formData.append('oprofiles', oprofiles.substr(0, oprofiles.length - 1));
				formData.append('groups', groups.substr(0, groups.length - 1));
				formData.append('profile', profile);
				formData.append('jgr', $('#profiles option:selected').attr('id'));
				formData.append('email', email);
				formData.append('newsletter', $('#news').is(':checked') ? 1 : 0);
				formData.append('university_id', $('#univ').val());
				formData.append('ldap', $('#ldap').is(':checked') ? 1 : 0);

				fetch(action, {
					method: 'POST',
					body: formData,
				}).then((response) => {
					if (response.ok) {
						return response.json();
					}
					throw new Error(Joomla.JText._('COM_EMUNDUS_ERROR_OCCURED'));
				}).then((result) => {
					removeLoader();

					if (result.status) {
						Swal.fire({
							position: 'center',
							icon: 'success',
							title: result.msg,
							showConfirmButton: false,
							timer: 1500,
						}).then(() => {
							reloadData();

							reloadActions($('#view').val(), undefined, false);
						});
					} else {
						Swal.fire({
							position: 'center',
							icon: 'warning',
							title: result.msg,
							customClass: {
								title: 'em-swal-title',
								confirmButton: 'em-swal-confirm-button',
								actions: "em-swal-single-action",
							},
						});
					}
				});

				break;

			case 23:
				action = window.location.origin + '/' + document.querySelector('#em-affect-groups').getAttribute('action');

				var checkInput = getUserCheck();

				if ($('#agroups').val() == null) {
					$('#agroups').parent('.form-group').addClass('has-error');
					$('#agroups').after('<span class="help-block">' + Joomla.JText._('SELECT_A_GROUP') + '</span>');
					return false;
				} else {
					var groups = '';
					for (var i = 0; i < $("#agroups").val().length; i++) {
						groups += $("#agroups").val()[i];
						groups += ',';
					}
				}

				formData = new FormData();
				formData.append('users', checkInput);
				formData.append('groups', groups.substr(0, groups.length - 1));

				fetch(action, {
					method: 'POST',
					body: formData,
				}).then((response) => {
					if (response.ok) {
						return response.json();
					}
					throw new Error(Joomla.JText._('COM_EMUNDUS_ERROR_OCCURED'));
				}).then((result) => {
					removeLoader();

					if (result.status) {
						Swal.fire({
							position: 'center',
							icon: 'success',
							title: result.msg,
							showConfirmButton: false,
							timer: 1500
						}).then(() => {
							reloadData();
						});
					} else {
						Swal.fire({
							position: 'center',
							icon: 'warning',
							title: result.msg,
							customClass: {
								title: 'em-swal-title',
								confirmButton: 'em-swal-confirm-button',
								actions: "em-swal-single-action",
							},
						});
					}
				})

				break;

			case 24:
				var groups = '';
				var campaigns = '';
				var oprofiles = '';

				if ($("#groups").val() != null && $("#groups").val().length > 0) {
					for (var i = 0; i < $("#groups").val().length; i++) {
						groups += $("#groups").val()[i];
						groups += ',';
					}
				}
				if ($("#campaigns").val() && $("#campaigns").val().length > 0) {
					for (var i = 0; i < $("#campaigns").val().length; i++) {
						campaigns += $("#campaigns").val()[i];
						campaigns += ',';
					}
				}
				if ($("#oprofiles").val() && $("#oprofiles").val().length > 0) {
					for (var i = 0; i < $("#oprofiles").val().length; i++) {
						oprofiles += $("#oprofiles").val()[i];
						oprofiles += ',';
					}
				}
				var login = $('#login').val();
				var fn = $('#fname').val();
				var ln = $('#lname').val();
				var email = $('#mail').val();
				var profile = $('#profiles').val();
				let sameLoginEmail = document.getElementById('same_login_email').checked ? 1 : 0;

				if (profile == "0") {
					$('#profiles').parent('.form-group').addClass('has-error');
					$('#profiles').after('<span class="help-block">' + Joomla.JText._('SELECT_A_VALUE') + '</span>');
					return false;
				}
				addLoader();

				formData = new FormData();
				formData.append('login', login);
				formData.append('firstname', fn);
				formData.append('lastname', ln);
				formData.append('campaigns', campaigns.substr(0, campaigns.length - 1));
				formData.append('oprofiles', oprofiles.substr(0, oprofiles.length - 1));
				formData.append('groups', groups.substr(0, groups.length - 1));
				formData.append('profile', profile);
				formData.append('jgr', $('#profiles option:selected').attr('id'));
				formData.append('email', email);
				formData.append('newsletter', $('#news').is(':checked') ? 1 : 0);
				formData.append('university_id', $('#univ').val());
				formData.append('sameLoginEmail', sameLoginEmail);
				action = window.location.origin + '/' + document.getElementById('em-add-user').getAttribute('action');
				if(action.indexOf('edituser') !== -1) {
					formData.append('id', $('.em-check:checked').attr('id').split('_')[0]);
				}

				fetch(action, {
					method: 'POST',
					body: formData,
				}).then((response) => {
					if (response.ok) {
						return response.json();
					}
					throw new Error(Joomla.JText._('COM_EMUNDUS_ERROR_OCCURED'));
				}).then((result) => {
					removeLoader();

					if (result.status) {
						Swal.fire({
							position: 'center',
							icon: 'success',
							title: result.msg,
							showConfirmButton: false,
							timer: 1500,
						}).then(() => {
							reloadData();
						});
					} else {
						Swal.fire({
							position: 'center',
							icon: 'warning',
							title: result.msg,
							customClass: {
								title: 'em-swal-title',
								confirmButton: 'em-swal-confirm-button',
								actions: "em-swal-single-action",
							},
						});
					}
				})

				break;

			/* Send an email to a user.*/
			case 34:
				action = window.location.origin + '/index.php?option=com_emundus&controller=messages&task=useremail';

				addLoader();

				/* Get all form elements.*/
				let data = {
					attachments		: []
				};

				$("#em-attachment-list li").each((idx, li) => {
					let attachment = $(li);
					data.attachments.push(attachment.find('.value').text());
				});

				formData = new FormData();
				formData.append('recipients', $('#uids').val());
				formData.append('template', $('#message_template :selected').val());
				formData.append('Bcc', $('#sendUserACopy').prop('checked'));
				formData.append('mail_from_name', $('#mail_from_name').text());
				formData.append('mail_from', $('#mail_from').text().trim());
				formData.append('mail_subject', $('#mail_subject').text());
				formData.append('message', $('#mail_body').val());
				formData.append('attachments', data.attachments);

				fetch(action, {
					method: 'POST',
					body: formData,
				}).then((response) => {
					if (response.ok) {
						return response.json();
					}
					throw new Error(Joomla.JText._('SEND_FAILED'));
				}).then((result) => {
					removeLoader();

					if(result.status) {
						if (result.sent.length > 0) {

							var sent_to = '<p>' + Joomla.JText._('SEND_TO') + '</p><ul class="list-group" id="em-mails-sent">';
							result.sent.forEach(element => {
								sent_to += '<li class="list-group-item alert-success">' + element + '</li>';
							});

							Swal.fire({
								position: 'center',
								icon: 'success',
								title: Joomla.JText._('COM_EMUNDUS_EMAILS_EMAILS_SENT') + result.sent.length,
								html: sent_to + '</ul>',
								customClass: {
									title: 'w-full justify-center',
									confirmButton: 'em-swal-confirm-button',
									actions: "em-swal-single-action",
								},
							});

						} else {
							Swal.fire({
								icon: 'error',
								title: Joomla.JText._('COM_EMUNDUS_EMAILS_NO_EMAILS_SENT'),
								customClass: {
									title: 'em-swal-title',
									confirmButton: 'em-swal-confirm-button',
									actions: "em-swal-single-action",
								},
							});
						}

						if (result.failed.length > 0) {

							// add sibling to #em-mails-sent
							const emailNotSentMessage = document.createElement('p');
							emailNotSentMessage.classList.add('em-mt-16');
							emailNotSentMessage.innerText = "Certains utilisateurs n'ont pas reçu l'email";

							const emailNotSent = document.createElement('div');
							emailNotSent.classList.add('alert', 'alert-danger', 'em-mt-16');
							emailNotSent.innerHTML = '<span class="badge">' + result.failed.length + '</span>';
							emailNotSent.appendChild(document.createElement('ul'));
							result.failed.forEach(element => {
								const emailNotSentItem = document.createElement('li');
								emailNotSentItem.innerHTML = element;
								emailNotSent.querySelector('ul').appendChild(emailNotSentItem);
							});

							$('#em-mails-sent').after(emailNotSent);
							$('#em-mails-sent').after(emailNotSentMessage);
						}
					} else {
						$("#em-email-messages").append('<span class="alert alert-danger">' + Joomla.JText._('SEND_FAILED') + '</span>');
					}
				});

			break;
		}
	}

	/* Button on Actions*/
	$(document).off('click', '#em-modal-actions .btn.btn-success');

	$(document).on('click', '#em-modal-actions .btn.btn-success', function (e) {

	});

	/*action fin*/
	$(document).on('change', '#em-modal-actions #em-export-form', function (e) {
		if (e.handle !== true) {
			e.handle = true;
			var id = $(this).val();
			var text = $('#em-modal-actions #em-export-form option:selected').attr('data-value');
			$('#em-export').append('<li class="em-export-item" id="' + id + '-item"><strong>' + text + '</strong><button class="btn btn-danger btn-xs pull-right"><span class="material-symbols-outlined">delete_outline</span></button></li>');
		}
	});

	$(document).on('click', '#em-export .em-export-item .btn.btn-danger', function (e) {
		$(this).parent('li').remove();
	});

	$(document).on('change', '.em-modal-check', function () {
		if ($(this).hasClass('em-check-all')) {
			var id = $(this).attr('name').split('-');
			id.pop();
			id = id.join('-');
			if ($(this).is(':checked')) {
				$(this).prop('checked', true);
				$('.' + id).prop('checked', true);
			} else {
				$(this).prop('checked', false);
				$('.' + id).prop('checked', false);
			}
		}
	});

	function displayErrorMessage(msg)
	{
		Swal.fire({
			icon: 'error',
			title: msg,
			customClass: {
				title: 'em-swal-title',
				confirmButton: 'em-swal-confirm-button',
				actions: "em-swal-single-action",
			},
		});
	}
})

function DoubleScroll(element) {
	const id = Math.random();
	if (element.scrollWidth > element.offsetWidth) {
		createScrollbarForElement(element, id);
	}

	window.addEventListener('resize', function () {
		let scrollbar = document.getElementById(id);
		if (scrollbar) {
			if (element.scrollWidth > element.offsetWidth) {
				scrollbar.firstChild.style.width = element.scrollWidth + 'px';
			} else {
				scrollbar.remove();
			}
		} else {
			if (element.scrollWidth > element.offsetWidth) {
				createScrollbarForElement(element, id);
			}
		}
	});
}

function createScrollbarForElement(element, id) {
	let new_scrollbar = document.createElement('div');
	new_scrollbar.appendChild(document.createElement('div'));
	new_scrollbar.style.overflowX = 'auto';
	new_scrollbar.style.overflowY = 'hidden';
	new_scrollbar.firstChild.style.height = '1px';
	new_scrollbar.firstChild.style.width = element.scrollWidth + 'px';
	new_scrollbar.firstChild.appendChild(document.createTextNode('\xA0'));
	new_scrollbar.id = id;
	let running = false;
	new_scrollbar.onscroll = function () {
		if (running) {
			running = false;
			return;
		}
		running = true;
		element.scrollLeft = new_scrollbar.scrollLeft;
	};
	element.onscroll = function () {
		if (running) {
			running = false;
			return;
		}
		running = true;
		new_scrollbar.scrollLeft = element.scrollLeft;
	};
	element.parentNode.insertBefore(new_scrollbar, element);
}

/*
 * Check/Uncheck checkboxes according to the "all" checkbox
 * (Only use in the file components/com_emundus/views/users/tmpl/export.php for now)
 */
function checkAllUserElement(checkboxAll){
	let allChecked = checkboxAll.checked;
	var checkboxes = document.querySelectorAll('input[type=checkbox][id^=checkbox-]');
	checkboxes.forEach(function (checkbox) {
		checkbox.checked = allChecked;
	});
}

/*
 * Uncheck the "all" checkbox when an another checkbox is uncheck
 * (Only use in the file components/com_emundus/views/users/tmpl/export.php for now)
 */
function uncheckCheckboxAllElement(checkbox)
{
	var checkboxAll = document.getElementById('checkbox-all');
	if (!checkbox.checked && checkboxAll.checked) {
		checkboxAll.checked = false;
	}
}
