const PHP_PARSER_URL = `${window.location.origin}/js-libs/php-parser.min.js`

let cachedParserCode: string | null = null

async function fetchParserCode(retries = 3): Promise<string> {
  if (cachedParserCode) return cachedParserCode

  for (let attempt = 1; attempt <= retries; attempt++) {
    try {
      const response = await fetch(PHP_PARSER_URL)
      if (!response.ok) {
        throw new Error(`Fetch failed with status ${response.status}`)
      }
      const code = await response.text()
      cachedParserCode = code
      return code
    } catch (err) {
      console.warn(`Attempt ${attempt} to fetch php-parser.min.js failed:`, err)
      if (attempt === retries) throw err
      await new Promise((resolve) => setTimeout(resolve, 300 * attempt)) // backoff
    }
  }

  throw new Error('Failed to fetch php-parser.min.js after retries')
}

export async function createPhpWorker(): Promise<Worker> {
  const parserCode = await fetchParserCode()

  const workerSource = `
self.process = { arch: 'x64' };

${parserCode}

const parser = new PhpParser.Engine({
  parser: {
    extractDoc: true,
    suppressErrors: true,
  },
  ast: {
    withPositions: true,
  },
});

self.onmessage = (e) => {
  const { code, requestId } = e.data;
  const hasOpenTag = /<\\?php|<\\?/.test(code);
  const codeToParse = hasOpenTag ? code : '<?php\\n' + code;
  const lineOffset = hasOpenTag ? 0 : -1;

  const markers = [];

  try {
    const ast = parser.parseCode(codeToParse, 'editor.php');
    if (ast && ast.errors && ast.errors.length) {
      ast.errors.forEach((err) => {
        const line = Math.max((err.line || 1) + lineOffset, 1);
        markers.push({
          startLineNumber: line,
          startColumn: (err.column || 0) + 1,
          endLineNumber: line,
          endColumn: (err.column || 0) + 20,
          message: err.message,
          severity: 8,
        });
      });
    }
  } catch (e) {
    markers.push({
      startLineNumber: 1,
      startColumn: 1,
      endLineNumber: 1,
      endColumn: 1,
      message: (e && e.message) || 'PHP parse error',
      severity: 8,
    });
  }

  self.postMessage({ requestId, markers });
};
`

  const blob = new Blob([workerSource], { type: 'application/javascript' })
  const blobUrl = URL.createObjectURL(blob)
  return new Worker(blobUrl)
}