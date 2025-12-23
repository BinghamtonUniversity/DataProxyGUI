

<template>
  <div :class="currentTheme.container">
    <!-- Header with title, actions, and search -->
    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
      <!-- Title and Description -->
      <div class="flex flex-col gap-0.5">
        <h2 v-if="schema.label || schema.title || formConfig.label || formConfig.title || title" :class="currentTheme.title">
          {{ schema.label || schema.title || formConfig.label || formConfig.title || title }}
        </h2>
        <p v-if="schema.description || formConfig.description" class="text-xs text-gray-500 dark:text-gray-400">
          {{ schema.description || formConfig.description }}
        </p>
      </div>
      
      <!-- Action buttons row -->
      <div v-if="processedActions.length > 0 || showNew || showEdit || showDelete" class="flex items-center justify-between gap-3 mt-3">
        <!-- Left positioned actions -->
        <div class="flex items-center gap-1.5">
          <template v-if="processedActions.length > 0">
            <template v-for="(action, index) in leftActions" :key="`left-${index}`">
              <div v-if="action.type === 'separator'" class="w-px h-4 bg-gray-300 dark:bg-gray-700"></div>
              <button
                v-else
                @click="handleCustomAction(action)"
                :disabled="action.disabled"
                :title="getActionTooltip(action)"
                :class="action.buttonClass"
              >
                <font-awesome-icon v-if="action.icon" :icon="action.icon" class="mr-2" />
                {{ action.label }}
              </button>
            </template>
          </template>
          <template v-else>
            <button v-if="showNew" @click="onCreate" :class="currentTheme.newButton">New</button>
          </template>
        </div>
        
        <!-- Right positioned actions -->
        <div class="flex items-center gap-2">
          <slot name="actions">
            <template v-if="processedActions.length > 0">
              <template v-for="(action, index) in rightActions" :key="`right-${index}`">
                <div v-if="action.type === 'separator'" class="w-px h-4 bg-gray-300 dark:bg-gray-700"></div>
                <button
                  v-else
                  @click="handleCustomAction(action)"
                  :disabled="action.disabled"
                  :title="getActionTooltip(action)"
                  :class="action.buttonClass"
                >
                  <font-awesome-icon v-if="action.icon" :icon="action.icon" class="mr-2" />
                  {{ action.label }}
                </button>
              </template>
            </template>
            <template v-else>
              <button
                v-if="showEdit"
                @click="onEdit"
                :disabled="selectedRows.length === 0"
                :title="selectedRows.length === 0 ? 'Please select at least one row' : ''"
                :class="currentTheme.editButton"
              >
                Edit
              </button>
              <button
                v-if="showDelete"
                @click="onDelete"
                :disabled="selectedRows.length === 0"
                :title="selectedRows.length === 0 ? 'Please select a row' : ''"
                :class="currentTheme.deleteButton"
              >
                Delete
              </button>
            </template>
          </slot>
        </div>
      </div>

      <!-- Search bar (conditional) -->
      <div v-if="search" class="flex items-center justify-between gap-2 mt-3">
        <div class="relative flex-1 max-w-sm">
          <TextField
          :required="false"
          :value="searchQuery"
          @update:value="searchQuery = $event"
          @input="onSearchInput"
          @change="onSearchInput"
          @keydown="handleSearchKeydown"
          name="searchQuery"
          label=""
          placeholder="Search (e.g. column_name:contains:string)"
          autocomplete="off"
          spellcheck="false"
        />
        <div v-if="showSuggestions && suggestions.length" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded shadow mt-1 absolute z-20">
          <div 
            v-for="(s, i) in suggestions" 
            :key="i" 
            @mousedown.prevent="applySuggestion(s)" 
            :class="[
              'px-4 py-2 cursor-pointer transition-colors',
              i === selectedSuggestionIndex 
                ? 'bg-blue-100 dark:bg-blue-900 text-blue-900 dark:text-blue-100' 
                : 'hover:bg-gray-100 dark:hover:bg-gray-700'
            ]"
          >
            {{ s }}
          </div>
        </div>

        </div>
        <div class="flex items-center gap-1">
          <div v-if="upload || download || columns" class="flex items-center justify-end  mt-4">
            <div class="flex border border-gray-200 dark:border-gray-700 rounded-md">
              <input
                v-if="upload"
                ref="fileInput"
                type="file"
                accept=".csv"
                @change="handleFileUpload"
                class="hidden"
              />
              <button
                v-if="download"
                @click="downloadCSV"
                class="p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors border-r border-gray-200 dark:border-gray-700"
                title="Download CSV"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
              </button>
              <button
                v-if="upload"
                @click="$refs.fileInput.click()"
                class="p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors border-r border-gray-200 dark:border-gray-700"
                title="Upload CSV"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                </svg>
              </button>
              <!-- Column visibility toggle button -->
              <div v-if="columns" class="relative column-selector-container">
                <button
                  @click="toggleColumnSelector"
                  class="p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                  title="Toggle Column Visibility"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                  </svg>
                </button>
                
                <!-- Column selector dropdown -->
                <div v-if="showColumnSelector" class="absolute right-0 mt-2 w-64 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg z-50">
                  <div class="p-4">
                    <div class="flex items-center justify-between mb-3">
                      <h3 class="text-sm font-medium text-gray-900 dark:text-white">Show Columns</h3>
                      <div class="flex gap-2">
                        <button
                          @click="selectAllColumns"
                          class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                        >
                          Select All
                        </button>
                        <button
                          @click="deselectAllColumns"
                          class="text-xs text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-300"
                        >
                          Deselect All
                        </button>
                      </div>
                    </div>
                    <div class="space-y-2 max-h-48 overflow-y-auto">
                      <label
                        v-for="col in allColumns.filter(c => c.showColumn !== false)"
                        :key="col.key"
                        class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 p-1 rounded"
                      >
                        <input
                          type="checkbox"
                          :checked="visibleColumns.has(col.key)"
                          @change="toggleColumnVisibility(col.key)"
                          class="form-checkbox h-4 w-4 text-blue-600 transition duration-150 ease-in-out"
                        />
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ col.label || col.key }}</span>
                      </label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="overflow-x-auto">
      <table :class="currentTheme.table">
        <thead>
          <tr>
            <th :class="[currentTheme.headerCell, 'w-[32px]', 'min-w-[32px]', 'max-w-[32px]']">
              <CheckboxField
                :name="'select-all'"
                :value="allSelected"
                @update:value="toggleSelectAll($event)"
                :required="false"
              
                :options="[
                  { label: '', value: 'false' },
                  { label: '', value: 'true' }
                ]"
              />
            </th>
            <th 
              v-for="(col, colIdx) in computedColumns" 
              :key="col.key" 
              :class="[
                currentTheme.headerCell, 
                'cursor-pointer select-none hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors'
              ]"
              @click="handleSort(col.key)"
            >
              <div class="flex items-center justify-between">
                <span>{{ col.label }}</span>
                <span 
                  :class="[
                    'ml-2 text-sm transition-colors',
                    getSortClass(col.key)
                  ]"
                  :title="`Sort by ${col.label} ${sortColumn === col.key ? (sortDirection === 'asc' ? '(ascending)' : '(descending)') : ''}`"
                >
                  {{ getSortIcon(col.key) }}
                </span>
              </div>
            </th>
            <th v-if="rowActions.length > 0" :class="[currentTheme.headerCell, 'text-right']"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="filter">
            <td :class="[currentTheme.filterCell]">
              <button @click="clearFilters" :class="currentTheme.clearButton" title="Clear all filters">Clear</button>
            </td>
            <td v-for="(col, colIdx) in computedColumns" :key="col.key" :class="[currentTheme.filterCell]">
              <span v-if="col.options">
                <select
                  class="input-field"
                  :id="col.key + '-filter'"
                  :name="col.key + '-filter'"
                  v-model="filters[col.key]"
                >
                  <option value="">All</option>
                  <option 
                    v-for="opt in col.options" 
                    :key="typeof opt === 'object' ? opt.value : opt" 
                    :value="typeof opt === 'object' ? opt.value : opt"
                  >
                    {{ typeof opt === 'object' ? opt.label : opt }}
                  </option>
                </select>
              </span>
              <span v-else>      
                <TextField
                  :required="false"
                  :value="filters[col.key]"
                  @update:value="filters[col.key] = $event"
                  :name="col.key + '-filter'"
                  :placeholder="col.label"
                />
              </span>
            </td>
            <td v-if="rowActions.length > 0" :class="currentTheme.filterCell"></td>  
          </tr>
          <tr
            v-for="(row, idx) in paginatedRows"
            :key="row.id || row.name"
            @click="emitRowClick(row, idx)"
            :class="[
              currentTheme.row,
              idx % 2 === 0 ? currentTheme.rowEven : currentTheme.rowOdd,
              currentTheme.rowHover,
              props.clickableRows ? 'cursor-pointer' : ''
            ]"
          >
            <td :class="[currentTheme.cell, 'w-[32px]', 'min-w-[32px]', 'max-w-[32px]']" @click.stop>
              <CheckboxField
                :name="'row-select-' + idx"
                :value="selectedRows.includes((currentPage - 1) * pageSize + idx) ? 'true' : 'false'"
                @update:value="toggleRowSelect(row, $event, (currentPage - 1) * pageSize + idx)"
                :show="true"
                :edit="true"
                :required="false"
                :inFieldset="true"
                :options="[
                  { label: '', value: 'false' },
                  { label: '', value: 'true' }
                ]"
              />
            </td>
            <td v-for="(col, colIdx) in computedColumns" :key="col.key" :class="[currentTheme.cell]">
              <!-- Render merged array objects if this column is a merge target -->
              <span v-if="col.mergedFrom && getMergedArrayData(row, col.mergedFrom).length > 0">
                <div class="flex flex-wrap gap-1">
                  <div 
                    v-for="(item, itemIndex) in getMergedArrayData(row, col.mergedFrom)" 
                    :key="itemIndex"
                    :class="[
                      'inline-flex flex-col p-1 rounded-lg border shadow-sm max-w-32',
                      item.targetColor || 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200'
                    ]"
                  >
                    <div class="text-xs font-semibold truncate">
                      {{ item[item.targetObjectAttribute] || item.name || item }}
                    </div>
                  </div>
                </div>
              </span>
              
              <!-- Render array of objects if isArrayObject is true -->
              <span v-else-if="col.isArrayObject && getArrayData(row[col.key]) && getArrayData(row[col.key]).length > 0">
                <div class="flex flex-wrap gap-1">
                  <div 
                    v-for="(item, itemIndex) in getArrayData(row[col.key])" 
                    :key="itemIndex"
                    :class="[
                      'inline-flex flex-col p-1 rounded-lg border shadow-sm max-w-32',
                      col.targetColor || 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200'
                    ]"
                  >
                    <div class="text-xs font-semibold truncate">
                      {{ item[col.targetObjectAttribute] || item }}
                    </div>
                   
                  </div>
                </div>
              </span>
              
              <span v-else-if="col.isArrayObject" class="text-xs text-gray-500">
                
              </span>
              
              <!-- Render single object if isObject is true -->
              <span v-else-if="col.isObject && getObjectData(row[col.key])">
                <div 
                  :class="[
                    'inline-flex flex-col p-1 rounded-lg border shadow-sm max-w-32',
                    col.targetColor || 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200'
                  ]"
                >
                  <div class="text-xs font-semibold truncate">
                    {{ getObjectData(row[col.key])[col.targetObjectAttribute] || JSON.stringify(getObjectData(row[col.key])) }}
                  </div>
                </div>
              </span>
              
              <!-- Render option badges if column has options -->
              <span v-else-if="col.options && row[col.key] !== undefined && row[col.key] !== null">
                <span v-if="col.options.length > 0 && typeof col.options[0] === 'object'">
                  <!-- Option objects with label and color -->
                  <span 
                    v-for="option in col.options" 
                    :key="option.value"
                    v-show="option.value === row[col.key]"
                    :class="[
                      'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                      option.color || getDynamicColor(option.label || option.value)
                    ]"
                  >
                    {{ option.label || option.value }}
                  </span>
                </span>
                <span v-else>
                  <!-- Simple string options with dynamic colors -->
                  <span :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    getDynamicColor(row[col.key])
                  ]">
                    {{ row[col.key] }}
                  </span>
                </span>
              </span>
              <!-- Regular text for non-option columns -->
              <span v-else :class="col.labelColor ? [
                'inline-flex items-center px-2 py-1 rounded border text-sm',
                col.labelColor
              ] : ''">
                {{ row[col.key] }}
              </span>
            </td>
            <td :class="[currentTheme.cell, 'text-right']">
              <div v-if="rowActions.length > 0" class="relative" @click.stop>
                <!-- Single action button when only one action -->
                <button 
                  v-if="rowActions.length === 1"
                  @click="emitAction(rowActions[0].type, row, (currentPage - 1) * pageSize + idx)" 
                  :class="[currentTheme.menuButton, rowActions[0].colorClass, 'cursor-pointer']"
                  :title="!props.rowActionLabels ? rowActions[0].label : ''"
                >
                  <font-awesome-icon v-if="rowActions[0].icon" :icon="rowActions[0].icon" :class="props.rowActionLabels ? 'mr-2' : ''" />
                  <span v-if="props.rowActionLabels">{{ rowActions[0].label }}</span>
                </button>
                
                <!-- Multiple actions as individual buttons when rowActionDropdown is false -->
                <div v-else-if="!props.rowActionDropdown" class="flex gap-1">
                  <button 
                    v-for="action in rowActions" 
                    :key="action.type"
                    @click="emitAction(action.type, row, (currentPage - 1) * pageSize + idx)" 
                    :class="[currentTheme.menuButton, action.colorClass, 'text-sm px-3 py-2 cursor-pointer']"
                    :title="!props.rowActionLabels ? action.label : ''"
                  >
                    <font-awesome-icon v-if="action.icon" :icon="action.icon" :class="props.rowActionLabels ? 'mr-1.5' : ''" />
                    <span v-if="props.rowActionLabels">{{ action.label }}</span>
                  </button>
                </div>
                
                <!-- Dropdown menu when multiple actions and rowActionDropdown is true -->
                <div v-else>
                  <button @click="toggleMenu(row.id || row.name || idx)" :class="[currentTheme.menuButton, 'cursor-pointer']" :data-row-id="row.id || row.name || idx">
                    <svg :class="currentTheme.menuIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <circle cx="12" cy="5" r="1.5"/>
                      <circle cx="12" cy="12" r="1.5"/>
                      <circle cx="12" cy="19" r="1.5"/>
                    </svg>
                  </button>
                  <Teleport to="body">
                    <div v-if="openMenuId === (row.id || row.name || idx)" :class="[currentTheme.dropdown, 'fixed z-50']" :style="getDropdownPosition(row.id || row.name || idx)">
                      <slot name="row-actions" :row="row" :close-menu="closeMenu">
                        <!-- Configurable row actions -->
                        <button 
                          v-for="action in rowActions" 
                          :key="action.type"
                          @click="emitAction(action.type, row, (currentPage - 1) * pageSize + idx); closeMenu()" 
                          :class="[currentTheme.dropdownItem, action.colorClass, 'cursor-pointer']"
                          :title="!props.rowActionLabels ? action.label : ''"
                        >
                          <font-awesome-icon v-if="action.icon" :icon="action.icon" :class="props.rowActionLabels ? 'mr-2' : ''" />
                          <span v-if="props.rowActionLabels">{{ action.label }}</span>
                        </button>
                      </slot>
                    </div>
                  </Teleport>
                </div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="mt-10 px-8 py-6 flex flex-col md:flex-row md:items-center md:justify-between gap-6 bg-gray-50 dark:bg-gray-900 rounded-b-lg shadow-inner">
      <div class="flex items-center flex-wrap gap-4 text-base">
        <span class="bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-full px-5 py-2 text-sm font-normal">{{ pageSummary }}</span>
        <select v-model="pageSize" class="ml-2 px-4 py-2 pr-8 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-base text-gray-700 dark:text-gray-200 focus:ring-2 focus:ring-blue-500">
          <option v-for="size in pageSizes" :key="size" :value="size">{{ size }}</option>
        </select>
        <span class="ml-2 text-gray-700 dark:text-gray-200">results per page</span>
      </div>
      <div class="flex items-center gap-3">
        <button @click="firstPage" :disabled="currentPage === 1" class="px-4 py-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none disabled:opacity-50" aria-label="First page">&laquo;</button>
        <button @click="prevPage" :disabled="currentPage === 1" class="px-4 py-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none disabled:opacity-50" aria-label="Previous page">&lsaquo;</button>
        <template v-for="page in visiblePages" :key="page">
          <span v-if="page === '...'" class="px-4 py-2 text-gray-400 select-none">&hellip;</span>
          <button
            v-else
            @click="goToPage(page)"
            :aria-current="page === currentPage ? 'page' : undefined"
            :class="[
              'px-4 py-2 rounded border',
              page === currentPage
                ? 'bg-blue-600 text-white border-blue-600 font-semibold shadow focus:ring-2 focus:ring-blue-400'
                : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700',
              'focus:outline-none transition-colors duration-100'
            ]"
          >
            {{ page }}
          </button>
        </template>
        <button @click="nextPage" :disabled="currentPage === totalPages" class="px-4 py-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none disabled:opacity-50" aria-label="Next page">&rsaquo;</button>
        <button @click="lastPage" :disabled="currentPage === totalPages" class="px-4 py-2 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none disabled:opacity-50" aria-label="Last page">&raquo;</button>
      </div>
    </div>
  </div>
 
