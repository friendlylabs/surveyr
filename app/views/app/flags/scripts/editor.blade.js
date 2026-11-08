/**
 * Flag group editor: one modal for both create and edit. Each flag row posts
 * flagKeys[] / flagLabels[] / flagColors[]; the key is blank for new flags and
 * kept for existing ones, so relabelling never orphans flagged submissions.
 */
window.FlagGroupEditor = (function () {
    'use strict';

    const PALETTE = ['#22c55e', '#f59e0b', '#ef4444', '#3b82f6', '#8b5cf6', '#14b8a6', '#ec4899', '#64748b'];

    const form = () => document.getElementById('flagGroupForm');
    const rows = () => document.getElementById('flagRows');

    function esc(s) {
        return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function addRow(flag = {}) {
        const color = flag.color || PALETTE[rows().children.length % PALETTE.length];
        const row = document.createElement('div');
        row.className = 'd-flex align-items-center gap-2';
        row.innerHTML = `
            <input type="hidden" name="flagKeys[]" value="${esc(flag.key)}">
            <input type="color" class="form-control form-control-color flex-shrink-0" name="flagColors[]" value="${esc(color)}" title="Flag colour" style="width: 42px; height: 36px;">
            <input type="text" class="form-control" name="flagLabels[]" value="${esc(flag.label)}" placeholder="Flag label" maxlength="30" required>
            <button type="button" class="btn btn-sm btn-phoenix-secondary px-2" aria-label="Remove flag">
                <i class="fa-solid fa-xmark"></i>
            </button>`;
        row.querySelector('button').addEventListener('click', () => {
            if (rows().children.length > 1) row.remove();
        });
        rows().appendChild(row);
        return row;
    }

    /** Open the modal, empty for a new group or filled from {id, name, description, flags}. */
    function open(group = null) {
        const f = form();
        f.reset();
        rows().innerHTML = '';

        if (group) {
            f.action = f.dataset.update.replace(':id', group.id);
            f.elements.name.value = group.name || '';
            f.elements.description.value = group.description || '';
            (group.flags || []).forEach(addRow);
        } else {
            f.action = f.dataset.store;
            ['Approved', 'Needs review', 'Rejected'].forEach(label => addRow({ label }));
        }
        if (!rows().children.length) addRow();

        document.getElementById('flagGroupModalLabel').textContent = group ? 'Edit flag group' : 'New flag group';
        document.getElementById('flagGroupSubmit').textContent = group ? 'Save changes' : 'Create flag group';

        bootstrap.Modal.getOrCreateInstance(document.getElementById('flagGroupModal')).show();
    }

    return { open, addRow };
})();
