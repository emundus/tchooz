<script>
import { Icon, Button } from '@emundus/ui';

export default {
	name: 'ModalHeader',
	components: { Icon, Button },
	props: {
		title: {
			type: String,
			default: '',
		},
		subtitle: {
			type: String,
			default: '',
		},
		showBack: {
			type: Boolean,
			default: false,
		},
		showClose: {
			type: Boolean,
			default: true,
		},
	},
	emits: ['back', 'close'],
};
</script>

<template>
	<div class="tw-mb-4 tw-grid tw-grid-cols-3 tw-items-center">
		<div>
			<slot name="leading">
				<Button v-if="showBack" @click.prevent="$emit('back')" variant="neutral" emphasis="ghost" class="tw-w-fit">
					<template #leading>
						<Icon name="arrow_back" />
					</template>
				</Button>
			</slot>
		</div>

		<div class="tw-flex tw-flex-col tw-gap-1 tw-text-center">
			<slot>
				<h2 v-if="title">{{ title }}</h2>
				<h3 v-if="subtitle" class="tw-font-light">{{ subtitle }}</h3>
			</slot>
		</div>

		<div class="tw-text-end">
			<slot name="trailing">
				<Button v-if="showClose" @click.prevent="$emit('close')" variant="neutral" emphasis="ghost" class="tw-w-fit">
					<template #leading>
						<Icon name="close" />
					</template>
				</Button>
			</slot>
		</div>
	</div>
</template>
