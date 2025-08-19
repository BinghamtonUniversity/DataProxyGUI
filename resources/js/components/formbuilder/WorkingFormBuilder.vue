<template>

  <div :class="formbuilderTheme.container">
    <div :class="formbuilderTheme.headerContainer">
      <h1 :class="formbuilderTheme.header">FormBuilder</h1>
      <p :class="formbuilderTheme.subheader">Drag field types above the canvas. Reorder fields. Configure field properties.</p>
    </div>
    <div class="flex flex-row gap-6">
      <!-- Left: Form Configuration Sidebar -->
      <div class="w-80 shrink-0 p-4">
        <div :class="formbuilderTheme.configSection">
          <h4 :class="formbuilderTheme.configHeader + ' mb-3'">Form Configuration</h4>
          
          <!-- Navigation Breadcrumb -->
          <div v-if="navigationPath.length > 0" class="mb-4 p-3 bg-surface-container-low rounded-lg border border-outline-variant">
            <div class="text-sm text-on-surface-variant mb-2">Navigation:</div>
            <div class="space-y-1">
              <button
                @click="navigateToLevel(-1)"
                class="block w-full text-left text-primary hover:text-primary-high transition-colors duration-200 font-medium">
                {{ formName }}
              </button>
              <template v-for="(item, index) in navigationPath" :key="index">
                <div class="flex items-center gap-1 pl-2">
                  <svg class="w-3 h-3 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                  </svg>
                  <button
                    @click="navigateToLevel(index)"
                    class="text-primary hover:text-primary-high transition-colors duration-200 font-medium">
                    {{ item.label }}
                  </button>
                </div>
              </template>
            </div>
          </div>
          <div class="space-y-3">
            <div>
              <label class="block text-xs text-on-surface-variant mb-2 font-medium">Form Name</label>
              <input
                v-model="formName"
                type="text"
                placeholder="Enter form name"
                class="md3-text-field">
            </div>
            <div class="text-xs text-on-surface-variant">
              <span class="font-medium">Files:</span> false (fixed value)
            </div>
          </div>
        </div>
      </div>
      <!-- Center: Main Area (Draggable Types + Canvas) -->
      <div class="flex-1 flex flex-col gap-4">
        <!-- Draggable field types (horizontal) -->
        <div class="flex flex-row gap-1 mb-4 pt-2">
          <div v-for="type in fieldTypes" :key="type.type"
            draggable="true"
            @dragstart="onDragStart($event, type)"
            class="flex flex-col items-center justify-center p-2 bg-white dark:bg-surface-container border-2 border-outline rounded-lg cursor-move hover:border-primary hover:shadow-elevation-2 transition-all duration-200 group min-w-[80px]">
            <!-- Icon -->
            <div class="mb-1">
              <svg v-if="type.type === 'input'" class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
              </svg>
              <svg v-else-if="type.type === 'options'" class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
              </svg>
              <svg v-else-if="type.type === 'boolean'" class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <svg v-else-if="type.type === 'section'" class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
              </svg>
            </div>
            <!-- Label -->
            <span class="text-xs font-medium text-on-surface group-hover:text-primary transition-colors duration-200">{{ type.label }}</span>
          </div>
        </div>
        <!-- Canvas: Drop/reorder fields -->
        <div :class="formbuilderTheme.canvas">
          <div
            :class="formbuilderTheme.canvasDrop"
            @dragover="onDragOver($event)"
            @dragenter="onDragEnter($event)"
            @dragleave="onDragLeave($event)"
            @drop="onDrop($event)"
            @dragend="onDragEnd($event)">
                          <div class="space-y-4">
              <!-- Drop preview at the top -->
                            <draggable v-model="currentFieldsWithPreviews" group="fields" item-key="name" class="space-y-4">
              <template #item="{ element, index }">
                <div>
                  <!-- Preview Element -->
                  <div
                    v-if="element.isPreview"
                    class="h-16 border-2 border-dashed border-primary bg-primary-8 rounded-lg flex items-center justify-center transition-all duration-200">
                    <div class="flex items-center gap-2 text-primary">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                      </svg>
                      <span class="text-sm font-medium">{{ element.label }} (pos: {{ previewPosition }})</span>
                    </div>
                  </div>
                  <!-- Regular Field -->
                  <div
                    v-else
                    @click="selectField(getOriginalIndex(index))"
                    :class="[
                      formbuilderTheme.fieldCard,
                      'field-card',
                      selectedFieldIndex === getOriginalIndex(index)
                        ? formbuilderTheme.fieldCardSelected
                        : formbuilderTheme.fieldCardUnselected
                    ]">
                    <!-- Field Preview -->
                    <div class="space-y-3">
                    <!-- Field Label with Info Icon -->
                    <div class="flex items-center justify-between">
                      <label :class="formbuilderTheme.fieldLabel">
                        {{ element.label }}
                        <span v-if="element.required" class="text-error ml-1">*</span>
                        <span
                          v-if="element.info"
                          class="relative cursor-pointer ml-1"
                          @mouseenter="hoveredInfoIndex = index"
                          @mouseleave="hoveredInfoIndex = null"
                        >
                          <svg class="w-4 h-4 text-primary inline" fill="currentColor" viewBox="0 0 20 20">
                            <circle cx="10" cy="10" r="9" fill="var(--md-sys-color-primary)"/>
                            <text x="10" y="15" text-anchor="middle" font-size="12" fill="white">?</text>
                          </svg>
                          <span
                            v-if="hoveredInfoIndex === index"
                            class="absolute left-1/2 z-10 -translate-x-1/2 mt-2 w-48 p-2 rounded-2xl bg-surface-container text-xs text-on-surface shadow-elevation-3 border border-outline"
                            style="pointer-events: none;"
                          >
                            {{ element.info }}
                          </span>
                        </span>
                      </label>
                      <span :class="formbuilderTheme.fieldType">{{ element.type }}</span>
                    </div>
                    <!-- Section Content -->
                    <div v-if="element.type === 'section' || element.type === 'fieldset'" class="space-y-3">
                      <p v-if="element.description" :class="formbuilderTheme.sectionDesc">{{ element.description }}</p>
                      
                      <!-- Show nested fields as regular fields -->
                      <div v-if="element.fields && Array.isArray(element.fields) && element.fields.length > 0" class="mt-4 space-y-3">
                        <div v-for="nestedField in element.fields" :key="nestedField?.name || 'nested'" class="border border-outline-variant rounded-lg p-3 bg-surface-container-low">
                          <!-- Nested Field Label -->
                          <div class="flex items-center justify-between mb-2">
                            <label :class="formbuilderTheme.fieldLabel">
                              {{ nestedField.label || 'Unnamed Field' }}
                              <span v-if="nestedField.required" class="text-error ml-1">*</span>
                            </label>
                            <span :class="formbuilderTheme.fieldType">{{ nestedField.type || 'unknown' }}</span>
                          </div>
                          
                          <!-- Nested Field Preview -->
                          <div v-if="nestedField.type === 'input'" class="space-y-2">
                            <input
                              :type="nestedField.inputType || 'text'"
                              :placeholder="nestedField.placeholder || getDefaultPlaceholder(nestedField.inputType, nestedField.label)"
                              :disabled="true"
                              :class="formbuilderTheme.input + ' cursor-not-allowed'">
                            <div v-if="nestedField.help" :class="formbuilderTheme.help" v-html="nestedField.help"></div>
                          </div>
                          
                          <div v-else-if="nestedField.type === 'options'" class="space-y-2">
                            <select :class="formbuilderTheme.select + ' cursor-not-allowed'" disabled>
                              <option>Select {{ nestedField.label ? nestedField.label.toLowerCase() : 'option' }}</option>
                            </select>
                            <div v-if="nestedField.help" :class="formbuilderTheme.help" v-html="nestedField.help"></div>
                          </div>
                          
                          <div v-else-if="nestedField.type === 'boolean'" class="space-y-2">
                            <div class="flex items-center">
                              <input type="checkbox" :disabled="true" class="cursor-not-allowed">
                              <label class="ml-2 text-sm text-on-surface">
                                {{ nestedField.defaultValue ? (nestedField.trueLabel || 'Yes') : (nestedField.falseLabel || 'No') }}
                              </label>
                            </div>
                            <div v-if="nestedField.help" :class="formbuilderTheme.help" v-html="nestedField.help"></div>
                          </div>
                          
                          <div v-else class="text-xs text-on-surface-variant">
                            {{ nestedField.type || 'unknown' }} field
                          </div>
                        </div>
                      </div>
                    </div>
                    <!-- Input Field Preview -->
                    <div v-if="element.type === 'input' && element.inputType !== 'hidden'">
                      <!-- Textarea -->
                      <textarea
                        v-if="element.inputType === 'textarea'"
                        :placeholder="element.placeholder || 'Enter ' + element.label.toLowerCase()"
                        :required="element.required"
                        :disabled="element.fillable === false"
                        rows="3"
                        :class="formbuilderTheme.textarea + ' cursor-not-allowed'">
                      </textarea>
                      <!-- Hidden field indicator -->
                      <div v-else-if="element.inputType === 'hidden'" :class="formbuilderTheme.info + ' italic'">
                        Hidden field: {{ element.name }}
                      </div>
                      <!-- Output field -->
                      <div v-else-if="element.inputType === 'output'" :class="formbuilderTheme.previewOutput" v-html="element.formatValue || (element.format && element.format.value) || ''"></div>
                      <!-- Color picker -->
                      <div v-else-if="element.inputType === 'color'" class="flex items-center gap-2">
                        <input
                          type="color"
                          value="#3b82f6"
                          :disabled="element.fillable === false"
                          class="w-12 h-8 border border-gray-300 dark:border-gray-600 rounded cursor-not-allowed">
                        <span :class="formbuilderTheme.sectionDesc">Color picker</span>
                      </div>
                      <!-- Currency field -->
                      <div v-else-if="element.inputType === 'currency'" class="relative">
                        <span class="absolute left-3 top-2 text-sm text-gray-500 dark:text-gray-400">$</span>
                        <input
                          type="number"
                          :placeholder="element.placeholder || '0.00'"
                          :required="element.required"
                          :disabled="element.fillable === false"
                          class="w-full pl-8 pr-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white cursor-not-allowed">
                      </div>
                      <!-- Standard input types with pre/post -->
                      <div v-else-if="['tel','email','url'].includes(element.inputType)" class="flex items-stretch">
                        <span class="inline-flex items-center px-3 border border-r-0 border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-sm font-normal rounded-l-md">
                          <i v-if="element.inputType==='tel'" class="fa fa-phone"></i>
                          <i v-else-if="element.inputType==='email'" class="fa fa-envelope"></i>
                          <i v-else-if="element.inputType==='url'" class="fa fa-link"></i>
                        </span>
                        <input
                          :type="element.inputType"
                          :placeholder="element.placeholder || getDefaultPlaceholder(element.inputType, element.label)"
                          :required="element.required"
                          :disabled="element.fillable === false"
                          :value="element.value"
                          class="flex-1 min-w-0 py-2 px-3 text-sm border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white cursor-not-allowed rounded-r-md">
                      </div>
                      <div v-else class="flex items-stretch w-full">
                        <span v-if="element.pre" class="inline-flex items-center px-3 border border-r-0 border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-sm font-normal rounded-l-md" v-html="element.pre"></span>
                        <input
                          :type="element.inputType || 'text'"
                          :placeholder="element.placeholder || getDefaultPlaceholder(element.inputType, element.label)"
                          :required="element.required"
                          :disabled="element.fillable === false"
                          :value="element.value"
                          :class="formbuilderTheme.input + ' cursor-not-allowed'">
                        <span v-if="element.post" class="inline-flex items-center px-3 border border-l-0 border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-sm font-normal rounded-r-md" v-html="element.post"></span>
                      </div>
                      <div v-if="element.help" :class="formbuilderTheme.help" v-html="element.help"></div>
                    </div>
                    <!-- Options/Select Preview -->
                    <div v-if="['dropdown', 'list', 'combobox', 'range'].includes(element.type) || (element.type === 'options' && element.optionsType)">
                      <!-- Dropdown Preview -->
                      <select
                        v-if="element.type === 'dropdown' || (element.type === 'options' && element.optionsType === 'dropdown')"
                        :multiple="element.multiple"
                        :disabled="element.fillable === false"
                        data-select="true"
                        :class="formbuilderTheme.select + ' cursor-not-allowed'">
                        <option value="" disabled selected>Select {{ element.label ? element.label.toLowerCase() : 'option' }}</option>
                        <template v-if="element.options && Array.isArray(element.options)">
                          <template v-for="section in element.options" :key="section?.label || 'section'">
                            <optgroup v-if="section?.label" :label="section?.label">
                              <option v-for="option in (section?.options || [])" :key="option?.value || 'option'" :value="option?.value">
                                {{ option?.label || option?.value || 'Option' }}
                              </option>
                            </optgroup>
                          </template>
                          <template v-if="!element.options.some(s => s?.label)">
                            <option v-for="option in getAllOptions(element.options)" :key="option?.value || 'option'" :value="option?.value">
                              {{ option?.label || option?.value || 'Option' }}
                            </option>
                          </template>
                        </template>
                      </select>
                      <div v-if="element.help && (element.type === 'dropdown' || (element.type === 'options' && element.optionsType === 'dropdown'))" :class="formbuilderTheme.help" v-html="element.help"></div>
                      
                      <!-- List Preview -->
                      <div v-else-if="element.type === 'list' || (element.type === 'options' && element.optionsType === 'list')" class="space-y-2">
                        <template v-if="element.options && Array.isArray(element.options)">
                          <div v-for="section in element.options" :key="section?.label || 'section'" class="space-y-1">
                            <div v-if="section?.label" class="text-xs font-medium text-on-surface-variant">
                              {{ section.label }}
                            </div>
                            <div class="space-y-1">
                              <label v-for="option in (section?.options || [])" :key="option?.value || 'option'" class="flex items-center text-sm">
                                <input
                                  :type="element.multiple ? 'checkbox' : 'radio'"
                                  :name="element.multiple ? `${element.name}_${option?.value}` : element.name"
                                  :disabled="element.fillable === false"
                                  class="mr-2 cursor-not-allowed">
                                <span>{{ option?.label || option?.value || 'Option' }}</span>
                              </label>
                            </div>
                          </div>
                        </template>
                        <div v-if="element.help" :class="formbuilderTheme.help" v-html="element.help"></div>
                      </div>
                      
                      <!-- Combobox Preview -->
                      <div v-else-if="element.type === 'combobox' || (element.type === 'options' && element.optionsType === 'combobox')" class="relative">
                        <input
                          type="text"
                          :placeholder="element.placeholder || 'Type or select...'"
                          :disabled="element.fillable === false"
                          :class="formbuilderTheme.input + ' cursor-not-allowed pr-10'">
                        <div class="absolute right-2 top-1/2 -translate-y-1/2 text-on-surface-variant">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                          </svg>
                        </div>
                        <div v-if="element.help" :class="formbuilderTheme.help" v-html="element.help"></div>
                      </div>
                      
                      <!-- Range Preview -->
                      <div v-else-if="element.type === 'range' || (element.type === 'options' && element.optionsType === 'range')" class="space-y-3">
                        <!-- Range Slider Container -->
                        <div class="relative">
                          <!-- Custom Range Slider -->
                          <div class="relative w-full h-8 flex items-center justify-center">
                            <input
                              type="range"
                              :min="getRangeMin(element)"
                              :max="getRangeMax(element)"
                              :step="getRangeStep(element)"
                              :value="getRangeMin(element)"
                              :disabled="element.fillable === false"
                              @input="updateRangeGradient($event, element)"
                              class="w-full h-2 bg-outline-variant rounded-lg appearance-none cursor-not-allowed slider-track">
                          </div>
                          
                          <!-- Option Labels -->
                          <div v-if="element.options && Array.isArray(element.options)" class="mt-3 relative">
                            <div class="flex justify-between text-xs text-on-surface-variant">
                              <template v-for="section in element.options" :key="section?.label || 'section'">
                                <template v-for="option in (section?.options || [])" :key="option?.value || 'option'" class="relative">
                                  <div class="flex flex-col items-center">
                                    <div class="w-1 h-3 bg-outline-variant rounded-full mb-1"></div>
                                    <span class="text-xs text-on-surface-variant text-center max-w-16 truncate" :title="option?.label || option?.value">
                                      {{ option?.label || option?.value }}
                                    </span>
                                  </div>
                                </template>
                              </template>
                            </div>
                          </div>
                        </div>
                        
                        <!-- Range Info -->
                        <div class="flex justify-between items-center text-xs text-on-surface-variant bg-surface-container-low px-3 py-2 rounded-lg">
                          <span class="font-medium">Range:</span>
                          <span>{{ getRangeMin(element) }} - {{ getRangeMax(element) }}</span>
                          <span class="font-medium">Step: {{ getRangeStep(element) }}</span>
                        </div>
                        
                        <div v-if="element.help" :class="formbuilderTheme.help" v-html="element.help"></div>
                      </div>
                    </div>
                    

                    

                    <!-- Boolean/Checkbox Preview -->
                    <div v-if="element.type === 'boolean'">
                      <div class="flex items-center">
                        <!-- Checkbox Type -->
                        <template v-if="element.booleanType === 'checkbox'">
                          <input
                            type="checkbox"
                            :checked="element.defaultValue"
                            :disabled="element.fillable === false"
                            class="cursor-not-allowed">
                          <label class="ml-2 text-sm text-on-surface">
                            {{ element.defaultValue ? (element.trueLabel || 'Yes') : (element.falseLabel || 'No') }}
                          </label>
                        </template>
                        
                        <!-- Switch Type -->
                        <template v-else-if="element.booleanType === 'switch'">
                          <div class="flex items-center">
                            <div
                              class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors duration-200"
                              :class="element.defaultValue ? 'bg-primary' : 'bg-gray-300'">
                              <span
                                class="inline-block h-3 w-3 transform rounded-full bg-white transition-transform duration-200"
                                :class="element.defaultValue ? 'translate-x-5' : 'translate-x-1'">
                              </span>
                            </div>
                                                            <label class="ml-2 text-sm text-on-surface">
                                {{ element.defaultValue ? (element.trueLabel || 'Yes') : (element.falseLabel || 'No') }}
                              </label>
                          </div>
                        </template>
                      </div>
                      <div v-if="element.help" :class="formbuilderTheme.help" v-html="element.help"></div>
                    </div>
                    <!-- Field Name (small text) -->
                    <div :class="formbuilderTheme.info">
                      Field name: <code class="bg-surface-container-high px-2 py-1 rounded-lg text-on-surface-variant">{{ element.name }}</code>
                    </div>
                  </div>
                </div>
                </div>
                
              </template>
            </draggable>
            
            <div v-if="getCurrentFields().length === 0" class="text-on-surface-variant text-center py-8">
              Drag fields here
            </div>
          </div>
          </div>
        </div>

      </div>
      <!-- Right: Field Configuration Sidebar -->
      <div class="w-[420px] shrink-0">
        <!-- Field Configuration -->
        <div :class="formbuilderTheme.configPanel">
          <!-- Action Buttons -->
          <div class="flex gap-2 mb-4 justify-end p-4">
            <button
              @click="showPreviewModal"
              :class="formbuilderTheme.configButton">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
              </svg>
              Preview
            </button>
            <button
              @click="showJsonModal"
              :class="formbuilderTheme.configButtonSecondary">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
              </svg>
              View JSON
            </button>
          </div>
          
          <!-- Field Configuration -->
          <div class="mb-4">
            <label :class="formbuilderTheme.configHeader + ' mb-3'">Field Configuration</label>
          </div>
                      <!-- Show selected field configuration or placeholder -->
          <div v-if="selectedField" class="space-y-4">
            <div class="mb-4 p-4 bg-surface-container-low rounded-2xl border border-outline-variant">
              <div class="flex items-center justify-between mb-4">
                <span :class="formbuilderTheme.sectionTitle">{{ selectedField.label }}</span>
                <button
                  @click="removeField(selectedFieldIndex)"
                  class="inline-flex items-center gap-2 px-3 py-1.5 text-error hover:text-error hover:bg-error-8 rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-error focus:ring-offset-2 text-sm font-medium">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                  </svg>
                  Remove
                </button>
              </div>
              <!-- Manage Section Button for Fieldsets -->
              <div v-if="selectedField.type === 'section' || selectedField.type === 'fieldset'" class="mb-4">
                <button
                  @click="manageSection(selectedFieldIndex)"
                  class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-primary text-on-primary rounded-xl hover:bg-primary-high transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 text-sm font-medium">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                  </svg>
                  Manage Section
                </button>
              </div>
              
              <!-- Field-specific configuration -->
              <div v-if="selectedField.type === 'input'">
                <!-- Basic Configuration -->
                <div class="mb-4">
                  <button
                    @click="toggleConfigSection('basic')"
                    class="flex items-center justify-between w-full text-left font-medium text-on-surface mb-3 p-3 rounded-xl hover:bg-surface-container-high transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                    <span class="flex items-center gap-2">
                      <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                      </svg>
                      Basic
                    </span>
                    <svg :class="['w-5 h-5 text-on-surface-variant transition-transform duration-200', configSections.basic ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                  </button>
                  <div class="config-section-content space-y-3 pl-2 border-l-2 border-outline-variant" :class="configSections.basic ? 'expanded' : 'collapsed'">
                    <!-- Type Selector -->
                    <div>
                      <label class="block text-xs text-on-surface-variant mb-2 font-medium">Type</label>
                      <div class="relative">
                        <select
                          v-model="selectedField.inputType"
                          class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                          <option value="text">Text</option>
                          <option value="textarea">Textarea</option>
                          <option value="tel">Telephone</option>
                          <option value="email">Email</option>
                          <option value="url">URL</option>
                          <option value="date">Date</option>
                          <option value="number">Number</option>
                          <option value="currency">Currency</option>
                          <option value="password">Password</option>
                          <option value="color">Color</option>
                          <option value="output">Output</option>
                          <option value="hidden">Hidden</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                          <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                          </svg>
                        </div>
                      </div>
                    </div>
                    <!-- Field Label and Name -->
                    <div class="flex gap-4">
                      <div class="flex-1">
                        <label class="block text-xs text-on-surface-variant mb-2 font-medium">Field Label<span class="text-error">*</span></label>
                        <input
                          v-model="selectedField.label"
                          type="text"
                          placeholder="Label"
                          class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200">
                      </div>
                      <div class="flex-1">
                        <label class="block text-xs text-on-surface-variant mb-2 font-medium">Name</label>
                        <input
                          v-model="selectedField.name"
                          type="text"
                          placeholder="Name"
                          class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200">
                      </div>
                    </div>
                    <!-- Placeholder, Pre, Post or Display -->
                    <template v-if="selectedField.inputType === 'output'">
                      <div>
                        <label :class="formbuilderTheme.configLabel">Display</label>
                        <textarea
                          v-model="selectedField.formatValue"
                          rows="2"
                          placeholder="Enter display HTML/text"
                          :class="formbuilderTheme.textarea"></textarea>
                      </div>
                    </template>
                    <template v-else>
                      <!-- Placeholder -->
                      <div>
                        <label :class="formbuilderTheme.configLabel">Placeholder</label>
                        <input
                          v-model="selectedField.placeholder"
                          type="text"
                          placeholder="Placeholder"
                          :class="formbuilderTheme.configInput">
                      </div>
                      <!-- Pre and Post -->
                      <div class="flex items-center gap-2">
                        <template v-if="['tel','email','url'].includes(selectedField.inputType)">
                          <span class="px-2 py-1 bg-surface-container-high rounded-lg text-xs text-on-surface-variant">
                            <i v-if="selectedField.inputType==='tel'" class="fa fa-phone"></i>
                            <i v-else-if="selectedField.inputType==='email'" class="fa fa-envelope"></i>
                            <i v-else-if="selectedField.inputType==='url'" class="fa fa-link"></i>
                          </span>
                        </template>
                        <template v-else>
                          <span class="px-2 py-1 bg-surface-container-high rounded-lg text-xs text-on-surface-variant">Pre</span>
                          <input
                            v-model="selectedField.pre"
                            type="text"
                            placeholder="a"
                            :class="formbuilderTheme.configInput + ' w-16'">
                        </template>
                        <div class="flex-1"></div>
                        <template v-if="!['tel','email','url'].includes(selectedField.inputType)">
                          <input
                            v-model="selectedField.post"
                            type="text"
                            placeholder="b"
                            :class="formbuilderTheme.configInput + ' w-16'">
                          <span class="px-2 py-1 bg-surface-container-high rounded-lg text-xs text-on-surface-variant">Post</span>
                        </template>
                      </div>
                    </template>
                    <!-- Default value -->
                    <div v-if="selectedField.inputType !== 'output'">
                      <label :class="formbuilderTheme.configLabel">Default value</label>
                      <input
                        v-model="selectedField.value"
                        type="text"
                        placeholder="Default Value"
                        :class="formbuilderTheme.configInput">
                    </div>
                    <!-- Instructions -->
                    <div>
                      <label :class="formbuilderTheme.configLabel">Instructions</label>
                      <textarea
                        v-model="selectedField.help"
                        rows="2"
                        :class="formbuilderTheme.textarea"></textarea>
                    </div>
                    <!-- More Information -->
                    <div>
                      <label :class="formbuilderTheme.configLabel">More Information</label>
                      <textarea
                        v-model="selectedField.info"
                        rows="2"
                        :class="formbuilderTheme.textarea"></textarea>
                    </div>
                    <!-- Fillable -->
                    <div class="flex items-center gap-4">
                                                <label class="flex items-center gap-2 text-xs text-on-surface-variant">
                          <input type="checkbox" v-model="selectedField.fillable">
                          Fillable
                        </label>
                    </div>
                  </div>
                </div>
                <!-- Display Configuration -->
                <div class="mb-4">
                  <button
                    @click="toggleConfigSection('display')"
                    class="flex items-center justify-between w-full text-left font-medium text-on-surface mb-3 p-3 rounded-xl hover:bg-surface-container-high transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                    <span class="flex items-center gap-2">
                      <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z"></path>
                      </svg>
                      Display
                    </span>
                    <svg :class="['w-5 h-5 text-on-surface-variant transition-transform duration-200', configSections.display ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                  </button>
                  <div class="config-section-content pl-2 border-l-2 border-outline-variant" :class="configSections.display ? 'expanded' : 'collapsed'">
                    <div class="space-y-3">
                      <!-- Width Configuration -->
                      <div>
                        <label :class="formbuilderTheme.configLabel">Width (Columns)</label>
                        <div class="relative">
                          <select
                            v-model="selectedField.width"
                            class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                            <option value="1">1 Column</option>
                            <option value="2">2 Columns</option>
                            <option value="3">3 Columns</option>
                            <option value="4">4 Columns</option>
                            <option value="5">5 Columns</option>
                            <option value="6">6 Columns</option>
                            <option value="7">7 Columns</option>
                            <option value="8">8 Columns</option>
                            <option value="9">9 Columns</option>
                            <option value="10">10 Columns</option>
                            <option value="11">11 Columns</option>
                            <option value="12">12 Columns (Full Width)</option>
                          </select>
                          <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                          </div>
                        </div>
                      </div>
                      <!-- Offset Configuration (only show if width is not 12) -->
                      <div v-if="selectedField.width && selectedField.width !== '12'">
                        <label :class="formbuilderTheme.configLabel">Offset (Columns)</label>
                        <div class="relative">
                          <select
                            v-model="selectedField.offset"
                            class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                            <option value="0">No Offset</option>
                            <option value="1">1 Column</option>
                            <option value="2">2 Columns</option>
                            <option value="3">3 Columns</option>
                            <option value="4">4 Columns</option>
                            <option value="5">5 Columns</option>
                            <option value="6">6 Columns</option>
                            <option value="7">7 Columns</option>
                            <option value="8">8 Columns</option>
                            <option value="9">9 Columns</option>
                            <option value="10">10 Columns</option>
                            <option value="11">11 Columns</option>
                          </select>
                          <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                          </div>
                        </div>
                      </div>
                      <!-- Force New Row (only show if width is not 12) -->
                      <div v-if="selectedField.width && selectedField.width !== '12'" class="flex items-center gap-4">
                        <label class="flex items-center gap-2 text-xs text-on-surface-variant">
                          <input 
                            type="checkbox" 
                            v-model="selectedField.forceRow">
                          Force New Row
                        </label>
                      </div>
                      <!-- Allow Duplication -->
                      <div class="flex items-center gap-4">
                        <label class="flex items-center gap-2 text-xs text-on-surface-variant">
                          <input 
                            type="checkbox" 
                            v-model="selectedField.allowDuplication">
                          Allow Duplication
                        </label>
                      </div>
                      <!-- Array Configuration (only show if allowDuplication is enabled) -->
                      <div v-if="selectedField.allowDuplication" class="space-y-3 pl-4 border-l-2 border-primary-20">
                        <!-- Min/Max Configuration -->
                        <div class="flex gap-4">
                          <div class="flex-1">
                            <label :class="formbuilderTheme.configLabel">Minimum</label>
                            <input
                              v-model="selectedField.arrayMin"
                              type="number"
                              placeholder="null"
                              :class="formbuilderTheme.configInput">
                          </div>
                          <div class="flex-1">
                            <label :class="formbuilderTheme.configLabel">Maximum</label>
                            <input
                              v-model="selectedField.arrayMax"
                              type="number"
                              placeholder="null"
                              :class="formbuilderTheme.configInput">
                          </div>
                        </div>
                        
                        <!-- Duplicate Configuration -->
                        <div>
                          <label :class="formbuilderTheme.configLabel">Duplicate Enable</label>
                          <div class="relative">
                            <select
                              v-model="selectedField.duplicateEnable"
                              class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                              <option value="auto">Auto</option>
                              <option value="yes">Yes</option>
                              <option value="no">No</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                              <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                              </svg>
                            </div>
                          </div>
                        </div>
                        
                        <!-- Clone Configuration -->
                        <div class="flex items-center gap-4">
                          <label class="flex items-center gap-2 text-xs text-on-surface-variant">
                            <input 
                              type="checkbox" 
                              v-model="selectedField.duplicateClone">
                              Clone
                          </label>
                        </div>
                        
                        <!-- Remove Configuration -->
                        <div>
                          <label :class="formbuilderTheme.configLabel">Remove Enable</label>
                          <div class="relative">
                            <select
                              v-model="selectedField.removeEnable"
                              class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                              <option value="auto">Auto</option>
                              <option value="yes">Yes</option>
                              <option value="no">No</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                              <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                              </svg>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- Conditions Configuration -->
                <div class="mb-4">
                  <button
                    @click="toggleConfigSection('conditions')"
                    class="flex items-center justify-between w-full text-left font-medium text-on-surface mb-3 p-3 rounded-xl hover:bg-surface-container-high transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                    <span class="flex items-center gap-2">
                      <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                      </svg>
                      Conditions
                    </span>
                    <svg :class="['w-5 h-5 text-on-surface-variant transition-transform duration-200', configSections.conditions ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                  </button>
                  <div class="config-section-content pl-2 border-l-2 border-outline-variant" :class="configSections.conditions ? 'expanded' : 'collapsed'">
                    <div class="space-y-3">
                      <!-- Show Condition -->
                      <div>
                        <label :class="formbuilderTheme.configLabel">Show Field</label>
                        <div class="relative">
                          <select v-model="selectedField.show" class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                            <option value="true">Always</option>
                            <option value="false">Never</option>
                            <option :value="'edit'">Same as Edit</option>
                            <option :value="'parse'">Same as Parse</option>
                            <option value="conditional">Conditionally</option>
                          </select>
                          <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                          </div>
                        </div>
                      </div>
                      
                      <!-- Show Conditional Logic UI -->
                      <ConditionalLogic
                        v-if="selectedField.show === 'conditional'"
                        v-model="selectedField.showGroups"
                        condition-type="show" />
                      
                      <!-- Edit Condition -->
                      <div>
                        <label :class="formbuilderTheme.configLabel">Allow Edit</label>
                        <div class="relative">
                          <select v-model="selectedField.edit" class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                            <option value="true">Always</option>
                            <option value="false">Never</option>
                            <option :value="'show'">Same as Show</option>
                            <option :value="'parse'">Same as Parse</option>
                            <option value="conditional">Conditionally</option>
                          </select>
                          <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                          </div>
                        </div>
                      </div>
                      
                      <!-- Edit Conditional Logic UI -->
                      <ConditionalLogic
                        v-if="selectedField.edit === 'conditional'"
                        v-model="selectedField.editGroups"
                        condition-type="edit" />
                      <!-- Parse Condition -->
                      <div>
                        <label :class="formbuilderTheme.configLabel">Include in Data (Parse)</label>
                        <div class="relative">
                          <select v-model="selectedField.parse" class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                            <option value="true">Always</option>
                            <option value="false">Never</option>
                            <option :value="'show'">Same as Show</option>
                            <option :value="'edit'">Same as Edit</option>
                            <option value="conditional">Conditionally</option>
                          </select>
                          <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                          </div>
                        </div>
                      </div>
                      
                      <!-- Parse Conditional Logic UI -->
                      <ConditionalLogic
                        v-if="selectedField.parse === 'conditional'"
                        v-model="selectedField.parseGroups"
                        condition-type="parse" />
                      <!-- Required Condition -->
                      <div>
                        <label :class="formbuilderTheme.configLabel">Required</label>
                        <div class="relative">
                          <select 
                            v-model="selectedField.required" 
                            class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                            <option value="true">Always</option>
                            <option value="false">Never</option>
                            <option :value="'show'">Same as Show</option>
                            <option :value="'edit'">Same as Edit</option>
                            <option :value="'parse'">Same as Parse</option>
                            <option value="conditional">Conditionally</option>
                          </select>
                          <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                          </div>
                        </div>
                      </div>
                      
                      <!-- Debug Info -->
                      <div class="mt-2 p-2 bg-yellow-100 text-xs text-gray-700 rounded">
                        Debug: required = "{{ selectedField.required }}", 
                        hasConditions = {{ !!selectedField.requiredGroups }}
                      </div>
                      
                      <!-- Required Conditional Logic UI -->
                      <ConditionalLogic
                        v-if="selectedField.required === 'conditional'"
                        v-model="selectedField.requiredGroups"
                        condition-type="required" />
                    </div>
                  </div>
                </div>
                <!-- Validation Configuration -->
                <div class="mb-4">
                  <button
                    @click="toggleConfigSection('validation')"
                    class="flex items-center justify-between w-full text-left font-medium text-on-surface mb-3 p-3 rounded-xl hover:bg-surface-container-high transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                    <span class="flex items-center gap-2">
                      <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                      </svg>
                      Validation
                    </span>
                    <svg :class="['w-5 h-5 text-on-surface-variant transition-transform duration-200', configSections.validation ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                  </button>
                  <div class="config-section-content pl-2 border-l-2 border-outline-variant" :class="configSections.validation ? 'expanded' : 'collapsed'">
                    <div class="space-y-3">
                      <!-- Enable Validation Switch -->
                      <div class="flex items-center justify-between">
                        <label :class="formbuilderTheme.configLabel">Enable Validation</label>
                        <input 
                          type="checkbox" 
                          v-model="selectedField.validate">
                      </div>
                      
                      <!-- Validation Configuration (only show if validation is enabled) -->
                      <div v-if="selectedField.validate" class="space-y-3 pl-4 border-l-2 border-primary-20">
                        <!-- Validation Type -->
                        <div>
                          <label :class="formbuilderTheme.configLabel">Validation Type</label>
                          <div class="relative">
                            <select
                              v-model="selectedField.validationType"
                              class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                              <option value="none">None</option>
                              <option value="matches">Matches</option>
                              <option value="date">Date</option>
                              <option value="valid_url">Valid URL</option>
                              <option value="valid_email">Valid Email</option>
                              <option value="length">Length</option>
                              <option value="numeric">Numeric</option>
                              <option value="pattern">Pattern</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                              <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                              </svg>
                            </div>
                          </div>
                        </div>
                        
                        <!-- Pattern Configuration (only for pattern type) -->
                        <div v-if="selectedField.validationType === 'pattern'" class="space-y-3">
                          <div>
                            <label :class="formbuilderTheme.configLabel">Regex Pattern</label>
                            <input
                              v-model="selectedField.validationPattern"
                              type="text"
                              placeholder="Enter regex pattern (e.g., ^[A-Za-z]+$)"
                              class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200">
                          </div>
                          <div>
                            <label :class="formbuilderTheme.configLabel">Regex Flags</label>
                            <input
                              v-model="selectedField.validationFlags"
                              type="text"
                              placeholder="Enter flags (e.g., gi)"
                              class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200">
                            <div class="text-xs text-on-surface-variant mt-1">
                              Common flags: g (global), i (case insensitive), m (multiline)
                            </div>
                          </div>
                        </div>
                        
                        <!-- Length Configuration (only for length type) -->
                        <div v-if="selectedField.validationType === 'length'" class="space-y-3">
                          <div class="grid grid-cols-2 gap-4">
                            <div>
                              <label :class="formbuilderTheme.configLabel">Minimum Length</label>
                              <input
                                v-model="selectedField.validationMinLength"
                                type="number"
                                min="0"
                                placeholder="0"
                                class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200">
                            </div>
                            <div>
                              <label :class="formbuilderTheme.configLabel">Maximum Length</label>
                              <input
                                v-model="selectedField.validationMaxLength"
                                type="number"
                                min="0"
                                placeholder="100"
                                class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200">
                            </div>
                          </div>
                        </div>
                        
                        <!-- Numeric Configuration (only for numeric type) -->
                        <div v-if="selectedField.validationType === 'numeric'" class="space-y-3">
                          <div class="grid grid-cols-2 gap-4">
                            <div>
                              <label :class="formbuilderTheme.configLabel">Minimum Value</label>
                              <input
                                v-model="selectedField.validationMinValue"
                                type="number"
                                placeholder="0"
                                class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200">
                            </div>
                            <div>
                              <label :class="formbuilderTheme.configLabel">Maximum Value</label>
                              <input
                                v-model="selectedField.validationMaxValue"
                                type="number"
                                placeholder="100"
                                class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200">
                            </div>
                          </div>
                        </div>
                        
                        <!-- Matches Configuration (only for matches type) -->
                        <div v-if="selectedField.validationType === 'matches'" class="space-y-3">
                          <div>
                            <label :class="formbuilderTheme.configLabel">Field Name</label>
                            <input
                              v-model="selectedField.validationFieldName"
                              type="text"
                              placeholder="Enter field name to match"
                              class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200">
                          </div>
                          <div>
                            <label :class="formbuilderTheme.configLabel">Expected Values</label>
                            <div class="space-y-2">
                              <div v-for="(value, index) in selectedField.validationValues || ['']" :key="index" class="flex gap-2">
                                <input
                                  v-model="selectedField.validationValues[index]"
                                  type="text"
                                  :placeholder="`Value ${index + 1}`"
                                  class="flex-1 px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200">
                                <button
                                  @click="removeValidationValue(index)"
                                  type="button"
                                  class="px-3 py-3 text-error hover:text-error hover:bg-error-8 rounded-xl transition-all duration-200"
                                  :disabled="(selectedField.validationValues || []).length <= 1">
                                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                  </svg>
                                </button>
                              </div>
                              <button
                                @click="addValidationValue"
                                type="button"
                                class="w-full px-4 py-2 text-primary hover:text-primary hover:bg-primary-8 rounded-xl transition-all duration-200 border border-primary text-sm">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Add Value
                              </button>
                            </div>
                          </div>
                        </div>
                        
                        <!-- Error Message -->
                        <div>
                          <label :class="formbuilderTheme.configLabel">Error Message</label>
                          <input
                            v-model="selectedField.validationMessage"
                            type="text"
                            :placeholder="getDefaultValidationMessage(selectedField.validationType)"
                            class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200">
                        </div>
                        
                        <!-- When to Apply Validation -->
                        <div>
                          <label :class="formbuilderTheme.configLabel">When to Apply</label>
                          <div class="relative">
                            <select
                              v-model="selectedField.validationWhen"
                              class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                              <option value="always">Always</option>
                              <option value="conditional">Conditionally</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                              <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                              </svg>
                            </div>
                          </div>
                        </div>
                        
                        <!-- Conditional Validation Logic -->
                        <div v-if="selectedField.validationWhen === 'conditional'">
                          <label :class="formbuilderTheme.configLabel">Validation Conditions</label>
                          <ConditionalLogic
                            v-model="selectedField.validationGroups"
                            condition-type="validation" />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Options Field Configuration -->
              <div v-if="selectedField.type === 'options'">
                <!-- Basic Configuration -->
                <div class="mb-4">
                  <button
                    @click="toggleConfigSection('basic')"
                    class="flex items-center justify-between w-full text-left font-medium text-on-surface mb-3 p-3 rounded-xl hover:bg-surface-container-high transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                    <span class="flex items-center gap-2">
                      <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                      </svg>
                      Basic
                    </span>
                    <svg :class="['w-5 h-5 text-on-surface-variant transition-transform duration-200', configSections.basic ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                  </button>
                  <div class="config-section-content space-y-3 pl-2 border-l-2 border-outline-variant" :class="configSections.basic ? 'expanded' : 'collapsed'">
                    <!-- Type Selector -->
                    <div>
                      <label class="block text-xs text-on-surface-variant mb-2 font-medium">Type</label>
                      <div class="relative">
                        <select
                          v-model="selectedField.optionsType"
                          class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                          <option value="dropdown">Dropdown</option>
                          <option value="list">List</option>
                          <option value="combobox">Combobox</option>
                          <option value="range">Range</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                          <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                          </svg>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Field Label and Name -->
                    <div class="flex gap-4">
                      <div class="flex-1">
                        <label class="block text-xs text-on-surface-variant mb-2 font-medium">Field Label<span class="text-error">*</span></label>
                        <input
                          v-model="selectedField.label"
                          type="text"
                          placeholder="Label"
                          class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200">
                      </div>
                      <div class="flex-1">
                        <label class="block text-xs text-on-surface-variant mb-2 font-medium">Name</label>
                        <input
                          v-model="selectedField.name"
                          type="text"
                          placeholder="Name"
                          class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200">
                      </div>
                    </div>
                    
                    <!-- Allow Multiple Selections (only for dropdown and list) -->
                    <div v-if="['dropdown', 'list'].includes(selectedField.optionsType)" class="flex items-center gap-4">
                      <label class="flex items-center gap-2 text-xs text-on-surface-variant">
                        <input type="checkbox" v-model="selectedField.multiple">
                        Allow Multiple Selections
                      </label>
                    </div>
                    
                    <!-- Instructions -->
                    <div>
                      <label :class="formbuilderTheme.configLabel">Instructions</label>
                      <textarea
                        v-model="selectedField.help"
                        rows="2"
                        :class="formbuilderTheme.textarea"></textarea>
                    </div>
                    
                    <!-- More Information -->
                    <div>
                      <label :class="formbuilderTheme.configLabel">More Information</label>
                      <textarea
                        v-model="selectedField.info"
                        rows="2"
                        :class="formbuilderTheme.textarea"></textarea>
                    </div>
                    
                    <!-- Fillable -->
                    <div class="flex items-center gap-4">
                      <label class="flex items-center gap-2 text-xs text-on-surface-variant">
                        <input type="checkbox" v-model="selectedField.fillable">
                        Fillable
                      </label>
                    </div>
                  </div>
                </div>
                
                <!-- Display Configuration -->
                <div class="mb-4">
                  <button
                    @click="toggleConfigSection('display')"
                    class="flex items-center justify-between w-full text-left font-medium text-on-surface mb-3 p-3 rounded-xl hover:bg-surface-container-high transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                    <span class="flex items-center gap-2">
                      <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z"></path>
                      </svg>
                      Display
                    </span>
                    <svg :class="['w-5 h-5 text-on-surface-variant transition-transform duration-200', configSections.display ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                  </button>
                  <div class="config-section-content pl-2 border-l-2 border-outline-variant" :class="configSections.display ? 'expanded' : 'collapsed'">
                    <div class="space-y-3">
                      <!-- Width Configuration -->
                      <div>
                        <label :class="formbuilderTheme.configLabel">Width (Columns)</label>
                        <div class="relative">
                          <select
                            v-model="selectedField.width"
                            class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                            <option value="1">1 Column</option>
                            <option value="2">2 Columns</option>
                            <option value="3">3 Columns</option>
                            <option value="4">4 Columns</option>
                            <option value="5">5 Columns</option>
                            <option value="6">6 Columns</option>
                            <option value="7">7 Columns</option>
                            <option value="8">8 Columns</option>
                            <option value="9">9 Columns</option>
                            <option value="10">10 Columns</option>
                            <option value="11">11 Columns</option>
                            <option value="12">12 Columns (Full Width)</option>
                          </select>
                          <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                          </div>
                        </div>
                      </div>
                      <!-- Offset Configuration (only show if width is not 12) -->
                      <div v-if="selectedField.width && selectedField.width !== '12'">
                        <label :class="formbuilderTheme.configLabel">Offset (Columns)</label>
                        <div class="relative">
                          <select
                            v-model="selectedField.offset"
                            class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                            <option value="0">No Offset</option>
                            <option value="1">1 Column</option>
                            <option value="2">2 Columns</option>
                            <option value="3">3 Columns</option>
                            <option value="4">4 Columns</option>
                            <option value="5">5 Columns</option>
                            <option value="6">6 Columns</option>
                            <option value="7">7 Columns</option>
                            <option value="8">8 Columns</option>
                            <option value="9">9 Columns</option>
                            <option value="10">10 Columns</option>
                            <option value="11">11 Columns</option>
                          </select>
                          <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                          </div>
                        </div>
                      </div>
                      <!-- Force New Row (only show if width is not 12) -->
                      <div v-if="selectedField.width && selectedField.width !== '12'" class="flex items-center gap-4">
                        <label class="flex items-center gap-2 text-xs text-on-surface-variant">
                          <input 
                            type="checkbox" 
                            v-model="selectedField.forceRow">
                          Force New Row
                        </label>
                      </div>
                      <!-- Allow Duplication -->
                      <div class="flex items-center gap-4">
                        <label class="flex items-center gap-2 text-xs text-on-surface-variant">
                          <input 
                            type="checkbox" 
                            v-model="selectedField.allowDuplication">
                          Allow Duplication
                        </label>
                      </div>
                      
                      <!-- Array Configuration (only show if allowDuplication is enabled) -->
                      <div v-if="selectedField.allowDuplication" class="space-y-3 pl-4 border-l-2 border-primary-20">
                        <!-- Min/Max Configuration -->
                        <div class="flex gap-4">
                          <div class="flex-1">
                            <label :class="formbuilderTheme.configLabel">Minimum</label>
                            <input
                              v-model="selectedField.arrayMin"
                              type="number"
                              placeholder="null"
                              :class="formbuilderTheme.configInput">
                          </div>
                          <div class="flex-1">
                            <label :class="formbuilderTheme.configLabel">Maximum</label>
                            <input
                              v-model="selectedField.arrayMax"
                              type="number"
                              placeholder="null"
                              :class="formbuilderTheme.configInput">
                          </div>
                        </div>
                        
                        <!-- Duplicate Configuration -->
                        <div>
                          <label :class="formbuilderTheme.configLabel">Duplicate Enable</label>
                          <div class="relative">
                            <select
                              v-model="selectedField.duplicateEnable"
                              class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                              <option value="auto">Auto</option>
                              <option value="yes">Yes</option>
                              <option value="no">No</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                              <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                              </svg>
                            </div>
                          </div>
                        </div>
                        
                        <!-- Clone Configuration -->
                        <div class="flex items-center gap-4">
                          <label class="flex items-center gap-2 text-xs text-on-surface-variant">
                            <input 
                              type="checkbox" 
                              v-model="selectedField.duplicateClone">
                              Clone
                          </label>
                        </div>
                        
                        <!-- Remove Configuration -->
                        <div>
                          <label :class="formbuilderTheme.configLabel">Remove Enable</label>
                          <div class="relative">
                            <select
                              v-model="selectedField.removeEnable"
                              class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                              <option value="auto">Auto</option>
                              <option value="yes">Yes</option>
                              <option value="no">No</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                              <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                              </svg>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Options Configuration -->
                <div class="mb-4">
                  <button
                    @click="toggleConfigSection('options')"
                    class="flex items-center justify-between w-full text-left font-medium text-on-surface mb-3 p-3 rounded-xl hover:bg-surface-container-high transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                    <span class="flex items-center gap-2">
                      <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                      </svg>
                      Options
                    </span>
                    <svg :class="['w-5 h-5 text-on-surface-variant transition-transform duration-200', configSections.options ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                  </button>
                  <div class="config-section-content pl-2 border-l-2 border-outline-variant" :class="configSections.options ? 'expanded' : 'collapsed'">
                    <div class="space-y-4">
                      <!-- Options Sections -->
                      <div v-for="(section, sectionIndex) in selectedField.options" :key="sectionIndex" class="border border-outline rounded-lg p-3">
                        <div class="flex items-center justify-between mb-2">
                          <h4 class="text-sm font-medium text-on-surface">Section {{ sectionIndex + 1 }}</h4>
                          <button
                            @click="removeOptionsSection(sectionIndex)"
                            type="button"
                            class="text-error hover:text-error hover:bg-error-8 p-1 rounded transition-all duration-200"
                            :disabled="selectedField.options.length <= 1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                          </button>
                        </div>
                        
                        <!-- Section Label -->
                        <div class="mb-2">
                          <label class="block text-xs text-on-surface-variant mb-1 font-medium">Section Label (Optional)</label>
                          <input
                            v-model="section.label"
                            type="text"
                            placeholder="Section Label"
                            class="w-full px-3 py-2 rounded-lg border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200">
                        </div>
                        
                        <!-- Type Dropdown -->
                        <div class="mb-2">
                          <label class="block text-xs text-on-surface-variant mb-1 font-medium">Type</label>
                          <div class="relative">
                            <select
                              v-model="section.type"
                              class="w-full px-3 py-2 rounded-lg border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                              <option value="optgroup">Manual</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                              <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                              </svg>
                            </div>
                          </div>
                        </div>
                        
                        <!-- Options List -->
                        <div class="mb-3">
                          <label class="block text-xs text-on-surface-variant mb-2 font-medium">Options</label>
                          <div class="space-y-2">
                            <!-- Column Headers -->
                            <div class="grid grid-cols-3 gap-2 mb-1">
                              <div class="text-xs font-medium text-on-surface-variant px-2">Label</div>
                              <div class="text-xs font-medium text-on-surface-variant px-2">Value</div>
                              <div class="text-xs font-medium text-on-surface-variant px-2 text-center">Action</div>
                            </div>
                            <!-- Options -->
                            <div v-for="(option, optionIndex) in section.options" :key="optionIndex" class="grid grid-cols-3 gap-2">
                              <input
                                v-model="option.label"
                                type="text"
                                placeholder="Label"
                                class="px-2 py-1.5 rounded-lg border border-outline bg-surface-container text-on-surface text-xs focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200">
                              <input
                                v-model="option.value"
                                type="text"
                                placeholder="Value"
                                class="px-2 py-1.5 rounded-lg border border-outline bg-surface-container text-on-surface text-xs focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200">
                              <button
                                @click="removeOptionsOption(sectionIndex, optionIndex)"
                                type="button"
                                class="px-2 py-1.5 text-error hover:text-error hover:bg-error-8 rounded-lg transition-all duration-200 flex items-center justify-center"
                                :disabled="section.options.length <= 1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                              </button>
                            </div>
                            <button
                              @click="addOptionsOption(sectionIndex)"
                              type="button"
                              class="w-full px-3 py-1.5 text-primary hover:text-primary hover:bg-primary-8 rounded-lg transition-all duration-200 border border-primary text-xs">
                              <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                              </svg>
                              Add Option
                            </button>
                          </div>
                        </div>
                        
                        <!-- Conditions for this section -->
                        <div class="space-y-2">
                          <!-- Show Condition -->
                          <div>
                            <label class="block text-xs text-on-surface-variant mb-1 font-medium">Show Section</label>
                            <div class="relative">
                              <select v-model="section.show" class="w-full px-3 py-2 rounded-lg border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                                <option :value="true">Always</option>
                                <option :value="false">Never</option>
                                <option value="conditional">Conditionally</option>
                              </select>
                              <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                              </div>
                            </div>
                          </div>
                          
                          <!-- Show Conditional Logic UI -->
                          <ConditionalLogic
                            v-if="section.show === 'conditional'"
                            v-model="section.showGroups"
                            condition-type="show" />
                          
                          <!-- Edit Condition -->
                          <div>
                            <label class="block text-xs text-on-surface-variant mb-1 font-medium">Allow Edit</label>
                            <div class="relative">
                              <select v-model="section.edit" class="w-full px-3 py-2 rounded-lg border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                                <option :value="true">Always</option>
                                <option :value="false">Never</option>
                                <option value="conditional">Conditionally</option>
                              </select>
                              <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                              </div>
                            </div>
                          </div>
                          
                          <!-- Edit Conditional Logic UI -->
                          <ConditionalLogic
                            v-if="section.edit === 'conditional'"
                            v-model="section.editGroups"
                            condition-type="edit" />
                        </div>
                      </div>
                      
                      <!-- Add Section Button -->
                      <button
                        @click="addOptionsSection"
                        type="button"
                        class="w-full px-3 py-2 text-primary hover:text-primary hover:bg-primary-8 rounded-lg transition-all duration-200 border border-primary text-sm">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Add Section
                      </button>
                    </div>
                  </div>
                </div>
                
                <!-- Conditions Configuration -->
                <div class="mb-4">
                  <button
                    @click="toggleConfigSection('conditions')"
                    class="flex items-center justify-between w-full text-left font-medium text-on-surface mb-3 p-3 rounded-xl hover:bg-surface-container-high transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                    <span class="flex items-center gap-2">
                      <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                      </svg>
                      Conditions
                    </span>
                    <svg :class="['w-5 h-5 text-on-surface-variant transition-transform duration-200', configSections.conditions ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                  </button>
                  <div class="config-section-content pl-2 border-l-2 border-outline-variant" :class="configSections.conditions ? 'expanded' : 'collapsed'">
                    <div class="space-y-3">
                      <!-- Show Condition -->
                      <div>
                        <label :class="formbuilderTheme.configLabel">Show Field</label>
                        <div class="relative">
                          <select v-model="selectedField.show" class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                            <option value="true">Always</option>
                            <option value="false">Never</option>
                            <option :value="'edit'">Same as Edit</option>
                            <option :value="'parse'">Same as Parse</option>
                            <option value="conditional">Conditionally</option>
                          </select>
                          <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                          </div>
                        </div>
                      </div>
                      
                      <!-- Show Conditional Logic UI -->
                      <ConditionalLogic
                        v-if="selectedField.show === 'conditional'"
                        v-model="selectedField.showGroups"
                        condition-type="show" />
                      
                      <!-- Edit Condition -->
                      <div>
                        <label :class="formbuilderTheme.configLabel">Allow Edit</label>
                        <div class="relative">
                          <select v-model="selectedField.edit" class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                            <option value="true">Always</option>
                            <option value="false">Never</option>
                            <option :value="'show'">Same as Show</option>
                            <option :value="'parse'">Same as Parse</option>
                            <option value="conditional">Conditionally</option>
                          </select>
                          <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                          </div>
                        </div>
                      </div>
                      
                      <!-- Edit Conditional Logic UI -->
                      <ConditionalLogic
                        v-if="selectedField.edit === 'conditional'"
                        v-model="selectedField.editGroups"
                        condition-type="edit" />
                      
                      <!-- Parse Condition -->
                      <div>
                        <label :class="formbuilderTheme.configLabel">Include in Data (Parse)</label>
                        <div class="relative">
                          <select v-model="selectedField.parse" class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                            <option value="true">Always</option>
                            <option value="false">Never</option>
                            <option :value="'show'">Same as Show</option>
                            <option :value="'edit'">Same as Edit</option>
                            <option value="conditional">Conditionally</option>
                          </select>
                          <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                          </div>
                        </div>
                      </div>
                      
                      <!-- Parse Conditional Logic UI -->
                      <ConditionalLogic
                        v-if="selectedField.parse === 'conditional'"
                        v-model="selectedField.parseGroups"
                        condition-type="parse" />
                      
                      <!-- Required Condition -->
                      <div>
                        <label :class="formbuilderTheme.configLabel">Required</label>
                        <div class="relative">
                          <select 
                            v-model="selectedField.required" 
                            class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                            <option value="true">Always</option>
                            <option value="false">Never</option>
                            <option :value="'show'">Same as Show</option>
                            <option :value="'edit'">Same as Edit</option>
                            <option :value="'parse'">Same as Parse</option>
                            <option value="conditional">Conditionally</option>
                          </select>
                          <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                          </div>
                        </div>
                      </div>
                      
                      <!-- Required Conditional Logic UI -->
                      <ConditionalLogic
                        v-if="selectedField.required === 'conditional'"
                        v-model="selectedField.requiredGroups"
                        condition-type="required" />
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Boolean Field Configuration -->
              <div v-if="selectedField.type === 'boolean'">
                <!-- Basic Configuration -->
                <div class="mb-4">
                  <button
                    @click="toggleConfigSection('basic')"
                    class="flex items-center justify-between w-full text-left font-medium text-on-surface mb-3 p-3 rounded-xl hover:bg-surface-container-high transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                    <span class="flex items-center gap-2">
                      <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                      </svg>
                      Basic
                    </span>
                    <svg :class="['w-5 h-5 text-on-surface-variant transition-transform duration-200', configSections.basic ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                  </button>
                  <div class="config-section-content space-y-3 pl-2 border-l-2 border-outline-variant" :class="configSections.basic ? 'expanded' : 'collapsed'">
                    <!-- Type Selector -->
                    <div>
                      <label class="block text-xs text-on-surface-variant mb-2 font-medium">Type</label>
                      <div class="relative">
                        <select
                          v-model="selectedField.booleanType"
                          class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                          <option value="checkbox">Checkbox</option>
                          <option value="switch">Switch</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                          <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                          </svg>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Field Label and Name -->
                    <div class="flex gap-4">
                      <div class="flex-1">
                        <label class="block text-xs text-on-surface-variant mb-2 font-medium">Field Label<span class="text-error">*</span></label>
                        <input
                          v-model="selectedField.label"
                          type="text"
                          placeholder="Label"
                          class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200">
                      </div>
                      <div class="flex-1">
                        <label class="block text-xs text-on-surface-variant mb-2 font-medium">Name</label>
                        <input
                          v-model="selectedField.name"
                          type="text"
                          placeholder="Name"
                          class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200">
                      </div>
                    </div>
                    
                    <!-- Boolean Default Value -->
                    <div>
                      <label class="block text-xs text-on-surface-variant mb-2 font-medium">Default Value</label>
                      <div class="flex items-center gap-4">
                        <label class="flex items-center gap-2 text-xs text-on-surface-variant">
                          <input type="checkbox" v-model="selectedField.defaultValue">
                          {{ selectedField.defaultValue ? 'True' : 'False' }}
                        </label>
                      </div>
                    </div>
                    

                    
                    <!-- Instructions -->
                    <div>
                      <label :class="formbuilderTheme.configLabel">Instructions</label>
                      <textarea
                        v-model="selectedField.help"
                        rows="2"
                        :class="formbuilderTheme.textarea"></textarea>
                    </div>
                    
                    <!-- More Information -->
                    <div>
                      <label :class="formbuilderTheme.configLabel">More Information</label>
                      <textarea
                        v-model="selectedField.info"
                        rows="2"
                        :class="formbuilderTheme.textarea"></textarea>
                    </div>
                    
                    <!-- Fillable -->
                    <div class="flex items-center gap-4">
                      <label class="flex items-center gap-2 text-xs text-on-surface-variant">
                        <input type="checkbox" v-model="selectedField.fillable">
                        Fillable
                      </label>
                    </div>
                  </div>
                </div>
                
                <!-- Display Configuration -->
                <div class="mb-4">
                  <button
                    @click="toggleConfigSection('display')"
                    class="flex items-center justify-between w-full text-left font-medium text-on-surface mb-3 p-3 rounded-xl hover:bg-surface-container-high transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                    <span class="flex items-center gap-2">
                      <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z"></path>
                      </svg>
                      Display
                    </span>
                    <svg :class="['w-5 h-5 text-on-surface-variant transition-transform duration-200', configSections.display ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                  </button>
                  <div class="config-section-content pl-2 border-l-2 border-outline-variant" :class="configSections.display ? 'expanded' : 'collapsed'">
                    <div class="space-y-3">
                      <!-- Width Configuration -->
                      <div>
                        <label :class="formbuilderTheme.configLabel">Width (Columns)</label>
                        <div class="relative">
                          <select
                            v-model="selectedField.width"
                            class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                            <option value="1">1 Column</option>
                            <option value="2">2 Columns</option>
                            <option value="3">3 Columns</option>
                            <option value="4">4 Columns</option>
                            <option value="5">5 Columns</option>
                            <option value="6">6 Columns</option>
                            <option value="7">7 Columns</option>
                            <option value="8">8 Columns</option>
                            <option value="9">9 Columns</option>
                            <option value="10">10 Columns</option>
                            <option value="11">11 Columns</option>
                            <option value="12">12 Columns (Full Width)</option>
                          </select>
                          <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                          </div>
                        </div>
                      </div>
                      <!-- Offset Configuration (only show if width is not 12) -->
                      <div v-if="selectedField.width && selectedField.width !== '12'">
                        <label :class="formbuilderTheme.configLabel">Offset (Columns)</label>
                        <div class="relative">
                          <select
                            v-model="selectedField.offset"
                            class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                            <option value="0">No Offset</option>
                            <option value="1">1 Column</option>
                            <option value="2">2 Columns</option>
                            <option value="3">3 Columns</option>
                            <option value="4">4 Columns</option>
                            <option value="5">5 Columns</option>
                            <option value="6">6 Columns</option>
                            <option value="7">7 Columns</option>
                            <option value="8">8 Columns</option>
                            <option value="9">9 Columns</option>
                            <option value="10">10 Columns</option>
                            <option value="11">11 Columns</option>
                          </select>
                          <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                          </div>
                        </div>
                      </div>
                      <!-- Force New Row (only show if width is not 12) -->
                      <div v-if="selectedField.width && selectedField.width !== '12'" class="flex items-center gap-4">
                        <label class="flex items-center gap-2 text-xs text-on-surface-variant">
                          <input 
                            type="checkbox" 
                            v-model="selectedField.forceRow">
                          Force New Row
                        </label>
                      </div>
                      <!-- Allow Duplication -->
                      <div class="flex items-center gap-4">
                        <label class="flex items-center gap-2 text-xs text-on-surface-variant">
                          <input 
                            type="checkbox" 
                            v-model="selectedField.allowDuplication">
                          Allow Duplication
                        </label>
                      </div>
                      
                      <!-- Array Configuration (only show if allowDuplication is enabled) -->
                      <div v-if="selectedField.allowDuplication" class="space-y-3 pl-4 border-l-2 border-primary-20">
                        <!-- Min/Max Configuration -->
                        <div class="flex gap-4">
                          <div class="flex-1">
                            <label :class="formbuilderTheme.configLabel">Minimum</label>
                            <input
                              v-model="selectedField.arrayMin"
                              type="number"
                              placeholder="null"
                              :class="formbuilderTheme.configInput">
                          </div>
                          <div class="flex-1">
                            <label :class="formbuilderTheme.configLabel">Maximum</label>
                            <input
                              v-model="selectedField.arrayMax"
                              type="number"
                              placeholder="null"
                              :class="formbuilderTheme.configInput">
                          </div>
                        </div>
                        
                        <!-- Duplicate Configuration -->
                        <div>
                          <label :class="formbuilderTheme.configLabel">Duplicate Enable</label>
                          <div class="relative">
                            <select
                              v-model="selectedField.duplicateEnable"
                              class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                              <option value="auto">Auto</option>
                              <option value="yes">Yes</option>
                              <option value="no">No</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                              <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                              </svg>
                            </div>
                          </div>
                        </div>
                        
                        <!-- Clone Configuration -->
                        <div class="flex items-center gap-4">
                          <label class="flex items-center gap-2 text-xs text-on-surface-variant">
                            <input 
                              type="checkbox" 
                              v-model="selectedField.duplicateClone">
                              Clone
                          </label>
                        </div>
                        
                        <!-- Remove Configuration -->
                        <div>
                          <label :class="formbuilderTheme.configLabel">Remove Enable</label>
                          <div class="relative">
                            <select
                              v-model="selectedField.removeEnable"
                              class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                              <option value="auto">Auto</option>
                              <option value="yes">Yes</option>
                              <option value="no">No</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                              <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                              </svg>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Options Configuration -->
                <div class="mb-4">
                  <button
                    @click="toggleConfigSection('options')"
                    class="flex items-center justify-between w-full text-left font-medium text-on-surface mb-3 p-3 rounded-xl hover:bg-surface-container-high transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                    <span class="flex items-center gap-2">
                      <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                      </svg>
                      Options
                    </span>
                    <svg :class="['w-5 h-5 text-on-surface-variant transition-transform duration-200', configSections.options ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                  </button>
                  <div class="config-section-content pl-2 border-l-2 border-outline-variant" :class="configSections.options ? 'expanded' : 'collapsed'">
                    <div class="space-y-3">
                      <!-- Boolean Options -->
                      <div class="space-y-3">
                        <div class="flex gap-4">
                          <div class="flex-1">
                            <label class="block text-xs text-on-surface-variant mb-2 font-medium">False Label</label>
                            <input
                              v-model="selectedField.falseLabel"
                              type="text"
                              placeholder="No"
                              class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200">
                          </div>
                          <div class="flex-1">
                            <label class="block text-xs text-on-surface-variant mb-2 font-medium">True Label</label>
                            <input
                              v-model="selectedField.trueLabel"
                              type="text"
                              placeholder="Yes"
                              class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200">
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Conditions Configuration -->
                <div class="mb-4">
                  <button
                    @click="toggleConfigSection('conditions')"
                    class="flex items-center justify-between w-full text-left font-medium text-on-surface mb-3 p-3 rounded-xl hover:bg-surface-container-high transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                    <span class="flex items-center gap-2">
                      <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                      </svg>
                      Conditions
                    </span>
                    <svg :class="['w-5 h-5 text-on-surface-variant transition-transform duration-200', configSections.conditions ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                  </button>
                  <div class="config-section-content pl-2 border-l-2 border-outline-variant" :class="configSections.conditions ? 'expanded' : 'collapsed'">
                    <div class="space-y-3">
                      <!-- Show Condition -->
                      <div>
                        <label :class="formbuilderTheme.configLabel">Show Field</label>
                        <div class="relative">
                          <select v-model="selectedField.show" class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                            <option value="true">Always</option>
                            <option value="false">Never</option>
                            <option :value="'edit'">Same as Edit</option>
                            <option :value="'parse'">Same as Parse</option>
                            <option value="conditional">Conditionally</option>
                          </select>
                          <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                          </div>
                        </div>
                      </div>
                      
                      <!-- Show Conditional Logic UI -->
                      <ConditionalLogic
                        v-if="selectedField.show === 'conditional'"
                        v-model="selectedField.showGroups"
                        condition-type="show" />
                      
                      <!-- Edit Condition -->
                      <div>
                        <label :class="formbuilderTheme.configLabel">Allow Edit</label>
                        <div class="relative">
                          <select v-model="selectedField.edit" class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                            <option value="true">Always</option>
                            <option value="false">Never</option>
                            <option :value="'show'">Same as Show</option>
                            <option :value="'parse'">Same as Parse</option>
                            <option value="conditional">Conditionally</option>
                          </select>
                          <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                          </div>
                        </div>
                      </div>
                      
                      <!-- Edit Conditional Logic UI -->
                      <ConditionalLogic
                        v-if="selectedField.edit === 'conditional'"
                        v-model="selectedField.editGroups"
                        condition-type="edit" />
                      
                      <!-- Parse Condition -->
                      <div>
                        <label :class="formbuilderTheme.configLabel">Include in Data (Parse)</label>
                        <div class="relative">
                          <select v-model="selectedField.parse" class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                            <option value="true">Always</option>
                            <option value="false">Never</option>
                            <option :value="'show'">Same as Show</option>
                            <option :value="'edit'">Same as Edit</option>
                            <option value="conditional">Conditionally</option>
                          </select>
                          <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                          </div>
                        </div>
                      </div>
                      
                      <!-- Parse Conditional Logic UI -->
                      <ConditionalLogic
                        v-if="selectedField.parse === 'conditional'"
                        v-model="selectedField.parseGroups"
                        condition-type="parse" />
                      
                      <!-- Required Condition -->
                      <div>
                        <label :class="formbuilderTheme.configLabel">Required</label>
                        <div class="relative">
                          <select 
                            v-model="selectedField.required" 
                            class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                            <option value="true">Always</option>
                            <option value="false">Never</option>
                            <option :value="'show'">Same as Show</option>
                            <option :value="'edit'">Same as Edit</option>
                            <option :value="'parse'">Same as Parse</option>
                            <option value="conditional">Conditionally</option>
                          </select>
                          <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                          </div>
                        </div>
                      </div>
                      
                      <!-- Required Conditional Logic UI -->
                      <ConditionalLogic
                        v-if="selectedField.required === 'conditional'"
                        v-model="selectedField.requiredGroups"
                        condition-type="required" />
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Section/Fieldset Configuration -->
              <div v-else-if="selectedField.type === 'section' || selectedField.type === 'fieldset'">
                <!-- Basic Configuration -->
                <div class="mb-4">
                  <button
                    @click="toggleConfigSection('basic')"
                    class="flex items-center justify-between w-full text-left font-medium text-on-surface mb-3 p-3 rounded-xl hover:bg-surface-container-high transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                    <span class="flex items-center gap-2">
                      <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                      </svg>
                      Basic
                    </span>
                    <svg :class="['w-5 h-5 text-on-surface-variant transition-transform duration-200', configSections.basic ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                  </button>
                  <div class="config-section-content space-y-3 pl-2 border-l-2 border-outline-variant" :class="configSections.basic ? 'expanded' : 'collapsed'">
                    <!-- Field Label and Name -->
                    <div class="flex gap-4">
                      <div class="flex-1">
                        <label class="block text-xs text-on-surface-variant mb-2 font-medium">Section Label<span class="text-error">*</span></label>
                        <input
                          v-model="selectedField.label"
                          type="text"
                          placeholder="Label"
                          class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200">
                      </div>
                      <div class="flex-1">
                        <label class="block text-xs text-on-surface-variant mb-2 font-medium">Name</label>
                        <input
                          v-model="selectedField.name"
                          type="text"
                          placeholder="Name"
                          class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200">
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Display Configuration -->
                <div class="mb-4">
                  <button
                    @click="toggleConfigSection('display')"
                    class="flex items-center justify-between w-full text-left font-medium text-on-surface mb-3 p-3 rounded-xl hover:bg-surface-container-high transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                    <span class="flex items-center gap-2">
                      <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z"></path>
                      </svg>
                      Display
                    </span>
                    <svg :class="['w-5 h-5 text-on-surface-variant transition-transform duration-200', configSections.display ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                  </button>
                  <div class="config-section-content pl-2 border-l-2 border-outline-variant" :class="configSections.display ? 'expanded' : 'collapsed'">
                    <div class="space-y-3">
                      <!-- Width Configuration -->
                      <div>
                        <label class="block text-xs text-on-surface-variant mb-2 font-medium">Width (Columns)</label>
                        <div class="relative">
                          <select
                            v-model="selectedField.width"
                            class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                            <option value="1">1 Column</option>
                            <option value="2">2 Columns</option>
                            <option value="3">3 Columns</option>
                            <option value="4">4 Columns</option>
                            <option value="5">5 Columns</option>
                            <option value="6">6 Columns</option>
                            <option value="7">7 Columns</option>
                            <option value="8">8 Columns</option>
                            <option value="9">9 Columns</option>
                            <option value="10">10 Columns</option>
                            <option value="11">11 Columns</option>
                            <option value="12">12 Columns (Full Width)</option>
                          </select>
                          <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                          </div>
                        </div>
                      </div>
                      <!-- Offset Configuration (only show if width is not 12) -->
                      <div v-if="selectedField.width && selectedField.width !== '12'">
                        <label class="block text-xs text-on-surface-variant mb-2 font-medium">Offset (Columns)</label>
                        <div class="relative">
                          <select
                            v-model="selectedField.offset"
                            class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                            <option value="0">No Offset</option>
                            <option value="1">1 Column</option>
                            <option value="2">2 Columns</option>
                            <option value="3">3 Columns</option>
                            <option value="4">4 Columns</option>
                            <option value="5">5 Columns</option>
                            <option value="6">6 Columns</option>
                            <option value="7">7 Columns</option>
                            <option value="8">8 Columns</option>
                            <option value="9">9 Columns</option>
                            <option value="10">10 Columns</option>
                            <option value="11">11 Columns</option>
                          </select>
                          <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                          </div>
                        </div>
                      </div>
                      <!-- Force New Row (only show if width is not 12) -->
                      <div v-if="selectedField.width && selectedField.width !== '12'" class="flex items-center gap-4">
                        <label class="flex items-center gap-2 text-xs text-on-surface-variant">
                          <input 
                            type="checkbox" 
                            v-model="selectedField.forceRow">
                          Force New Row
                        </label>
                      </div>
                      <!-- Allow Duplication -->
                      <div class="flex items-center gap-4">
                        <label class="flex items-center gap-2 text-xs text-on-surface-variant">
                          <input 
                            type="checkbox" 
                            v-model="selectedField.allowDuplication">
                          Allow Duplication
                        </label>
                      </div>
                      
                      <!-- Array Configuration (only show if allowDuplication is enabled) -->
                      <div v-if="selectedField.allowDuplication" class="space-y-3 pl-4 border-l-2 border-primary-20">
                        <!-- Min/Max Configuration -->
                        <div class="flex gap-4">
                          <div class="flex-1">
                            <label class="block text-xs text-on-surface-variant mb-2 font-medium">Minimum</label>
                            <input
                              v-model="selectedField.arrayMin"
                              type="number"
                              placeholder="null"
                              :class="formbuilderTheme.configInput">
                          </div>
                          <div class="flex-1">
                            <label :class="formbuilderTheme.configLabel">Maximum</label>
                            <input
                              v-model="selectedField.arrayMax"
                              type="number"
                              placeholder="null"
                              :class="formbuilderTheme.configInput">
                          </div>
                        </div>
                        
                        <!-- Duplicate Configuration -->
                        <div>
                          <label :class="formbuilderTheme.configLabel">Duplicate Enable</label>
                          <div class="relative">
                            <select
                              v-model="selectedField.duplicateEnable"
                              class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                              <option value="auto">Auto</option>
                              <option value="yes">Yes</option>
                              <option value="no">No</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                              <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                              </svg>
                            </div>
                          </div>
                        </div>
                        
                        <!-- Clone Configuration -->
                        <div class="flex items-center gap-4">
                          <label class="flex items-center gap-2 text-xs text-on-surface-variant">
                            <input 
                              type="checkbox" 
                              v-model="selectedField.duplicateClone">
                              Clone
                          </label>
                        </div>
                        
                        <!-- Remove Configuration -->
                        <div>
                          <label :class="formbuilderTheme.configLabel">Remove Enable</label>
                          <div class="relative">
                            <select
                              v-model="selectedField.removeEnable"
                              class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                              <option value="auto">Auto</option>
                              <option value="yes">Yes</option>
                              <option value="no">No</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                              <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                              </svg>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Conditions Configuration -->
                <div class="mb-4">
                  <button
                    @click="toggleConfigSection('conditions')"
                    class="flex items-center justify-between w-full text-left font-medium text-on-surface mb-3 p-3 rounded-xl hover:bg-surface-container-high transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                    <span class="flex items-center gap-2">
                      <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                      </svg>
                      Conditions
                    </span>
                    <svg :class="['w-5 h-5 text-on-surface-variant transition-transform duration-200', configSections.conditions ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                  </button>
                  <div class="config-section-content pl-2 border-l-2 border-outline-variant" :class="configSections.conditions ? 'expanded' : 'collapsed'">
                    <div class="space-y-3">
                      <!-- Show Condition -->
                      <div>
                        <label :class="formbuilderTheme.configLabel">Show Field</label>
                        <div class="relative">
                          <select v-model="selectedField.show" class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                            <option value="true">Always</option>
                            <option value="false">Never</option>
                            <option :value="'edit'">Same as Edit</option>
                            <option :value="'parse'">Same as Parse</option>
                            <option value="conditional">Conditionally</option>
                          </select>
                          <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                          </div>
                        </div>
                      </div>
                      
                      <!-- Show Conditional Logic UI -->
                      <ConditionalLogic
                        v-if="selectedField.show === 'conditional'"
                        v-model="selectedField.showGroups"
                        condition-type="show" />
                      
                      <!-- Edit Condition -->
                      <div>
                        <label :class="formbuilderTheme.configLabel">Allow Edit</label>
                        <div class="relative">
                          <select v-model="selectedField.edit" class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                            <option value="true">Always</option>
                            <option value="false">Never</option>
                            <option :value="'show'">Same as Show</option>
                            <option :value="'parse'">Same as Parse</option>
                            <option value="conditional">Conditionally</option>
                          </select>
                          <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                          </div>
                        </div>
                      </div>
                      
                      <!-- Edit Conditional Logic UI -->
                      <ConditionalLogic
                        v-if="selectedField.edit === 'conditional'"
                        v-model="selectedField.editGroups"
                        condition-type="edit" />
                      
                      <!-- Parse Condition -->
                      <div>
                        <label :class="formbuilderTheme.configLabel">Include in Data (Parse)</label>
                        <div class="relative">
                          <select v-model="selectedField.parse" class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                            <option value="true">Always</option>
                            <option value="false">Never</option>
                            <option :value="'show'">Same as Show</option>
                            <option :value="'edit'">Same as Edit</option>
                            <option value="conditional">Conditionally</option>
                          </select>
                          <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                            <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                          </div>
                        </div>
                      </div>
                      
                      <!-- Parse Conditional Logic UI -->
                      <ConditionalLogic
                        v-if="selectedField.parse === 'conditional'"
                        v-model="selectedField.parseGroups"
                        condition-type="parse" />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- JSON Modal -->
    <AlertModal
      :is-open="isJsonModalOpen"
      title="Form JSON Configuration"
      @close="closeJsonModal">
      <div class="space-y-4">
        <p class="text-sm text-on-surface-variant">
          This is the JSON representation of your form configuration:
        </p>
        <pre :class="formbuilderTheme.modalJson">{{ generatedJson }}</pre>
      </div>
      <template #footer>
        <button
          @click="copyToClipboard"
          class="md3-button-filled-primary">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
          </svg>
          Copy to Clipboard
        </button>
        <button
          @click="closeJsonModal"
          class="md3-button-outlined-secondary">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
          Close
        </button>
      </template>
    </AlertModal>
    <!-- Preview Modal -->
    <AlertModal
      :is-open="isPreviewModalOpen"
      title="Form Preview"
      @close="closePreviewModal">
      <div class="space-y-6">
        <div class="text-sm text-on-surface-variant">
          Test your form fields below:
        </div>
        <!-- Interactive Form Preview -->
        <FormViewer 
          ref="formViewerRef"
          :key="previewKey"
          :form-config="formConfigForPreview"
          v-model="previewFormData" />
      </div>
      <template #footer>
        <button
          @click="handlePreviewSubmit"
          class="md3-button-filled-primary">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
          </svg>
          Submit Form
        </button>
        <button
          @click="closePreviewModal"
          class="md3-button-outlined-secondary">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
          Close
        </button>
      </template>
    </AlertModal>
  </div>

