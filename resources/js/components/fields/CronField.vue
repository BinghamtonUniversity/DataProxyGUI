<template>
  <div v-if="show" :class="containerClass">
    <!-- Label -->
    <label v-if="label" :for="fieldId" class="block text-sm font-medium text-gray-900 dark:text-white mb-2" :class="{ 'text-red-500': localError }">
      {{ label }}
      <span v-if="required === true || required === 'true'" class="text-red-500 ml-1">*</span>
      <span
        v-if="info"
        class="relative cursor-pointer ml-1"
        @mouseenter="showInfo = true || showInfo === 'true'"
        @mouseleave="showInfo = false || showInfo === 'false'"
      >
        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 inline" fill="currentColor" viewBox="0 0 20 20">
          <circle cx="10" cy="10" r="9" fill="currentColor"/>
          <text x="10" y="15" text-anchor="middle" font-size="12" fill="white">?</text>
        </svg>
        <span
          v-if="showInfo"
          class="absolute left-1/2 z-10 -translate-x-1/2 mt-2 w-48 p-2 rounded-lg bg-white dark:bg-gray-800 text-xs text-gray-900 dark:text-white shadow-lg border border-gray-200 dark:border-gray-700"
          style="pointer-events: none;"
        >
          {{ info }}
        </span>
      </span>
    </label>

    <!-- Cron Input Toggle -->
    <div class="mb-3">
      <div class="flex space-x-4">
        <button
          type="button"
          @click="inputMode = 'manual'"
          :class="[
            'px-3 py-2 text-sm font-medium rounded-md transition-colors',
            inputMode === 'manual' 
              ? 'bg-blue-500 text-white' 
              : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
          ]"
        >
          <FontAwesomeIcon :icon="faKeyboard" class="mr-2" />
          Manual Input
        </button>
        <button
          type="button"
          @click="inputMode = 'builder'"
          :class="[
            'px-3 py-2 text-sm font-medium rounded-md transition-colors',
            inputMode === 'builder' 
              ? 'bg-blue-500 text-white' 
              : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
          ]"
        >
          <FontAwesomeIcon :icon="faCogs" class="mr-2" />
          Visual Builder
        </button>
      </div>
    </div>

    <!-- Manual Input Mode -->
    <div v-if="inputMode === 'manual'" class="space-y-3">
      <!-- Cron Expression Input -->
      <div class="flex items-stretch w-full">
        <!-- Pre (clock icon) -->
        <span 
          class="inline-flex items-center justify-center px-3 border border-r-0 border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-sm font-normal rounded-l-md min-w-[44px]"
        >
          <FontAwesomeIcon :icon="faClock" class="text-base" />
        </span>
        
        <!-- Main Input -->
        <input
          :id="fieldId"
          type="text"
          v-model="internalValue"
          :placeholder="placeholder || '0 0 * * *'"
          :required="required"
          :disabled="!edit"
          :readonly="!edit"
          :name="name"
          :class="inputClass"
          @input="handleInput"
          @change="handleChange"
          @blur="handleBlur"
          @focus="handleFocus"
          @keydown="(event) => emit('keydown', event)"
        >
      </div>

      <!-- Cron Expression Preview -->
      <div v-if="cronDescription" class="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md">
        <div class="flex items-start">
          <FontAwesomeIcon :icon="faInfoCircle" class="text-blue-500 mt-0.5 mr-2" />
          <div>
            <p class="text-sm font-medium text-blue-900 dark:text-blue-100">Schedule Description:</p>
            <p class="text-sm text-blue-700 dark:text-blue-200">{{ cronDescription }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Visual Builder Mode -->
    <div v-if="inputMode === 'builder'" class="space-y-4">
      <!-- Cron Builder Grid -->
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <!-- Minute -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Minute</label>
          <select 
            v-model="cronParts.minute"
            :disabled="!edit"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            @change="updateCronExpression"
          >
            <option value="*">Every minute</option>
            <option value="0">At minute 0</option>
            <option value="15">At minute 15</option>
            <option value="30">At minute 30</option>
            <option value="45">At minute 45</option>
            <option value="*/5">Every 5 minutes</option>
            <option value="*/10">Every 10 minutes</option>
            <option value="*/15">Every 15 minutes</option>
            <option value="*/30">Every 30 minutes</option>
          </select>
        </div>

        <!-- Hour -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hour</label>
          <select 
            v-model="cronParts.hour"
            :disabled="!edit"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            @change="updateCronExpression"
          >
            <option value="*">Every hour</option>
            <option value="0">At 12:00 AM</option>
            <option value="6">At 6:00 AM</option>
            <option value="9">At 9:00 AM</option>
            <option value="12">At 12:00 PM</option>
            <option value="15">At 3:00 PM</option>
            <option value="18">At 6:00 PM</option>
            <option value="21">At 9:00 PM</option>
            <option value="*/2">Every 2 hours</option>
            <option value="*/4">Every 4 hours</option>
            <option value="*/6">Every 6 hours</option>
            <option value="*/12">Every 12 hours</option>
          </select>
        </div>

        <!-- Day of Month -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Day of Month</label>
          <select 
            v-model="cronParts.dayOfMonth"
            :disabled="!edit"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            @change="updateCronExpression"
          >
            <option value="*">Every day</option>
            <option value="1">1st of month</option>
            <option value="15">15th of month</option>
            <option value="*/7">Every 7 days</option>
            <option value="L">Last day of month</option>
          </select>
        </div>

        <!-- Month -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Month</label>
          <select 
            v-model="cronParts.month"
            :disabled="!edit"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            @change="updateCronExpression"
          >
            <option value="*">Every month</option>
            <option value="1">January</option>
            <option value="2">February</option>
            <option value="3">March</option>
            <option value="4">April</option>
            <option value="5">May</option>
            <option value="6">June</option>
            <option value="7">July</option>
            <option value="8">August</option>
            <option value="9">September</option>
            <option value="10">October</option>
            <option value="11">November</option>
            <option value="12">December</option>
            <option value="*/3">Every 3 months</option>
            <option value="*/6">Every 6 months</option>
          </select>
        </div>

        <!-- Day of Week -->
        <div class="space-y-2">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Day of Week</label>
          <select 
            v-model="cronParts.dayOfWeek"
            :disabled="!edit"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            @change="updateCronExpression"
          >
            <option value="*">Every day</option>
            <option value="0">Sunday</option>
            <option value="1">Monday</option>
            <option value="2">Tuesday</option>
            <option value="3">Wednesday</option>
            <option value="4">Thursday</option>
            <option value="5">Friday</option>
            <option value="6">Saturday</option>
            <option value="1-5">Monday to Friday</option>
            <option value="0,6">Weekends</option>
          </select>
        </div>
      </div>

      <!-- Preset Options -->
      <div class="space-y-2">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Quick Presets</label>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
          <button
            v-for="preset in cronPresets"
            :key="preset.value"
            type="button"
            @click="applyPreset(preset.value)"
            :disabled="!edit"
            class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ preset.label }}
          </button>
        </div>
      </div>
    </div>

    <!-- Help Text -->
    <div v-if="help" class="mt-2 text-xs text-gray-600 dark:text-gray-400" v-html="help"></div>
    
    <!-- Error Message -->
    <div v-if="localError || (errors && errors.length > 0)" class="mt-2 text-xs text-red-500">
      {{ localError || errors[0] }}
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { 
  generateFieldId, 
  createEventHandlers, 
  getFieldContainerClass,
  getSafeFieldValue 
} from './functions.js';
import { validateField } from './validation.js';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faKeyboard, faCogs, faClock, faInfoCircle } from '@fortawesome/free-solid-svg-icons';

