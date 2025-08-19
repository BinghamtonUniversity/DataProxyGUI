<template>
  <div :class="formbuilderTheme.container">
    <div :class="formbuilderTheme.headerContainer">
      <h1 :class="formbuilderTheme.header">FormBuilder</h1>
      <p :class="formbuilderTheme.subheader">Drag field types above the canvas. Reorder fields. Configure field properties.</p>
    </div>
    <div class="flex flex-row gap-6">
      <!-- Left: Form Configuration Sidebar -->
  <div class="w-80 shrink-0 px-4 py-4">
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
              <TextField
                v-model:value="formName"
                name="formName"
                placeholder="Enter form name"
                class="w-full"
              />
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
        <div class="flex flex-row gap-4 mb-4 pt-2">
          <!-- Text Fields -->
          <div class="flex flex-col gap-2">
            <h3 class="text-sm font-medium text-on-surface-variant mb-2">Text</h3>
            <div class="flex flex-row gap-1"><div v-for="type in getFieldTypesByCategory('input')" :key="type.type"
                draggable="true"
                @dragstart="onDragStart($event, type)"
                class="flex flex-col items-center justify-center p-2 bg-white dark:bg-surface-container border-2 border-outline rounded-lg cursor-move hover:border-primary hover:shadow-elevation-2 transition-all duration-200 group min-w-[80px]">
                <!-- Icon -->
                <div class="mb-1">
                  <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                  </svg>
                </div>
                <!-- Label -->
                <span class="text-xs font-medium text-on-surface group-hover:text-primary transition-colors duration-200">{{ type.label }}</span>
              </div>
            </div>
          </div>
          
          <!-- Options Fields -->
          <div class="flex flex-col gap-2">
            <h3 class="text-sm font-medium text-on-surface-variant mb-2">Options</h3>
            <div class="flex flex-row gap-1">
              <div v-for="type in getFieldTypesByCategory('options')" :key="type.type"
                draggable="true"
                @dragstart="onDragStart($event, type)"
                class="flex flex-col items-center justify-center px-2 py-2 bg-white dark:bg-surface-container border-2 border-outline rounded-lg cursor-move hover:border-primary hover:shadow-elevation-2 transition-all duration-200 group min-w-[80px]">
                <!-- Icon -->
                <div class="mb-1">
                  <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                  </svg>
                </div>
                <!-- Label -->
                <span class="text-xs font-medium text-on-surface group-hover:text-primary transition-colors duration-200">{{ type.label }}</span>
              </div>
            </div>
          </div>
          
          <!-- Boolean Fields -->
          <div class="flex flex-col gap-2">
            <h3 class="text-sm font-medium text-on-surface-variant mb-2">Boolean</h3>
            <div class="flex flex-row gap-1">
              <div v-for="type in getFieldTypesByCategory('boolean')" :key="type.type"
                draggable="true"
                @dragstart="onDragStart($event, type)"
                class="flex flex-col items-center justify-center px-2 py-2 bg-white dark:bg-surface-container border-2 border-outline rounded-lg cursor-move hover:border-primary hover:shadow-elevation-2 transition-all duration-200 group min-w-[80px]">
                <!-- Icon -->
                <div class="mb-1">
                  <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                </div>
                <!-- Label -->
                <span class="text-xs font-medium text-on-surface group-hover:text-primary transition-colors duration-200">{{ type.label }}</span>
              </div>
            </div>
          </div>
          
          <!-- Section Fields -->
          <div class="flex flex-col gap-2">
            <h3 class="text-sm font-medium text-on-surface-variant mb-2">Section</h3>
            <div class="flex flex-row gap-1">
              <div v-for="type in getFieldTypesByCategory('section')" :key="type.type"
                draggable="true"
                @dragstart="onDragStart($event, type)"
                class="flex flex-col items-center justify-center px-2 py-2 bg-white dark:bg-surface-container border-2 border-outline rounded-lg cursor-move hover:border-primary hover:shadow-elevation-2 transition-all duration-200 group min-w-[80px]">
                <!-- Icon -->
                <div class="mb-1">
                  <svg class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                  </svg>
                </div>
                <!-- Label -->
                <span class="text-xs font-medium text-on-surface group-hover:text-primary transition-colors duration-200">{{ type.label }}</span>
              </div>
            </div>
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
              <div v-for="(element, index) in currentFieldsWithPreviews" :key="`${element.name}-${element.label}-${element.type}-${element.placeholder}-${element.value}-${element.updateKey || 0}`" class="space-y-4">
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
                      'field-card cursor-pointer',
                      selectedFieldIndex === getOriginalIndex(index)
                        ? formbuilderTheme.fieldCardSelected
                        : formbuilderTheme.fieldCardUnselected
                    ]">
                    <!-- Field Preview -->
                    <div class="space-y-3">

                      
                      <!-- Dynamic Field Preview -->
                      <div class="preview-container" @click.stop>
                        <component 
                          :is="getFieldComponent(element.type)"
                          v-bind="getFieldProps(element)"
                          :disabled="true"
                          class="preview-field pointer-events-none"
                        />
                      </div>
                      
                      <!-- Field Name (small text) -->
                      <div :class="formbuilderTheme.info">
                        <div class="flex items-center justify-between">
                          <span>Field name: <code class="bg-surface-container-high px-2 py-1 rounded-lg text-on-surface-variant">{{ element.name }}</code></span>
                          <span :class="formbuilderTheme.fieldType">{{ element.type }}</span>
                        </div>

                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
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
          <div class="flex gap-2 mb-4 justify-end px-4 py-4">
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
            <div class="mb-4 px-4 py-4 bg-surface-container-low rounded-2xl border border-outline-variant">
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
              <div v-if="selectedField.type === 'fieldset'" class="mb-4">
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
              <div class="space-y-4">
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
                       <SelectField
                         v-model:value="selectedField.type"
                         name="fieldType"
                         :options="fieldTypeOptions"
                         placeholder="Select field type"
                         class="w-full"
                       />
                     </div>
                    
                                         <!-- Field Label and Name -->
                     <div class="flex gap-4">
                       <div class="flex-1">
                         <label class="block text-xs text-on-surface-variant mb-2 font-medium">Field Label<span class="text-error">*</span></label>
                                                <TextField
                         v-model:value="selectedField.label"
                         name="fieldLabel"
                         placeholder="Label"
                         class="w-full"
                       />
                       </div>
                       <div class="flex-1">
                         <label class="block text-xs text-on-surface-variant mb-2 font-medium">Name</label>
                         <TextField
                           v-model:value="selectedField.name"
                           name="fieldName"
                           placeholder="Name"
                           class="w-full"
                         />
                       </div>
                     </div>
                    
                     <!-- Placeholder -->
                     <div v-if="selectedField.type !== 'output' && selectedField.type !== 'hidden'">
                       <label :class="formbuilderTheme.configLabel">Placeholder</label>
                       <TextField
                         v-model:value="selectedField.placeholder"
                         name="fieldPlaceholder"
                         placeholder="Placeholder"
                         class="w-full"
                       />
                     </div>
                    
                    <!-- Default value -->
                    <div v-if="selectedField.type !== 'output' && selectedField.type !== 'hidden'">
                      <label :class="formbuilderTheme.configLabel">Default value</label>
                      <TextField
                        v-model:value="selectedField.value"
                        name="fieldValue"
                        placeholder="Default Value"
                        class="w-full"
                      />
                    </div>
                    
                    <!-- Instructions -->
                    <div>
                      <label :class="formbuilderTheme.configLabel">Instructions</label>
                      <TextAreaField
                        v-model:value="selectedField.help"
                        name="fieldHelp"
                        :rows="2"
                        placeholder="Enter instructions"
                        class="w-full"
                      />
                    </div>
                    
                    <!-- More Information -->
                    <div>
                      <label :class="formbuilderTheme.configLabel">More Information</label>
                      <TextAreaField
                        v-model:value="selectedField.info"
                        name="fieldInfo"
                        :rows="2"
                        placeholder="Enter additional information"
                        class="w-full"
                      />
                    </div>
                    <!-- TODO -->
                    <!-- Fillable -->
                    <!-- <div class="flex items-center gap-4">
                      <CheckboxField
                        v-model:value="selectedField.fillable"
                        name="fieldFillable"
                        label="Fillable"
                        class="w-full"
                      />
                    </div> -->
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
                  <div class="config-section-content space-y-3 pl-2 border-l-2 border-outline-variant" :class="configSections.display ? 'expanded' : 'collapsed'">
                    <!-- Width Configuration -->
                    <div>
                      <label class="block text-xs text-on-surface-variant mb-2 font-medium">Width (Columns)</label>
                      <SelectField
                        v-model:value="selectedField.width"
                        name="fieldWidth"
                        :options="[
                          { label: '1 Column', value: '1' },
                          { label: '2 Columns', value: '2' },
                          { label: '3 Columns', value: '3' },
                          { label: '4 Columns', value: '4' },
                          { label: '5 Columns', value: '5' },
                          { label: '6 Columns', value: '6' },
                          { label: '7 Columns', value: '7' },
                          { label: '8 Columns', value: '8' },
                          { label: '9 Columns', value: '9' },
                          { label: '10 Columns', value: '10' },
                          { label: '11 Columns', value: '11' },
                          { label: '12 Columns (Full Width)', value: '12' }
                        ]"
                  
                        placeholder="Select width"
                        class="w-full"
                      />
                    </div>
                    
                    <!-- Offset Configuration (only show if width is not 12) -->
                    <div v-if="selectedField.width && selectedField.width !== '12'">
                      <label class="block text-xs text-on-surface-variant mb-2 font-medium">Offset (Columns)</label>
                      <SelectField
                        v-model:value="selectedField.offset"
                        name="fieldOffset"
                        :options="[
                          { label: 'No Offset', value: '0' },
                          { label: '1 Column', value: '1' },
                          { label: '2 Columns', value: '2' },
                          { label: '3 Columns', value: '3' },
                          { label: '4 Columns', value: '4' },
                          { label: '5 Columns', value: '5' },
                          { label: '6 Columns', value: '6' },
                          { label: '7 Columns', value: '7' },
                          { label: '8 Columns', value: '8' },
                          { label: '9 Columns', value: '9' },
                          { label: '10 Columns', value: '10' },
                          { label: '11 Columns', value: '11' }
                        ]"
                        placeholder="Select offset"
                        class="w-full"
                      />
                    </div>
                    
                    <!-- Force New Row (only show if width is not 12) -->
                    <div v-if="selectedField.width && selectedField.width !== '12'" class="flex items-center gap-4 px-4 py-4">
                      <CheckboxField
                        v-model:value="selectedField.forceRow"
                        name="fieldForceRow"
                        label="Force New Row"
                        class="w-full"
                      />
                    </div>
                    
                    <!-- Allow Duplication -->
                    <div class="flex items-center gap-4">
                      <SwitchField
                        v-model:value="selectedField.allowDuplication"
                        name="fieldAllowDuplication"
                        label="Allow Duplication"
                        class="w-full"
                        :required=false
                        :options="[
                          { label: 'Not Allow', value: false },
                          { label: 'Allow', value: true }
                        ]"
                      />
                    </div>
                    
                    <!-- Array Configuration (only show if allowDuplication is enabled) -->
                    <div v-if="selectedField.allowDuplication" class="space-y-3 pl-4 border-l-2 border-primary-20">
                      <!-- Min/Max Configuration -->
                      <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-6">
                          
                          <NumberField
                            :label ='`Minimum`'
                            v-model:value="selectedField.arrayMin"
                            name="fieldArrayMin"
                            placeholder="1"
                            class="w-full"
                          />
                        </div>
                        <div class="col-span-6">
                          <NumberField
                            :label ='`Maximum`'
                            v-model:value="selectedField.arrayMax"
                            name="fieldArrayMax"
                            placeholder="5"
                            class="w-full"
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Field-Specific Configuration -->
                <div v-if="selectedField.type === 'select' || selectedField.type === 'combobox' || selectedField.type === 'radio' || selectedField.type === 'checkbox' || selectedField.type === 'switch'" class="mb-4">
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
                  <div class="config-section-content space-y-3 pl-2 border-l-2 border-outline-variant" :class="configSections.options ? 'expanded' : 'collapsed'">
                    <!-- Multiple Selection (only for select/combobox) -->
                    <div v-if="selectedField.type === 'select' || selectedField.type === 'combobox'" class="flex items-center gap-4">
                      <SwitchField
                        v-model:value="selectedField.multiple"
                        name="fieldMultiple"
                        label="Allow Multiple Selections"
                        class="w-full"
                      />
                    </div>
                    <!-- Options List -->
                    <div>
                      <label class="block text-xs text-on-surface-variant mb-2 font-medium">Options</label>
                      <div class="space-y-2">
                        <template v-if="selectedField.type === 'checkbox' || selectedField.type === 'switch'">
                          <div v-for="(option, index) in selectedField.options || []" :key="index" class="flex gap-2">
                            <TextField
                              v-model:value="option.label"
                              :name="`optionLabel_${index}`"
                              :placeholder="index === 0 ? 'False Label' : 'True Label'"
                              class="flex-1"
                            />
                            <TextField
                              v-model:value="option.value"
                              :name="`optionValue_${index}`"
                              :placeholder="index === 0 ? 'False Value' : 'True Value'"
                              class="flex-1"
                            />
                          </div>
                        </template>
                        <template v-else>
                          <div v-for="(option, index) in selectedField.options || []" :key="index" class="flex gap-2">
                            <TextField
                              v-model:value="option.label"
                              :name="`optionLabel_${index}`"
                              placeholder="Label"
                              class="flex-1"
                            />
                            <TextField
                              v-model:value="option.value"
                              :name="`optionValue_${index}`"
                              placeholder="Value"
                              class="flex-1"
                            />
                            <button
                              @click="removeOption(index)"
                              type="button"
                              class="px-3 py-2 text-error hover:text-error hover:bg-error-8 rounded-xl transition-all duration-200"
                              :disabled="(selectedField.options || []).length <= 1">
                              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                              </svg>
                            </button>
                          </div>
                          <button
                            @click="addOption"
                            type="button"
                            class="w-full px-4 py-2 text-primary hover:text-primary hover:bg-primary-8 rounded-xl transition-all duration-200 border border-primary text-sm">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Option
                          </button>
                        </template>
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Range Configuration -->
                <div v-if="selectedField.type === 'range'" class="mb-4">
                  <button
                    @click="toggleConfigSection('range')"
                    class="flex items-center justify-between w-full text-left font-medium text-on-surface mb-3 p-3 rounded-xl hover:bg-surface-container-high transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                    <span class="flex items-center gap-2">
                      <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z"></path>
                      </svg>
                      Range Settings
                    </span>
                    <svg :class="['w-5 h-5 text-on-surface-variant transition-transform duration-200', configSections.range ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                  </button>
                  <div class="config-section-content space-y-3 pl-2 border-l-2 border-outline-variant" :class="configSections.range ? 'expanded' : 'collapsed'">
                    <div class="grid grid-cols-3 gap-4">
                      <div>
                        <label class="block text-xs text-on-surface-variant mb-2 font-medium">Min</label>
                        <NumberField
                          v-model:value="selectedField.min"
                          name="fieldMin"
                          placeholder="0"
                          class="w-full"
                        />
                      </div>
                      <div>
                        <label class="block text-xs text-on-surface-variant mb-2 font-medium">Max</label>
                        <NumberField
                          v-model:value="selectedField.max"
                          name="fieldMax"
                          placeholder="100"
                          class="w-full"
                        />
                      </div>
                      <div>
                        <label class="block text-xs text-on-surface-variant mb-2 font-medium">Step</label>
                        <NumberField
                          v-model:value="selectedField.step"
                          name="fieldStep"
                          placeholder="1"
                          class="w-full"
                        />
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Section Configuration -->
                <div v-if="selectedField.type === 'fieldset'" class="mb-4">
                  <button
                    @click="toggleConfigSection('section')"
                    class="flex items-center justify-between w-full text-left font-medium text-on-surface mb-3 p-3 rounded-xl hover:bg-surface-container-high transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                    <span class="flex items-center gap-2">
                      <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                      </svg>
                      Section Settings
                    </span>
                    <svg :class="['w-5 h-5 text-on-surface-variant transition-transform duration-200', configSections.section ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                  </button>
                  <div class="config-section-content space-y-3 pl-2 border-l-2 border-outline-variant" :class="configSections.section ? 'expanded' : 'collapsed'">
                    <div>
                      <label class="block text-xs text-on-surface-variant mb-2 font-medium">Description</label>
                      <TextAreaField
                        v-model:value="selectedField.description"
                        name="fieldDescription"
                        placeholder="Enter section description"
                        :rows="2"
                        class="w-full"
                      />
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
                  <div class="config-section-content space-y-3 pl-2 border-l-2 border-outline-variant" :class="configSections.conditions ? 'expanded' : 'collapsed'">
                    <!-- Show Condition -->
                    <div>
                      <label class="block text-xs text-on-surface-variant mb-2 font-medium">Show Field</label>
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
                      <label class="block text-xs text-on-surface-variant mb-2 font-medium">Edit Field</label>
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
                      <label class="block text-xs text-on-surface-variant mb-2 font-medium">Parse Field</label>
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
                      <label class="block text-xs text-on-surface-variant mb-2 font-medium">Required Field</label>
                      <div class="relative">
                        <select v-model="selectedField.required" class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200 appearance-none pr-10">
                          <option value="true">Always</option>
                          <option value="false">Never</option>
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
                
                <!-- Validation Configuration -->
                <div class="mb-4">
                  <button
                    @click="toggleConfigSection('validation')"
                    class="flex items-center justify-between w-full text-left font-medium text-on-surface mb-3 p-3 rounded-xl hover:bg-surface-container-high transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
                    <span class="flex items-center gap-2">
                      <svg class="w-5 h-5 text-on-surface-variant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                      </svg>
                      Validation
                    </span>
                    <svg :class="['w-5 h-5 text-on-surface-variant transition-transform duration-200', configSections.validation ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                  </button>
                  <div class="config-section-content space-y-3 pl-2 border-l-2 border-outline-variant" :class="configSections.validation ? 'expanded' : 'collapsed'">
                    <!-- Enable Validation Switch -->
                    <!-- <div class="flex items-center justify-between">
                      <label class="block text-xs text-on-surface-variant mb-2 font-medium">Enable Validation</label>
                      <input 
                        type="checkbox" 
                        v-model="selectedField.validate"
                        class="w-4 h-4 text-primary bg-surface-container border-outline rounded focus:ring-primary-20 focus:ring-2">
                    </div> -->

                    <div class="flex items-center gap-4">

                    <SwitchField
                        v-model:value="selectedField.enableValidate"
                        name="fieldEnableValidation"
                        label="Enable Validation"
                        class="w-full"
                        :required=false
                        :options="[
                          { label: 'Not Validate', value: false },
                          { label: 'Validate', value: true }
                        ]"
                      />
                    </div>
                    <!-- Validation Configuration (only show if validation is enabled) -->
                    <div v-if="selectedField.enableValidate" class="space-y-3 pl-4 border-l-2 border-primary-20">
                      <!-- Validation Type -->
                      <div>
                        <SelectField
                          v-model:value="selectedField.validationType"
                          name="fieldValidationType"
                          :options="[
                            { label: 'None', value: 'none' },
                            { label: 'Matches', value: 'matches' },
                            { label: 'Date', value: 'date' },
                            { label: 'Valid URL', value: 'valid_url' },
                            { label: 'Valid Email', value: 'valid_email' },
                            { label: 'Length', value: 'length' },
                            { label: 'Numeric', value: 'numeric' },
                            { label: 'Pattern', value: 'pattern' }
                          ]"
                          placeholder="Select validation type"
                          class="w-full">
                        </SelectField>
                      </div>
                      
                      <!-- Pattern Configuration (only for pattern type) -->
                      <div v-if="selectedField.validationType === 'pattern'" class="space-y-3">
                        <TextField
                          v-model:value="selectedField.validationPattern"
                          name="fieldValidationPattern"
                          label="Regex Pattern"
                          placeholder="Enter regex pattern"
                          class="w-full">
                        </TextField>

                        
                        
                        <div>
                          <TextField
                            v-model:value="selectedField.validationFlags"
                            name="fieldValidationFlags"
                            label="Regex Flags"
                            placeholder="Enter flags (e.g., gi)"
                            help="Common flags: g (global), i (case insensitive), m (multiline)"
                            class="w-full">
                          </TextField>                          
                        </div>
                      </div>
                      
                      <!-- Length Configuration (only for length type) -->
                      <div v-if="selectedField.validationType === 'length'" class="space-y-3">
                        <div class="grid grid-cols-2 gap-4">
                          <div>
                            <label class="block text-xs text-on-surface-variant mb-2 font-medium">Minimum Length</label>
                            <input
                              v-model="selectedField.validationMinLength"
                              type="number"
                              min="0"
                              placeholder="0"
                              class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200">
                          </div>
                          <div>
                            <label class="block text-xs text-on-surface-variant mb-2 font-medium">Maximum Length</label>
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
                            <label class="block text-xs text-on-surface-variant mb-2 font-medium">Minimum Value</label>
                            <input
                              v-model="selectedField.validationMinValue"
                              type="number"
                              placeholder="0"
                              class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200">
                          </div>
                          <div>
                            <label class="block text-xs text-on-surface-variant mb-2 font-medium">Maximum Value</label>
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
                          <label class="block text-xs text-on-surface-variant mb-2 font-medium">Field Name</label>
                          <input
                            v-model="selectedField.validationFieldName"
                            type="text"
                            placeholder="Enter field name to match"
                            class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200">
                        </div>
                        <div>
                          <label class="block text-xs text-on-surface-variant mb-2 font-medium">Expected Values</label>
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
                        <label class="block text-xs text-on-surface-variant mb-2 font-medium">Error Message</label>
                        <input
                          v-model="selectedField.validationMessage"
                          type="text"
                          :placeholder="getDefaultValidationMessage(selectedField.validationType)"
                          class="w-full px-4 py-3 rounded-xl border border-outline bg-surface-container text-on-surface text-sm focus:outline-none focus:ring-2 focus:ring-primary-20 focus:border-primary transition-all duration-200">
                      </div>
                      
                      <!-- When to Apply Validation -->
                      <div>
                        <label class="block text-xs text-on-surface-variant mb-2 font-medium">When to Apply</label>
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
                        <label class="block text-xs text-on-surface-variant mb-2 font-medium">Validation Conditions</label>
                        <ConditionalLogic
                          v-model="selectedField.validationGroups"
                          condition-type="validation" />
                      </div>
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
        <!-- Collapsible JSON Output -->
        <div class="mt-6">
          <button @click="showPreviewJson = !showPreviewJson" class="w-full flex items-center justify-between px-4 py-2 bg-surface-container-low border border-outline-variant rounded-xl text-sm font-medium hover:bg-surface-container-high transition-all duration-200">
            <span>Show Form Data JSON</span>
            <svg :class="['w-5 h-5 transition-transform duration-200', showPreviewJson ? 'rotate-180' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
          </button>
          <div v-if="showPreviewJson" class="mt-2 p-3 bg-surface-container-high rounded-xl border border-outline-variant overflow-auto max-h-64">
            <pre class="text-xs text-on-surface-variant">{{ JSON.stringify(previewFormData, null, 2) }}</pre>
          </div>
        </div>
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
import ConditionalLogic from '../formbuilder/components/ConditionalLogic.vue';
import { getThemeClasses } from '../Theme.js';

