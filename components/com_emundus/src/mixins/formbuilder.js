import moment from 'moment';
import fr from 'moment/dist/locale/fr';
import Swal from 'sweetalert2';
import { useFormBuilderStore } from '@/stores/formbuilder.js';
import { useGlobalStore } from '@/stores/global.js';

export default {
	methods: {
		updateLastSave() {
			if (useGlobalStore().shortLang === 'fr') {
				moment.locale('fr', fr);
			}
			const formBuilderStore = useFormBuilderStore();
			formBuilderStore.updateLastSave(moment().format('LT'));
		},
		async swalConfirm(title, text, confirm, cancel, callback = null, showCancelButton = true, html = false) {
			let options = {
				title: title,
				text: text,
				icon: 'warning',
				showCancelButton: showCancelButton,
				confirmButtonText: confirm,
				cancelButtonText: cancel,
				reverseButtons: true,
				customClass: {
					title: 'em-swal-title',
					cancelButton: 'em-swal-cancel-button',
					confirmButton: 'em-swal-confirm-button',
				},
			};
			if (html) {
				options.html = text;
			} else {
				options.text = text;
			}

			Swal.fire({
				title: 'test',
			});

			return Swal.fire(options).then((result) => {
				if (result.value) {
					if (callback != null) {
						callback();
					}
					return true;
				} else {
					return false;
				}
			});
		},
	},
};
