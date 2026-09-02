export interface PolicyMarker {
  startLineNumber: number
  startColumn: number
  endLineNumber: number
  endColumn: number
  message: string
  severity: number
}

const FORBIDDEN_MODULES = [
  'threading',
  'concurrent.futures',
  'concurrent',
  'asyncio',
  'multiprocessing',
  'gevent',
  'eventlet',
  'greenlet',
]

const FORBIDDEN_KEYWORDS = ['async', 'await']

const FORBIDDEN_CLASSES = ['ThreadPoolExecutor', 'ProcessPoolExecutor', 'create_task', 'gather']

/**
 * Checks Python code for forbidden threading/concurrency/async usage.
 * Runs on the main thread — cheap regex checks, no parser needed.
 * Skips string/comment content using manual char-scanning (same
 * approach as the original editorValidator.ts) to avoid false positives.
 */
export function checkPythonForbiddenUsage(code: string): PolicyMarker[] {
  const markers: PolicyMarker[] = []
  const lines = code.split('\n')

  let inMultilineString = false
  let multilineStringChar = ''

  for (let i = 0; i < lines.length; i++) {
    const line = lines[i]
    const trimmed = line.trim()

    // Track triple-quoted multiline strings
    const tripleQuoteMatch = line.match(/"""|'''/)
    if (tripleQuoteMatch) {
      if (!inMultilineString) {
        inMultilineString = true
        multilineStringChar = tripleQuoteMatch[0]
      } else if (tripleQuoteMatch[0] === multilineStringChar) {
        inMultilineString = false
        multilineStringChar = ''
      }
    }

    if (inMultilineString && !tripleQuoteMatch) continue
    if (!trimmed || trimmed.startsWith('#')) continue

    // Mask single-line string content before matching, so a forbidden
    // word inside a string literal on this line doesn't false-positive
    const maskedLine = maskInlineStrings(line)

    for (const module of FORBIDDEN_MODULES) {
      const escapedModule = module.replace(/\./g, '\\.')
      const importPatterns = [
        new RegExp(`^\\s*import\\s+${escapedModule}\\b`, 'i'),
        new RegExp(`^\\s*from\\s+${escapedModule}\\s+import`, 'i'),
        new RegExp(`\\bimport\\s+${escapedModule}\\b`, 'i'),
        new RegExp(`\\bfrom\\s+${escapedModule}\\s+import`, 'i'),
      ]

      for (const pattern of importPatterns) {
        const match = maskedLine.match(pattern)
        if (match) {
          const startCol = line.indexOf(match[0].trim().split(/\s+/)[0]) + 1
          markers.push({
            startLineNumber: i + 1,
            startColumn: startCol,
            endLineNumber: i + 1,
            endColumn: startCol + match[0].length,
            message: `Forbidden: '${module}' module is not allowed (threading/concurrency operations are disabled)`,
            severity: 8,
          })
          break
        }
      }

      const usagePattern = new RegExp(`\\b${escapedModule}\\.`, 'i')
      const usageMatch = maskedLine.match(usagePattern)
      if (usageMatch) {
        const startCol = maskedLine.indexOf(usageMatch[0]) + 1
        markers.push({
          startLineNumber: i + 1,
          startColumn: startCol,
          endLineNumber: i + 1,
          endColumn: startCol + usageMatch[0].length - 1,
          message: `Forbidden: '${module}' module usage is not allowed (threading/concurrency operations are disabled)`,
          severity: 8,
        })
      }
    }

    for (const keyword of FORBIDDEN_KEYWORDS) {
      const pattern = new RegExp(`\\b${keyword}\\b`, 'i')
      const match = maskedLine.match(pattern)
      if (match) {
        const startCol = maskedLine.indexOf(match[0]) + 1
        markers.push({
          startLineNumber: i + 1,
          startColumn: startCol,
          endLineNumber: i + 1,
          endColumn: startCol + keyword.length,
          message: `Forbidden: '${keyword}' keyword is not allowed (async operations are disabled)`,
          severity: 8,
        })
      }
    }

    for (const className of FORBIDDEN_CLASSES) {
      const pattern = new RegExp(`\\b${className}\\b`, 'i')
      const match = maskedLine.match(pattern)
      if (match) {
        const startCol = maskedLine.indexOf(match[0]) + 1
        markers.push({
          startLineNumber: i + 1,
          startColumn: startCol,
          endLineNumber: i + 1,
          endColumn: startCol + className.length,
          message: `Forbidden: '${className}' is not allowed (threading/concurrency operations are disabled)`,
          severity: 8,
        })
      }
    }
  }

  return markers
}

/**
 * Replaces content inside single-line string literals (single/double
 * quotes) with spaces, preserving line length/positions, so forbidden-word
 * regex matching above doesn't fire on text that's just inside a string.
 */
function maskInlineStrings(line: string): string {
  let result = ''
  let inString = false
  let stringChar = ''
  let escaped = false

  for (let i = 0; i < line.length; i++) {
    const ch = line[i]

    if ((ch === '"' || ch === "'") && !escaped) {
      if (!inString) {
        inString = true
        stringChar = ch
        result += ch
        continue
      } else if (ch === stringChar) {
        inString = false
        result += ch
        continue
      }
    }

    escaped = ch === '\\' && !escaped

    result += inString ? ' ' : ch
  }

  return result
}