<script>
import List from '@/views/List.vue';

export default {
	name: 'Products',
	components: {
		List,
	},
	data() {
		return {
			config: {
				products: {
					title: 'COM_EMUNDUS_ONBOARD_PRODUCTS_TITLE',
					intro: 'COM_EMUNDUS_ONBOARD_PRODUCTS_INTRO',
					tabs: [
						{
							title: 'COM_EMUNDUS_PRODUCTS',
							key: 'products',
							controller: 'payment',
							getter: 'getproducts',
							noData: 'COM_EMUNDUS_ONBOARD_NO_PRODUCTS',
							actions: [
								{
									action: 'index.php?option=com_emundus&view=payment&layout=productedit',
									label: 'COM_EMUNDUS_ONBOARD_ADD_PRODUCT',
									controller: 'payment',
									name: 'add',
									type: 'redirect',
								},
								{
									action: 'index.php?option=com_emundus&view=payment&layout=productedit&id=%id%',
									label: 'COM_EMUNDUS_ONBOARD_MODIFY',
									controller: 'payment',
									type: 'redirect',
									name: 'edit',
								},
								{
									action: 'duplicateproduct',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_DUPLICATE',
									controller: 'payment',
									name: 'duplicate',
									multiple: true,
									method: 'post',
								},
								{
									action: 'deleteproduct',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_DELETE',
									controller: 'payment',
									name: 'delete',
									multiple: true,
									method: 'delete',
									confirm: 'COM_EMUNDUS_ONBOARD_PRODUCT_DELETE',
								},
							],
							filters: [
								{
									label: 'COM_EMUNDUS_ONBOARD_PRODUCT_FILTER_CATEGORY',
									allLabel: 'COM_EMUNDUS_ONBOARD_PRODUCT_FILTER_CATEGORY_ALL',
									getter: 'getfilterproductcategory',
									controller: 'payment',
									key: 'category',
									values: null,
									multiselect: true,
									alwaysDisplay: true,
								},
							],
						},
						{
							title: 'COM_EMUNDUS_DISCOUNTS',
							key: 'discounts',
							controller: 'payment',
							getter: 'getdiscounts',
							noData: 'COM_EMUNDUS_ONBOARD_NO_DISCOUNTS',
							actions: [
								{
									action: 'index.php?option=com_emundus&view=payment&layout=discountedit',
									label: 'COM_EMUNDUS_ONBOARD_ADD_DISCOUNT',
									controller: 'payment',
									name: 'add',
									type: 'redirect',
								},
								{
									action: 'index.php?option=com_emundus&view=payment&layout=discountedit&id=%id%',
									label: 'COM_EMUNDUS_ONBOARD_MODIFY',
									controller: 'payment',
									type: 'redirect',
									name: 'edit',
								},
								{
									action: 'deletediscount',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_DELETE',
									controller: 'payment',
									name: 'delete',
									multiple: true,
									method: 'delete',
									confirm: 'COM_EMUNDUS_ONBOARD_DISCOUNT_DELETE',
								},
							],
						},
					],
				},
			},
		};
	},
};
</script>

<template>
	<div id="products-list">
		<list :default-lists="config" :default-type="'products'" :encoded="false"></list>
	</div>
</template>

<style scoped></style>