// Props
const props = defineProps({
  // Field identification
  name: {
    type: String,
    required: true
  },
  fieldId: {
    type: String,
    default: () => generateFieldId('cron')
  },
  
  // Display properties
  label: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: '0 0 * * *'
  },
  help: {
    type: String,
    default: ''
  },
  info: {
    type: String,
    default: ''
  },
  
  // Value and model
  value: {
    type: String,
    default: ''
  },
  
  // Input properties
  required:  { type: [Boolean,String,Array], default: false },
  show:  { type: [Boolean,String,Array], default: true },
  edit:  { type: [Boolean,String,Array], default: true },
  parse:  { type: [Boolean,String,Array], default: true },
  
  // Fieldset context
  inFieldset: {
    type: Boolean,
    default: false
  },
  
  // Validation
  errors: {
    type: Array,
    default: () => []
  },
  
  // Accessibility
  autofocus: {
    type: Boolean,
    default: false
  },
  
  // Error handling
  localError: {
    type: String,
    default: ''
  },
  
  // Features
  validate: {
    type: Array,
    default: () => []
  },
  matchValues: {
    type: Object,
    default: undefined
  }
});

// Emits
const emit = defineEmits([
  'update:value',
  'input',
  'change',
  'blur',
  'focus',
  'keydown',
  'validation-error',
  'validation-success'
]);

