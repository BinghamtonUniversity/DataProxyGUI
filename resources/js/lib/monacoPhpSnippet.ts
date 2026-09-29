import type * as Monaco from 'monaco-editor'

/**
 * PHP highlighting for code that has no `<?php` tag.
 *
 * Monaco's built-in `php` language starts in HTML mode and only switches to PHP
 * after `<?php`. This registers a copy whose tokenizer starts directly in PHP mode,
 * so function bodies stored without the tag are still highlighted.
 *
 * The PHP definition is fetched from Monaco's own language registry instead of a
 * deep import, because its file path changed between versions
 * (esm/vs/basic-languages/php/php.js in <= 0.55, esm/vs/languages/definitions/php/php.js in >= 0.56).
 */
export const PHP_SNIPPET_LANGUAGE = 'php-snippet'

let registration: Promise<void> | null = null

export function registerPhpSnippetLanguage(monaco: typeof Monaco): Promise<void> {
    registration ??= (async () => {
        // `loader` isn't in Monaco's public types, but every built-in language has one
        const php = monaco.languages.getLanguages().find((l) => l.id === 'php') as
            | (Monaco.languages.ILanguageExtensionPoint & { loader?: () => Promise<any> })
            | undefined
        if (!php?.loader) {
            console.warn('Monaco PHP language not found; php-snippet highlighting disabled')
            return
        }

        const { conf, language } = await php.loader()

        monaco.languages.register({ id: PHP_SNIPPET_LANGUAGE })
        monaco.languages.setLanguageConfiguration(PHP_SNIPPET_LANGUAGE, conf)
        monaco.languages.setMonarchTokensProvider(PHP_SNIPPET_LANGUAGE, {
            ...language,
            tokenizer: { ...language.tokenizer, root: [{ include: 'phpRoot' }] },
        })
    })()
    return registration
}

/** Map the app's language names to the Monaco language id to use. */
export const monacoLanguageFor = (lang?: string) => (lang === 'php' ? PHP_SNIPPET_LANGUAGE : lang)

/** Remove a leading `<?php` tag (e.g. if someone pastes one in); the backend doesn't want it. */
export const stripPhpTag = (content: string) =>
    content.trimStart().startsWith('<?php') ? content.trimStart().replace(/^<\?php\s*/, '') : content