</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted } from 'vue';
import AlertModal from '../AlertModal.vue';
import FormViewer from '../formviewer/FormViewer.vue';
import ConditionalLogic from './components/ConditionalLogic.vue';
import draggable from 'vuedraggable';
import { getThemeClasses } from '../Theme.js';

const fieldTypes = [
{ type: 'input', label: 'Input' },
{ type: 'options', label: 'Options' },
{ type: 'boolean', label: 'Boolean' },
{ type: 'section', label: 'Section' },
];

const fields = ref([]);
const selectedFieldIndex = ref(null);
const isJsonModalOpen = ref(false);
const isPreviewModalOpen = ref(false);
const formName = ref('my-form');
const previewData = ref({});
const previewArrayData = ref({}); // New ref for array preview data
const previewFormData = ref({});
const previewKey = ref(0);
const modalClasses = computed(() => getThemeClasses('modal'));
const hoveredInfoIndex = ref(null);
const dragOverIndex = ref(null);
const isDragging = ref(false);
const previewPosition = ref(null);
const previewElement = ref(null);

// Navigation data for managing nested fieldsets
const navigationPath = ref([]);
const currentFieldsetIndex = ref(null);
const currentFieldset = ref(null);

// Computed property to create fields with preview elements
const currentFieldsWithPreviews = computed(() => {
const currentFields = getCurrentFields();

if (!isDragging.value || previewPosition.value === null) {
  return currentFields || [];
}

const result = [];
for (let i = 0; i <= (currentFields?.length || 0); i++) {
  if (i === previewPosition.value) {
    result.push({
      type: 'preview',
      label: previewElement.value?.label || 'New Field',
      isPreview: true
    });
  }
  if (i < (currentFields?.length || 0)) {
    result.push(currentFields[i]);
  }
}
return result;
});