// Import all dynamic field components
import TextField from '../fields/TextField.vue';
import TextAreaField from '../fields/TextAreaField.vue';
import EmailField from '../fields/EmailField.vue';
import TelField from '../fields/TelField.vue';
import URLField from '../fields/URLField.vue';
import DateField from '../fields/DateField.vue';
import NumberField from '../fields/NumberField.vue';
import CurrencyField from '../fields/CurrencyField.vue';
import PasswordField from '../fields/PasswordField.vue';
import ColorField from '../fields/ColorField.vue';
import RangeField from '../fields/RangeField.vue';
import SelectField from '../fields/SelectField.vue';
import ComboboxField from '../fields/ComboboxField.vue';
import RadioField from '../fields/RadioField.vue';
import CheckboxField from '../fields/CheckboxField.vue';
import SwitchField from '../fields/SwitchField.vue';
import FieldsetField from '../fields/FieldsetField.vue';
import ArrayField from '../fields/ArrayField.vue';
import HiddenField from '../fields/HiddenField.vue';
import OutputField from '../fields/OutputField.vue';


const fieldTypes = [
  { type: 'text', label: 'Text', category: 'input' },
  { type: 'select', label: 'Options', category: 'options' },
  { type: 'checkbox', label: 'Boolean', category: 'boolean' },
  { type: 'fieldset', label: 'Section', category: 'section' },
];

