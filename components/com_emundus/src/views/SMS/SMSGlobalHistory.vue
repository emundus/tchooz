<template>
  <div id="sms-history">
    <h1 class="tw-mb-4">{{ translate('COM_EMUNDUS_SMS_HISTORY') }}</h1>

    <div class="tw-flex tw-flex-row tw-justify-between">
      <div>
        <select v-model="selectedStatus" @change="onSelectStatus">
          <option value="" selected>{{ translate('COM_EMUNDUS_SMS_ALL_STATUS') }}</option>
          <option v-for="status in status" :key="status.value" :value="status.value">{{ status.label }}</option>
        </select>
      </div>

      <div class="tw-flex tw-items-center tw-min-w-[15rem]">
        <input type="text" class="!tw-rounded-coordinator !tw-h-[38px] tw-m-0" v-model="search" :placeholder="translate('COM_EMUNDUS_ACTIONS_SEARCH')" @keyup="onSearch"/>
        <span class="material-symbols-outlined tw-mr-2 tw-cursor-pointer tw-ml-[-32px]"> search </span>
      </div>
    </div>

    <Pagination
        :limit="limit"
        :page="page"
        :dataLength="total"
        @update:limit="onUpdateLimit"
        @update:page="onUpdatePage"
    ></Pagination>

    <div v-if="smsHistory.length > 0">
      <div
          v-for="sms in smsHistory"
          :key="sms.id"
          class="tw-border tw-border-neutral-300 em-card-shadow tw-rounded-lg tw-bg-white tw-p-6 tw-mb-4"
      >
        <div class="from tw-mb-2 tw-flex tw-justify-between">
          <div class="tw-flex tw-flex-col">
            <span class="tw-text-neutral-500 tw-text-xs">{{ sms.params.date }}</span>
            <span class="tw-text-xs">
              {{ translate('COM_EMUNDUS_EMAILS_MESSAGE_FROM') }} {{ sms.user_name_from }}
              {{ translate('COM_EMUNDUS_EMAILS_MESSAGE_TO') + ' ' }} <strong>{{ sms.fnum }}</strong>
            </span>

          </div>
          <div>
            <span v-if="sms.status === 'sent'" class="material-symbols-outlined tw-text-main-400" :title="translate('COM_EMUNDUS_SMS_SENT')">done_all</span>
            <span v-else-if="sms.status === 'pending'" class="material-symbols-outlined tw-text-yellow-600" :title="translate('COM_EMUNDUS_SMS_PENDING')">schedule_send</span>
            <span v-else-if="sms.status === 'failed'" class="material-symbols-outlined tw-text-red-400" :title="translate('COM_EMUNDUS_SMS_FAILED')">cancel_schedule_send</span>
          </div>
        </div>
        <p v-html="replaceWithBr(sms.params.message)" class="tw-whitespace-pre-line"></p>
      </div>
    </div>
    <div v-else id="empty-list" class="tw-text-center">
      <img src="@media/com_emundus/images/tchoozy/complex-illustrations/no-result.svg" alt="empty-list" class="tw-mx-auto tw-mt-8 tw-w-1/2" style="width: 10vw; height: 10vw; margin: 0 auto;"/>
      <p>{{ translate('COM_EMUNDUS_SMS_EMPTY_HISTORY') }}</p>
    </div>
  </div>
</template>

<script>
import smsService from "@/services/sms";
import Pagination from "@/components/Utils/Pagination.vue";

export default {
  name: "SMSGlobalHistory",
  components: {
    Pagination
  },
  data() {
    return {
      page: 1,
      limit: 10,
      total: 0,
      smsHistory: [],
      search: '',
      status: [
        {
          'value': 'sent',
          'label': this.translate('COM_EMUNDUS_SMS_SENT')
        },
        {
          'value': 'pending',
          'label': this.translate('COM_EMUNDUS_SMS_PENDING')
        },
        {
          'value': 'failed',
          'label': this.translate('COM_EMUNDUS_SMS_FAILED')
        }
      ],
      selectedStatus: ''
    }
  },
  created() {
    this.getGlobalHistory();
  },
  methods: {
    getGlobalHistory() {
      let pageoffset = this.page -1;

      smsService.getGlobalHistory(pageoffset, this.limit, this.search, this.selectedStatus).then(response => {
        this.total = response.data.count;
        this.smsHistory = response.data.datas.map((message) => {
          return {
            id: message.id,
            fnum: message.fnum,
            message: message.message,
            user_id_from: message.user_id_from,
            user_name_from: message.lastname + ' ' + message.firstname,
            params: message.params,
            status: message.status
          };
        });
      });
    },
    onUpdateLimit(limit) {
      this.limit = limit;
      this.page = 1;
      this.getGlobalHistory();
    },
    onUpdatePage(page) {
      this.page = page;
      this.getGlobalHistory();
    },
    onSearch() {
      this.page = 1;
      this.getGlobalHistory();
    },
    onSelectStatus() {
      this.page = 1;
      this.getGlobalHistory();
    },
    replaceWithBr(text) {
      return text.replace(/\n/g, '<br>').replace(/\r/g, '<br>');
    }
  },
}
</script>

<style scoped>

</style>