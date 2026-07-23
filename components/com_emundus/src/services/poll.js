import { FetchClient } from './fetchClient.js';

const client = new FetchClient('poll');

export default {
	async savePoll(poll) {
		try {
			return await client.post('savepoll', poll);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async savePollSlot(slot) {
		try {
			return await client.post('savepollslot', slot);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async deletePollSlot(slotId) {
		try {
			return await client.post('deletepollslot', { id: slotId });
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async savePollAnswers(payload) {
		try {
			return await client.post('savepollanswers', {
				poll_id: payload.poll_id,
				answers: JSON.stringify(payload.answers),
			});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async runPoll(payload) {
		try {
			return await client.post('runpoll', payload);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async closePoll(payload) {
		try {
			return await client.post('closepoll', payload);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async contactParticipants(payload) {
		try {
			return await client.post('contactparticipants', payload);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
};
