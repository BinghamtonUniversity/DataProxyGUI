<template>
  <div class="mt-4 p-4 bg-surface-container-low rounded-xl border border-outline-variant">
    <div class="space-y-4">
      <!-- Logical Operator Toggle -->
      <div class="flex items-center justify-center gap-4">
        <span class="text-sm font-medium text-on-surface-variant">or</span>
        <div class="relative">
          <input
            type="checkbox"
            :checked="modelValue[0].op === 'and'"
            @change="updateOperator($event.target.checked ? 'and' : 'or')"
            class="sr-only"
            :id="`${conditionType}-op-toggle`">
          <label
            :for="`${conditionType}-op-toggle`"
            class="flex items-center w-16 h-8 bg-outline rounded-full cursor-pointer transition-colors duration-200"
            :class="{ 'bg-primary': modelValue[0].op === 'and' }">
            <div
              class="w-6 h-6 bg-white rounded-full shadow-md transform transition-transform duration-200"
              :class="{ 'translate-x-8': modelValue[0].op === 'and' }">
            </div>
          </label>
        </div>
        <span class="text-sm font-medium text-on-surface-variant">and</span>
      </div>
      
      <!-- Condition Groups -->
      <div class="relative">
        <!-- Dashed line for visual grouping -->
        <div class="absolute left-4 top-0 bottom-0 w-0.5 border-l-2 border-dashed border-outline-variant"></div>
        
        <!-- Condition Groups -->
        <div v-for="(group, groupIndex) in modelValue" :key="groupIndex" class="mb-4">
          <div class="ml-8 p-4 bg-surface-container rounded-xl border border-outline">
            <!-- Condition Group Header -->
            <div class="flex items-center justify-between mb-3">
              <span class="text-sm font-medium text-on-surface">Condition Group {{ groupIndex + 1 }}</span>
              <button
                @click="removeConditionGroup(groupIndex)"
                type="button"
                class="text-error hover:text-error hover:bg-error-8 p-1 rounded transition-colors duration-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>
            
            <!-- Conditions in this group -->
            <div class="space-y-3">
              <!-- Type -->
              <div>
                <label class="block text-xs text-on-surface-variant mb-1 font-medium">Type</label>
                <div class="relative">
                  <select
                    v-model="group.conditions[0].type"
                    class="w-full px-3 py-2 rounded-lg border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-8">
                    <option value="matches">matches</option>
                    <option value="not_matches">not_matches</option>
                    <option value="contains">contains</option>
                    <option value="requires">requires</option>
                    <option value="conditions">conditions</option>
                  </select>
                  <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                    <svg class="w-4 h-4 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                  </div>
                </div>
              </div>
              
              <!-- Name (only show if type is not 'conditions') -->
              <div v-if="group.conditions[0].type !== 'conditions'">
                <label class="block text-xs text-on-surface-variant mb-1 font-medium">Name</label>
                <input
                  v-model="group.conditions[0].name"
                  type="text"
                  placeholder="field_name"
                  class="w-full px-3 py-2 rounded-lg border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200">
              </div>
              
              <!-- Values (only show if type is not 'requires' and not 'conditions') -->
              <div v-if="group.conditions[0].type !== 'requires' && group.conditions[0].type !== 'conditions'">
                <label class="block text-xs text-on-surface-variant mb-1 font-medium">Values</label>
                <div class="space-y-2">
                  <div v-for="(value, valueIndex) in group.conditions[0].value" :key="valueIndex" class="flex items-center gap-2 min-w-0">
                    <input
                      v-model="group.conditions[0].value[valueIndex]"
                      type="text"
                      placeholder="field_value"
                      class="min-w-0 flex-1 px-3 py-2 rounded-lg border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200">
                    <button
                      @click="removeValue(groupIndex, valueIndex)"
                      type="button"
                      class="flex-shrink-0 text-error hover:text-error hover:bg-error-8 p-2 rounded transition-colors duration-200">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                      </svg>
                    </button>
                  </div>
                </div>
                
                <!-- Add Value Button -->
                <button
                  @click="addValue(groupIndex)"
                  type="button"
                  class="flex items-center gap-2 text-primary hover:text-primary hover:bg-primary-8 px-3 py-2 rounded-lg transition-colors duration-200 mt-2">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                  </svg>
                  <span class="text-sm">Add Value</span>
                </button>
              </div>
              
              <!-- Nested Conditions (only show if type is 'conditions') -->
              <div v-if="group.conditions[0].type === 'conditions'" class="mt-4">
                <label class="block text-xs text-on-surface-variant mb-2 font-medium">Nested Conditions</label>
                <div class="p-3 bg-surface-container-low rounded-lg border border-outline-variant">
                  <div class="space-y-3">
                    <!-- Logical Operator Toggle -->
                    <div class="flex items-center justify-center gap-4">
                      <span class="text-xs font-medium text-on-surface-variant">or</span>
                      <div class="relative">
                        <input
                          type="checkbox"
                          :checked="group.conditions[0].nestedOp === 'and'"
                          @change="group.conditions[0].nestedOp = $event.target.checked ? 'and' : 'or'"
                          class="sr-only"
                          :id="`nested-${conditionType}-op-${groupIndex}`">
                        <label
                          :for="`nested-${conditionType}-op-${groupIndex}`"
                          class="flex items-center w-12 h-6 bg-outline rounded-full cursor-pointer transition-colors duration-200"
                          :class="{ 'bg-primary': group.conditions[0].nestedOp === 'and' }">
                          <div
                            class="w-4 h-4 bg-white rounded-full shadow-md transform transition-transform duration-200"
                            :class="{ 'translate-x-6': group.conditions[0].nestedOp === 'and' }">
                          </div>
                        </label>
                      </div>
                      <span class="text-xs font-medium text-on-surface-variant">and</span>
                    </div>
                    
                    <!-- Nested Condition Groups -->
                    <div class="relative">
                      <div class="absolute left-2 top-0 bottom-0 w-0.5 border-l border-dashed border-outline-variant"></div>
                      
                      <!-- Nested Condition Groups -->
                      <div v-for="(nestedGroup, nestedGroupIndex) in group.conditions[0].nestedConditions" :key="nestedGroupIndex" class="mb-3">
                        <div class="ml-4 p-3 bg-surface-container rounded-lg border border-outline">
                          <!-- Nested Group Header -->
                          <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-medium text-on-surface">Nested Group {{ nestedGroupIndex + 1 }}</span>
                            <button
                              @click="removeNestedConditionGroup(groupIndex, nestedGroupIndex)"
                              type="button"
                              class="text-error hover:text-error hover:bg-error-8 p-1 rounded transition-colors duration-200">
                              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                              </svg>
                            </button>
                          </div>
                          
                          <!-- Nested Conditions -->
                          <div class="space-y-2">
                            <!-- Type -->
                            <div>
                              <label class="block text-xs text-on-surface-variant mb-1">Type</label>
                              <div class="relative">
                                <select
                                  v-model="nestedGroup.type"
                                  class="w-full px-2 py-1 rounded border border-outline bg-surface-container text-on-surface text-xs focus:outline-none focus:ring-1 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-6">
                                  <option value="matches">matches</option>
                                  <option value="not_matches">not_matches</option>
                                  <option value="contains">contains</option>
                                  <option value="requires">requires</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-1 pointer-events-none">
                                  <svg class="w-3 h-3 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                  </svg>
                                </div>
                              </div>
                            </div>
                            
                            <!-- Name -->
                            <div>
                              <label class="block text-xs text-on-surface-variant mb-1">Name</label>
                              <input
                                v-model="nestedGroup.name"
                                type="text"
                                placeholder="field_name"
                                class="w-full px-2 py-1 rounded border border-outline bg-surface-container text-on-surface text-xs focus:outline-none focus:ring-1 focus:ring-primary-20 focus:border-primary transition-all duration-200">
                            </div>
                            
                            <!-- Values (only show if type is not 'requires') -->
                            <div v-if="nestedGroup.type !== 'requires'">
                              <label class="block text-xs text-on-surface-variant mb-1">Values</label>
                              <div class="space-y-1">
                                <div v-for="(value, valueIndex) in nestedGroup.value" :key="valueIndex" class="flex items-center gap-1 min-w-0">
                                  <input
                                    v-model="nestedGroup.value[valueIndex]"
                                    type="text"
                                    placeholder="field_value"
                                    class="min-w-0 flex-1 px-2 py-1 rounded border border-outline bg-surface-container text-on-surface text-xs focus:outline-none focus:ring-1 focus:ring-primary-20 focus:border-primary transition-all duration-200">
                                  <button
                                    @click="removeNestedValue(groupIndex, nestedGroupIndex, valueIndex)"
                                    type="button"
                                    class="flex-shrink-0 text-error hover:text-error hover:bg-error-8 p-1 rounded transition-colors duration-200">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                  </button>
                                </div>
                              </div>
                              
                              <!-- Add Nested Value Button -->
                              <button
                                @click="addNestedValue(groupIndex, nestedGroupIndex)"
                                type="button"
                                class="flex items-center gap-1 text-primary hover:text-primary hover:bg-primary-8 px-2 py-1 rounded transition-colors duration-200 mt-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                <span class="text-xs">Add Value</span>
                              </button>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Add Nested Condition Group Button -->
                    <div class="flex items-center justify-center mt-3">
                      <button
                        @click="addNestedConditionGroup(groupIndex)"
                        type="button"
                        class="flex items-center justify-center w-6 h-6 bg-primary text-white rounded-full hover:bg-primary-8 transition-colors duration-200">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Add Condition Group Button (Bottom) -->
      <div class="flex items-center justify-center mt-4">
        <button
          @click="addConditionGroup"
          type="button"
          class="flex items-center justify-center w-8 h-8 bg-primary text-white rounded-full hover:bg-primary-8 transition-colors duration-200">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
  modelValue: {
    type: Array,
    required: true
  },
  conditionType: {
    type: String,
    required: true,
    validator: (value) => ['show', 'edit', 'parse', 'required'].includes(value)
  }
});

