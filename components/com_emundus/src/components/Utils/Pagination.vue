<template>
	<div class="tw-flex tw-items-center tw-justify-between tw-py-2" :class="stickyClass" :style="stickyStyle">
		<div
			class="tw-flex tw-h-[40px] tw-items-center tw-rounded-coordinator tw-border tw-border-neutral-300 tw-bg-white tw-px-3 tw-py-2"
		>
			<div class="tw-flex tw-items-center tw-gap-2">
				<label for="pager-select" class="!tw-mb-0">{{ translate('COM_EMUNDUS_PAGINATION_DISPLAY') }}</label>
				<select name="pager-select" id="pager-select" class="em-select-no-border !tw-py-0" v-model="currentLimit">
					<option v-for="availableLimit in limits" :value="availableLimit">
						{{ displayAvailableLimit(availableLimit) }}
					</option>
				</select>
			</div>
		</div>

		<div class="em-container-pagination-selectPage">
			<ul class="pagination pagination-sm tw-flex tw-items-center tw-gap-1">
				<li class="tw-flex">
					<a
						class="tw-cursor-pointer"
						:class="{ disabled: this.currentPage === 1 }"
						@click="this.currentPage !== 1 ? (this.currentPage -= 1) : null"
					>
						<span class="material-symbols-outlined">navigate_before</span>
					</a>
				</li>
				<li
					v-for="pageAvailable in pagesAvailable"
					:key="pageAvailable"
					:class="{ active: pageAvailable === this.currentPage }"
					@click="this.currentPage = pageAvailable"
					class="tw-flex tw-cursor-pointer"
				>
					<a class="!tw-rounded-coordinator">{{ pageAvailable }}</a>
				</li>
				<li class="tw-flex">
					<a
						class="tw-cursor-pointer"
						:class="{ disabled: this.currentPage >= totalPages }"
						@click="this.currentPage < totalPages ? (this.currentPage += 1) : null"
					>
						<span class="material-symbols-outlined">navigate_next</span>
					</a>
				</li>
			</ul>
		</div>
	</div>
</template>

<script>
export default {
	name: 'Pagination',
	props: {
		limits: {
			type: Array,
			default: () => [1, 5, 10, 25, 50, 100],
		},
		dataLength: {
			type: Number,
			default: 0,
		},
		page: {
			type: Number,
			default: 1,
		},
		limit: {
			type: Number | String,
			default: 5,
		},
		sticky: {
			type: Boolean,
			default: false,
		},
	},
	emits: ['update:page', 'update:limit'],
	data: () => ({
		currentPage: 1,
		currentLimit: 5,
	}),
	created() {
		this.currentPage = this.page;
		this.currentLimit = this.limit;
	},
	methods: {
		displayAvailableLimit(limit) {
			if (isNaN(limit)) {
				return this.translate(limit);
			}

			return limit;
		},
		updateCurrentPage() {
			const total = Math.ceil(this.dataLength / this.currentLimit);
			if (this.currentPage > total) {
				this.currentPage = total;
			}
		},
	},
	watch: {
		currentPage() {
			this.$emit('update:page', this.currentPage);
		},
		currentLimit() {
			this.$emit('update:limit', this.currentLimit);
		},
		dataLength() {
			this.updateCurrentPage();
		},
	},
	computed: {
		stickyClass() {
			return this.sticky ? 'tw-sticky tw-border-b tw-border-neutral-400 tw-top-0' : '';
		},
		stickyStyle() {
			let banner = document.querySelector('.alerte-message-container');
			if (banner) {
				let top = banner.offsetHeight;
				return { top: `${top}px` };
			}
		},
		totalPages() {
			return Math.ceil(this.dataLength / this.currentLimit);
		},
		pagesAvailable() {
			// the current page and the 5 pages around it
			let pages = [this.currentPage];

			for (let i = 1; i <= 5; i++) {
				if (this.currentPage - i > 0) {
					pages.unshift(this.currentPage - i);
				}
				if (this.currentPage + i <= this.totalPages) {
					pages.push(this.currentPage + i);
				}
			}

			return pages;
		},
	},
};
</script>

<style scoped>
.pagination li a {
	background: hsl(from var(--em-profile-color) h s l / 15%);
	border: none;
	color: var(--em-profile-color) !important;
	border-radius: var(--em-applicant-br);
	padding: 5px 10px;
	font-size: 12px;
	height: 30px;
	width: 30px;
	display: flex;
	justify-content: center;
	align-items: center;
	text-decoration: unset;
}
.pagination li.active a {
	background: var(--em-profile-color);
	color: var(--neutral-0) !important;
}
.pagination li:first-child a,
.pagination li:last-child a {
	background: none;
	border-radius: var(--em-applicant-br);
}
.pagination li a:hover {
	background: hsl(from var(--em-profile-color) h s l / 30%);
}
.pagination li a.disabled:hover {
	background: none;
}
.pagination li a.disabled span {
	color: var(--neutral-500) !important;
}
</style>
