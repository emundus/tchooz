document.addEventListener('DOMContentLoaded', function() {
	const copiedItems = { fnum: false, token: false };

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

		// Update checklist
		const checkIcon = document.getElementById('check-' + itemKey);
		const checkLabel = document.getElementById('check-' + itemKey + '-label');
		if (checkIcon) {
			checkIcon.textContent = 'check_circle';
			checkIcon.classList.add('tw-text-green-600');
		}
		if (checkLabel) {
			checkLabel.classList.add('tw-text-green-700', 'tw-font-medium');
		}

		// Track copied items
		copiedItems[itemKey] = true;

		// Enable continue button if both items are copied
		if (copiedItems.fnum && copiedItems.token) {
			const continueBtn = document.getElementById('continue-btn');
			if (continueBtn) {
				continueBtn.disabled = false;
				continueBtn.classList.remove('tw-opacity-50', 'tw-cursor-not-allowed');

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
		}
	}

	// add event listener on #continue-btn
	document.getElementById('continue-btn').addEventListener('click', function() {
		if (copiedItems.fnum && copiedItems.token)
		{
			window.location.href = '/index.php?option=com_emundus&task=openfile';
		}
		else
		{
			Swal.fire({
				title: Joomla.Text._('COM_EMUNDUS_PLEASE_COPY_ACCESS_TOKEN_BEFORE_CONTINUE'),
				text: '',
				icon: 'warning',
				showConfirmButton: false,
				customClass: {
					title: 'em-swal-title',
				},
			});
		}
	});

	document.getElementById('copy-token-btn').addEventListener('click', function() {
		copyToClipboard('storetoken-token', 'copy-token-btn', 'token');
	});

	document.getElementById('copy-fnum-btn').addEventListener('click', function () {
		copyToClipboard('storetoken-fnum', 'copy-fnum-btn', 'fnum');
	});
});