// Helper function to get the original index from fieldsWithPreviews
function getOriginalIndex(previewIndex) {
if (!isDragging.value || previewPosition.value === null) {
  return previewIndex;
}

let originalIndex = 0;
for (let i = 0; i <= fields.value.length; i++) {
  if (i === previewIndex) {
    if (i === previewPosition.value) {
      // This is a preview element, return the index of the next real field
      return originalIndex;
    }
    originalIndex++;
  }
  if (i < fields.value.length) {
    if (i === previewIndex) {
      return originalIndex - 1;
    }
    originalIndex++;
  }
}
return previewIndex;
}
const configSections = ref({
basic: true,
display: false,
options: false,
conditions: false,
validation: false
});
const formViewerRef = ref(null);

const formbuilderTheme = getThemeClasses('formbuilder');

function onDragStart(event, type) {
event.dataTransfer.setData('application/json', JSON.stringify(type));
event.dataTransfer.effectAllowed = 'copy';
isDragging.value = true;
previewElement.value = type;
}

function onDragEnter(event) {
event.preventDefault();
isDragging.value = true;
}

function onDragOver(event) {
event.preventDefault();
if (isDragging.value) {
  // Add visual feedback by highlighting the drop area
  const canvas = event.currentTarget;
  canvas.style.backgroundColor = 'var(--md-sys-color-primary-8)';
  
  // Calculate and update preview position
  const dropPosition = calculateDropPosition(event);
  console.log('Drop position calculated:', dropPosition, 'Mouse Y:', event.clientY);
  previewPosition.value = dropPosition;
}
}

