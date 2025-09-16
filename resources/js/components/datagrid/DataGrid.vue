

<template>
  <div :class="currentTheme.container">
    <!-- Header with title and actions -->
    <div :class="currentTheme.header">
      <div>
        <h2 v-if="schema.label || schema.title || formConfig.label || formConfig.title || title" :class="currentTheme.title">
          {{ schema.label || schema.title || formConfig.label || formConfig.title || title }}
        </h2>
        <p v-if="schema.description || formConfig.description" class="text-sm text-gray-600 dark:text-gray-300 mt-1">
          {{ schema.description || formConfig.description }}
        </p>
      </div>
      <!-- Custom actions slot for external action buttons -->
      <div class="flex flex-col items-end gap-2">
        <!-- Main action buttons -->
        <div class="flex items-center gap-2">
          <slot name="actions">
            <!-- Default actions if no custom actions provided -->
            <button v-if="showNew" @click="onCreate" :class="currentTheme.newButton">New</button>
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
          </slot>
        </div>
        
        <!-- Icon-only utility buttons (Download, Upload, Columns) -->
        <div v-if="upload || download || columns" class="flex items-center border border-gray-200 dark:border-gray-700 rounded-md">
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
                    v-for="col in allColumns"
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
        <template v-for="(action, idx) in customActions" :key="action.type || action.label || idx">
          <button
            :class="['px-4 py-2 rounded focus:outline-none', action.colorClass]"
            @click="$emit('action', { type: action.type || action.label, payload: selectedRows })"
            :disabled="action.disabled || false"
          >
            {{ action.label }}
          </button>
        </template>
      </div>
    </div>
    <!-- Built-in search bar below header (conditional) -->
    <div v-if="search" :class="currentTheme.searchContainer">
      <TextField
        :required="false"
        :value="searchQuery"
        @update:value="searchQuery = $event"
        @input="onSearchInput"
        @change="onSearchInput"
        @keydown="handleSearchKeydown"
        name="searchQuery"
        label="Search"
        placeholder="Search (e.g. column_name:contains:string)"
        autocomplete="off"
        spellcheck="false"
      />
      <div v-if="showSuggestions && suggestions.length" class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded shadow mt-1 absolute z-20 w-full max-w-xl">
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
    <div class="overflow-x-auto">
      <table :class="currentTheme.table">
        <thead>
          <tr>
            <th :class="[currentTheme.headerCell, currentTheme.borderRight, 'w-[32px]', 'min-w-[32px]', 'max-w-[32px]']">
              <CheckboxField
                :name="'select-all'"
                :value="allSelected"
                @update:value="toggleSelectAll($event)"
                :required="false"
                :inFieldset="true"
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
                colIdx < computedColumns.length - 1 ? currentTheme.borderRight : '',
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
            <th :class="[currentTheme.headerCell, 'text-right']"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="filter">
            <td :class="[currentTheme.filterCell, currentTheme.borderRight]">
              <button @click="clearFilters" :class="currentTheme.clearButton" title="Clear all filters">Clear</button>
            </td>
            <td v-for="(col, colIdx) in computedColumns" :key="col.key" :class="[currentTheme.filterCell, colIdx < computedColumns.length - 1 ? currentTheme.borderRight : '']">
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
            <td :class="currentTheme.filterCell"></td>
          </tr>
          <tr
            v-for="(row, idx) in paginatedRows"
            :key="row.id || row.name"
            @click="emitRowClick(row)"
            :class="[
              currentTheme.row,
              idx % 2 === 0 ? currentTheme.rowEven : currentTheme.rowOdd,
              currentTheme.rowHover
            ]"
          >
            <td :class="[currentTheme.cell, currentTheme.borderRight, 'w-[32px]', 'min-w-[32px]', 'max-w-[32px]']" @click.stop>
              <CheckboxField
                :name="'row-select-' + (row.id || row.name)"
                :value="selectedRows.includes(row.id || row.name)"
                @update:value="toggleRowSelect(row, $event)"
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
            <td v-for="(col, colIdx) in computedColumns" :key="col.key" :class="[currentTheme.cell, colIdx < computedColumns.length - 1 ? currentTheme.borderRight : '']">
              <!-- Render option badges if column has options -->
              <span v-if="col.options && row[col.key]">
                <span v-if="typeof col.options[0] === 'object'">
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
              <span v-else>
                {{ row[col.key] }}
              </span>
            </td>
            <td :class="[currentTheme.cell, 'text-right']">
              <div class="relative" @click.stop>
                <button @click="toggleMenu(row.id || row.name || idx)" :class="currentTheme.menuButton">
                  <svg :class="currentTheme.menuIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="12" cy="5" r="1.5"/>
                    <circle cx="12" cy="12" r="1.5"/>
                    <circle cx="12" cy="19" r="1.5"/>
                  </svg>
                </button>
                <div v-if="openMenuId === (row.id || row.name || idx)" :class="currentTheme.dropdown">
                  <slot name="row-actions" :row="row" :close-menu="closeMenu">
                    <!-- Configurable row actions -->
                    <button 
                      v-for="action in rowActions" 
                      :key="action.type"
                      @click="emitAction(action.type, row); closeMenu()" 
                      :class="[currentTheme.dropdownItem, action.colorClass]"
                    >
                      {{ action.label }}
                    </button>
                  </slot>
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
 
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
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
  rowActions: { 
    type: Array, 
    default: () => [
      { type: 'single-edit', label: 'Edit', icon: 'edit', colorClass: 'text-blue-600 hover:bg-blue-50' },
      { type: 'single-delete', label: 'Delete', icon: 'delete', colorClass: 'text-red-600 hover:bg-red-50' }
    ]
    // Each action should have: { type: string, label: string, icon?: string, colorClass?: string }
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
  }
});
const emit = defineEmits(['rowClick', 'action', 'create', 'edit', 'multiple-edit', 'delete', 'upload']);

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

