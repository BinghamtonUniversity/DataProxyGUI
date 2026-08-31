// resources/js/lib/createPhpWorker.ts

const PHP_PARSER_URL = `${window.location.origin}/js-libs/php-parser.min.js`;

const workerSource = `
self.process = { arch: 'x64' }; // minimal stub — php-parser checks process.arch for int overflow limits

importScripts('${PHP_PARSER_URL}');

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
    console.log('PHP worker parsed, errors:', ast && ast.errors); // debug log, safely inside scope

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
    // console.error('PHP worker caught exception:', e);
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

export function createPhpWorker(): Worker {
  const blob = new Blob([workerSource], { type: 'application/javascript' })
  const blobUrl = URL.createObjectURL(blob)
  return new Worker(blobUrl)
}