// Field type options for each category
const fieldTypeOptionsByCategory = {
  input: [
    { label: 'Text', value: 'text' },
    { label: 'Textarea', value: 'textarea' },
    { label: 'Email', value: 'email' },
    { label: 'Telephone', value: 'tel' },
    { label: 'URL', value: 'url' },
    { label: 'Date', value: 'date' },
    { label: 'Number', value: 'number' },
    { label: 'Currency', value: 'currency' },
    { label: 'Password', value: 'password' },
    { label: 'Color', value: 'color' },
    { label: 'Hidden', value: 'hidden' },
    { label: 'Output', value: 'output' },
  ],
  options: [
    { label: 'Dropdown', value: 'select' },
    { label: 'List', value: 'radio' },
    { label: 'Combobox', value: 'combobox' },
    { label: 'Range', value: 'range' },
  ],
  boolean: [
    { label: 'Checkbox', value: 'checkbox' },
    { label: 'Switch', value: 'switch' },
  ],
  section: [
    { label: 'Section', value: 'fieldset' },
  ]
};

// Computed field type options based on selected field category
const fieldTypeOptions = computed(() => {
  if (!selectedField.value) return [];
  
  // Determine category based on current field type
  let category = 'input';
  if (['select', 'combobox', 'radio', 'range'].includes(selectedField.value.type)) {
    category = 'options';
  } else if (['checkbox', 'switch'].includes(selectedField.value.type)) {
    category = 'boolean';
  } else if (['fieldset'].includes(selectedField.value.type)) {
    category = 'section';
  }
  
  return fieldTypeOptionsByCategory[category] || fieldTypeOptionsByCategory.input;
});