</template>

<script setup>
import { ref, computed, watch, onMounted, getCurrentInstance } from 'vue';
import { getThemeClasses, getDynamicColor } from '../Theme.js';
import TextField from '../fields/TextField.vue';
import CheckboxField from '../fields/CheckboxField.vue';

const props = defineProps({
  title: String,
  // New prop names
  schema: {
    type: Object,
    default: () => ({
      label: '',
      title: '',
      description: '',
      fields: []
    })
  },
  data: {
    type: Array,
    default: () => []
  },
  
  // Configuration options
  count: {
    type: Number,
    default: 25,
    validator: (value) => value > 0
  },
  search: {
    type: Boolean,
    default: true
  },
  filter: {
    type: Boolean,
    default: true
  },
  upload: {
    type: Boolean,
    default: true
  },
  download: {
    type: Boolean,
    default: true
  },
  columns: {
    type: Boolean,
    default: true
  },
  
  // Theme and actions
  theme: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'minimal', 'bordered'].includes(value)
  },
  showNew: { type: Boolean, default: true },
  showEdit: { type: Boolean, default: true },
  showDelete: { type: Boolean, default: true },
  customActions: { type: Array, default: () => [] },
  actions: { 
    type: Array, 
    default: () => [],
    validator: (actions) => {
      return actions.every(action => {
        if (typeof action === 'string') return action === '|'; // Separator
        return action && typeof action === 'object' && action.name && action.type && action.label;
      });
    }
  },
  rowActions: { 
    type: Array,
    default: () => [],
  },
  
  // Backward compatibility - deprecated but still supported
  formConfig: {
    type: Object,
    default: () => ({
      label: '',
      title: '',
      description: '',
      fields: []
    })
  },
  formData: {
    type: Array,
    default: () => []
  },
  // Control cursor behavior
  clickableRows: {
    type: Boolean,
    default: false
  },
  // Control row action display
  rowActionDropdown: {
    type: Boolean,
    default: true
  },
  // Control row action labels
  rowActionLabels: {
    type: Boolean,
    default: true
  }
});
const emit = defineEmits(['rowClick', 'rowActionHandler', 'create', 'edit', 'multiple-edit', 'delete', 'upload', 'actionHandler', 'action']);

