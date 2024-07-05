<template>
  <div id="form-builder-elements">
    <div class="tw-flex tw-items-center tw-justify-around">
      <div v-for="menu in menus" :key="menu.id" id="form-builder-elements-title" class="em-light-tabs tw-cursor-pointer"
           @click="selected = menu.id" :class="selected === menu.id ? 'em-light-selected-tab' : ''">
        {{ translate(menu.name) }}
      </div>
    </div>

    <div v-if="selected === 1">
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
            <span class="material-icons-outlined" style="font-size: 18px">{{ element.icon }}</span>
            <p class="tw-w-full tw-flex tw-flex-col">
              {{ translate(element.name) }}
              <span class="tw-text-neutral-600 tw-text-xs">{{ translate(element.description) }}</span>
            </p>
            <div class="tw-flex tw-items-center">
<!--              <span class="material-icons-outlined" style="font-size: 18px">drag_indicator</span>-->
              <span v-show="elementHovered == element.value" class="material-icons-outlined tw-cursor-copy" style="font-size: 18px" @click="$emit('create-element-lastgroup', element)">add_circle_outline</span>
            </div>
          </div>
        </transition-group>
      </draggable>
    </div>

    <div v-if="selected === 2">
      <div
          v-for="group in publishedGroups"
          :key="group.id"
          class="draggables-list"
          @click="addGroup(group)"
      >
        <div
            class="form-builder-element tw-flex tw-items-center tw-justify-between tw-cursor-pointer tw-gap-3 tw-p-3"
        >
          <span class="material-icons-outlined">{{ group.icon }}</span>
          <p class="tw-w-full tw-flex tw-flex-col">{{ translate(group.name) }}</p>
          <span class="material-icons-outlined">add_circle_outline</span>
        </div>
      </div>
    </div>

    <div class="em-page-loader" v-if="loading"></div>
  </div>
</template>

<script>
// external libraries
import draggable from 'vuedraggable';

import formBuilderService from '../../services/formbuilder';
import formBuilderMixin from '../../mixins/formbuilder';
import errorsMixin from '../../mixins/errors';

export default {
  components: {
    draggable
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
    }
  },
  created() {
    this.elements = this.getElements();
    this.groups = this.getSections();
  },
  methods: {
    getElements() {
      return require('../../../data/form-builder/form-builder-elements.json');
    },
    getSections() {
      return require('../../../data/form-builder/form-builder-sections.json');
    },
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

      const data = this.$store.getters['global/datas'];
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

      const data = this.$store.getters['global/datas'];
      const mode = typeof data.mode !== 'undefined' ? data.mode.value : 'forms';

      formBuilderService.createSectionSimpleElements({
        gid: group.id,
        fid: this.form.id,
        mode: mode
      }).then(response => {
        console.log(response);
        if (response.status && response.data.data.length > 0) {
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
    }
  },
  computed: {
    publishedElements() {
      return this.elements.filter(element => element.published);
    },
    publishedGroups() {
      return this.groups.filter(group => group.published);
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

  .em-light-selected-tab {
    border-bottom: 1px solid var(--main-400);
  }
</style>