// Function to get field types by category
function getFieldTypesByCategory(category) {
  return fieldTypes.filter(type => type.category === category);
}

const fields = ref([]);


const selectedFieldIndex = ref(null);
const isJsonModalOpen = ref(false);
const isPreviewModalOpen = ref(false);
const formName = ref('my-form');
const previewFormData = ref({});
const previewKey = ref(0);
const hoveredInfoIndex = ref(null);
const dragOverIndex = ref(null);
const isDragging = ref(false);
const previewPosition = ref(null);
const previewElement = ref(null);
const showPreviewJson = ref(false); // <-- Define showPreviewJson as a ref

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

  const currentFields = getCurrentFields();
  let originalIndex = 0;
  for (let i = 0; i <= currentFields.length; i++) {
    if (i === previewIndex) {
      if (i === previewPosition.value) {
        // This is a preview element, return the index of the next real field
        return originalIndex;
      }
      originalIndex++;
    }
    if (i < currentFields.length) {
      if (i === previewIndex) {
        return originalIndex - 1;
      }
      originalIndex++;
    }
  }
  return previewIndex;
}

const configSections = ref({
  basic: false,
  display: true,
  options: false,
  range: false,
  section: false,
  conditions: false,
  validation: false
});

const formViewerRef = ref(null);
const formbuilderTheme = getThemeClasses('formbuilder');

