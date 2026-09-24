export function pluginTestStatusIcon(status) {
    return { ok: 'i-lucide-circle-check', partial: 'i-lucide-circle-alert' }[status] ?? 'i-lucide-circle-dashed';
}

export function pluginTestStatusClass(status) {
    return { ok: 'text-success', partial: 'text-warning' }[status] ?? 'text-muted';
}
