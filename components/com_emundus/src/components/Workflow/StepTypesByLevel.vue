<template>
  <div :class="'step-types-level-' + parentId + ' tw-p-2'">
    <div v-for="type in stepTypesByParentId" :key="type.id">
      <div class="tw-w-full tw-flex tw-flex-row tw-items-center">
        <span v-for="i in level" :key="i" class="material-symbols-outlined">horizontal_rule</span>
        <input :id="'type-' + type.id + '-label'" :name="'type-' + type.id + '-label'"  v-model="type.label" />
        <span v-if="!type.system" class="material-symbols-outlined tw-cursor-pointer" @click="deleteType(type.id)">
          delete
        </span>
      </div>
      <div>
        <StepTypesByLevel @updateTypes="onUpdateTypes" v-if="stepTypesOfParentId(type.id).length > 0" :defaultTypes="types" :parentId="type.id" :level="level + 1"></StepTypesByLevel>
      </div>
      <div class="tw-w-full tw-flex tw-flex-row tw-items-center">
        <span v-for="i in (level+1)" :key="i" class="material-symbols-outlined">horizontal_rule</span>
        <button @click="addChildrenStepType(type)">
          {{ translate('COM_EMUNDUS_WORKFLOW_ADD_CHILDREN_STEP_TYPE') }}
        </button>
      </div>
    </div>
    <div v-if="level === 0" class="tw-flex tw-flex-row tw-justify-end">
      <button @click="saveStepTypes" class="tw-btn-primary">
        {{ translate('SAVE') }}
      </button>
    </div>
  </div>
</template>

<script>
import workflowService from '@/services/workflow.js';


export default {
  name: "StepTypesByLevel",
  props: {
    defaultTypes: {
      type: Array,
      required: true
    },
    parentId: {
      type: Number,
      default: 0
    },
    level: {
      type: Number,
      default: 0
    }
  },
  data() {
    return {
      types: []
    }
  },
  mounted() {
    this.types = this.defaultTypes;
  },
  methods: {
    addStepType() {
      this.types.push({
        id: this.lastId + 1,
        label: 'Nouveau type',
        parent_id: this.parentId
      });
      this.$emit('updateTypes', this.types);
    },
    deleteType(id) {
      this.types = this.types.filter(type => type.id !== id);
      this.$emit('updateTypes', this.types);
    },
    addChildrenStepType(type) {
      this.types.push({
        id: this.lastId + 1,
        label: 'Nouveau type',
        parent_id: type.id
      });
      this.$emit('updateTypes', this.types);
    },
    stepTypesOfParentId(parentId) {
      return this.types.filter(type => type.parent_id === parentId);
    },
    saveStepTypes() {
      workflowService.saveTypes(this.types).then(response => {
        if (response.status) {
          Swal.fire({
            icon: 'success',
            title: this.translate('COM_EMUNDUS_WORKFLOW_SAVE_STEP_TYPES_SUCCESS'),
            showConfirmButton: false,
            timer: 1500
          });
        }
      }).catch(error => {
        console.log(error);
      });
    },
    onUpdateTypes(types) {
      this.types = types;
      this.$emit('updateTypes', this.types);
    }
  },
  computed: {
    stepTypesByParentId() {
      return this.types.filter(type => type.parent_id === this.parentId);
    },
    lastId() {
      return this.types.reduce((acc, type) => {
        return type.id > acc ? type.id : acc;
      }, 0);
    }
  }
}
</script>

<style scoped>

</style>