// Reactive state
const showInfo = ref(false);
const isFocused = ref(false);
const internalValue = ref(props.value);
const localError = ref('');
const inputMode = ref('manual');

// Cron parts for builder mode
const cronParts = ref({
  minute: '*',
  hour: '*',
  dayOfMonth: '*',
  month: '*',
  dayOfWeek: '*'
});

// Cron presets
const cronPresets = ref([
  { label: 'Every minute', value: '* * * * *' },
  { label: 'Every hour', value: '0 * * * *' },
  { label: 'Every day at midnight', value: '0 0 * * *' },
  { label: 'Every day at noon', value: '0 12 * * *' },
  { label: 'Every Monday', value: '0 0 * * 1' },
  { label: 'Every weekday', value: '0 0 * * 1-5' },
  { label: 'Every month', value: '0 0 1 * *' },
  { label: 'Every year', value: '0 0 1 1 *' }
]);

// Create event handlers using shared functions
const { handleChange, handleBlur, handleFocus, handleInput } = createEventHandlers(
  props, 
  emit, 
  { internalValue, localError }, 
  'cron'
);

// Computed properties
const containerClass = computed(() => getFieldContainerClass(props, props.inFieldset));

const inputClass = computed(() => [
  'bg-white dark:bg-gray-900',
  'w-full px-3 py-2 text-sm border rounded-r-md transition-colors duration-200',
  'dark:!bg-gray-800 text-gray-900 dark:!text-white',
  'border-gray-300 dark:!border-gray-600',
  'focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500',
  'placeholder-gray-500 dark:placeholder-gray-400',
  // Disabled states
  !props.edit ? 'cursor-not-allowed bg-gray-100 dark:!bg-gray-700 text-gray-500 dark:!text-gray-400 border-gray-200 dark:!border-gray-600 opacity-75' : 'hover:border-gray-400 dark:hover:border-gray-500',
  // Error states
  (localError.value || (props.errors && props.errors.length > 0)) ? 'border-red-500 focus:ring-red-500/20 focus:border-red-500' : '',
  // Readonly states
  !props.edit ? 'bg-gray-100 dark:!bg-gray-700' : ''
]);

// Cron description generator
const cronDescription = computed(() => {
  if (!internalValue.value) return '';
  
  try {
    return generateCronDescription(internalValue.value);
  } catch (error) {
    return 'Invalid cron expression';
  }
});

// Methods
const generateCronDescription = (cronExpression) => {
  const parts = cronExpression.split(' ');
  if (parts.length !== 5) return 'Invalid format';
  
  const [minute, hour, dayOfMonth, month, dayOfWeek] = parts;
  
  let description = '';
  
  // Minute
  if (minute === '*') {
    description += 'every minute';
  } else if (minute === '0') {
    description += 'at minute 0';
  } else if (minute.startsWith('*/')) {
    const interval = minute.substring(2);
    description += `every ${interval} minutes`;
  } else {
    description += `at minute ${minute}`;
  }
  
  // Hour
  if (hour === '*') {
    description += ' of every hour';
  } else if (hour === '0') {
    description += ' of midnight';
  } else if (hour.startsWith('*/')) {
    const interval = hour.substring(2);
    description += ` of every ${interval} hours`;
  } else {
    description += ` of ${hour}:00`;
  }
  
  // Day of Month
  if (dayOfMonth !== '*') {
    if (dayOfMonth === '1') {
      description += ' on the 1st';
    } else if (dayOfMonth.startsWith('*/')) {
      const interval = dayOfMonth.substring(2);
      description += ` every ${interval} days`;
    } else {
      description += ` on the ${dayOfMonth}`;
    }
  }
  
  // Month
  if (month !== '*') {
    const monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June', 
                       'July', 'August', 'September', 'October', 'November', 'December'];
    if (month.startsWith('*/')) {
      const interval = month.substring(2);
      description += ` every ${interval} months`;
    } else {
      description += ` in ${monthNames[parseInt(month)] || month}`;
    }
  }
  
  // Day of Week
  if (dayOfWeek !== '*') {
    const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    if (dayOfWeek.includes(',')) {
      const days = dayOfWeek.split(',').map(d => dayNames[parseInt(d)]).join(', ');
      description += ` on ${days}`;
    } else if (dayOfWeek.includes('-')) {
      const [start, end] = dayOfWeek.split('-');
      description += ` from ${dayNames[parseInt(start)]} to ${dayNames[parseInt(end)]}`;
    } else {
      description += ` on ${dayNames[parseInt(dayOfWeek)] || dayOfWeek}`;
    }
  }
  
  return description;
};

