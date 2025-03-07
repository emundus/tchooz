<template>
  <div id="form-builder-elements" style="min-width: 260px">
    <div class="tw-flex tw-items-center tw-justify-around">
      <div v-for="menu in menus" :key="menu.id" id="form-builder-elements-title" class="em-light-tabs tw-cursor-pointer"
           @click="selected = menu.id" :class="selected === menu.id ? 'em-light-selected-tab' : ''">
        {{ translate(menu.name) }}
      </div>
    </div>

    <div v-if="selected === 1" class="tw-mt-2">
      <input
          v-model="keywords"
          type="text"
          class="formbuilder-searchbar"
          :placeholder="translate('COM_EMUNDUS_FORM_BUILDER_SEARCH_ELEMENT')"
      />
      <draggable
          v-model="publishedElements"
          class="draggables-list"
          :group="{ name: 'form-builder-section-elements', pull: 'clone', put: false }"
          :sort="false"
          :clone="setCloneElement"
          @end="onDragEnd"
      >
        <transition-group>
          <div
              v-for="element in publishedElements"
              :key="element.value"
              @mouseover="elementHovered = element.value" @mouseleave="elementHovered = 0"
              class="form-builder-element tw-flex tw-justify-between tw-items-start tw-gap-3 tw-p-3 tw-cursor-move"
          >
            <span class="material-symbols-outlined" style="font-size: 18px">{{ element.icon }}</span>
            <p class="tw-w-full tw-flex tw-flex-col">
              {{ translate(element.name) }}
              <span class="tw-text-neutral-600 tw-text-xs">{{ translate(element.description) }}</span>
            </p>
            <div class="tw-flex tw-items-center tw-h-[18px] tw-w-[18px]">
<!--              <span class="material-symbols-outlined" style="font-size: 18px">drag_indicator</span>-->
              <span v-show="elementHovered == element.value" class="material-symbols-outlined tw-cursor-copy" style="font-size: 18px" @click="clickCreateElement(element)">add_circle_outline</span>
            </div>
          </div>
        </transition-group>
      </draggable>
    </div>

    <div v-if="selected === 2" class="tw-mt-2">
      <input
          v-model="keywords"
          type="text"
          class="formbuilder-searchbar"
          :placeholder="translate('COM_EMUNDUS_FORM_BUILDER_SEARCH_SECTION')"
      />
      <div
          v-for="group in publishedGroups"
          :key="group.id"
          class="draggables-list"
          @click="addGroup(group)"
      >
        <div
            class="form-builder-element tw-flex tw-items-center tw-justify-between tw-cursor-pointer tw-gap-3 tw-p-3"
        >
          <span class="material-symbols-outlined">{{ group.icon }}</span>
          <p class="tw-w-full tw-flex tw-flex-col">{{ translate(group.name) }}</p>
          <span class="material-symbols-outlined">add_circle_outline</span>
        </div>
      </div>
    </div>

    <div class="em-page-loader" v-if="loading"></div>
  </div>
</template>

<script>
// external libraries
import { VueDraggableNext } from 'vue-draggable-next';
import formBuilderService from '@/services/formbuilder';
import eventsService from '@/services/events';
import formBuilderMixin from '@/mixins/formbuilder';
import errorsMixin from '@/mixins/errors';
import formBuilderElements from '../../../data/form-builder/form-builder-elements.json';
import formBuilderSections from '../../../data/form-builder/form-builder-sections.json';
import { useGlobalStore } from '@/stores/global';

