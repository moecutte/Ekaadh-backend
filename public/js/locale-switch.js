(function () {
    const KEY = 'ekaadh.localeDraft';
    let restored = false;

    function pageKey() {
        return location.pathname + location.search;
    }

    function cloneValue(value, depth) {
        if (depth > 8) {
            return undefined;
        }
        if (value == null) {
            return value;
        }
        const type = typeof value;
        if (type === 'string' || type === 'number' || type === 'boolean') {
            return value;
        }
        if (type === 'function' || type === 'symbol' || type === 'bigint') {
            return undefined;
        }
        if (typeof File !== 'undefined' && value instanceof File) {
            return undefined;
        }
        if (typeof Blob !== 'undefined' && value instanceof Blob) {
            return undefined;
        }
        if (typeof HTMLElement !== 'undefined' && value instanceof HTMLElement) {
            return undefined;
        }
        if (Array.isArray(value)) {
            return value.map((item) => cloneValue(item, depth + 1));
        }
        if (type === 'object') {
            if (value instanceof Date) {
                return value.toISOString();
            }
            const proto = Object.getPrototypeOf(value);
            if (proto !== Object.prototype && proto !== null) {
                return undefined;
            }
            const out = {};
            Object.keys(value).forEach((key) => {
                if (key.startsWith('$') || key.startsWith('_x_')) {
                    return;
                }
                const cloned = cloneValue(value[key], depth + 1);
                if (cloned !== undefined) {
                    out[key] = cloned;
                }
            });
            return out;
        }
        return undefined;
    }

    function snapshotAlpine() {
        if (!window.Alpine || typeof Alpine.$data !== 'function') {
            return [];
        }
        return Array.from(document.querySelectorAll('[x-data]')).map((el) => {
            try {
                return cloneValue(Alpine.$data(el), 0) || {};
            } catch (e) {
                return {};
            }
        });
    }

    function snapshotFields() {
        const fields = [];
        document.querySelectorAll('input, textarea, select').forEach((el, index) => {
            if (el.closest('[data-locale-toggle]')) {
                return;
            }
            const type = (el.type || el.tagName || '').toLowerCase();
            if (['file', 'password', 'submit', 'button', 'image', 'reset'].includes(type)) {
                return;
            }
            if (el.name === '_token' || el.name === '_method') {
                return;
            }
            fields.push({
                index,
                name: el.name || '',
                id: el.id || '',
                type,
                value: el.value,
                checked: !!el.checked,
            });
        });
        return fields;
    }

    function saveDraft() {
        try {
            sessionStorage.setItem(KEY, JSON.stringify({
                page: pageKey(),
                scrollY: window.scrollY,
                alpine: snapshotAlpine(),
                fields: snapshotFields(),
            }));
        } catch (e) {
            // sessionStorage can be unavailable in private mode
        }
    }

    function applyAlpine(list) {
        if (!window.Alpine || typeof Alpine.$data !== 'function' || !Array.isArray(list)) {
            return;
        }
        const roots = document.querySelectorAll('[x-data]');
        list.forEach((data, i) => {
            const el = roots[i];
            if (!el || !data || typeof data !== 'object') {
                return;
            }
            try {
                const live = Alpine.$data(el);
                Object.keys(data).forEach((key) => {
                    if (typeof live[key] === 'function') {
                        return;
                    }
                    try {
                        live[key] = data[key];
                    } catch (e) {
                        // getter-only computed fields
                    }
                });
            } catch (e) {
                // ignore
            }
        });
    }

    function findField(snapshot, elements) {
        if (snapshot.id) {
            const byId = document.getElementById(snapshot.id);
            if (byId) {
                return byId;
            }
        }
        if (snapshot.name) {
            const matches = elements.filter((el) => el.name === snapshot.name);
            if (snapshot.type === 'radio' || snapshot.type === 'checkbox') {
                const exact = matches.find((el) => el.value === snapshot.value);
                if (exact) {
                    return exact;
                }
            }
            if (matches.length === 1) {
                return matches[0];
            }
        }
        return elements[snapshot.index] || null;
    }

    function applyFields(fields) {
        if (!Array.isArray(fields)) {
            return;
        }
        const elements = Array.from(document.querySelectorAll('input, textarea, select'));
        fields.forEach((snapshot) => {
            const el = findField(snapshot, elements);
            if (!el || el.closest('[data-locale-toggle]')) {
                return;
            }
            const type = (el.type || '').toLowerCase();
            if (type === 'file' || type === 'password') {
                return;
            }
            if (type === 'checkbox' || type === 'radio') {
                el.checked = !!snapshot.checked;
            } else {
                el.value = snapshot.value ?? '';
            }
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    function restoreDraft() {
        if (restored) {
            return;
        }
        let raw;
        try {
            raw = sessionStorage.getItem(KEY);
        } catch (e) {
            return;
        }
        if (!raw) {
            return;
        }
        let draft;
        try {
            draft = JSON.parse(raw);
        } catch (e) {
            return;
        }
        if (!draft || draft.page !== pageKey()) {
            return;
        }
        restored = true;
        try {
            sessionStorage.removeItem(KEY);
        } catch (e) {
            // ignore
        }
        applyAlpine(draft.alpine || []);
        applyFields(draft.fields || []);
        if (typeof draft.scrollY === 'number') {
            window.scrollTo(0, draft.scrollY);
        }
    }

    document.addEventListener('click', (event) => {
        const link = event.target.closest('[data-locale-switch]');
        if (!link) {
            return;
        }
        event.preventDefault();
        saveDraft();
        window.location.href = link.href;
    });

    function hasAlpineRoots() {
        return !!document.querySelector('[x-data]');
    }

    document.addEventListener('alpine:initialized', restoreDraft);

    function restoreWhenReady() {
        if (!hasAlpineRoots()) {
            restoreDraft();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', restoreWhenReady);
    } else {
        restoreWhenReady();
    }

    window.addEventListener('load', () => {
        setTimeout(restoreDraft, 0);
    });
})();
