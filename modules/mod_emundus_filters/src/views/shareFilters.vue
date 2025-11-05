<>
<template>
  <div id="share-filters" class="em-w-100 em-p-16">
    <div v-if="!displaySharings">
      <section id="share-to" class="em-mb-8">
        <p>
          {{ translate('MOD_EMUNDUS_FILTERS_SHARE_WITH_1') + filter.name + translate('MOD_EMUNDUS_FILTERS_SHARE_WITH_2')
          }}</p>
        <div v-if="usersToShareTo.length > 0" class="em-mt-8 em-mb-8">
          <label>{{ translate('MOD_EMUNDUS_FILTERS_SHARE_WITH_USERS') }} : </label>
          <div id="selected-users" class="em-mt-8 em-mb-8 em-flex-row">
            <div v-for="user in selectedUsersLabels" :key="user.id"
                 class="em-flex-row em-mb-8 em-mr-8 label label-default">
              <span class="material-icons-outlined em-pointer" @click="removeUser(user.id)">close</span>
              <span class="em-ml-8">{{ user.label }}</span>
            </div>
          </div>
          <advanced-select :filters="usersOptions" :close-on-choose="false" :position-absolute="true" :max-height="150"
                           @filter-selected="onSelectUser"></advanced-select>
        </div>

        <div v-if="groupsToShareTo.length > 0" class="em-mt-8 em-mb-8">
          <label>{{ translate('MOD_EMUNDUS_FILTERS_SHARE_WITH_GROUPS') }} : </label>
          <div id="selected-groups" class="selected-values-to-share em-mt-8 em-mb-8 em-flex-row">
            <div v-for="group in selectedGroupsLabels" :key="group.id"
                 class="em-flex-row em-mb-8 em-mr-8 label label-default">
              <span class="material-icons-outlined em-pointer" @click="removeGroup(group.id)">close</span>
              <span class="em-ml-8">{{ group.label }}</span>
            </div>
          </div>
          <advanced-select :filters="groupsOptions" :close-on-choose="false" :position-absolute="true" :max-height="150"
                           @filter-selected="onSelectGroup"></advanced-select>
        </div>
      </section>
      <button @click="displaySharings = true" class="tw-underline tw-underline-offset-1">
        {{ translate('MOD_EMUNDUS_FILTERS_DISPLAY_ALREADY_SHARED_TO') }}
      </button>
      <section id="share-actions" class="em-flex-row em-flex-row-justify-end">
        <button class="btn btn-primary" @click="shareFilter">{{ translate('MOD_EMUNDUS_FILTERS_SHARE_BUTTON') }}
        </button>
      </section>
    </div>
    <div v-else>
      <h2>{{ translate('MOD_EMUNDUS_FILTERS_SHARINGS_FOR') + ' ' + filter.name }}</h2>
      <section id="already-shared-with" class="tw-mt-4 tw-mb-4">
        <p>{{ translate('MOD_EMUNDUS_FILTERS_ALREADY_SHARED_WITH') }}</p>
        <div v-if="alreadySharedTo.users.length > 0" class="em-mt-8 em-mb-8">
          <label>{{ translate('MOD_EMUNDUS_FILTERS_ALREADY_SHARED_WITH_USERS') }} : </label>
          <div id="already-shared-users" class="selected-values-to-share em-mt-8 em-mb-8 em-flex-row">
            <div v-for="user in alreadySharedTo.users" :key="user.id"
                 class="em-flex-row em-mb-8 em-mr-8 label label-default">
              <span class="material-icons-outlined em-pointer" @click="deleteUserSharing(user.id)">close</span>
              <span class="em-ml-8">{{ user.label }}</span>
            </div>
          </div>
        </div>

        <div v-if="alreadySharedTo.groups.length > 0" class="em-mt-8 em-mb-8">
          <label>{{ translate('MOD_EMUNDUS_FILTERS_ALREADY_SHARED_WITH_GROUPS') }} : </label>
          <div id="already-shared-groups" class="em-mt-8 em-mb-8 em-flex-row">
            <div v-for="group in alreadySharedTo.groups" :key="group.id"
                 class="em-flex-row em-mb-8 em-mr-8 label label-default">
              <span class="material-icons-outlined em-pointer"
                    @click="deleteGroupSharing(group.id, 'group')">close</span>
              <span class="em-ml-8">{{ group.label }}</span>
            </div>
          </div>
        </div>
      </section>
      <div class="tw-flex tw-flex-row tw-items-end tw-justify-end">
        <button class="em-primary-button tw-w-fit" @click="displaySharings = false">{{ translate('OK') }}</button>
      </div>
    </div>
  </div>