// Field component mapping
const fieldComponents = {
  text: TextField,
  textarea: TextAreaField,
  email: EmailField,
  tel: TelField,
  url: URLField,
  date: DateField,
  number: NumberField,
  currency: CurrencyField,
  password: PasswordField,
  color: ColorField,
  range: RangeField,
  select: SelectField,
  combobox: ComboboxField,
  radio: RadioField,
  checkbox: CheckboxField,
  switch: SwitchField,
  fieldset: FieldsetField,
  array: ArrayField,
  hidden: HiddenField,
  output: OutputField,
};

function getFieldComponent(type) {
  return fieldComponents[type] || TextField;
}

function getFieldProps(field) {
  const baseProps = {
    name: field.name,
    label: field.label,
    placeholder: field.placeholder,
    value: field.value,
    required: field.required,
    help: field.help,
    info: field.info,    
  };

  switch (field.type) {
    case 'select':
    case 'combobox':
    case 'radio':
      return {
        ...baseProps,
        options: field.options || [
          { label: 'Option 1', value: 'option1' },
          { label: 'Option 2', value: 'option2' }
        ],
        multiple: field.multiple || false,
      };
    case 'checkbox':
    case 'switch':
      return {
        ...baseProps,
        options: field.options && field.options.length === 2
          ? [
              { label: field.options[0].label, value: field.options[0].value },
              { label: field.options[1].label, value: field.options[1].value }
            ]
          : [
              { label: 'false', value: 'false' },
              { label: 'true', value: 'true' }
            ],
      };
    case 'range':
      return {
        ...baseProps,
        min: field.min || 0,
        max: field.max || 100,
        step: field.step || 1,
      };
    case 'fieldset':
      return {
        ...baseProps,
        fields: field.fields || [],
        description: field.description,
      };
    case 'array':
      return {
        ...baseProps,
        min: field.arrayMin || null,
        max: field.arrayMax || null,
        duplicateEnable: field.duplicateEnable || 'auto',
        removeEnable: field.removeEnable || 'auto',
        duplicateClone: field.duplicateClone || false,
      };
    default:
      return baseProps;
  }
}

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