const searchQuery = ref('');
const filters = ref({});
const selectedRows = ref([]);
const openMenuId = ref(null);

// Sorting state
const sortColumn = ref(null);
const sortDirection = ref('asc'); // 'asc' or 'desc'

// File input ref
const fileInput = ref(null);

// Column visibility state
const showColumnSelector = ref(false);
const visibleColumns = ref(new Set());

const showSuggestions = ref(false);
const suggestions = ref([]);
const selectedSuggestionIndex = ref(-1);
const columnKeys = computed(() => computedColumns.value?.map(col => col.key) || []);
const operators = ['contains', 'startsWith', 'endsWith', '='];

// Pagination state
const pageSizes = [25, 50, 100];
const pageSize = ref(props.count || pageSizes[0]);
const currentPage = ref(1);

// Get current theme classes from Theme component
const currentTheme = computed(() => getThemeClasses('datatable', props.theme));

// Process actions configuration
const processedActions = computed(() => {
  if (!props.actions || props.actions.length === 0) {
    return [];
  }
  
  return props.actions.map(action => {
    if (typeof action === 'string' && action === '|') {
      return { type: 'separator' };
    }
    
    return {
      ...action,
      min: action.min !== undefined ? action.min : 1,
      max: action.max !== undefined ? action.max : 1,
      loc: action.loc || 'right',
      disabled: isActionDisabled(action),
      buttonClass: getActionButtonClass(action)
    };
  });
});

