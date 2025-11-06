export default class AlertError extends Error {
	constructor(message, description = '') {
		super(message);

		this.description = description;
	}

	getMessage() {
		return this.message;
	}

	getDescription() {
		return this.description;
	}
}