const parseCronExpression = (expression) => {
  if (!expression) return;
  
  const parts = expression.split(' ');
  if (parts.length === 5) {
    cronParts.value = {
      minute: parts[0],
      hour: parts[1],
      dayOfMonth: parts[2],
      month: parts[3],
      dayOfWeek: parts[4]
    };
  }
};

const updateCronExpression = () => {
  const expression = `${cronParts.value.minute} ${cronParts.value.hour} ${cronParts.value.dayOfMonth} ${cronParts.value.month} ${cronParts.value.dayOfWeek}`;
  internalValue.value = expression;
  emit('update:value', expression);
  emit('change', expression);
};

const applyPreset = (presetValue) => {
  internalValue.value = presetValue;
  parseCronExpression(presetValue);
  emit('update:value', presetValue);
  emit('change', presetValue);
};

// Watchers
watch(() => props.value, (newValue) => {
  internalValue.value = newValue;
  parseCronExpression(newValue);
}, { immediate: true });

watch(() => props.validate, () => {
  // Validation will happen on blur instead
});

// Lifecycle
onMounted(() => {
  if (props.autofocus) {
    const input = document.getElementById(props.fieldId);
    if (input) {
      input.focus();
    }
  }
  
  // Parse initial value
  parseCronExpression(props.value);
});
</script>

<script>
export default {
  components: {
    FontAwesomeIcon
  }
}
</script>

<style scoped>
.cron-field-container {
  width: 100%;
}

/* Custom focus ring animation */
.focus\:ring-2:focus {
  animation: focusRing 0.2s ease-out;
}

@keyframes focusRing {
  0% {
    box-shadow: 0 0 0 0 rgb(59 130 246 / 0.2); /* blue-500, 20% opacity */
  }
  100% {
    box-shadow: 0 0 0 4px rgb(59 130 246 / 0.2);
  }
}

.dark .focus\:ring-2:focus {
  animation: focusRingDark 0.2s ease-out;
}

@keyframes focusRingDark {
  0% {
    box-shadow: 0 0 0 0 rgb(96 165 250 / 0.2); /* blue-400, 20% opacity */
  }
  100% {
    box-shadow: 0 0 0 4px rgb(96 165 250 / 0.2);
  }
}

/* Error focus ring */
.focus\:ring-error-20:focus {
  animation: focusRingError 0.2s ease-out;
}

@keyframes focusRingError {
  0% {
    box-shadow: 0 0 0 0 rgb(239 68 68 / 0.2); /* red-500, 20% opacity */
  }
  100% {
    box-shadow: 0 0 0 4px rgb(239 68 68 / 0.2);
  }
}

.dark .focus\:ring-error-20:focus {
  animation: focusRingErrorDark 0.2s ease-out;
}

@keyframes focusRingErrorDark {
  0% {
    box-shadow: 0 0 0 0 rgb(248 113 113 / 0.2); /* red-400, 20% opacity */
  }
  100% {
    box-shadow: 0 0 0 4px rgb(248 113 113 / 0.2);
  }
}

/* Smooth transitions */
.transition-colors {
  transition: all 0.2s ease-in-out;
}

/* Force dark mode styles for input fields */
.dark input[type="text"] {
  background-color: rgb(31 41 55) !important; /* gray-800 */
  color: rgb(255 255 255) !important; /* white */
  border-color: rgb(75 85 99) !important; /* gray-600 */
}

.dark input[type="text"]::placeholder {
  color: rgb(156 163 175) !important; /* gray-400 */
}

.dark input[type="text"]:disabled {
  background-color: rgb(55 65 81) !important; /* gray-700 */
  color: rgb(156 163 175) !important; /* gray-400 */
}

.dark select {
  background-color: rgb(31 41 55) !important; /* gray-800 */
  color: rgb(255 255 255) !important; /* white */
  border-color: rgb(75 85 99) !important; /* gray-600 */
}

.dark select:disabled {
  background-color: rgb(55 65 81) !important; /* gray-700 */
  color: rgb(156 163 175) !important; /* gray-400 */
}
</style>