// Separate actions by location
const leftActions = computed(() => {
  return processedActions.value.filter(action => action.loc === 'left');
});

const rightActions = computed(() => {
  return processedActions.value.filter(action => action.loc === 'right');
});

// Check if action should be disabled based on selection requirements
const isActionDisabled = (action) => {
  const selectedCount = selectedRows.value.length;
  
  if (action.min !== undefined && selectedCount < action.min) {
    return true;
  }
  
  if (action.max !== undefined && selectedCount > action.max) {
    return true;
  }
  
  return false;
};

// Get button class based on action type
const getActionButtonClass = (action) => {
  const baseClass = 'px-4 py-2 rounded-md text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2';
  const disabledClass = 'opacity-50 cursor-not-allowed';
  
  // Add disabled styling if action is disabled
  const disabledStyle = action.disabled ? disabledClass : '';
  
  switch (action.type) {
    case 'success':
      return `${baseClass} ${disabledStyle} bg-green-600 text-white hover:bg-green-700 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed`;
    case 'primary':
      return `${baseClass} ${disabledStyle} bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed`;
    case 'danger':
      return `${baseClass} ${disabledStyle} bg-red-600 text-white hover:bg-red-700 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed`;
    case 'warning':
      return `${baseClass} ${disabledStyle} bg-yellow-600 text-white hover:bg-yellow-700 focus:ring-yellow-500 disabled:opacity-50 disabled:cursor-not-allowed`;
    case 'info':
      return `${baseClass} ${disabledStyle} bg-gray-600 text-white hover:bg-gray-700 focus:ring-gray-500 disabled:opacity-50 disabled:cursor-not-allowed`;
    default:
      return `${baseClass} ${disabledStyle} bg-gray-600 text-white hover:bg-gray-700 focus:ring-gray-500 disabled:opacity-50 disabled:cursor-not-allowed`;
  }
};

