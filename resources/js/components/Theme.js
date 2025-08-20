// Project-wide theme configuration, categorized

export const theme = {
  layout: {
    container: 'flex flex-col min-h-screen bg-gray-50 dark:bg-gray-900',
    main: 'flex flex-1',
    content: 'flex-1 transition-all duration-400 p-6 bg-gray-50 dark:bg-gray-900',
    flexRow: 'flex flex-1',
    appbarSpacer: '', // for sticky/fixed appbar if needed
  },
  sidebar: {
    container: 'fixed top-0 left-0 h-screen z-50 flex flex-col bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 transition-all duration-400',
    background: '', // now included above
    links: 'flex-1 overflow-y-auto',
    item: 'flex items-center gap-3 px-4 py-4 rounded hover:bg-gray-100 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-200 font-medium whitespace-nowrap transition-colors',
    itemActive: 'bg-gray-200 text-gray-900 dark:bg-gray-700 dark:text-white font-semibold text-sm',
    logo: 'flex items-center gap-2 px-4 py-6 font-bold text-lg text-gray-700 dark:text-gray-200',
    logoBg: ' dark:bg-gray-800',
  },
  appbar: {
    container: 'w-full flex items-center px-6 py-2 bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700',
    title: 'text-xl font-semibold text-gray-900 dark:text-white',
    actions: 'flex items-center gap-2',
    avatar: 'w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold',
  },
  modal: {
  overlay: 'fixed inset-0 z-50 overflow-y-auto backdrop-blur-sm  bg-opacity-15',
    backdrop: 'fixed inset-0 bg-opacity-50 transition-opacity',
    container: 'flex min-h-full items-center justify-center p-4',
    dialog: 'relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[80vh] overflow-hidden',
    header: 'flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700',
    title: 'text-lg font-semibold text-gray-900 dark:text-white',
    closeButton: 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors',
    content: 'p-6 overflow-y-auto max-h-[60vh]',
    footer: 'flex items-center justify-end gap-3 p-6 border-t border-gray-200 dark:border-gray-700',
    primaryButton: 'px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors',
    secondaryButton: 'px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-md transition-colors',
    dangerButton: 'px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md transition-colors',
  },
  datatable: {
    default: {
      container: 'bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700',
      header: 'flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700',
      title: 'text-xl font-semibold text-gray-900 dark:text-gray-100',
      newButton: 'px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 focus:outline-none',
      editButton: 'px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed',
      deleteButton: 'px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed',
      searchContainer: 'p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900',
      table: 'min-w-full bg-white dark:bg-gray-800 rounded shadow border border-gray-200 dark:border-gray-700',
      headerCell: 'px-6 py-3 text-left text-gray-700 dark:text-gray-100 border-b border-gray-200 dark:border-gray-700',
      filterCell: 'px-6 py-2 border-b border-gray-100 dark:border-gray-700',
      clearButton: 'px-2 py-1 text-xs rounded bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-100 hover:bg-gray-300 dark:hover:bg-gray-600',
      row: 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100',
      rowEven: 'bg-gray-50 dark:bg-gray-900',
      rowOdd: 'bg-white dark:bg-gray-800',
      rowHover: 'hover:bg-gray-100 dark:hover:bg-gray-700',
      cell: 'px-6 py-4 text-gray-900 dark:text-gray-100 border-b border-gray-100 dark:border-gray-700',
      borderRight: 'border-r border-gray-200 dark:border-gray-700',
      menuButton: 'p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 focus:outline-none text-gray-600 dark:text-gray-100',
      menuIcon: 'w-5 h-5 text-gray-600 dark:text-gray-100',
      dropdown: 'absolute right-0 mt-2 w-32 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded shadow-lg z-10',
      dropdownItem: 'block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700',
      dropdownItemDanger: 'block w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900'
    },
    minimal: {
      container: 'bg-white dark:bg-gray-800 rounded-lg',
      header: 'flex items-center justify-between p-4 border-b border-gray-100 dark:border-gray-700',
      title: 'text-lg font-medium text-gray-900 dark:text-white',
      newButton: 'px-3 py-1.5 rounded text-sm bg-blue-500 text-white hover:bg-blue-600 focus:outline-none',
      editButton: 'px-3 py-1.5 rounded text-sm bg-gray-100 text-gray-700 hover:bg-gray-200 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed',
      deleteButton: 'px-3 py-1.5 rounded text-sm bg-red-500 text-white hover:bg-red-600 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed',
      searchContainer: 'p-3 border-b border-gray-100 dark:border-gray-700',
      table: 'min-w-full bg-white dark:bg-gray-800',
      headerCell: 'px-4 py-2 text-left text-sm text-gray-600 dark:text-gray-300 border-b border-gray-100 dark:border-gray-700',
      filterCell: 'px-4 py-1 border-b border-gray-50 dark:border-gray-900',
      clearButton: 'px-2 py-1 text-xs rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600',
      row: 'bg-white dark:bg-gray-800',
      rowEven: 'bg-gray-50 dark:bg-gray-900',
      rowOdd: 'bg-white dark:bg-gray-800',
      rowHover: 'hover:bg-gray-50 dark:hover:bg-gray-700',
      cell: 'px-4 py-2 text-sm text-gray-900 dark:text-gray-100 border-b border-gray-50 dark:border-gray-900',
      borderRight: 'border-r border-gray-100 dark:border-gray-700',
      menuButton: 'p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none',
      menuIcon: 'w-4 h-4 text-gray-500 dark:text-gray-400',
      dropdown: 'absolute right-0 mt-1 w-28 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded shadow-lg z-10',
      dropdownItem: 'block w-full text-left px-3 py-1.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700',
      dropdownItemDanger: 'block w-full text-left px-3 py-1.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900'
    },
    bordered: {
      container: 'bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-300 dark:border-gray-600',
      header: 'flex items-center justify-between p-6 border-b-2 border-gray-300 dark:border-gray-600',
      title: 'text-xl font-bold text-gray-900 dark:text-white',
      newButton: 'px-4 py-2 rounded border-2 border-blue-600 bg-blue-600 text-white hover:bg-blue-700 hover:border-blue-700 focus:outline-none',
      editButton: 'px-4 py-2 rounded border-2 border-gray-300 text-gray-700 hover:bg-gray-50 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed',
      deleteButton: 'px-4 py-2 rounded border-2 border-red-600 bg-red-600 text-white hover:bg-red-700 hover:border-red-700 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed',
      searchContainer: 'p-4 border-b-2 border-gray-300 dark:border-gray-600',
      table: 'min-w-full bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600',
      headerCell: 'px-6 py-3 text-left font-semibold text-gray-900 dark:text-white border-b-2 border-gray-300 dark:border-gray-600',
      filterCell: 'px-6 py-2 border-b border-gray-200 dark:border-gray-700',
      clearButton: 'px-3 py-1 text-sm rounded border border-gray-300 text-gray-700 hover:bg-gray-50 focus:outline-none',
      row: 'bg-white dark:bg-gray-800',
      rowEven: 'bg-gray-50 dark:bg-gray-900',
      rowOdd: 'bg-white dark:bg-gray-800',
      rowHover: 'hover:bg-gray-100 dark:hover:bg-gray-700',
      cell: 'px-6 py-4 text-gray-900 dark:text-gray-100 border-b border-gray-200 dark:border-gray-700',
      borderRight: 'border-r-2 border-gray-300 dark:border-gray-600',
      menuButton: 'p-2 rounded border border-gray-300 hover:bg-gray-100 focus:outline-none',
      menuIcon: 'w-5 h-5 text-gray-700 dark:text-gray-300',
      dropdown: 'absolute right-0 mt-2 w-32 bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 rounded shadow-lg z-10',
      dropdownItem: 'block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700',
      dropdownItemDanger: 'block w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900 border-b border-gray-100 dark:border-gray-700'
    }
  },
  formbuilder: {
    container: 'max-w-7xl mx-auto mt-8 mb-12 p-0 bg-white dark:bg-gray-800',
    header: 'text-2xl font-bold text-gray-900 dark:text-white',
    subheader: 'mt-1 text-sm text-gray-600 dark:text-gray-300',
    main: 'flex gap-6 p-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm',
    sidebarType: 'w-48 flex flex-col gap-4',
    typeButton: 'cursor-move px-4 py-2 text-center font-semibold transition-all duration-200 bg-white dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700',
    canvas: 'flex-1',
    canvasDrop: 'min-h-[200px] p-4 bg-gray-50 dark:bg-gray-900 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg',
    fieldCard: 'p-4 cursor-pointer transition-all duration-200 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm hover:border-gray-300 dark:hover:border-gray-600',
    fieldCardSelected: 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 shadow-md ring-2 ring-blue-200 dark:ring-blue-800 border-l-4 border-l-blue-500',
    fieldCardUnselected: 'hover:border-gray-300 dark:hover:border-gray-600 shadow-md',
    fieldLabel: 'block text-sm font-medium text-gray-900 dark:text-white flex items-center gap-1',
    fieldType: 'text-xs px-2 py-1 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300',
    sectionHeader: 'border-b border-gray-200 dark:border-gray-700 pb-2',
    sectionTitle: 'text-lg font-semibold text-gray-900 dark:text-white',
    sectionDesc: 'text-sm mt-1 text-gray-600 dark:text-gray-300',
    input: 'w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200',
    textarea: 'w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200',
    select: 'w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200',
    checkbox: 'w-5 h-5 text-blue-600 bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200',
    help: 'text-xs text-gray-600 dark:text-gray-400 mt-1',
    info: 'text-xs text-gray-600 dark:text-gray-400',
    configPanel: 'w-96',
    configHeader: 'block font-semibold text-gray-900 dark:text-white',
    configSection: 'mb-4 p-4 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg',
    configLabel: 'block text-xs mb-1 text-gray-600 dark:text-gray-400',
    configInput: 'w-full px-4 py-3 text-sm focus:outline-none transition-all duration-200 bg-white dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-md focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20',
    configButton: 'px-3 py-1.5 text-xs rounded-full transition-all duration-200 bg-blue-600 text-white shadow-sm hover:shadow-md',
    configButtonSecondary: 'px-3 py-1.5 text-xs rounded-full transition-all duration-200 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 shadow-sm hover:shadow-md',
    modalJson: 'bg-white dark:bg-gray-800 p-4 rounded-lg text-xs overflow-x-auto text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 shadow-lg',
    previewModal: 'bg-gray-50 dark:bg-gray-900',
    previewForm: 'grid grid-cols-12 gap-4 auto-rows-auto items-start',
    previewField: 'w-full flex flex-col items-start',
    previewLabel: 'block text-sm font-medium text-gray-900 dark:text-white flex items-center gap-1',
    previewInput: 'w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition-all duration-200',
    previewSelect: 'w-full px-4 py-3 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-200',
    previewCheckbox: 'w-5 h-5 text-blue-600 bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200',
    previewHelp: 'text-xs text-gray-600 dark:text-gray-400 mt-1',
    previewOutput: 'w-full px-4 py-3 text-sm border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-900 text-gray-600 dark:text-gray-400',
    previewButton: 'w-full px-6 py-3 text-sm font-medium text-white bg-blue-600 rounded-lg transition-all duration-200 shadow-sm hover:shadow-md',
    headerContainer: 'px-6 pt-6 pb-2 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 rounded-lg rounded-b-none',
  },
};

export const badgeColors = [
  'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
  'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
  'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
  'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
  'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
  'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
  'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200',
  'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'
];

export function getThemeClasses(category, themeName = 'default') {
  if (!theme[category]) throw new Error(`Theme category '${category}' not found.`);
  
  // For datatable, return the specific theme variation
  if (category === 'datatable') {
    if (!theme.datatable[themeName]) throw new Error(`Datatable theme '${themeName}' not found.`);
    return theme.datatable[themeName];
  }
  
  // For other categories, return the category directly
  return theme[category];
}

export function getAvailableThemes() {
  return ['default', 'minimal', 'bordered'];
}

export function isValidTheme(themeName) {
  return ['default', 'minimal', 'bordered'].includes(themeName);
}

export function getDynamicColor(value) {
  let hash = 0;
  const str = String(value).toLowerCase();
  for (let i = 0; i < str.length; i++) {
    hash = str.charCodeAt(i) + ((hash << 5) - hash);
  }
  return badgeColors[Math.abs(hash) % badgeColors.length];
} 