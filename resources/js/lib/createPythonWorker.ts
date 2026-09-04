// resources/js/lib/createPythonWorker.ts

const PYTHON_PARSER_URL = `${window.location.origin}/js-libs/lezer-python-bundle.js`

const workerSource = `
import { parser } from '${PYTHON_PARSER_URL}';

function offsetToLineCol(code, offset) {
  const lines = code.split('\\n');
  let line = 1, col = 1, count = 0;
  for (let i = 0; i < lines.length; i++) {
    if (offset <= count + lines[i].length) {
      line = i + 1;
      col = offset - count + 1;
      break;
    }
    count += lines[i].length + 1;
  }
  return { line, col };
}

self.onmessage = (e) => {
  const { code, requestId } = e.data;
  const markers = [];

  try {
    const tree = parser.parse(code);

    tree.iterate({
      enter: (node) => {
        if (node.type.isError) {
          const start = offsetToLineCol(code, node.from);
          const endOffset = node.to > node.from ? node.to : node.from + 1;
          const end = offsetToLineCol(code, endOffset);
          markers.push({
            startLineNumber: start.line,
            startColumn: start.col,
            endLineNumber: end.line,
            endColumn: end.col,
            message: 'Syntax error',
            severity: 8,
          });
        }
      },
    });
  } catch (err) {
    markers.push({
      startLineNumber: 1,
      startColumn: 1,
      endLineNumber: 1,
      endColumn: 1,
      message: (err && err.message) || 'Python parse error',
      severity: 8,
    });
  }

  self.postMessage({ requestId, markers });
};
`

export function createPythonWorker(): Worker {
  const blob = new Blob([workerSource], { type: 'application/javascript' })
  const blobUrl = URL.createObjectURL(blob)
  return new Worker(blobUrl, { type: 'module' }) // required: worker source uses `import`
}