// Generate columns from schema or formConfig (backward compatibility)
const allColumns = computed(() => {
  // Priority: schema > formConfig > columns
  const config = props.schema || props.formConfig;
  
  if (config?.fields && config.fields.length > 0) {
    const columns = config.fields.map(field => ({
      key: field.name,
      label: field.label || field.name,
      type: field.type,
      required: field.required || false,
      options: field.options || null,
      width: field.width || field.columns || 12,
      showColumn: field.showColumn !== false, // Default to true if not specified
      isArrayObject: field.isArrayObject || false,
      isObject: field.isObject || false,
      targetObjectAttribute: field.targetObjectAttribute || null,
      targetColor: field.targetColor || null,
      labelColor: field.labelColor || null,
      mergedTo: field.mergedTo || null,
      mergedFrom: null // Will be set below
    }));
    
    // Process mergedTo relationships
    columns.forEach(col => {
      if (col.mergedTo) {
        const targetCol = columns.find(c => c.key === col.mergedTo);
        if (targetCol) {
          targetCol.mergedFrom = targetCol.mergedFrom || [];
          targetCol.mergedFrom.push({
            sourceKey: col.key,
            targetColor: col.targetColor,
            targetObjectAttribute: col.targetObjectAttribute
          });
        }
      }
    });
    
    return columns;
  }
  
  // Fall back to provided columns prop
  return props.columns || [];
});

// Filter columns based on visibility selection and showColumn property
const computedColumns = computed(() => {
 
  // First filter by showColumn property
  let filteredColumns = allColumns.value.filter(col => col.showColumn !== false);
  
  // Then apply user visibility selection if columns prop is enabled
  if (props.columns && visibleColumns.value.size > 0) {
    filteredColumns = filteredColumns.filter(col => visibleColumns.value.has(col.key));
  }
  return filteredColumns;
});

// Use data or formData (backward compatibility) or rows prop
const computedRows = computed(() => {
  if (props.data && props.data.length > 0) {
    return props.data;
  }
  if (props.formData && props.formData.length > 0) {
    return props.formData;
  }
  return props.rows || [];
});

// Generate filters from computed columns only if filter is enabled
function initFilters() {
  if (props.filter && computedColumns.value && computedColumns.value.length > 0) {
    filters.value = Object.fromEntries(computedColumns.value.map(col => [col.key, '']));
  } else {
    filters.value = {};
  }
}
watch(() => computedColumns.value, initFilters, { immediate: true });
watch(() => props.filter, initFilters, { immediate: true });

// Helper function to handle both array and JSON string data
function getArrayData(data) {
  
  
  if (Array.isArray(data)) {
    
    return data;
  }
  
  if (typeof data === 'string') {
    try {
      const parsed = JSON.parse(data);
      
      return Array.isArray(parsed) ? parsed : null;
    } catch (e) {
      
      return null;
    }
  }

  return null;
}

// Helper function to handle both object and JSON string data
function getObjectData(data) {
  if (data && typeof data === 'object' && !Array.isArray(data)) {
    return data;
  }
  
  if (typeof data === 'string') {
    try {
      const parsed = JSON.parse(data);
      if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) {
        return parsed;
      }
      return null;
    } catch (e) {
      return null;
    }
  }

  return null;
}

// Helper function to get merged array data from multiple source columns
function getMergedArrayData(row, mergedFrom) {
  if (!mergedFrom || !Array.isArray(mergedFrom)) {
    return [];
  }
  
  const mergedData = [];
  
  mergedFrom.forEach(source => {
    const sourceData = getArrayData(row[source.sourceKey]);
    if (sourceData && sourceData.length > 0) {
      sourceData.forEach(item => {
        mergedData.push({
          ...item,
          targetColor: source.targetColor,
          targetObjectAttribute: source.targetObjectAttribute
        });
      });
    }
  });
  
  return mergedData;
}

function onSearchInput() {
  const value = searchQuery.value;
  const last = value.split(/\s+/).pop();
  selectedSuggestionIndex.value = -1; // Reset selection when typing
  if (!last) {
    showSuggestions.value = false;
    suggestions.value = [];
    return;
  }
  if (!last.includes(':')) {
    // Suggest both column keys and labels
    const keySuggestions = computedColumns.value
      .filter(c => c.key.startsWith(last))
      .map(c => c.key + ':');
    const labelSuggestions = computedColumns.value
      .filter(c => c.label.toLowerCase().startsWith(last.toLowerCase()))
      .map(c => c.label + ':');
    
    suggestions.value = [...keySuggestions, ...labelSuggestions];
    showSuggestions.value = !!suggestions.value.length;
  } else if (last.split(':').length === 2) {
    const [col, op] = last.split(':');
    // Check if the column part matches any key or label
    const foundColumn = computedColumns.value.find(c => c.key === col || c.label === col);
    if (foundColumn) {
      suggestions.value = operators.filter(operator => operator.startsWith(op)).map(operator => col + ':' + operator + ':');
      showSuggestions.value = !!suggestions.value.length;
    } else {
      showSuggestions.value = false;
      suggestions.value = [];
    }
  } else {
    showSuggestions.value = false;
    suggestions.value = [];
  }
}
function applySuggestion(s) {
  const parts = searchQuery.value.split(/\s+/);
  parts[parts.length - 1] = s;
  searchQuery.value = parts.join(' ');
  showSuggestions.value = false;
  suggestions.value = [];
  selectedSuggestionIndex.value = -1;
}

