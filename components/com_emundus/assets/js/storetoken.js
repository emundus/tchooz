document.addEventListener('DOMContentLoaded', function() {
	const copiedItems = { token: false };

	window.copyToClipboard = function(inputId, btnId, itemKey) {
		const input = document.getElementById(inputId);
		const text = input.value;

		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(text).then(function() {
				markCopied(btnId, itemKey);
			}).catch(function() {
				fallbackCopy(input, btnId, itemKey);
			});
		} else {
			fallbackCopy(input, btnId, itemKey);
		}
	};

	function fallbackCopy(input, btnId, itemKey) {
		input.select();
		input.setSelectionRange(0, 99999);
		try {
			document.execCommand('copy');
			markCopied(btnId, itemKey);
		} catch (err) {
			console.error('Copy failed', err);
		}
	}

	function markCopied(btnId, itemKey) {
		// Update button icon
		const iconId = btnId.replace('btn', 'icon');
		const icon = document.getElementById(iconId);
		if (icon) {
			icon.textContent = 'check';
			icon.classList.add('tw-text-green-600');
		}

		// Track copied items
		copiedItems[itemKey] = true;

		if (copiedItems.token) {
			fetch('/index.php?option=com_emundus&task=markPublicAccessKeyAsStored', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-CSRF-Token': Joomla.getOptions('csrf.token'),
				},
			}).catch(function (err) {
				console.error('Failed to mark token as copied', err);
			});
		}

		updateContinueButton();
	}

	function updateContinueButton() {
		const continueBtn = document.getElementById('continue-btn');
		const confirmCheckbox = document.getElementById('confirm-copy');
		if (continueBtn && confirmCheckbox) {
			continueBtn.disabled = !confirmCheckbox.checked;
			if (confirmCheckbox.checked) {
				continueBtn.classList.remove('tw-opacity-50', 'tw-cursor-not-allowed');
			} else {
				continueBtn.classList.add('tw-opacity-50', 'tw-cursor-not-allowed');
			}
		}
	}

	document.getElementById('confirm-copy').addEventListener('change', function() {
		const wrapper = this.closest('.tw-flex.tw-flex-row');
		if (wrapper && this.checked) {
			wrapper.classList.remove('tw-border-red-500');
			wrapper.querySelector('label').classList.remove('tw-text-red-500');
		}
		updateContinueButton();
	});

	// add event listener on #continue-btn
	document.getElementById('continue-btn').addEventListener('click', function() {
		const confirmCheckbox = document.getElementById('confirm-copy');
		if (confirmCheckbox && confirmCheckbox.checked)
		{
			window.location.href = '/index.php?option=com_emundus&task=openfile&fnum=' + Joomla.getOptions('storetoken.fnum');
		}
		else
		{
			const wrapper = confirmCheckbox.closest('.tw-flex.tw-flex-row');
			if (wrapper) {
				wrapper.classList.add('tw-border-red-500');
				wrapper.querySelector('label').classList.add('tw-text-red-500');
			}
		}
	});

	document.getElementById('copy-token-btn').addEventListener('click', function() {
		copyToClipboard('storetoken-token', 'copy-token-btn', 'token');
	});

	document.getElementById('abort-btn').addEventListener('click', function()
	{
		fetch('/index.php?option=com_emundus&controller=application&task=abortPublicApplicationCreation', {method: 'POST', headers: {'X-CSRF-Token': Joomla.getOptions('csrf.token')}})
			.then(function() {
				window.location.href = '/';
			})
			.catch(function(err) {
				console.error('Failed to abort application creation', err);
				Swal.fire({
					title: Joomla.Text._('COM_EMUNDUS_FAILED_TO_ABORT_APPLICATION_CREATION'),
					text: '',
					icon: 'error',
					showConfirmButton: false,
					customClass: {
						title: 'em-swal-title',
					},
				});
			});
	});
});