function onDragLeave(event) {
// Only set isDragging to false if we're leaving the entire drop area
if (!event.currentTarget.contains(event.relatedTarget)) {
  isDragging.value = false;
  dragOverIndex.value = null;
  previewPosition.value = null;
  previewElement.value = null;
  // Reset canvas background
  const canvas = event.currentTarget;
  canvas.style.backgroundColor = '';
}
}

function onDragEnd(event) {
isDragging.value = false;
dragOverIndex.value = null;
previewPosition.value = null;
previewElement.value = null;
// Reset canvas background
const canvas = event.currentTarget;
if (canvas) {
  canvas.style.backgroundColor = '';
}
}

function onDrop(event) {
event.preventDefault();
isDragging.value = false;
dragOverIndex.value = null;
previewPosition.value = null;
previewElement.value = null;

// Reset canvas background
const canvas = event.currentTarget;
canvas.style.backgroundColor = '';

// Calculate drop position based on mouse position
const dropPosition = calculateDropPosition(event);
createAndAddField(event, dropPosition);
}

function calculateDropPosition(event) {
const canvas = event.currentTarget;
const rect = canvas.getBoundingClientRect();
const mouseY = event.clientY - rect.top;

// Get all field elements (only real fields, not previews)
const fieldElements = canvas.querySelectorAll('.field-card');

if (fieldElements.length === 0) {
  return 0; // First field
}

// Check if dropping before the first field
const firstField = fieldElements[0];
const firstFieldRect = firstField.getBoundingClientRect();
const firstFieldTop = firstFieldRect.top - rect.top;
const firstFieldCenter = firstFieldTop + firstFieldRect.height / 2;

if (mouseY < firstFieldCenter) {
  return 0; // Before first field
}

// Check each field to find the right position
for (let i = 0; i < fieldElements.length; i++) {
  const field = fieldElements[i];
  const fieldRect = field.getBoundingClientRect();
  const fieldTop = fieldRect.top - rect.top;
  const fieldCenter = fieldTop + fieldRect.height / 2;
  
  // Check if mouse is in the upper half of this field
  if (mouseY < fieldCenter) {
    return i; // Insert at this position
  }
  
  // If this is the last field, check if we should insert after it
  if (i === fieldElements.length - 1 && mouseY > fieldCenter) {
    return i + 1; // After the last field
  }
}

// If we get here, drop at the end
return fieldElements.length;
}