// Generate columns from schema or formConfig (backward compatibility)
const allColumns = computed(() => {
  // Priority: schema > formConfig > columns
  const config = props.schema || props.formConfig;
  
  if (config?.fields && config.fields.length > 0) {
    return config.fields.map(field => ({
      key: field.name,
      label: field.label || field.name,
      type: field.type,
      required: field.required || false,
      options: field.options || null,
      width: field.width || field.columns || 12
    }));
  }
  
  // Fall back to provided columns prop
  return props.columns || [];
});

// Filter columns based on visibility selection
const computedColumns = computed(() => {
  if (!props.columns || visibleColumns.value.size === 0) {
    return allColumns.value;
  }
  
  return allColumns.value.filter(col => visibleColumns.value.has(col.key));
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
        result = result.filter(row => String(row[key] ?? '').toLowerCase().includes(val.toLowerCase()));
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
  // Separate selected and unselected rows
  const selectedRowsData = filteredRows.value.filter(row => 
    selectedRows.value.includes(row.id || row.name)
  );
  const unselectedRowsData = filteredRows.value.filter(row => 
    !selectedRows.value.includes(row.id || row.name)
  );
  
  // Combine: selected rows first, then unselected rows
  const sortedRows = [...selectedRowsData, ...unselectedRowsData];
  
  const start = (currentPage.value - 1) * pageSize.value;
  return sortedRows.slice(start, start + pageSize.value);
});

watch([filteredRows, pageSize], () => {
  // Reset to first page if filters or page size change
  currentPage.value = 1;
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
  return filteredRows.value.length > 0 && filteredRows.value.every(row => selectedRows.value.includes(row.id || row.name));
});

function toggleSelectAll(checked) {
  const isChecked = checked === 'true' || checked === true;
  if (isChecked) {
    selectedRows.value = filteredRows.value.map(row => row.id || row.name);
  } else {
    selectedRows.value = [];
  }
}
function toggleRowSelect(row, checked) {
  const rowId = row.id || row.name;
  const isChecked = checked === 'true' || checked === true;
  if (isChecked) {
    if (!selectedRows.value.includes(rowId)) {
      selectedRows.value.push(rowId);
    }
  } else {
    selectedRows.value = selectedRows.value.filter(id => id !== rowId);
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
    visibleColumns.value.add(col.key);
  });
}

function deselectAllColumns() {
  visibleColumns.value.clear();
}

// Initialize visible columns on mount
function initializeVisibleColumns() {
  if (props.columns) {
    // Start with all columns visible
    allColumns.value.forEach(col => {
      visibleColumns.value.add(col.key);
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
  });
});
function emitRowClick(row) {
  emit('rowClick', row);
}
function emitAction(type, payload) {
  emit('action', { type, payload });
}
function toggleMenu(id) {
  console.log('Toggle menu for ID:', id, 'Current openMenuId:', openMenuId.value);
  openMenuId.value = openMenuId.value === id ? null : id;
  console.log('New openMenuId:', openMenuId.value);
}
function closeMenu() {
  console.log('Closing menu, current openMenuId:', openMenuId.value);
  openMenuId.value = null;
}

function onCreate() {
  emit('create');
  // Default logic: placeholder (e.g., open modal, log, etc.)
  // console.log('OnCreate triggered');
}
function onEdit() {
  if (selectedRows.value.length === 1) {
    // Find the full row object by ID
    const rowId = selectedRows.value[0];
    const row = computedRows.value.find(r => (r.id || r.name) === rowId);
    emit('edit', row);
    // Default logic: placeholder
    // console.log('OnEdit triggered', row);
  } else if (selectedRows.value.length > 1) {
    onMultipleEdit();
  }
}
function onMultipleEdit() {
  emit('multiple-edit', selectedRows.value);
  // Default logic: placeholder
  // console.log('OnMultipleEdit triggered', selectedRows.value);
}
function onDelete() {
  emit('delete', selectedRows.value);
  // Default logic: placeholder
  // console.log('OnDelete triggered', selectedRows.value);
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