export default {
  components: {
    draggable: VueDraggableNext
  },
  mixins: [formBuilderMixin, errorsMixin],
  props: {
    form: {
      type: Object,
      required: false
    }
  },
  data() {
    return {
      selected: 1,
      menus: [
        {
          id: 1,
          name: 'COM_EMUNDUS_FORM_BUILDER_ELEMENTS'
        },
        {
          id: 2,
          name: 'COM_EMUNDUS_FORM_BUILDER_SECTIONS'
        }
      ],
      elements: [],
      groups: [],
      cloneElement: {},
      loading: false,
      elementHovered: 0,
      keywords: '',
      debounce: false,

      eventsCount: 0,
    }
  },
  setup() {
    const globalStore = useGlobalStore();

    return {
      globalStore
    }
  },
  created() {
    this.elements = formBuilderElements;
    this.groups = formBuilderSections;

    eventsService.getEvents().then(response => {
      if (response.status) {
        this.eventsCount = response.data.count;

        // Remove the event element from the list if there are no events
        if (this.eventsCount === 0) {
          this.elements = this.elements.filter(element => element.value !== 'booking');
        }
      }
    });
  },
  methods: {
    setCloneElement(element) {
      this.cloneElement = element;
    },
    onDragEnd(event) {
      this.loading = true;
      const to = event.to;
      if (to === null) {
        this.loading = false;
        return;
      }

      const group_id = to.dataset.sid;
      if (!group_id) {
        this.loading = false;
        return;
      }

	    const data = this.globalStore.getDatas;
      const mode = typeof data.mode !== 'undefined' ? data.mode.value : 'forms';

      formBuilderService.createSimpleElement({
        gid: group_id,
        plugin: this.cloneElement.value,
        mode: mode
      }).then(response => {
        if (response.status && response.data > 0) {
          formBuilderService.updateElementOrder(group_id, response.data, event.newDraggableIndex).then(() => {
            this.$emit('element-created', response.data);
            this.updateLastSave();
            this.loading = false;
          });
        } else {
          this.displayError(response.msg);
          this.loading = false;
        }
      }).catch((error) => {
        console.warn(error);
        this.loading = false;
      });
    },
    addGroup(group) {
      this.loading = true;

      const globalStore = useGlobalStore();

      const data = globalStore.datas;
      const mode = typeof data.mode !== 'undefined' ? data.mode.value : 'forms';

      formBuilderService.createSectionSimpleElements({
        gid: group.id,
        fid: this.form.id,
        mode: mode
      }).then(response => {
        if (response.status && response.data.length > 0) {
          this.$emit('element-created');
          this.updateLastSave();
          this.loading = false;
        } else {
          this.displayError(response.msg);
          this.loading = false;
        }
      }).catch((error) => {
        console.warn(error);
        this.loading = false;
      });
    },
    clickCreateElement(element) {
      if(this.debounce) {
        return;
      }
      this.debounce = true;
      this.$emit('create-element-lastgroup', element)
      setTimeout(() => {
        this.debounce = false;
      }, 1000);
    }
  },
  computed: {
    publishedElements() {
      // Filter this.elements with keywords
      if (this.keywords) {
        return this.elements.filter(element => element.published && this.translate(element.name).toLowerCase().includes(this.keywords.toLowerCase()));
      } else {
        return this.elements.filter(element => element.published);
      }
    },
    publishedGroups() {
      if (this.keywords) {
        return this.groups.filter(group => group.published && this.translate(group.name).toLowerCase().includes(this.keywords.toLowerCase()));
      } else {
        return this.groups.filter(group => group.published);
      }
    }
  }
}
</script>

<style lang="scss">
.form-builder-element {
  width: 258px;
  height: auto;
  font-size: 14px;
  margin: 8px 0px;
  background-color: #FAFAFA;
  border: 1px solid #F2F2F3;
  border-radius: calc(var(--em-default-br) / 2);

  &:hover {
    background-color: var(--neutral-200);
  }
}

#form-builder-elements input.formbuilder-searchbar,
#form-builder-document-formats input.formbuilder-searchbar,
#form-builder-rules-list input.formbuilder-searchbar {
  border-width: 0 0 1px 0;
  border-radius: 0;
  border-color: var(--neutral-400);
  &:focus {
    outline: unset;
    border-bottom-color: var(--em-form-outline-color-focus);
  }
}

  .em-light-selected-tab {
    border-bottom: 1px solid var(--main-400);
  }
</style>