function onDropAtPosition(event, position) {
event.preventDefault();
event.stopPropagation();
isDragging.value = false;
dragOverIndex.value = null;

createAndAddField(event, position);
}

function createAndAddField(event, position) {
const typeData = event.dataTransfer.getData('application/json');

if (typeData) {
  try {
    const type = JSON.parse(typeData);
    const newField = {
      type: type.type,
      label: type.label,
      name: `${type.type}_${Date.now()}`,
      showColumn: true,
      fillable: true // Default to fillable
    };

    // Add top-level condition properties
    newField.show = 'true';
    newField.edit = 'true';
    newField.parse = 'true';
    newField.required = false;
    
    // Initialize conditional properties
    newField.showGroups = [{ op: 'and', conditions: [{ type: 'matches', name: '', value: [''] }] }];
    newField.editGroups = [{ op: 'and', conditions: [{ type: 'matches', name: '', value: [''] }] }];
    newField.parseGroups = [{ op: 'and', conditions: [{ type: 'matches', name: '', value: [''] }] }];
    newField.requiredGroups = [{ op: 'and', conditions: [{ type: 'matches', name: '', value: [''] }] }];
    
    // Initialize validation properties
    newField.validate = false;
    newField.validationType = 'none';
    newField.validationWhen = 'always';
    newField.validationMessage = '';
    newField.validationPattern = '';
    newField.validationFlags = '';
    newField.validationMinLength = null;
    newField.validationMaxLength = null;
    newField.validationMinValue = null;
    newField.validationMaxValue = null;
    newField.validationFieldName = '';
    newField.validationValues = [''];
    newField.validationGroups = [{ op: 'and', conditions: [{ type: 'matches', name: '', value: [''] }] }];
    
    if (type.type === 'input') {
      newField.inputType = 'text';
      newField.placeholder = '';
      newField.required = false;
      newField.width = '12';
      newField.offset = '0';
      newField.forceRow = false;
      newField.allowDuplication = false;
      newField.arrayMin = null;
      newField.arrayMax = null;
      newField.duplicateEnable = 'auto';
      newField.duplicateClone = false;
      newField.removeEnable = 'auto';
    } else if (type.type === 'options') {
      newField.type = 'options'; // Keep the type as 'options'
      newField.optionsType = 'dropdown';
      newField.multiple = false;
      newField.options = [
        {
          label: 'Section 1',
          type: 'optgroup',
          options: [
            { label: 'Option 1', value: 'option1' },
            { label: 'Option 2', value: 'option2' }
          ],
          show: true,
          edit: true
        }
      ];
      newField.required = false;
      newField.width = '12';
      newField.offset = '0';
      newField.forceRow = false;
      newField.allowDuplication = false;
      newField.arrayMin = null;
      newField.arrayMax = null;
      newField.duplicateEnable = 'auto';
      newField.duplicateClone = false;
      newField.removeEnable = 'auto';
    } else if (type.type === 'boolean') {
      newField.booleanType = 'checkbox';
      newField.defaultValue = false;
      newField.trueLabel = 'Yes';
      newField.falseLabel = 'No';
      newField.required = false;
      newField.width = '12';
      newField.offset = '0';
      newField.forceRow = false;
      newField.allowDuplication = false;
      newField.arrayMin = null;
      newField.arrayMax = null;
      newField.duplicateEnable = 'auto';
      newField.duplicateClone = false;
      newField.removeEnable = 'auto';
    } else if (type.type === 'section') {
      newField.type = 'fieldset'; // Set type to fieldset for JSON export
      newField.fields = []; // Initialize fields array for nested fields
      newField.description = '';
      newField.required = false;
      newField.width = '12';
      newField.offset = '0';
      newField.forceRow = false;
      newField.allowDuplication = false;
      newField.arrayMin = null;
      newField.arrayMax = null;
      newField.duplicateEnable = 'auto';
      newField.duplicateClone = false;
      newField.removeEnable = 'auto';
    }

    // Insert at the specified position in current context
    const currentFields = getCurrentFields();
    currentFields.splice(position, 0, newField);
    
    // Automatically select the newly added field
    selectedFieldIndex.value = position;
    
    // Debug log for options fields
    if (type.type === 'options') {
      console.log('Created options field:', newField);
      console.log('Field options:', newField.options);
    }
  } catch (error) {
    console.error('Error parsing dropped data:', error);
  }
}
}