function handleSearchKeydown(event) {
  if (!showSuggestions.value || suggestions.value.length === 0) {
    return;
  }

  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault();
      selectedSuggestionIndex.value = Math.min(selectedSuggestionIndex.value + 1, suggestions.value.length - 1);
      break;
    case 'ArrowUp':
      event.preventDefault();
      selectedSuggestionIndex.value = Math.max(selectedSuggestionIndex.value - 1, -1);
      break;
    case 'Tab':
      event.preventDefault();
      if (suggestions.value.length > 0) {
        // If no suggestion is selected, use the first one
        const suggestionIndex = selectedSuggestionIndex.value >= 0 ? selectedSuggestionIndex.value : 0;
        applySuggestion(suggestions.value[suggestionIndex]);
      }
      break;
    case 'Enter':
      event.preventDefault();
      if (suggestions.value.length > 0) {
        // If no suggestion is selected, use the first one
        const suggestionIndex = selectedSuggestionIndex.value >= 0 ? selectedSuggestionIndex.value : 0;
        applySuggestion(suggestions.value[suggestionIndex]);
      }
      break;
    case 'Escape':
      showSuggestions.value = false;
      suggestions.value = [];
      selectedSuggestionIndex.value = -1;
      break;
  }
}

const filteredRows = computed(() => {
  // Basic search: support column:operator:value and free text
  let result = computedRows.value;

  const query = searchQuery.value.trim();
  if (query) {
    const tokens = query.split(/\s+/);
    tokens.forEach(token => {
      const [col, op, ...rest] = token.split(':');
      const value = rest.join(':');
      
      // Find the column by key or label
      const foundColumn = computedColumns.value.find(c => c.key === col || c.label === col);
      
      if (foundColumn && op && value) {
        // Column-specific search with operator
        result = result.filter(row => {
          const cell = String(row[foundColumn.key] ?? '').toLowerCase();
          const val = value.toLowerCase();
          
          if (op === 'contains') return cell.includes(val);
          if (op === 'startsWith') return cell.startsWith(val);
          if (op === 'endsWith') return cell.endsWith(val);
          if (op === '=') return cell === val;
          return true;
        });
      } else if (foundColumn && op && !value) {
        // If only column:operator is typed, don't filter yet
        return;
      } else {
        // Free text search across all columns
        result = result.filter(row =>
          computedColumns.value.some(c => String(row[c.key] ?? '').toLowerCase().includes(token.toLowerCase()))
        );
      }
    });
  }
  // Per-column filters (only if filter is enabled)
  if (props.filter) {
    Object.entries(filters.value).forEach(([key, val]) => {

      if (val) {
        // Find the column configuration to check if it has options
        const column = computedColumns.value.find(col => col.key === key);

        if (column && column.options) {
          // For select fields with options, do exact match on the value
          // Special handling for boolean values
          if (val === 'true' || val === 'false' || val === true || val === false) {
            const boolVal = val === 'true' || val === true;
            result = result.filter(row => Boolean(row[key]) === boolVal);
          } else {
            result = result.filter(row => String(row[key] ?? '') === String(val));
          }
        } else {

          // For regular text fields, use substring match
          result = result.filter(row => String(row[key] ?? '').toLowerCase().includes(String(val).toLowerCase()));
        }
      }
      else if (val === 'false' || val === false) {
        result = result.filter(row => Boolean(row[key]) === false);
      }
    });
  }
  
  // Apply sorting
  if (sortColumn.value) {
    result = [...result].sort((a, b) => {
      const aVal = a[sortColumn.value];
      const bVal = b[sortColumn.value];
      
      // Handle null/undefined values
      if (aVal == null && bVal == null) return 0;
      if (aVal == null) return sortDirection.value === 'asc' ? -1 : 1;
      if (bVal == null) return sortDirection.value === 'asc' ? 1 : -1;
      
      // Smart type detection and comparison
      const comparison = smartCompare(aVal, bVal);
      
      return sortDirection.value === 'asc' ? comparison : -comparison;
    });
  }
  
  return result;
});

const totalResults = computed(() => filteredRows.value.length);
const totalPages = computed(() => Math.max(1, Math.ceil(totalResults.value / pageSize.value)));
const paginatedRows = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value;
  return filteredRows.value.slice(start, start + pageSize.value);
});

watch([filteredRows, pageSize], () => {
  // Reset to first page if filters or page size change
  currentPage.value = 1;
  // Clean up invalid selections (indices that are now out of range)
  selectedRows.value = selectedRows.value.filter(idx => idx >= 0 && idx < filteredRows.value.length);
});

function goToPage(page) {
  if (page < 1) page = 1;
  if (page > totalPages.value) page = totalPages.value;
  currentPage.value = page;
}
function prevPage() { goToPage(currentPage.value - 1); }
function nextPage() { goToPage(currentPage.value + 1); }
function firstPage() { goToPage(1); }
function lastPage() { goToPage(totalPages.value); }

const pageSummary = computed(() => {
  const start = totalResults.value === 0 ? 0 : (currentPage.value - 1) * pageSize.value + 1;
  const end = Math.min(currentPage.value * pageSize.value, totalResults.value);
  return `Showing ${start}–${end} of ${totalResults.value} results`;
});