function createAndAddField(event, position) {
  const typeData = event.dataTransfer.getData('application/json');

  if (typeData) {
    try {
      const type = JSON.parse(typeData);
      let defaultType = 'text';
      let defaultLabel = 'New Field';
      switch (type.category) {
        case 'input':
          defaultType = 'text';
          defaultLabel = 'Text Field';
          break;
        case 'options':
          defaultType = 'select';
          defaultLabel = 'Dropdown';
          break;
        case 'boolean':
          defaultType = 'checkbox';
          defaultLabel = 'Checkbox';
          break;
        case 'section':
          defaultType = 'fieldset';
          defaultLabel = 'Section';
          break;
      }
      const newField = {
        allowDuplication:false,
        enableValidate: false,
        validationType: 'none',
        validationWhen: 'always', // Todo
        type: defaultType,
        label: defaultLabel,
        name: `${defaultType}_${Date.now()}`,
        placeholder: '',
        value: (defaultType === 'checkbox' || defaultType === 'switch') ? 'false' : '',
        required: false,
        help: '',
        info: '',
        updateKey: 0,
        show: true,
        edit: true,
        parse: true,
        width: '12',
        offset: '0',
        options: (defaultType === 'checkbox' || defaultType === 'switch')
          ? [
              { label: 'false', value: 'false' },
              { label: 'true', value: 'true' }
            ]
          : undefined,
      };
      if (defaultType === 'select' || defaultType === 'combobox' || defaultType === 'radio') {
        newField.options = [
          { label: 'Option 1', value: 'option1' },
          { label: 'Option 2', value: 'option2' }
        ];

      } else if (defaultType === 'range') {
        newField.min = 0;
        newField.max = 100;
        newField.step = 1;
      } else if (defaultType === 'fieldset') {
        newField.fields = [];
        newField.description = '';
      } else if (defaultType === 'array') {
        newField.arrayMin = null;
        newField.arrayMax = null;
        newField.duplicateEnable = 'auto';
        newField.removeEnable = 'auto';
        newField.duplicateClone = false;
      }
      const currentFields = getCurrentFields();
      currentFields.splice(position, 0, newField);
      selectedFieldIndex.value = position;
    } catch (error) {
      console.error('Error parsing dropped data:', error);
    }
  }
}

function selectField(index) {
  // Simple selection - just set the selectedFieldIndex
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

  console.log('Fields array:', fields.value);
  console.log('Form config for preview:', formConfigForPreview.value);

  isPreviewModalOpen.value = true;
}

function closePreviewModal() {
  isPreviewModalOpen.value = false;
  previewFormData.value = {};
}