function selectField(index) {
selectedFieldIndex.value = index;
}

function removeField(index) {
const currentFields = getCurrentFields();
currentFields.splice(index, 1);
// Clear selection if the removed field was selected
if (selectedFieldIndex.value === index) {
  selectedFieldIndex.value = null;
} else if (selectedFieldIndex.value > index) {
  // Adjust selection index if we removed a field before the selected one
  selectedFieldIndex.value--;
}
}

function addOption(field) {
if (!field.options) field.options = [];
field.options.push({ value: '', label: '' });
}

function removeOption(field, index) {
field.options.splice(index, 1);
}

function showJsonModal() {
isJsonModalOpen.value = true;
}

function closeJsonModal() {
isJsonModalOpen.value = false;
}

function showPreviewModal() {
// Initialize preview form data
previewFormData.value = {};
// Increment key to force re-render
previewKey.value++;

console.log('Fields array:', fields.value); // Debug log
console.log('Fields type:', typeof fields.value); // Debug log
console.log('Is array:', Array.isArray(fields.value)); // Debug log
console.log('Length:', fields.value ? fields.value.length : 'undefined'); // Debug log
console.log('Form config for preview:', formConfigForPreview.value); // Debug log

isPreviewModalOpen.value = true;
}

function closePreviewModal() {
isPreviewModalOpen.value = false;
previewData.value = {};
previewArrayData.value = {}; // Clear array preview data
}

function copyToClipboard() {
navigator.clipboard.writeText(generatedJson.value).then(() => {
  // You could add a toast notification here
  console.log('JSON copied to clipboard');
}).catch(err => {
  console.error('Failed to copy: ', err);
});
}

function handlePreviewSubmit() {
// Use the ref to access the FormViewer component and validate
if (formViewerRef.value && formViewerRef.value.submitForm) {
  const isValid = formViewerRef.value.submitForm();
  if (isValid) {
    console.log('Preview form submitted:', previewFormData.value);
    // In a real application, you would send this data to your backend or process it
    alert('Form submitted successfully! Check console for form data.');
    closePreviewModal();
  } else {
    console.log('Form validation failed');
    // The FormViewer component will show validation errors
  }
} else {
  // Fallback if we can't access the component
  console.log('Preview form submitted:', previewFormData.value);
  alert('Form submitted successfully! Check console for form data.');
  closePreviewModal();
}
}

function toggleConfigSection(section) {
configSections.value[section] = !configSections.value[section];
}

// Navigation methods for managing nested fieldsets
function manageSection(fieldIndex) {
const currentFields = getCurrentFields();
const field = currentFields[fieldIndex];

if (field && (field.type === 'section' || field.type === 'fieldset')) {
  // Initialize the fieldset's fields array if it doesn't exist
  if (!field.fields) {
    field.fields = [];
  }
  
  // Set current fieldset
  currentFieldsetIndex.value = fieldIndex;
  currentFieldset.value = field;
  
  // Add to navigation path
  navigationPath.value.push({
    index: fieldIndex,
    label: field.label || 'Unnamed Section',
    field: field
  });
  
  // Switch to fieldset view
  selectedFieldIndex.value = null;
}
}