const allSelected = computed(() => {
  if (filteredRows.value.length === 0) return 'false';
  const allSelectedBool = filteredRows.value.every((row, index) => selectedRows.value.includes(index));
  return allSelectedBool ? 'true' : 'false';
});

function toggleSelectAll(checked) {
  const isChecked = checked === 'true' || checked === true;

  if (isChecked) {
    // Select all indices in filteredRows - create new array for reactivity
    selectedRows.value = Array.from({ length: filteredRows.value.length }, (_, index) => index);

  } else {
    selectedRows.value = [];

  }
}
function toggleRowSelect(row, checked, index) {

  const isChecked = checked === 'true' || checked === true;
  // Index is now passed directly from template
  const rowIndex = index;
  
  if (isChecked) {
    if (!selectedRows.value.includes(rowIndex)) {
      selectedRows.value.push(rowIndex);
    }
  } else {
    selectedRows.value = selectedRows.value.filter(idx => idx !== rowIndex);
  }
}
function clearFilters() {
  if (props.filter) {
    initFilters();
  }
}

// Smart comparison function for different data types
function smartCompare(a, b) {
  // Convert to strings for analysis
  const aStr = String(a).trim();
  const bStr = String(b).trim();
  
  // Check if values are numbers (including decimal numbers)
  const aNum = parseFloat(aStr);
  const bNum = parseFloat(bStr);
  const aIsNum = !isNaN(aNum) && isFinite(aNum) && aStr !== '';
  const bIsNum = !isNaN(bNum) && isFinite(bNum) && bStr !== '';
  
  // If both are numbers, compare numerically
  if (aIsNum && bIsNum) {
    return aNum - bNum;
  }
  
  // Check if values are dates
  const aDate = new Date(aStr);
  const bDate = new Date(bStr);
  const aIsDate = !isNaN(aDate.getTime()) && aStr !== '';
  const bIsDate = !isNaN(bDate.getTime()) && bStr !== '';
  
  // If both are valid dates, compare by date
  if (aIsDate && bIsDate) {
    return aDate.getTime() - bDate.getTime();
  }
  
  // If one is a number and the other isn't, numbers come first
  if (aIsNum && !bIsNum) return -1;
  if (!aIsNum && bIsNum) return 1;
  
  // If one is a date and the other isn't, dates come first
  if (aIsDate && !bIsDate) return -1;
  if (!aIsDate && bIsDate) return 1;
  
  // Default to case-insensitive string comparison
  return aStr.toLowerCase().localeCompare(bStr.toLowerCase());
}

// Sorting functions
function handleSort(columnKey) {
  if (sortColumn.value === columnKey) {
    // Same column - cycle through: asc → desc → none (reset)
    if (sortDirection.value === 'asc') {
      sortDirection.value = 'desc';
    } else if (sortDirection.value === 'desc') {
      // Reset to no sorting
      sortColumn.value = null;
      sortDirection.value = 'asc';
    }
  } else {
    // New column, start with ascending
    sortColumn.value = columnKey;
    sortDirection.value = 'asc';
  }
}

function getSortIcon(columnKey) {
  if (sortColumn.value !== columnKey) {
    return '⇅'; // Neutral sort icon (up and down arrows together)
  }
  return sortDirection.value === 'asc' ? '↑' : '↓';
}

function getSortClass(columnKey) {
  if (sortColumn.value !== columnKey) {
    return 'text-gray-400 hover:text-gray-600';
  }
  return 'text-blue-600 font-semibold';
}

// CSV Upload/Download functions
function handleFileUpload(event) {
  const file = event.target.files[0];
  if (!file) return;
  
  if (!file.name.toLowerCase().endsWith('.csv')) {
    alert('Please select a CSV file.');
    return;
  }
  
  const reader = new FileReader();
  reader.onload = (e) => {
    try {
      const csv = e.target.result;
      const data = parseCSV(csv);
      
      if (data.length === 0) {
        alert('CSV file is empty.');
        return;
      }
      
      // Emit upload event with parsed data
      emit('upload', data);
      
      // Clear the file input
      event.target.value = '';
    } catch (error) {
      console.error('Error parsing CSV:', error);
      alert('Error parsing CSV file. Please check the format.');
    }
  };
  reader.readAsText(file);
}