function copyToClipboard() {
  navigator.clipboard.writeText(generatedJson.value).then(() => {
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
      alert('Form submitted successfully! Check console for form data.');
      closePreviewModal();
    } else {
      console.log('Form validation failed');
    }
  } else {
    console.log('Preview form submitted:', previewFormData.value);
    alert('Form submitted successfully! Check console for form data.');
    closePreviewModal();
  }
}

function toggleConfigSection(section) {
  configSections.value[section] = !configSections.value[section];
}

// Options management functions
function addOption() {
  if (!selectedField.value.options) {
    selectedField.value.options = [];
  }
  selectedField.value.options.push({
    label: `Option ${selectedField.value.options.length + 1}`,
    value: `option${selectedField.value.options.length + 1}`
  });
}

function removeOption(index) {
  if (selectedField.value.options && selectedField.value.options.length > 1) {
    selectedField.value.options.splice(index, 1);
  }
}

// Validation management functions
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
  const field = selectedField.value;
  if (field) {
    if (!field.validationValues) {
      field.validationValues = [''];
    }
    field.validationValues.push('');
  }
}

function removeValidationValue(index) {
  const field = selectedField.value;
  if (field && field.validationValues && field.validationValues.length > 1) {
    field.validationValues.splice(index, 1);
  }
}