function navigateToLevel(level) {
if (level === -1) {
  // Navigate back to root
  navigationPath.value = [];
  currentFieldsetIndex.value = null;
  currentFieldset.value = null;
  selectedFieldIndex.value = null;
} else {
  // Navigate to specific level
  navigationPath.value = navigationPath.value.slice(0, level + 1);
  if (navigationPath.value.length > 0) {
    const lastPathItem = navigationPath.value[navigationPath.value.length - 1];
    currentFieldsetIndex.value = lastPathItem.index;
    currentFieldset.value = lastPathItem.field;
  } else {
    currentFieldsetIndex.value = null;
    currentFieldset.value = null;
  }
  selectedFieldIndex.value = null;
}
}

// Get current fields based on navigation state
function getCurrentFields() {
if (navigationPath.value.length === 0) {
  return fields.value;
} else {
  const result = currentFieldset.value?.fields || [];
  return result;
}
}

// Add field to current context (root or fieldset)
function addFieldToCurrentContext(field) {
if (navigationPath.value.length === 0) {
  // Add to root
  fields.value.push(field);
} else {
  // Add to current fieldset
  if (!currentFieldset.value.fields) {
    currentFieldset.value.fields = [];
  }
  currentFieldset.value.fields.push(field);
}
}

// Helper function to get the currently selected field
function getSelectedField() {
if (selectedFieldIndex.value === null) return null;
const currentFields = getCurrentFields();
return currentFields[selectedFieldIndex.value];
}

// Computed property for the currently selected field
const selectedField = computed(() => {
if (selectedFieldIndex.value === null) return null;
const currentFields = getCurrentFields();
return currentFields[selectedFieldIndex.value];
});

// Helper function to get field by index in current context
function getFieldByIndex(index) {
if (index === null) return null;
const currentFields = getCurrentFields();
return currentFields[index];
}



function getDefaultPlaceholder(inputType, label) {
const placeholders = {
  'tel': `Enter ${label.toLowerCase()}`,
  'email': `Enter ${label.toLowerCase()} email`,
  'url': `Enter ${label.toLowerCase()} URL`,
  'date': `Select ${label.toLowerCase()} date`,
  'number': `Enter ${label.toLowerCase()}`,
  'currency': `Enter ${label.toLowerCase()} amount`,
  'password': `Enter ${label.toLowerCase()} password`,
  'color': `Select ${label.toLowerCase()} color`,
  'textarea': `Enter ${label.toLowerCase()}`,
  'output': 'Output field (read-only)',
  'hidden': 'Hidden field'
};
return placeholders[inputType] || `Enter ${label.toLowerCase()}`;
}

function getFieldLayoutClasses(field) {
// Defensive check for undefined field
if (!field) return 'col-span-12';

// Ensure width/columns and offset are properly converted to numbers
const columns = parseInt(field.columns || field.width || '12');
const offset = parseInt(field.offset || '0');
const forceRow = field.forceRow || false;

let classes = '';

// Always use col-span-12 for full width
if (columns === 12) {
  classes = 'col-span-12';
} else {
  // Map columns to Tailwind's 12-column grid
  const colSpanMap = {
    1: 'col-span-1', 2: 'col-span-2', 3: 'col-span-3', 4: 'col-span-4',
    5: 'col-span-5', 6: 'col-span-6', 7: 'col-span-7', 8: 'col-span-8',
    9: 'col-span-9', 10: 'col-span-10', 11: 'col-span-11'
  };
  classes = colSpanMap[columns] || 'col-span-12';
  
  // Only apply offset if not forcing a new row
  if (offset > 0 && !forceRow) {
    const colStartMap = {
      1: 'col-start-2', 2: 'col-start-3', 3: 'col-start-4', 4: 'col-start-5',
      5: 'col-start-6', 6: 'col-start-7', 7: 'col-start-8', 8: 'col-start-9',
      9: 'col-start-10', 10: 'col-start-11', 11: 'col-start-12'
    };
    classes += ' ' + (colStartMap[offset] || '');
  }
}

// Add a class to mark this field as forcing a new row (for CSS targeting)
if (forceRow) {
  classes += ' force-new-row';
}

return classes.trim();
}

function mapCondition(val) {
if (val === undefined || val === null) return true; // Default to true for undefined values
if (val === 'true' || val === true) return true;
if (val === 'false' || val === false) return false;
if (val === 'edit') return 'edit';
if (val === 'parse') return 'parse';
if (val === 'show') return 'show';
if (val === 'conditional') return 'conditional';
return val;
}

function exportConditionGroup(group) {
// group: { op, conditions: [ ... ] }
const exportedConditions = [];

for (const condition of group.conditions) {
  if (condition.type === 'conditions') {
    // For nested conditions, flatten them into the parent group
    const nested = Array.isArray(condition.nestedConditions)
      ? condition.nestedConditions
      : (condition.conditions || []);
    
    // Add each nested condition to the parent group
    nested.forEach(nestedCondition => {
      exportedConditions.push(exportCondition(nestedCondition));
    });
  } else {
    // Regular condition
    exportedConditions.push(exportCondition(condition));
  }
}

return {
  op: group.op || 'and',
  conditions: exportedConditions
};
}

function exportCondition(condition) {
if (condition.type === 'conditions') {
  // Nested group: use nestedConditions if present, otherwise conditions
  const nested = Array.isArray(condition.nestedConditions)
    ? condition.nestedConditions
    : (condition.conditions || []);
  return {
    type: 'conditions',
    op: condition.nestedOp || 'and',
    conditions: nested.map(exportCondition)
  };
} else {
  // Leaf condition
  return {
    type: condition.type,
    name: condition.name,
    value: condition.value
  };
}
}

function getFieldJson(field) {
if (!field) return {};

// Only include the properties we want in the export
const base = {
  name: field.name,
  type: field.type,
  label: field.label,
  placeholder: field.placeholder,
  defaultValue: field.defaultValue,
  required: field.required,
  show: field.show,
  edit: field.edit,
  parse: field.parse
};

// Add help and info fields
if (field.help) {
  base.help = field.help;
}
if (field.info) {
  base.info = field.info;
}

// Add inputType-specific properties
if (field.inputType) {
  base.inputType = field.inputType;
}

// Add format for output type
if (field.inputType === 'output' && field.formatValue) {
  base.format = { value: field.formatValue };
}

// Add boolean-specific properties
if (field.type === 'boolean') {
  // Change the type based on booleanType selection
  base.type = field.booleanType || 'checkbox';
  base.value = field.defaultValue || false;
  base.showColumn = true;
  base.options = [
    {
      label: field.falseLabel || 'No',
      value: false
    },
    {
      label: field.trueLabel || 'Yes',
      value: true
    }
  ];
}

// Add options-specific properties
if (field.type === 'options' || ['dropdown', 'list', 'combobox', 'range'].includes(field.type)) {
  // Map options types to correct JSON types
  const optionsType = field.type === 'options' ? (field.optionsType || 'dropdown') : field.type;
  const typeMapping = {
    'dropdown': 'select',
    'list': 'radio',
    'combobox': 'combobox',
    'range': 'range'
  };
  base.type = typeMapping[optionsType] || 'select';
  base.multiple = field.multiple || false;
  base.options = field.options || [];
}

// Add section/fieldset-specific properties
if (field.type === 'section' || field.type === 'fieldset') {
  base.type = 'fieldset';
  if (field.description) {
    base.description = field.description;
  }
  // Add nested fields if they exist
  if (field.fields && Array.isArray(field.fields) && field.fields.length > 0) {
    base.fields = field.fields.map(getFieldJson).filter(field => field && Object.keys(field).length > 0);
  }
}

// Transform display properties to new format
if (field.width) {
  base.columns = parseInt(field.width);
}
if (field.offset) {
  base.offset = parseInt(field.offset);
}
if (field.forceRow !== undefined) {
  base.forceRow = field.forceRow;
}

// Add array configuration if allowDuplication is enabled
if (field.allowDuplication) {
  base.array = {
    min: field.arrayMin || null,
    max: field.arrayMax || null,
    duplicate: {
      enable: field.duplicateEnable || "auto",
      label: "",
      clone: field.duplicateClone || false
    },
    remove: {
      enable: field.removeEnable || "auto",
      label: ""
    }
  };
}

// Export conditions recursively
['show', 'edit', 'parse', 'required'].forEach(key => {
  if (field[key] === 'conditional' && Array.isArray(field[`${key}Groups`])) {
    // Merge all groups into a single group
    const allConditions = [];
    const firstGroup = field[`${key}Groups`][0];
    const op = firstGroup ? firstGroup.op : 'and';
    
    field[`${key}Groups`].forEach(group => {
      group.conditions.forEach(condition => {
        if (condition.type === 'conditions') {
          // For nested conditions, export as a conditions type with its own structure
          const nested = Array.isArray(condition.nestedConditions)
            ? condition.nestedConditions
            : (condition.conditions || []);
          
          const nestedConditions = nested.map(nestedCondition => exportCondition(nestedCondition));
          
          allConditions.push({
            type: 'conditions',
            op: condition.nestedOp || 'and',
            conditions: nestedConditions
          });
        } else {
          allConditions.push(exportCondition(condition));
        }
      });
    });
    
    base[key] = [{
      op: op,
      conditions: allConditions
    }];
  } else if (field[key] !== undefined) {
    base[key] = mapCondition(field[key]);
  }
});

// Export validation properties
if (field.validate) {
  const validationRule = {
    type: field.validationType || 'none',
    message: field.validationMessage || getDefaultValidationMessage(field.validationType)
  };
  
  // Add type-specific validation properties
  if (field.validationType === 'pattern') {
    validationRule.regex = field.validationPattern || '';
    validationRule.flags = field.validationFlags || '';
  } else if (field.validationType === 'length') {
    validationRule.min = field.validationMinLength || null;
    validationRule.max = field.validationMaxLength || null;
  } else if (field.validationType === 'numeric') {
    validationRule.min = field.validationMinValue || null;
    validationRule.max = field.validationMaxValue || null;
  } else if (field.validationType === 'matches') {
    validationRule.name = field.validationFieldName || '';
    validationRule.value = field.validationValues || [''];
  } else if (field.validationType === 'date') {
    // Date validation has no additional properties
  }
  
  // Add conditional validation logic
  if (field.validationWhen === 'conditional' && Array.isArray(field.validationGroups)) {
    const allConditions = [];
    const firstGroup = field.validationGroups[0];
    const op = firstGroup ? firstGroup.op : 'and';
    
    field.validationGroups.forEach(group => {
      group.conditions.forEach(condition => {
        if (condition.type === 'conditions') {
          const nested = Array.isArray(condition.nestedConditions)
            ? condition.nestedConditions
            : (condition.conditions || []);
          
          const nestedConditions = nested.map(nestedCondition => exportCondition(nestedCondition));
          
          allConditions.push({
            type: 'conditions',
            op: condition.nestedOp || 'and',
            conditions: nestedConditions
          });
        } else {
          allConditions.push(exportCondition(condition));
        }
      });
    });
    
    validationRule.conditions = [{
      op: op,
      conditions: allConditions
    }];
  }
  
  // Set validation as an array with the rule
  base.validate = [validationRule];
}

return base;
}
const generatedJson = computed(() => {
return JSON.stringify({
  name: formName.value,
  files: false,
  fields: (fields.value || []).map(getFieldJson).filter(field => field && Object.keys(field).length > 0)
}, null, 2);
});

// Computed property for form configuration used in preview
const formConfigForPreview = computed(() => {
return {
  name: formName.value,
  files: false,
  fields: (fields.value || []).map(getFieldJson).filter(field => field && Object.keys(field).length > 0)
};
});



watch(
() => fields.value[selectedFieldIndex.value]?.inputType,
(newType, oldType) => {
  if (newType === 'output' && fields.value[selectedFieldIndex.value] && !fields.value[selectedFieldIndex.value].formatValue) {
    fields.value[selectedFieldIndex.value].formatValue = '';
  }
}
);

// Watch for changes in condition fields to initialize conditional properties
watch(
() => fields.value[selectedFieldIndex.value]?.required,
(newValue) => {
  console.log('Required field changed to:', newValue);
  if (newValue === 'conditional' && fields.value[selectedFieldIndex.value]) {
    const field = fields.value[selectedFieldIndex.value];
    console.log('Initializing required conditional properties for field:', field.name);
    if (!field.requiredGroups) {
      field.requiredGroups = [{ op: 'and', conditions: [{ type: 'matches', name: '', value: [''] }] }];
    }
    console.log('Required conditions after init:', field.requiredGroups);
  }
}
);

watch(
() => fields.value[selectedFieldIndex.value]?.show,
(newValue) => {
  if (newValue === 'conditional' && fields.value[selectedFieldIndex.value]) {
    const field = fields.value[selectedFieldIndex.value];
    if (!field.showGroups) {
      field.showGroups = [{ op: 'and', conditions: [{ type: 'matches', name: '', value: [''] }] }];
    }
  }
}
);

watch(
() => fields.value[selectedFieldIndex.value]?.edit,
(newValue) => {
  if (newValue === 'conditional' && fields.value[selectedFieldIndex.value]) {
    const field = fields.value[selectedFieldIndex.value];
    if (!field.editGroups) {
      field.editGroups = [{ op: 'and', conditions: [{ type: 'matches', name: '', value: [''] }] }];
    }
  }
}
);

watch(
() => fields.value[selectedFieldIndex.value]?.parse,
(newValue) => {
  if (newValue === 'conditional' && fields.value[selectedFieldIndex.value]) {
    const field = fields.value[selectedFieldIndex.value];
    if (!field.parseGroups) {
      field.parseGroups = [{ op: 'and', conditions: [{ type: 'matches', name: '', value: [''] }] }];
    }
  }
}
);

// Watch for validation when changes to initialize validation groups
watch(
() => fields.value[selectedFieldIndex.value]?.validationWhen,
(newValue) => {
  if (newValue === 'conditional' && fields.value[selectedFieldIndex.value]) {
    const field = fields.value[selectedFieldIndex.value];
    if (!field.validationGroups) {
      field.validationGroups = [{ op: 'and', conditions: [{ type: 'matches', name: '', value: [''] }] }];
    }
  }
}
);

// Watch for changes in fields array to update preview
watch(
() => fields.value,
() => {
  // Update preview key to force re-render when fields change
  if (isPreviewModalOpen.value) {
    previewKey.value++;
  }
},
{ deep: true }
);

// New methods for array preview
function addArrayItem(fieldName) {
const field = fields.value.find(f => f.name === fieldName);
if (field && field.allowDuplication) {
  const currentItems = previewArrayData.value[fieldName] || [];
  if (field.arrayMax === null || field.arrayMax === '' || currentItems.length < Number(field.arrayMax)) {
    previewArrayData.value[fieldName] = [...currentItems, ''];
  }
}
}

function removeArrayItem(fieldName, index) {
const field = fields.value.find(f => f.name === fieldName);
if (field && field.allowDuplication) {
  const currentItems = previewArrayData.value[fieldName] || [];
  if (field.arrayMin === null || field.arrayMin === '' || currentItems.length > Number(field.arrayMin)) {
    previewArrayData.value[fieldName] = currentItems.filter((_, i) => i !== index);
  }
}
}

function addArrayItemAt(fieldName, index) {
const field = fields.value.find(f => f.name === fieldName);
if (field && field.allowDuplication) {
  const currentItems = previewArrayData.value[fieldName] || [];
  if (field.arrayMax === null || field.arrayMax === '' || currentItems.length < Number(field.arrayMax)) {
    let newValue = '';
    if (field.duplicateClone) {
      newValue = currentItems[index] || '';
    }
    previewArrayData.value[fieldName] = [
      ...currentItems.slice(0, index + 1),
      newValue,
      ...currentItems.slice(index + 1)
    ];
  }
}
}

