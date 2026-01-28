/**
 * Editor Validator
 * Validates Python and PHP code syntax
 */

export interface ValidationMarker {
  startLineNumber: number
  startColumn: number
  endLineNumber: number
  endColumn: number
  message: string
  severity: number
}

export interface ValidationResult {
  markers: ValidationMarker[]
  errors: number
  warnings: number
}

/**
 * Validates code based on language
 * @param code - The code to validate
 * @param language - The programming language ('python' | 'php')
 * @param model - Monaco editor model (for getting line content)
 * @returns Validation result with markers, error count, and warning count
 */
export function validateCode(
  code: string,
  language: 'python' | 'php',
  model: any
): ValidationResult {
  const markers: ValidationMarker[] = []
  const lines = code.split('\n')

  // --------------------------------------------------------------------
  // 🔹 Python Validation
  // --------------------------------------------------------------------
  if (language === 'python') {
    let openParens = 0
    let openBrackets = 0
    let openBraces = 0
    const unclosedPositions: { type: string; line: number; col: number }[] = []
    const keywordsWithColon = /^(if|elif|else|for|while|try|except|finally|with|def|class|async\s+def|async\s+with|async\s+for)\s/
    let inMultilineString = false
    let multilineStringChar = ''

    // 🔸 Forbidden threading/concurrency/async configuration
    const forbiddenModules = [
      'threading',
      'concurrent.futures',
      'concurrent',
      'asyncio',
      'multiprocessing',
      'gevent',
      'eventlet',
      'greenlet'
    ]
    const forbiddenKeywords = ['async', 'await']
    const forbiddenClasses = ['ThreadPoolExecutor', 'ProcessPoolExecutor', 'create_task', 'gather']

    for (let i = 0; i < lines.length; i++) {
      const line = lines[i]
      const trimmed = line.trim()
      
      // Check for triple-quoted strings (multiline)
      const tripleQuoteMatch = line.match(/"""|(''')/)
      if (tripleQuoteMatch) {
        if (!inMultilineString) {
          inMultilineString = true
          multilineStringChar = tripleQuoteMatch[0]
        } else if (tripleQuoteMatch[0] === multilineStringChar) {
          inMultilineString = false
          multilineStringChar = ''
        }
      }

      // Skip lines inside multiline strings
      if (inMultilineString && !tripleQuoteMatch) continue
      
      // Skip empty lines and comments
      if (!trimmed || trimmed.startsWith('#')) continue

      // 🔸 Detect forbidden threading/concurrency/async keywords and modules
      // Check for module imports
      for (const module of forbiddenModules) {
        // Match: import threading, import concurrent.futures, from threading import, etc.
        const escapedModule = module.replace(/\./g, '\\.')
        const importPatterns = [
          new RegExp(`^\\s*import\\s+${escapedModule}\\b`, 'i'),
          new RegExp(`^\\s*from\\s+${escapedModule}\\s+import`, 'i'),
          new RegExp(`\\bimport\\s+${escapedModule}\\b`, 'i'),
          new RegExp(`\\bfrom\\s+${escapedModule}\\s+import`, 'i')
        ]
        
        for (const pattern of importPatterns) {
          if (pattern.test(line)) {
            const match = line.match(pattern)
            if (match) {
              const startCol = line.indexOf(match[0]) + 1
              markers.push({
                startLineNumber: i + 1,
                startColumn: startCol,
                endLineNumber: i + 1,
                endColumn: startCol + match[0].length,
                message: `Forbidden: '${module}' module is not allowed (threading/concurrency operations are disabled)`,
                severity: 8, // Monaco.MarkerSeverity.Error
              })
              break // Only report once per line
            }
          }
        }
        
        // Check for module usage (e.g., threading.Thread(), asyncio.run())
        const moduleUsagePattern = new RegExp(`\\b${escapedModule}\\.`, 'i')
        if (moduleUsagePattern.test(line)) {
          const match = line.match(moduleUsagePattern)
          if (match) {
            const startCol = line.indexOf(match[0]) + 1
            markers.push({
              startLineNumber: i + 1,
              startColumn: startCol,
              endLineNumber: i + 1,
              endColumn: startCol + match[0].length - 1,
              message: `Forbidden: '${module}' module usage is not allowed (threading/concurrency operations are disabled)`,
              severity: 8, // Monaco.MarkerSeverity.Error
            })
          }
        }
      }
      
      // Check for async/await keywords
      for (const keyword of forbiddenKeywords) {
        const keywordPattern = new RegExp(`\\b${keyword}\\b`, 'i')
        if (keywordPattern.test(line)) {
          const match = line.match(keywordPattern)
          if (match) {
            const startCol = line.indexOf(match[0]) + 1
            markers.push({
              startLineNumber: i + 1,
              startColumn: startCol,
              endLineNumber: i + 1,
              endColumn: startCol + keyword.length,
              message: `Forbidden: '${keyword}' keyword is not allowed (async operations are disabled)`,
              severity: 8, // Monaco.MarkerSeverity.Error
            })
          }
        }
      }
      
      // Check for forbidden classes/functions
      for (const className of forbiddenClasses) {
        const classPattern = new RegExp(`\\b${className}\\b`, 'i')
        if (classPattern.test(line)) {
          const match = line.match(classPattern)
          if (match) {
            const startCol = line.indexOf(match[0]) + 1
            markers.push({
              startLineNumber: i + 1,
              startColumn: startCol,
              endLineNumber: i + 1,
              endColumn: startCol + className.length,
              message: `Forbidden: '${className}' is not allowed (threading/concurrency operations are disabled)`,
              severity: 8, // Monaco.MarkerSeverity.Error
            })
          }
        }
      }

      // Calculate current indentation
      const leadingSpaces = line.search(/\S/)
      const currentIndent = leadingSpaces === -1 ? 0 : leadingSpaces

      // Track parentheses/brackets/braces with proper nesting
      let inString = false
      let stringChar = ''
      let escaped = false

      for (let j = 0; j < line.length; j++) {
        const ch = line[j]
        const next2 = line.substring(j, j + 3)
        
        // Handle triple quotes
        if ((next2 === '"""' || next2 === "'''") && !inString) {
          j += 2 // Skip the triple quote
          continue
        }

        // Handle single/double quote strings
        if ((ch === '"' || ch === "'") && !escaped) {
          if (!inString) {
            inString = true
            stringChar = ch
          } else if (ch === stringChar) {
            inString = false
            stringChar = ''
          }
        }

        escaped = ch === '\\' && !escaped

        // Only track brackets outside of strings
        if (!inString && ch !== '#') {
          if (ch === '(') {
            openParens++
            unclosedPositions.push({ type: '(', line: i, col: j })
          } else if (ch === ')') {
            if (openParens > 0) {
              openParens--
              unclosedPositions.pop()
            } else {
              markers.push({
                startLineNumber: i + 1,
                startColumn: j + 1,
                endLineNumber: i + 1,
                endColumn: j + 2,
                message: 'Unmatched closing parenthesis ")"',
                severity: 8, // Monaco.MarkerSeverity.Error
              })
            }
          }
          
          if (ch === '[') {
            openBrackets++
            unclosedPositions.push({ type: '[', line: i, col: j })
          } else if (ch === ']') {
            if (openBrackets > 0) {
              openBrackets--
              unclosedPositions.pop()
            } else {
              markers.push({
                startLineNumber: i + 1,
                startColumn: j + 1,
                endLineNumber: i + 1,
                endColumn: j + 2,
                message: 'Unmatched closing bracket "]"',
                severity: 8,
              })
            }
          }
          
          if (ch === '{') {
            openBraces++
            unclosedPositions.push({ type: '{', line: i, col: j })
          } else if (ch === '}') {
            if (openBraces > 0) {
              openBraces--
              unclosedPositions.pop()
            } else {
              markers.push({
                startLineNumber: i + 1,
                startColumn: j + 1,
                endLineNumber: i + 1,
                endColumn: j + 2,
                message: 'Unmatched closing brace "}"',
                severity: 8,
              })
            }
          }
        }
      }

      // Check for unterminated strings
      if (inString) {
        markers.push({
          startLineNumber: i + 1,
          startColumn: 1,
          endLineNumber: i + 1,
          endColumn: line.length + 1,
          message: 'Unterminated string literal',
          severity: 8,
        })
      }

      // Missing colon after block headers
      const colonMatch = trimmed.match(keywordsWithColon)
      if (colonMatch && !trimmed.endsWith(':') && !trimmed.endsWith('\\')) {
        markers.push({
          startLineNumber: i + 1,
          startColumn: 1,
          endLineNumber: i + 1,
          endColumn: line.length + 1,
          message: `Missing colon (:) after '${colonMatch[1].trim()}' statement`,
          severity: 8,
        })
      }

      // Check indentation (multiples of 4 spaces or consistent tabs)
      if (currentIndent > 0) {
        const hasSpaces = line.match(/^[ ]+/)
        const hasTabs = line.match(/^\t+/)
        
        if (hasSpaces && hasTabs) {
          markers.push({
            startLineNumber: i + 1,
            startColumn: 1,
            endLineNumber: i + 1,
            endColumn: currentIndent + 1,
            message: 'Mixed tabs and spaces in indentation',
            severity: 8,
          })
        } else if (hasSpaces && currentIndent % 4 !== 0) {
          markers.push({
            startLineNumber: i + 1,
            startColumn: 1,
            endLineNumber: i + 1,
            endColumn: currentIndent + 1,
            message: 'Indentation should be a multiple of 4 spaces',
            severity: 4, // Monaco.MarkerSeverity.Warning
          })
        }
      }

      

      // Invalid variable names
      const assignmentMatch = trimmed.match(/^(\w+)\s*=/)
      if (assignmentMatch) {
        const varName = assignmentMatch[1]
        if (/^\d/.test(varName)) {
          markers.push({
            startLineNumber: i + 1,
            startColumn: line.indexOf(varName) + 1,
            endLineNumber: i + 1,
            endColumn: line.indexOf(varName) + varName.length + 1,
            message: `Invalid variable name '${varName}' (cannot start with a digit)`,
            severity: 8,
          })
        }
      }

      // Check for invalid return statement assignment (return x = 5)
      if (trimmed.match(/^\s*return\s+\w+\s*=\s*[^=]/)) {
        markers.push({
          startLineNumber: i + 1,
          startColumn: 1,
          endLineNumber: i + 1,
          endColumn: line.length + 1,
          message: 'Cannot assign to a return statement',
          severity: 8,
        })
      }

      // Check for double colons outside of slice notation [::] 
      if (trimmed.match(/::/) && !trimmed.match(/\[.*::.*\]/)) {
        markers.push({
          startLineNumber: i + 1,
          startColumn: 1,
          endLineNumber: i + 1,
          endColumn: line.length + 1,
          message: 'Invalid syntax: double colon (::) is not valid in Python',
          severity: 8,
        })
      }
      
    }

    // Check for unclosed brackets at end of file
    unclosedPositions.forEach((pos) => {
      markers.push({
        startLineNumber: pos.line + 1,
        startColumn: pos.col + 1,
        endLineNumber: pos.line + 1,
        endColumn: pos.col + 2,
        message: `Unmatched opening ${pos.type === '(' ? 'parenthesis' : pos.type === '[' ? 'bracket' : 'brace'} "${pos.type}"`,
        severity: 8,
      })
    })

  }

  // --------------------------------------------------------------------
  // 🔹 PHP Validation
  // --------------------------------------------------------------------
  else if (language === 'php') {
    let openBraces = 0
    let openParens = 0
    let openBrackets = 0
    let hasOpenTag = false
    const unclosedBraces: number[] = []
    
    // Track multi-line string state
    let inMultiLineString = false
    let multiLineStringChar = ''
    let multiLineStringStart = -1
    
    // Track multi-line comment state
    let inMultiLineComment = false
    let multiLineCommentStart = -1

    for (let i = 0; i < lines.length; i++) {
      const line = lines[i]
      const trimmed = line.trim()
      
      // Skip empty lines
      if (!trimmed) continue

      // Check for PHP open tag
      if (trimmed.includes('<?php') || trimmed.includes('<?')) {
        hasOpenTag = true
      }

      // Track characters with proper string and comment handling
      let escaped = false

      for (let j = 0; j < line.length; j++) {
        const ch = line[j]
        const nextCh = j + 1 < line.length ? line[j + 1] : ''

        // Handle multi-line comment start
        if (!inMultiLineString && !inMultiLineComment && ch === '/' && nextCh === '*') {
          inMultiLineComment = true
          multiLineCommentStart = i
          j++ // Skip the *
          continue
        }

        // Handle multi-line comment end
        if (inMultiLineComment && ch === '*' && nextCh === '/') {
          inMultiLineComment = false
          multiLineCommentStart = -1
          j++ // Skip the /
          continue
        }

        // Skip processing if in multi-line comment
        if (inMultiLineComment) {
          continue
        }

        // Handle string detection
        if ((ch === '"' || ch === "'") && !escaped) {
          if (!inMultiLineString) {
            inMultiLineString = true
            multiLineStringChar = ch
            multiLineStringStart = i
          } else if (ch === multiLineStringChar) {
            inMultiLineString = false
            multiLineStringChar = ''
            multiLineStringStart = -1
          }
        }

        escaped = ch === '\\' && !escaped

        // Track brackets outside strings and comments
        if (!inMultiLineString && !inMultiLineComment) {
          if (ch === '{') {
            openBraces++
            unclosedBraces.push(i)
          } else if (ch === '}') {
            openBraces--
            unclosedBraces.pop()
          }
          if (ch === '(') openParens++
          else if (ch === ')') openParens--
          if (ch === '[') openBrackets++
          else if (ch === ']') openBrackets--
        }
      }

      // Skip further line checks if we're in a multi-line string or comment
      if (inMultiLineString || inMultiLineComment) {
        continue
      }

      // Skip single-line comments
      if (trimmed.startsWith('//') || trimmed.startsWith('#')) {
        continue
      }

      // Check if next non-empty line starts with -> (method chaining continuation)
      let nextLineStartsWithArrow = false
      for (let j = i + 1; j < lines.length; j++) {
        const nextTrimmed = lines[j].trim()
        // Skip empty lines and comments
        if (!nextTrimmed || nextTrimmed.startsWith('//') || nextTrimmed.startsWith('#')) {
          continue
        }
        // Check if it's inside a multi-line comment at this position
        // We need to track comment state for lookahead
        let tempInComment = inMultiLineComment
        if (nextTrimmed.startsWith('/*')) {
          tempInComment = true
        }
        if (nextTrimmed.endsWith('*/')) {
          tempInComment = false
        }
        
        if (!tempInComment) {
          nextLineStartsWithArrow = nextTrimmed.startsWith('->')
          break
        }
      }
      

      // Improved semicolon check
      // A statement needs a semicolon if:
      // 1. It doesn't end with {, }, ;, :, or ,
      // 2. It's not a control structure keyword
      // 3. It's not a method chain (ends with -> OR next line starts with ->)
      // 4. It's not inside parentheses (method calls can span multiple lines)
      // 5. It's not a modifier/declaration keyword
      // 6. Current line doesn't start with -> (it's a continuation)
      const startsWithMethodChain = trimmed.startsWith('->')
      const endsWithMethodChain = trimmed.endsWith('->')
      const endsWithContinuation = trimmed.endsWith(',') || trimmed.endsWith('(') || trimmed.endsWith('[')
      const isControlStructure = trimmed.match(/^(if|else|elseif|while|for|foreach|function|class|switch|case|default|do|try|catch|finally)\b/)
      const isModifier = trimmed.match(/^(public|private|protected|static|abstract|final|const|namespace|use|interface|trait|extends|implements)\s/)
      const isPhpTag = trimmed.startsWith('<?php') || trimmed.startsWith('<?') || trimmed.startsWith('?>')
      const isArrayStart = trimmed.endsWith('[') || trimmed.match(/^\s*[\]\[]/)
      
      if (
        trimmed.length > 0 &&
        !trimmed.endsWith(';') &&
        !trimmed.endsWith('{') &&
        !trimmed.endsWith('}') &&
        !trimmed.endsWith(':') &&
        !startsWithMethodChain && // Line is a continuation from previous
        !endsWithMethodChain && // Line continues to next
        !nextLineStartsWithArrow && // Next line is a continuation
        !endsWithContinuation &&
        !isControlStructure &&
        !isModifier &&
        !isPhpTag &&
        !isArrayStart &&
        openParens === 0 && // Not inside a function call
        openBrackets === 0 && // Not inside an array
        hasOpenTag
      ) {
        markers.push({
          startLineNumber: i + 1,
          startColumn: 1,
          endLineNumber: i + 1,
          endColumn: line.length + 1,
          message: 'Missing semicolon (;) at end of statement',
          severity: 4, // Warning
        })
      }

      // Check for variable syntax
      if (trimmed.match(/\$\d/)) {
        markers.push({
          startLineNumber: i + 1,
          startColumn: 1,
          endLineNumber: i + 1,
          endColumn: line.length + 1,
          message: 'Invalid variable name: PHP variables cannot start with a digit after $',
          severity: 8,
        })
      }
    }

    // Check for unterminated multi-line string
    if (inMultiLineString) {
      markers.push({
        startLineNumber: multiLineStringStart + 1,
        startColumn: 1,
        endLineNumber: lines.length,
        endColumn: lines[lines.length - 1].length + 1,
        message: 'Unterminated string literal',
        severity: 8,
      })
    }

    // Check for unterminated multi-line comment
    if (inMultiLineComment) {
      markers.push({
        startLineNumber: multiLineCommentStart + 1,
        startColumn: 1,
        endLineNumber: lines.length,
        endColumn: lines[lines.length - 1].length + 1,
        message: 'Unterminated comment block',
        severity: 8,
      })
    }

    // Final checks
    if (!hasOpenTag && code.trim().length > 0) {
      markers.push({
        startLineNumber: 1,
        startColumn: 1,
        endLineNumber: 1,
        endColumn: 1,
        message: 'Missing PHP opening tag (<?php)',
        severity: 4, // Warning
      })
    }

    if (openBraces !== 0) {
      markers.push({
        startLineNumber: unclosedBraces.length > 0 ? unclosedBraces[0] + 1 : 1,
        startColumn: 1,
        endLineNumber: unclosedBraces.length > 0 ? unclosedBraces[0] + 1 : model.getLineCount(),
        endColumn: 2,
        message: openBraces > 0 ? 'Unmatched opening brace "{"' : 'Unmatched closing brace "}"',
        severity: 8,
      })
    }

    if (openParens !== 0) {
      markers.push({
        startLineNumber: 1,
        startColumn: 1,
        endLineNumber: model.getLineCount(),
        endColumn: 2,
        message: 'Unmatched parentheses',
        severity: 8,
      })
    }

    if (openBrackets !== 0) {
      markers.push({
        startLineNumber: 1,
        startColumn: 1,
        endLineNumber: model.getLineCount(),
        endColumn: 2,
        message: 'Unmatched square brackets',
        severity: 8,
      })
    }
  }

  // Calculate error and warning counts
  const errors = markers.filter((m) => m.severity === 8).length
  const warnings = markers.filter((m) => m.severity === 4).length

  return {
    markers,
    errors,
    warnings,
  }
}

