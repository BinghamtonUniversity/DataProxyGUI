import { diffLines } from 'diff'

export type DiffStatus = 'added' | 'removed' | 'modified' | 'unchanged'

export interface LineStats {
    additions: number
    deletions: number
}

export interface DiffEntry<T = any> extends LineStats {
    key: string
    name: string
    current: T | null
    selected: T | null
    oldCode: string
    newCode: string
    status: DiffStatus
}

export const getCode = (item: any): string => item?.content ?? item?.code ?? ''

/** GitHub-style +/- line counts. old = selected version, new = current version. */
export function lineStats(oldCode = '', newCode = ''): LineStats {
    if (oldCode === newCode) return { additions: 0, deletions: 0 }
    let additions = 0
    let deletions = 0
    for (const part of diffLines(oldCode, newCode)) {
        if (part.added) additions += part.count ?? 0
        else if (part.removed) deletions += part.count ?? 0
    }
    return { additions, deletions }
}

/**
 * Pairs items across two versions by a stable key (name by default) instead of
 * by array index, so reordering or deleting one function doesn't shift every
 * comparison after it. Items only in the selected version show up as "removed".
 */
export function buildDiffEntries<T>(
    current: T[] = [],
    selected: T[] = [],
    getKey: (item: any, index: number) => string = (item, i) => item?.name ?? item?.id ?? `#${i}`,
): DiffEntry<T>[] {
    const selectedByKey = new Map<string, T>()
    selected.forEach((item, i) => selectedByKey.set(getKey(item, i), item))

    const entries: DiffEntry<T>[] = []
    const seen = new Set<string>()

    const push = (key: string, cur: T | null, sel: T | null) => {
        const oldCode = sel ? getCode(sel) : ''
        const newCode = cur ? getCode(cur) : ''
        const stats = lineStats(oldCode, newCode)
        const status: DiffStatus = !sel
            ? 'added'
            : !cur
              ? 'removed'
              : stats.additions || stats.deletions
                ? 'modified'
                : 'unchanged'
        entries.push({
            key,
            name: (cur as any)?.name ?? (sel as any)?.name ?? key,
            current: cur,
            selected: sel,
            oldCode,
            newCode,
            status,
            ...stats,
        })
    }

    current.forEach((item, i) => {
        const key = getKey(item, i)
        seen.add(key)
        push(key, item, selectedByKey.get(key) ?? null)
    })
    selected.forEach((item, i) => {
        const key = getKey(item, i)
        if (!seen.has(key)) push(key, null, item)
    })

    return entries
}

export function sumStats(entries: LineStats[]): LineStats {
    return entries.reduce(
        (acc, e) => ({ additions: acc.additions + e.additions, deletions: acc.deletions + e.deletions }),
        { additions: 0, deletions: 0 },
    )
}

const EXT_LANG: Record<string, string> = {
    js: 'javascript', mjs: 'javascript', ts: 'typescript', py: 'python', php: 'php',
    json: 'json', yml: 'yaml', yaml: 'yaml', md: 'markdown', html: 'html',
    css: 'css', sql: 'sql', xml: 'xml', sh: 'shell', txt: 'plaintext',
}

export function languageForFile(name = ''): string {
    const ext = name.split('.').pop()?.toLowerCase() ?? ''
    return EXT_LANG[ext] ?? 'plaintext'
}