function addRequiredConditionGroup() {
const field = fields.value[selectedFieldIndex.value];
if (field) {
  if (!field.requiredGroups) {
    field.requiredGroups = [];
  }
  field.requiredGroups.push({ op: 'and', conditions: [{ type: 'matches', name: '', value: [''] }] });
}
}

function removeRequiredConditionGroup(index) {
const field = fields.value[selectedFieldIndex.value];
if (field && field.requiredGroups) {
  field.requiredGroups.splice(index, 1);
}
}

function addRequiredCondition(groupIndex) {
const field = fields.value[selectedFieldIndex.value];
if (field && field.requiredGroups && field.requiredGroups[groupIndex]) {
  field.requiredGroups[groupIndex].conditions.push({ type: 'matches', name: '', value: [''] });
}
}

function removeRequiredCondition(groupIndex, conditionIndex) {
const field = fields.value[selectedFieldIndex.value];
if (field && field.requiredGroups && field.requiredGroups[groupIndex]) {
  field.requiredGroups[groupIndex].conditions.splice(conditionIndex, 1);
}
}

// Parse condition helpers
function addParseConditionGroup() {
const field = fields.value[selectedFieldIndex.value];
if (field) {
  if (!field.parseGroups) {
    field.parseGroups = [];
  }
  field.parseGroups.push({ op: 'and', conditions: [{ type: 'matches', name: '', value: [''] }] });
}
}

function removeParseConditionGroup(index) {
const field = fields.value[selectedFieldIndex.value];
if (field && field.parseGroups) {
  field.parseGroups.splice(index, 1);
}
}

function addParseCondition(groupIndex) {
const field = fields.value[selectedFieldIndex.value];
if (field && field.parseGroups && field.parseGroups[groupIndex]) {
  field.parseGroups[groupIndex].conditions.push({ type: 'matches', name: '', value: [''] });
}
}

function removeParseCondition(groupIndex, conditionIndex) {
const field = fields.value[selectedFieldIndex.value];
if (field && field.parseGroups && field.parseGroups[groupIndex]) {
  field.parseGroups[groupIndex].conditions.splice(conditionIndex, 1);
}
}

// Show condition helpers
function addShowConditionGroup() {
const field = fields.value[selectedFieldIndex.value];
if (field) {
  if (!field.showGroups) {
    field.showGroups = [];
  }
  field.showGroups.push({ op: 'and', conditions: [{ type: 'matches', name: '', value: [''] }] });
}
}

function removeShowConditionGroup(index) {
const field = fields.value[selectedFieldIndex.value];
if (field && field.showGroups) {
  field.showGroups.splice(index, 1);
}
}

function addShowCondition(groupIndex) {
const field = fields.value[selectedFieldIndex.value];
if (field && field.showGroups && field.showGroups[groupIndex]) {
  field.showGroups[groupIndex].conditions.push({ type: 'matches', name: '', value: [''] });
}
}

function removeShowCondition(groupIndex, conditionIndex) {
const field = fields.value[selectedFieldIndex.value];
if (field && field.showGroups && field.showGroups[groupIndex]) {
  field.showGroups[groupIndex].conditions.splice(conditionIndex, 1);
}
}

// Edit condition helpers
function addEditConditionGroup() {
const field = fields.value[selectedFieldIndex.value];
if (field) {
  if (!field.editGroups) {
    field.editGroups = [];
  }
  field.editGroups.push({ op: 'and', conditions: [{ type: 'matches', name: '', value: [''] }] });
}
}

function removeEditConditionGroup(index) {
const field = fields.value[selectedFieldIndex.value];
if (field && field.editGroups) {
  field.editGroups.splice(index, 1);
}
}

function addEditCondition(groupIndex) {
const field = fields.value[selectedFieldIndex.value];
if (field && field.editGroups && field.editGroups[groupIndex]) {
  field.editGroups[groupIndex].conditions.push({ type: 'matches', name: '', value: [''] });
}
}

function removeEditCondition(groupIndex, conditionIndex) {
const field = fields.value[selectedFieldIndex.value];
if (field && field.editGroups && field.editGroups[groupIndex]) {
  field.editGroups[groupIndex].conditions.splice(conditionIndex, 1);
}
}

// Show value helpers
function addShowValue(groupIndex) {
const field = fields.value[selectedFieldIndex.value];
if (field && field.showGroups && field.showGroups[groupIndex]) {
  field.showGroups[groupIndex].conditions[0].value.push('');
}
}

function removeShowValue(groupIndex, valueIndex) {
const field = fields.value[selectedFieldIndex.value];
if (field && field.showGroups && field.showGroups[groupIndex]) {
  field.showGroups[groupIndex].conditions[0].value.splice(valueIndex, 1);
}
}

// Edit value helpers
function addEditValue(groupIndex) {
const field = fields.value[selectedFieldIndex.value];
if (field && field.editGroups && field.editGroups[groupIndex]) {
  field.editGroups[groupIndex].conditions[0].value.push('');
}
}

function removeEditValue(groupIndex, valueIndex) {
const field = fields.value[selectedFieldIndex.value];
if (field && field.editGroups && field.editGroups[groupIndex]) {
  field.editGroups[groupIndex].conditions[0].value.splice(valueIndex, 1);
}
}

// Parse value helpers
function addParseValue(groupIndex) {
const field = fields.value[selectedFieldIndex.value];
if (field && field.parseGroups && field.parseGroups[groupIndex]) {
  field.parseGroups[groupIndex].conditions[0].value.push('');
}
}

function removeParseValue(groupIndex, valueIndex) {
const field = fields.value[selectedFieldIndex.value];
if (field && field.parseGroups && field.parseGroups[groupIndex]) {
  field.parseGroups[groupIndex].conditions[0].value.splice(valueIndex, 1);
}
}

// Required value helpers
function addRequiredValue(groupIndex) {
const field = fields.value[selectedFieldIndex.value];
if (field && field.requiredGroups && field.requiredGroups[groupIndex]) {
  field.requiredGroups[groupIndex].conditions[0].value.push('');
}
}

function removeRequiredValue(groupIndex, valueIndex) {
const field = fields.value[selectedFieldIndex.value];
if (field && field.requiredGroups && field.requiredGroups[groupIndex]) {
  field.requiredGroups[groupIndex].conditions[0].value.splice(valueIndex, 1);
}
}

// Nested Show condition helpers
function addNestedShowConditionGroup(groupIndex) {
const field = fields.value[selectedFieldIndex.value];
if (field && field.showGroups && field.showGroups[groupIndex]) {
  if (!field.showGroups[groupIndex].conditions[0].nestedConditions) {
    field.showGroups[groupIndex].conditions[0].nestedConditions = [];
  }
  field.showGroups[groupIndex].conditions[0].nestedConditions.push({ 
    type: 'matches', 
    name: '', 
    value: ['']
  });
}
}

function removeNestedShowConditionGroup(groupIndex, nestedGroupIndex) {
const field = fields.value[selectedFieldIndex.value];
if (field && field.showGroups && field.showGroups[groupIndex]) {
  field.showGroups[groupIndex].conditions[0].nestedConditions.splice(nestedGroupIndex, 1);
}
}

function addNestedShowValue(groupIndex, nestedGroupIndex) {
const field = fields.value[selectedFieldIndex.value];
if (field && field.showGroups && field.showGroups[groupIndex]) {
  field.showGroups[groupIndex].conditions[0].nestedConditions[nestedGroupIndex].value.push('');
}
}

function removeNestedShowValue(groupIndex, nestedGroupIndex, valueIndex) {
const field = fields.value[selectedFieldIndex.value];
if (field && field.showGroups && field.showGroups[groupIndex]) {
  field.showGroups[groupIndex].conditions[0].nestedConditions[nestedGroupIndex].value.splice(valueIndex, 1);
}
}

function getDefaultValidationMessage(validationType) {
const messages = {
  'matches': 'Please enter a valid value',
  'date': 'Please enter a valid date',
  'valid_url': 'Please enter a valid URL',
  'valid_email': 'Please enter a valid email address',
  'length': 'Please enter the correct length',
  'numeric': 'Please enter a valid number',
  'pattern': 'Please enter a value that matches the required pattern'
};
return messages[validationType] || 'Please enter a valid value';
}

function addValidationValue() {
const field = fields.value[selectedFieldIndex.value];
if (field) {
  if (!field.validationValues) {
    field.validationValues = [''];
  }
  field.validationValues.push('');
}
}

function removeValidationValue(index) {
const field = fields.value[selectedFieldIndex.value];
if (field && field.validationValues && field.validationValues.length > 1) {
  field.validationValues.splice(index, 1);
}
}

// Options management functions
function addOptionsSection() {
const field = fields.value[selectedFieldIndex.value];
    if (field && field.type === 'options') {
  if (!field.options) {
    field.options = [];
  }
  field.options.push({
    label: `Section ${field.options.length + 1}`,
    type: 'optgroup',
    options: [
      { label: 'Option 1', value: 'option1' }
    ],
    show: true,
    edit: true
  });
}
}

function removeOptionsSection(sectionIndex) {
const field = fields.value[selectedFieldIndex.value];
if (field && field.options && field.options.length > 1) {
  field.options.splice(sectionIndex, 1);
}
}

function addOptionsOption(sectionIndex) {
const field = fields.value[selectedFieldIndex.value];
if (field && field.options && field.options[sectionIndex]) {
  const section = field.options[sectionIndex];
  if (!section.options) {
    section.options = [];
  }
  section.options.push({
    label: `Option ${section.options.length + 1}`,
    value: `option${section.options.length + 1}`
  });
}
}

function removeOptionsOption(sectionIndex, optionIndex) {
const field = fields.value[selectedFieldIndex.value];
if (field && field.options && field.options[sectionIndex]) {
  const section = field.options[sectionIndex];
  if (section.options && section.options.length > 1) {
    section.options.splice(optionIndex, 1);
  }
}
}

// Preview helper functions for options
function getRangeMin(element) {
if (!element.options || !Array.isArray(element.options)) return 0;
const allOptions = element.options.flatMap(section => section.options || []);
const numericValues = allOptions
  .map(option => parseFloat(option.value))
  .filter(value => !isNaN(value));
return numericValues.length > 0 ? Math.min(...numericValues) : 0;
}

function getRangeMax(element) {
if (!element.options || !Array.isArray(element.options)) return 100;
const allOptions = element.options.flatMap(section => section.options || []);
const numericValues = allOptions
  .map(option => parseFloat(option.value))
  .filter(value => !isNaN(value));
return numericValues.length > 0 ? Math.max(...numericValues) : 100;
}

function getRangeStep(element) {
if (!element.options || !Array.isArray(element.options)) return 1;
const allOptions = element.options.flatMap(section => section.options || []);
const numericValues = allOptions
  .map(option => parseFloat(option.value))
  .filter(value => !isNaN(value));

if (numericValues.length < 2) return 1;

// Calculate step based on the difference between consecutive values
const sortedValues = numericValues.sort((a, b) => a - b);
const differences = [];
for (let i = 1; i < sortedValues.length; i++) {
  differences.push(sortedValues[i] - sortedValues[i-1]);
}

// Return the smallest difference, or 1 if no differences found
return differences.length > 0 ? Math.min(...differences) : 1;
}

function getAllOptions(options) {
if (!options || !Array.isArray(options)) return [];
return options.flatMap(section => {
  if (!section || !Array.isArray(section.options)) return [];
  return section.options.filter(option => option && typeof option === 'object');
});
}

// Range slider gradient update function
function updateRangeGradient(event, element) {
const slider = event.target;
const min = parseFloat(slider.min);
const max = parseFloat(slider.max);
const value = parseFloat(slider.value);
const percentage = ((value - min) / (max - min)) * 100;

slider.style.background = `linear-gradient(to right, var(--md-sys-color-primary) 0%, var(--md-sys-color-primary) ${percentage}%, var(--md-sys-color-outline-variant) ${percentage}%, var(--md-sys-color-outline-variant) 100%)`;
}

// Initialize range slider gradients
function initializeRangeGradients() {
nextTick(() => {
  const rangeSliders = document.querySelectorAll('input[type="range"].slider-track');
  rangeSliders.forEach(slider => {
    const min = parseFloat(slider.min);
    const max = parseFloat(slider.max);
    const value = parseFloat(slider.value);
    const percentage = ((value - min) / (max - min)) * 100;
    
    slider.style.background = `linear-gradient(to right, var(--md-sys-color-primary) 0%, var(--md-sys-color-primary) ${percentage}%, var(--md-sys-color-outline-variant) ${percentage}%, var(--md-sys-color-outline-variant) 100%)`;
  });
});
}

// Watch for field changes to reinitialize gradients
watch(fields, () => {
initializeRangeGradients();
}, { deep: true });

// Initialize on mount
onMounted(() => {
initializeRangeGradients();
});

</script>

<style scoped>
.json-output {
@apply p-4 rounded text-xs overflow-x-auto;
background-color: var(--md-sys-color-surface-container);
color: var(--md-sys-color-on-surface);
}

/* Force next element to start on new line */
.force-new-row {
grid-column-start: 1;
}

/* Smooth animations for config sections */
.config-section-content {
transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.config-section-content.collapsed {
opacity: 0;
max-height: 0;
overflow: hidden;
margin-top: 0;
margin-bottom: 0;
padding-top: 0;
padding-bottom: 0;
}

.config-section-content.expanded {
opacity: 1;
max-height: none;
overflow: visible;
}

/* Custom checkbox styling */
input[type="checkbox"] {
appearance: none;
-webkit-appearance: none;
-moz-appearance: none;
width: 1rem;
height: 1rem;
border: 2px solid var(--md-sys-color-outline);
border-radius: 0.25rem;
background-color: var(--md-sys-color-surface-container);
cursor: pointer;
position: relative;
transition: all 0.2s;
}

input[type="checkbox"]:checked {
background-color: var(--md-sys-color-primary);
border-color: var(--md-sys-color-primary);
}

input[type="checkbox"]:checked::after {
content: '';
position: absolute;
left: 50%;
top: 50%;
transform: translate(-50%, -50%);
width: 0.5rem;
height: 0.5rem;
background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3cpath d='M12.207 4.793a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L6.5 9.086l4.293-4.293a1 1 0 011.414 0z'/%3e%3c/svg%3e");
background-size: contain;
background-repeat: no-repeat;
}

input[type="checkbox"]:focus {
outline: none;
box-shadow: 0 0 0 2px var(--md-sys-color-primary);
}

/* Custom Range Slider Styling */
.slider-track {
background: linear-gradient(to right, var(--md-sys-color-primary) 0%, var(--md-sys-color-primary) 50%, var(--md-sys-color-outline-variant) 50%, var(--md-sys-color-outline-variant) 100%);
}

.slider-track::-webkit-slider-thumb {
appearance: none;
width: 16px;
height: 16px;
background: var(--md-sys-color-primary);
border-radius: 50%;
cursor: pointer;
box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
transition: all 0.2s ease;
}

.slider-track::-webkit-slider-thumb:hover {
transform: scale(1.1);
box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
}

.slider-track::-moz-range-thumb {
width: 16px;
height: 16px;
background: var(--md-sys-color-primary);
border-radius: 50%;
cursor: pointer;
border: none;
box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
transition: all 0.2s ease;
}

.slider-track::-moz-range-thumb:hover {
transform: scale(1.1);
box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
}

.slider-track:focus {
outline: none;
}

.slider-track:focus::-webkit-slider-thumb {
box-shadow: 0 0 0 3px var(--md-sys-color-primary-20);
}

.slider-track:focus::-moz-range-thumb {
box-shadow: 0 0 0 3px var(--md-sys-color-primary-20);
}
</style> 