function parseCSV(csv) {
  const lines = csv.split('\n').filter(line => line.trim());
  if (lines.length === 0) return [];
  
  // Get headers from first line
  const headers = lines[0].split(',').map(h => h.trim().replace(/"/g, ''));
  
  // Parse data rows
  const data = [];
  for (let i = 1; i < lines.length; i++) {
    const values = lines[i].split(',').map(v => v.trim().replace(/"/g, ''));
    if (values.length === headers.length) {
      const row = {};
      headers.forEach((header, index) => {
        row[header] = values[index] || '';
      });
      data.push(row);
    }
  }
  
  return data;
}

function downloadCSV() {
  if (computedRows.value.length === 0) {
    alert('No data to download.');
    return;
  }
  
  const headers = computedColumns.value.map(col => col.label || col.key);
  const csvContent = [
    headers.join(','),
    ...computedRows.value.map(row => 
      headers.map(header => {
        const col = computedColumns.value.find(c => (c.label || c.key) === header);
        const value = row[col.key] || '';
        // Escape commas and quotes in values
        return `"${String(value).replace(/"/g, '""')}"`;
      }).join(',')
    )
  ].join('\n');
  
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  link.setAttribute('href', url);
  link.setAttribute('download', `datagrid-export-${new Date().toISOString().split('T')[0]}.csv`);
  link.style.visibility = 'hidden';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

// Column visibility functions
function toggleColumnSelector() {
  showColumnSelector.value = !showColumnSelector.value;
}

function toggleColumnVisibility(columnKey) {
  if (visibleColumns.value.has(columnKey)) {
    visibleColumns.value.delete(columnKey);
  } else {
    visibleColumns.value.add(columnKey);
  }
}

function selectAllColumns() {
  allColumns.value.forEach(col => {
    if (col.showColumn !== false) {
      visibleColumns.value.add(col.key);
    }
  });
}

function deselectAllColumns() {
  visibleColumns.value.clear();
}

// Initialize visible columns on mount
function initializeVisibleColumns() {
  if (props.columns) {
    // Start with all columns visible (only those with showColumn !== false)
    allColumns.value.forEach(col => {
      if (col.showColumn !== false) {
        visibleColumns.value.add(col.key);
      }
    });
  }
}

// Initialize on mount
onMounted(() => {
  initializeVisibleColumns();
  
  // Close column selector when clicking outside
  document.addEventListener('click', (event) => {
    if (showColumnSelector.value && !event.target.closest('.column-selector-container')) {
      showColumnSelector.value = false;
    }
    
    // Close dropdown menu when clicking outside
    if (openMenuId.value && !event.target.closest('[data-row-id]') && !event.target.closest('.fixed.z-50')) {
      openMenuId.value = null;
    }
  });
});
function emitRowClick(row, idx) {
  // Calculate the actual index in filteredRows
  const actualIndex = (currentPage.value - 1) * pageSize.value + idx;
  emit('rowClick', row, actualIndex);
}
function emitAction(type, payload, index) {
  // Calculate the actual index in filteredRows if not provided
  const actualIndex = index !== undefined ? index : filteredRows.value.findIndex(r => r === payload);
  emit('rowActionHandler', { type, payload, index: actualIndex });
  // Clear selection after actions that modify data (edit, delete, etc.)
  if (type === 'single-edit' || type === 'single-delete' || type.includes('delete') || type.includes('edit')) {
    selectedRows.value = [];
  }
}
function toggleMenu(id) {

  openMenuId.value = openMenuId.value === id ? null : id;

}
function closeMenu() {

  openMenuId.value = null;
}

// Calculate dropdown position
function getDropdownPosition(rowId) {
  const button = document.querySelector(`[data-row-id="${rowId}"]`);
  if (!button) return {};
  
  const rect = button.getBoundingClientRect();
  const viewportWidth = window.innerWidth;
  const viewportHeight = window.innerHeight;
  
  // Calculate position
  let left = rect.right;
  let top = rect.top;
  
  // Adjust if dropdown would go off-screen
  if (left + 200 > viewportWidth) {
    left = rect.left - 200; // Show to the left instead
  }
  
  if (top + 150 > viewportHeight) {
    top = rect.bottom - 150; // Show above instead
  }
  
  return {
    left: `${left}px`,
    top: `${top}px`,
    minWidth: '200px'
  };
}

function onCreate() {
  emit('create');
  // Default logic: placeholder (e.g., open modal, log, etc.)

}
function onEdit() {
  if (selectedRows.value.length === 1) {
    // selectedRows now contains indices, get row by index
    const index = selectedRows.value[0];
    const row = filteredRows.value[index];
    emit('edit', row);
    // Clear selection after action
    selectedRows.value = [];
  } else if (selectedRows.value.length > 1) {
    onMultipleEdit();
  }
}
function onMultipleEdit() {
  // Get rows by indices
  const rows = selectedRows.value.map(index => filteredRows.value[index]).filter(Boolean);
  emit('multiple-edit', rows);
  // Clear selection after action
  selectedRows.value = [];
}
function onDelete() {
  // Get rows by indices
  const rows = selectedRows.value.map(index => filteredRows.value[index]).filter(Boolean);
  emit('delete', rows);
  // Clear selection after action
  selectedRows.value = [];
}

// Get tooltip text for action button
function getActionTooltip(action) {
  const selectedCount = selectedRows.value.length;
  
  if (action.min !== undefined && selectedCount < action.min) {
    return `Please select at least ${action.min} row${action.min > 1 ? 's' : ''}`;
  }
  
  if (action.max !== undefined && selectedCount > action.max) {
    return `Please select at most ${action.max} row${action.max > 1 ? 's' : ''}`;
  }
  
  return action.label;
}

// Handle custom actions
function handleCustomAction(action) {
  // selectedRows now contains indices, not IDs
  // Get the selected rows data by index
  const selectedData = selectedRows.value
    .map(index => filteredRows.value[index])
    .filter(Boolean); // Remove any undefined values
  
  // selectedRows.value already contains the indices
  const selectedIndex = selectedRows.value.filter(idx => idx >= 0 && idx < filteredRows.value.length);
  
  // Emit action handler with correct data
  emit('actionHandler', {
    action: action.name,
    selectedRows: selectedRows.value, // These are now indices
    selectedData: selectedData,
    selectedIndex: selectedIndex
  });
  // Clear selection after action
  selectedRows.value = [];
}

const visiblePages = computed(() => {
  const pages = [];
  if (totalPages.value <= 5) {
    for (let i = 1; i <= totalPages.value; i++) pages.push(i);
  } else {
    if (currentPage.value <= 3) {
      pages.push(1, 2, 3, 4, '...', totalPages.value);
    } else if (currentPage.value >= totalPages.value - 2) {
      pages.push(1, '...', totalPages.value - 3, totalPages.value - 2, totalPages.value - 1, totalPages.value);
    } else {
      pages.push(1, '...', currentPage.value - 1, currentPage.value, currentPage.value + 1, '...', totalPages.value);
    }
  }
  return pages;
});
</script> 