const emit = defineEmits(['update:modelValue']);

function updateOperator(op) {
  const updatedValue = [...props.modelValue];
  updatedValue[0].op = op;
  emit('update:modelValue', updatedValue);
}

function addConditionGroup() {
  const updatedValue = [...props.modelValue];
  updatedValue.push({ 
    op: 'and', 
    conditions: [{ type: 'matches', name: '', value: [''] }] 
  });
  emit('update:modelValue', updatedValue);
}

function removeConditionGroup(groupIndex) {
  const updatedValue = [...props.modelValue];
  updatedValue.splice(groupIndex, 1);
  emit('update:modelValue', updatedValue);
}

function addValue(groupIndex) {
  const updatedValue = [...props.modelValue];
  updatedValue[groupIndex].conditions[0].value.push('');
  emit('update:modelValue', updatedValue);
}

function removeValue(groupIndex, valueIndex) {
  const updatedValue = [...props.modelValue];
  updatedValue[groupIndex].conditions[0].value.splice(valueIndex, 1);
  emit('update:modelValue', updatedValue);
}

function addNestedConditionGroup(groupIndex) {
  const updatedValue = [...props.modelValue];
  if (!updatedValue[groupIndex].conditions[0].nestedConditions) {
    updatedValue[groupIndex].conditions[0].nestedConditions = [];
  }
  updatedValue[groupIndex].conditions[0].nestedConditions.push({ 
    type: 'matches', 
    name: '', 
    value: [''] 
  });
  emit('update:modelValue', updatedValue);
}

function removeNestedConditionGroup(groupIndex, nestedGroupIndex) {
  const updatedValue = [...props.modelValue];
  updatedValue[groupIndex].conditions[0].nestedConditions.splice(nestedGroupIndex, 1);
  emit('update:modelValue', updatedValue);
}

function addNestedValue(groupIndex, nestedGroupIndex) {
  const updatedValue = [...props.modelValue];
  updatedValue[groupIndex].conditions[0].nestedConditions[nestedGroupIndex].value.push('');
  emit('update:modelValue', updatedValue);
}

function removeNestedValue(groupIndex, nestedGroupIndex, valueIndex) {
  const updatedValue = [...props.modelValue];
  updatedValue[groupIndex].conditions[0].nestedConditions[nestedGroupIndex].value.splice(valueIndex, 1);
  emit('update:modelValue', updatedValue);
}
</script> 