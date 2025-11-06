import moment from 'moment';
import fr from 'moment/dist/locale/fr';
import Swal from 'sweetalert2';
import { useFormBuilderStore } from '@/stores/formbuilder.js';
import { useGlobalStore } from '@/stores/global.js';

export default {
	methods: {
		programColor(program) {
			if (program.color) {
				return program.color;
			}

			return '#0A53CC';
		},

		openCampaignDetails(campaignId, campaigns) {
			const campaign = campaigns.find((c) => c.id === campaignId);
			if (campaign && campaign.alias) {
				window.open(campaign.alias, '_blank');
			}
		},

		openCampaignDetailsWithAlias(alias) {
			if (alias) {
				window.open(alias, '_blank');
			}
		},
	},
};