</template>

<script>
  import AdvancedSelect from '@/components/AdvancedSelect.vue'
  import groupsService from '@/services/groups.js'
  import filtersService from '@/services/filters.js'

  export default {
    name: 'ShareFilters',
    components: { AdvancedSelect },
    props: {
      filter: {
        type: Object,
        required: true
      }
    },
    data() {
      return {
        displaySharings: false,
        selectedUsers: [],
        usersToShareTo: [],
        selectedGroups: [],
        groupsToShareTo: [],
        alreadySharedTo: {
          users: [],
          groups: []
        }
      }
    },
    mounted() {
      // if filter does not have id or name => close modal
      if (!this.filter.id || !this.filter.name) {
        this.$emit('close')
      }

      this.getUsersToShareTo()
      this.getGroupsToShareTo()
      this.getAlreadySharedTo()
    },
    methods: {
      getUsersToShareTo() {
        groupsService.getUsersToShareTo().then((users) => {
          this.usersToShareTo = users
        })
      },
      getGroupsToShareTo() {
        groupsService.getGroupsToShareTo().then((groups) => {
          this.groupsToShareTo = groups
        })
      },
      getAlreadySharedTo() {
        filtersService.getAlreadySharedTo(this.filter.id).then((response) => {
          this.alreadySharedTo = response
        })
      },
      shareFilter() {
        filtersService.shareFilter(this.filter.id, this.selectedUsers, this.selectedGroups).then((response) => {
          if (response.status) {
            this.$emit('close')
          } else {
            console.log(response)
          }
        })
      },
      deleteUserSharing(id) {
        filtersService.deleteSharing(this.filter.id, id, 'user_id').catch((error) => {
          console.log(error)
        })
        this.alreadySharedTo.users = this.alreadySharedTo.users.filter((user) => user.id !== id)
      },
      deleteGroupSharing(id) {
        filtersService.deleteSharing(this.filter.id, id, 'group_id').catch((error) => {
          console.log(error)
        })
        this.alreadySharedTo.groups = this.alreadySharedTo.groups.filter((group) => group.id !== id)
      },
      onSelectUser(id) {
        this.selectedUsers.push(id)
      },
      onSelectGroup(id) {
        this.selectedGroups.push(id)
      },
      removeUser(id) {
        this.selectedUsers = this.selectedUsers.filter((user) => user !== id)
      },
      removeGroup(id) {
        this.selectedGroups = this.selectedGroups.filter((group) => group !== id)
      }
    },
    computed: {
      usersOptions() {
        return this.usersToShareTo.filter((user) => {
          const foundInAlreadyShared = this.alreadySharedTo.users.find((u) => u.id === user.id)
          return !this.selectedUsers.includes(user.id) && !foundInAlreadyShared
        })
      },
      groupsOptions() {
        return this.groupsToShareTo.filter((group) => {
          const foundInAlreadyShared = this.alreadySharedTo.groups.find((g) => g.id === group.id)
          return !this.selectedGroups.includes(group.id) && !foundInAlreadyShared
        })
      },
      selectedUsersLabels() {
        return this.selectedUsers.map((user) => {
          return this.usersToShareTo.find((u) => u.id === user)
        })
      },
      selectedGroupsLabels() {
        return this.selectedGroups.map((group) => {
          return this.groupsToShareTo.find((g) => g.id === group)
        })
      }
    }
  }
</script>

<style scoped>
  .selected-values-to-share {
    flex-wrap: wrap;
    max-height: 100px;
    overflow-y: auto;
  }
</style>
</>