// Navigation methods for managing nested fieldsets
function manageSection(fieldIndex) {
  const currentFields = getCurrentFields();
  const field = currentFields[fieldIndex];

  if (field && field.type === 'fieldset') {
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

// Computed property for the currently selected field
const selectedField = computed(() => {
  if (selectedFieldIndex.value === null) return null;
  const currentFields = getCurrentFields();
  return currentFields[selectedFieldIndex.value];
});

function getFieldJson(field) {
  if (!field) return {};

  // Only include the properties we want in the export
  const base = {
    name: field.name,
    label: field.label,
    type: field.type,
    placeholder: field.placeholder,
    value: field.value,
    required: field.required,
    help: field.help,
    info: field.info,
  };

  // Add display properties
  if (field.width) {
    base.width = field.width;
  }
  if (field.offset) {
    base.offset = field.offset;
  }
  if (field.forceRow) {
    base.forceRow = field.forceRow;
  }
  if (field.allowDuplication) {
    base.allowDuplication = field.allowDuplication;
  }

  // Add type-specific properties
  switch (field.type) {
    case 'select':
    case 'combobox':
    case 'radio':
      base.options = field.options || [];
      base.multiple = field.multiple || false;
      break;
    case 'range':
      base.min = field.min || 0;
      base.max = field.max || 100;
      base.step = field.step || 1;
      break;
    case 'fieldset':
      base.description = field.description;
      if (field.fields && Array.isArray(field.fields) && field.fields.length > 0) {
        base.fields = field.fields.map(getFieldJson).filter(field => field && Object.keys(field).length > 0);
      }
      break;
    case 'array':
      base.array = {
        min: field.arrayMin || null,
        max: field.arrayMax || null,
        duplicate: {
          enable: field.duplicateEnable || "auto",
          clone: field.duplicateClone || false
        },
        remove: {
          enable: field.removeEnable || "auto"
        }
      };
      break;
    case 'checkbox':
    case 'switch':
      base.type = field.booleanType || field.type || 'checkbox';
      base.value = field.value || 'false';
      base.showColumn = true;
      base.options = field.options && field.options.length === 2
        ? [
            { label: field.options[0].label, value: field.options[0].value },
            { label: field.options[1].label, value: field.options[1].value }
          ]
        : [
            { label: 'false', value: 'false' },
            { label: 'true', value: 'true' }
          ];
      break;
  }

  // Add array properties for any field that has allowDuplication enabled
  if (field.allowDuplication && (field.arrayMin !== undefined || field.arrayMax !== undefined)) {
    if (!base.array) {
      base.array = {};
    }
    if (field.arrayMin !== undefined) {
      base.array.min = field.arrayMin;
    }
    if (field.arrayMax !== undefined) {
      base.array.max = field.arrayMax;
    }
  }

  // Add conditions
  if (field.show !== undefined) {
    base.show = field.show;
  }
  if (field.edit !== undefined) {
    base.edit = field.edit;
  }
  if (field.parse !== undefined) {
    base.parse = field.parse;
  }
  if (field.required !== undefined) {
    base.required = field.required;
  }

  // Add conditional logic groups
  if (field.show === 'conditional' && field.showGroups) {
    base.show = field.showGroups;
  }
  if (field.edit === 'conditional' && field.editGroups) {
    base.edit = field.editGroups;
  }
  if (field.parse === 'conditional' && field.parseGroups) {
    base.parse = field.parseGroups;
  }
  if (field.required === 'conditional' && field.requiredGroups) {
    base.required = field.requiredGroups;
  }

  // Add validation properties
  if (field.enableValidate) {
    const validationRule = {
      type: field.validationType || 'none'
    };

    // Add validation type-specific properties
    switch (field.validationType) {
      case 'pattern':
        if (field.validationPattern) {
          validationRule.pattern = field.validationPattern;
        }
        if (field.validationFlags) {
          validationRule.flags = field.validationFlags;
        }
        break;
      case 'length':
        if (field.validationMinLength !== undefined) {
          validationRule.minLength = field.validationMinLength;
        }
        if (field.validationMaxLength !== undefined) {
          validationRule.maxLength = field.validationMaxLength;
        }
        break;
      case 'numeric':
        if (field.validationMinValue !== undefined) {
          validationRule.minValue = field.validationMinValue;
        }
        if (field.validationMaxValue !== undefined) {
          validationRule.maxValue = field.validationMaxValue;
        }
        break;
      case 'matches':
        if (field.validationFieldName) {
          validationRule.fieldName = field.validationFieldName;
        }
        if (field.validationValues && field.validationValues.length > 0) {
          validationRule.values = field.validationValues.filter(v => v !== '');
        }
        break;
    }

    // Add error message
    if (field.validationMessage) {
      validationRule.message = field.validationMessage;
    }

    // Add conditional validation logic
    if (field.validationWhen === 'conditional' && field.validationGroups) {
      validationRule.conditions = field.validationGroups;
    }

    // Set validation as an array with the rule
    base.enableValidate = [validationRule];
  }

  // Legacy validation properties (for backward compatibility)
  if (field.minLength !== undefined) {
    base.minLength = field.minLength;
  }
  if (field.maxLength !== undefined) {
    base.maxLength = field.maxLength;
  }
  if (field.pattern) {
    base.pattern = field.pattern;
  }
  if (field.validation) {
    base.validation = field.validation;
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

// Simple watcher for fields changes (like original FormBuilder)
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

// Watch for field type changes to update field properties
watch(
  () => selectedField.value?.type,
  (newType, oldType) => {
    if (newType && newType !== oldType && selectedField.value) {
      const field = selectedField.value;
      if (
        newType === 'select' ||
        newType === 'combobox' ||
        newType === 'radio' ||
        newType === 'checkbox' ||
        newType === 'switch'
      ) {
        if (!field.options) {
          if (newType === 'select' || newType === 'combobox' || newType === 'radio') {
            field.options = [
              { label: 'Option 1', value: 'option1' },
              { label: 'Option 2', value: 'option2' }
            ];
          }
          if (newType === 'checkbox' || newType === 'switch') {
            field.options = [
              { label: 'false', value: 'false' },
              { label: 'true', value: 'true' }
            ];
          }
        }
        if (newType === 'select' || newType === 'combobox' || newType === 'radio') {
          field.multiple = false;
        }
      } else {
        delete field.options;
        delete field.multiple;
      }
      if (newType === 'range') {
        field.min = field.min || 0;
        field.max = field.max || 100;
        field.step = field.step || 1;
      } else {
        delete field.min;
        delete field.max;
        delete field.step;
      }
      if (newType === 'fieldset') {
        field.fields = field.fields || [];
        field.description = field.description || '';
      } else {
        delete field.fields;
        delete field.description;
      }
      if (newType === 'array') {
        field.arrayMin = field.arrayMin || null;
        field.arrayMax = field.arrayMax || null;
        field.duplicateEnable = field.duplicateEnable || 'auto';
        field.removeEnable = field.removeEnable || 'auto';
        field.duplicateClone = field.duplicateClone || false;
      } else {
        delete field.arrayMin;
        delete field.arrayMax;
        delete field.duplicateEnable;
        delete field.removeEnable;
        delete field.duplicateClone;
      }
    }
  }
);

// Watch for changes in condition fields to initialize conditional properties
watch(
  () => selectedField.value?.show,
  (newValue) => {
    if (newValue === 'conditional' && selectedField.value) {
      const field = selectedField.value;
      if (!field.showGroups) {
        field.showGroups = [{ op: 'and', conditions: [{ type: 'matches', name: '', value: [''] }] }];
      }
    }
  }
);

watch(
  () => selectedField.value?.edit,
  (newValue) => {
    if (newValue === 'conditional' && selectedField.value) {
      const field = selectedField.value;
      if (!field.editGroups) {
        field.editGroups = [{ op: 'and', conditions: [{ type: 'matches', name: '', value: [''] }] }];
      }
    }
  }
);

watch(
  () => selectedField.value?.parse,
  (newValue) => {
    if (newValue === 'conditional' && selectedField.value) {
      const field = selectedField.value;
      if (!field.parseGroups) {
        field.parseGroups = [{ op: 'and', conditions: [{ type: 'matches', name: '', value: [''] }] }];
      }
    }
  }
);

watch(
  () => selectedField.value?.required,
  (newValue) => {
    if (newValue === 'conditional' && selectedField.value) {
      const field = selectedField.value;
      if (!field.requiredGroups) {
        field.requiredGroups = [{ op: 'and', conditions: [{ type: 'matches', name: '', value: [''] }] }];
      }
    }
  }
);

// Watch for validation type changes to initialize validation properties
watch(
  () => selectedField.value?.validationType,
  (newValue) => {
    if (newValue && selectedField.value) {
      const field = selectedField.value;
      
      // Initialize validation values for matches type
      if (newValue === 'matches' && !field.validationValues) {
        field.validationValues = [''];
      }
      
      // Initialize validation message if not set
      if (!field.validationMessage) {
        field.validationMessage = getDefaultValidationMessage(newValue);
      }
    }
  }
);

// Watch for validation when changes to initialize conditional validation
watch(
  () => selectedField.value?.validationWhen,
  (newValue) => {
    if (newValue === 'conditional' && selectedField.value) {
      const field = selectedField.value;
      if (!field.validationGroups) {
        field.validationGroups = [{ op: 'and', conditions: [{ type: 'matches', name: '', value: [''] }] }];
      }
    }
  }
);

// Ensure all fields have updateKey property on mount
onMounted(() => {
  const currentFields = getCurrentFields();
  currentFields.forEach(field => {
    if (field.updateKey === undefined) {
      field.updateKey = 0;
    }
  });
  

});


</script>

<style scoped>
.json-output {
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

/* Preview field styling */
.preview-container {
  pointer-events: none;
}

.preview-field {
  opacity: 0.7;
  cursor: not-allowed;
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
</style>