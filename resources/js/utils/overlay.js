/**
 * Shared modal helpers: a single body-scroll lock and a stack that makes sure
 * only the topmost dialog reacts to keyboard events (drawer + confirm dialog).
 */

let dialogSequence = 0;
const state = {
    lockCount: 0,
    previousOverflow: '',
    stack: [],
};

export function createDialogId() {
    dialogSequence += 1;
    return `ui-dialog-${dialogSequence}`;
}

export function lockBodyScroll() {
    if (state.lockCount === 0) {
        state.previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
    }
    state.lockCount += 1;
}

export function unlockBodyScroll() {
    state.lockCount = Math.max(0, state.lockCount - 1);
    if (state.lockCount === 0) {
        document.body.style.overflow = state.previousOverflow;
    }
}

export function registerDialog(id, getElement) {
    state.stack.push({ id, getElement });
}

export function unregisterDialog(id) {
    const index = state.stack.findIndex((entry) => entry.id === id);
    if (index !== -1) state.stack.splice(index, 1);
}

export function isTopDialog(id) {
    return state.stack.length > 0 && state.stack[state.stack.length - 1].id === id;
}

export function focusableElements(container) {
    if (!container) return [];
    const selector = [
        'a[href]',
        'button:not([disabled])',
        'input:not([disabled])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])',
    ].join(', ');

    return Array.from(container.querySelectorAll(selector)).filter(
        (element) => !element.hidden && element.getClientRects().length > 0
    );
}

export function focusFirst(container) {
    if (!container) return;
    const target = focusableElements(container)[0] || (container.hasAttribute('tabindex') ? container : null);
    if (target) target.focus({ preventScroll: true });
}

export function trapFocus(event, container) {
    if (event.key !== 'Tab' || !container) return;
    const elements = focusableElements(container);
    if (!elements.length) {
        event.preventDefault();
        container.focus({ preventScroll: true });
        return;
    }

    const first = elements[0];
    const last = elements[elements.length - 1];
    const active = document.activeElement;

    if (!container.contains(active)) {
        event.preventDefault();
        first.focus({ preventScroll: true });
        return;
    }
    if (event.shiftKey && active === first) {
        event.preventDefault();
        last.focus({ preventScroll: true });
        return;
    }
    if (!event.shiftKey && active === last) {
        event.preventDefault();
        first.focus({ preventScroll: true });
    }
}

export function restoreFocus(element) {
    if (!element || typeof element.focus !== 'function' || !document.contains(element)) return;
    element.focus({ preventScroll: true });
}
