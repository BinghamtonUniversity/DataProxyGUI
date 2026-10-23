
import { readFile, writeFile } from 'node:fs/promises';
import { resolve } from 'node:path';

const file = resolve(
    'node_modules/monaco-editor/esm/vs/base/browser/dompurify/dompurify.js'
);

const original = await readFile(file, 'utf8');

if (!original.includes("from 'dompurify'")) {
    await writeFile(
        file,
        "export { default } from 'dompurify';\n"
    );
    console.log('Patched Monaco DOMPurify to use installed version');
} else {
    console.log('Monaco DOMPurify already patched');
}
