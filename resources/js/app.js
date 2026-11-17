import './bootstrap';
import './datepicker';

import Alpine from 'alpinejs';
import $ from 'jquery';
import toastr from 'toastr';
import 'toastr/build/toastr.min.css';
import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';

window.Alpine = Alpine;
window.$ = window.jQuery = $;
window.toastr = toastr;

toastr.options = {
    closeButton: true,
    progressBar: true,
    positionClass: 'toast-bottom-center',
    timeOut: 4000,
    extendedTimeOut: 1500,
    newestOnTop: true,
    preventDuplicates: true,
    showMethod: 'slideDown',
    hideMethod: 'fadeOut',
};

Alpine.store('iaProgress', { value: 0, visible: false });

const setProgress = (value, visible = true) => {
    if (window.iaSetProgress) window.iaSetProgress(value, visible);

    const store = Alpine.store('iaProgress');
    store.value = value;
    store.visible = visible;
    window.dispatchEvent(new CustomEvent('ia-progress', { detail: { value, visible } }));
};

document.addEventListener('livewire:init', () => {
    window.Livewire.on('notify', (payload) => {
        const data = Array.isArray(payload) ? payload[0] : payload;
        const type = ['success', 'error', 'warning', 'info'].includes(data?.type) ? data.type : 'info';
        toastr[type](data?.message ?? '', data?.title ?? '');
    });

    window.Livewire.hook('commit', ({ succeed, fail }) => {
        setProgress(8);
        let value = 8;
        const timer = setInterval(() => {
            value = Math.min(92, value + Math.ceil(Math.random() * 12));
            setProgress(value);
        }, 220);

        succeed(() => {
            clearInterval(timer);
            setProgress(100);
            setTimeout(() => setProgress(0, false), 500);
        });

        fail(() => {
            clearInterval(timer);
            setProgress(0, false);
        });
    });
});

Alpine.data('tiptapEditor', (config = {}) => ({
    editor: null,

    init() {
        this.editor = new Editor({
            element: this.$refs.editor,
            extensions: [
                StarterKit,
                Link.configure({ openOnClick: false }),
                Placeholder.configure({ placeholder: config.placeholder ?? 'Tulis di sini...' }),
            ],
            content: config.content ?? '',
            onUpdate: () => {
                const html = this.editor.getHTML();
                if (this.$refs.input) {
                    this.$refs.input.value = html;
                    this.$refs.input.dispatchEvent(new Event('input', { bubbles: true }));
                }
            },
        });
    },

    destroy() {
        this.editor?.destroy();
    },
}));


/**
 * Copy the header text into each cell so the mobile card layout can show
 * a label without changing every Blade table by hand.
 */
const labelTableCells = () => {
    document.querySelectorAll('table.table-view').forEach((table) => {
        const headers = Array.from(table.querySelectorAll('thead th')).map((header) => header.textContent.trim());

        table.querySelectorAll('tbody tr').forEach((row) => {
            const cells = Array.from(row.querySelectorAll('td'));

            if (cells.length === 1 && cells[0].hasAttribute('colspan')) {
                cells[0].classList.add('table-empty');

                return;
            }

            cells.forEach((cell, index) => {
                if (!cell.hasAttribute('colspan') && headers[index]) {
                    cell.dataset.label = headers[index];
                }
            });
        });
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', labelTableCells);
} else {
    labelTableCells();
}

Alpine.data('userForm', (config) => ({
    department: config.department ?? '',
    unit: config.unit ?? '',
    position: config.position ?? '',
    role: config.role ?? '',
    supervisor: config.supervisor ?? '',
    departments: config.departments,
    units: config.units,
    positions: config.positions,
    supervisors: config.supervisors,
    roleLevels: config.roleLevels,

    init() {
        this.departmentChanged();
        this.roleChanged();
    },

    get roleLevel() {
        return Number(this.roleLevels[this.role] ?? -1);
    },

    /** Admin and direksi sit above the organization, so they need no supervisor. */
    get isTopLevel() {
        return this.role !== '' && this.roleLevel <= 1;
    },

    get supervisorRequired() {
        return this.role !== '' && !this.isTopLevel;
    },

    /** General manager, secretary and every role below them can be assigned a department. */
    get needsDepartment() {
        return this.role !== '' && this.roleLevel >= 2;
    },

    /** Manager, supervisor and every role below them can be placed inside a unit. */
    get unitEnabled() {
        return this.role !== '' && this.roleLevel >= 3;
    },

    get availableUnits() {
        return this.department
            ? this.units.filter((unit) => String(unit.department_id) === String(this.department))
            : [];
    },

    get availablePositions() {
        if (!this.role) {
            return [];
        }

        // General manager and secretary positions are company-wide or tied to a department.
        if (this.roleLevel === 2) {
            return this.positions.filter((position) => position.level === 2
                && !position.unit_id
                && (!position.department_id || String(position.department_id) === String(this.department)));
        }

        // Managers keep their department-level position even when placed in a unit.
        if (this.roleLevel === 3) {
            return this.department
                ? this.positions.filter((position) => position.level === 3
                    && String(position.department_id) === String(this.department)
                    && (!position.unit_id || String(position.unit_id) === String(this.unit)))
                : [];
        }

        if (this.unit) {
            return this.positions.filter((position) => position.level === this.roleLevel && String(position.unit_id) === String(this.unit));
        }

        if (this.department) {
            return this.positions.filter((position) => position.level === this.roleLevel && String(position.department_id) === String(this.department) && !position.unit_id);
        }

        return this.positions.filter((position) => position.level === this.roleLevel && !position.department_id && !position.unit_id);
    },

    /** Only the immediate superior role in the selected branch: staff → supervisor, supervisor → manager, manager → GM, GM → direksi. */
    get availableSupervisors() {
        if (!this.supervisorRequired) {
            return [];
        }

        const targetLevel = this.roleLevel - 1;
        const allowedLevels = targetLevel >= 2
            ? [targetLevel, targetLevel - 1]
            : (targetLevel >= 1 ? [targetLevel] : []);

        return this.supervisors.filter((supervisor) => {
            if (!allowedLevels.includes(supervisor.level)) {
                return false;
            }

            if (supervisor.level === 1) {
                return !supervisor.department_id && !supervisor.unit_id;
            }

            if (supervisor.level === 2) {
                return !supervisor.unit_id
                    && (!supervisor.department_id || String(supervisor.department_id) === String(this.department));
            }

            if (supervisor.level === 3) {
                return this.department
                    && String(supervisor.department_id) === String(this.department);
            }

            return String(supervisor.unit_id) === String(this.unit);
        });
    },

    departmentChanged() {
        if (!this.needsDepartment) {
            this.department = '';
        }

        if (!this.availableUnits.some((unit) => String(unit.id) === String(this.unit))) {
            this.unit = '';
        }

        this.unitChanged();
    },

    unitChanged() {
        if (!this.unitEnabled) {
            this.unit = '';
        }

        this.syncPosition();
        this.supervisorChanged();
    },

    roleChanged() {
        this.departmentChanged();
        this.syncPosition();
        this.supervisorChanged();
    },

    /** Keep the selected position only while it still matches the chosen organization. */
    syncPosition() {
        if (!this.availablePositions.some((position) => String(position.id) === String(this.position))) {
            this.position = '';
        }
    },

    /** Currently selected supervisor object (for display in the custom dropdown trigger). */
    get selectedSupervisor() {
        return this.availableSupervisors.find((supervisor) => String(supervisor.id) === String(this.supervisor)) ?? null;
    },

    supervisorChanged() {
        if (!this.availableSupervisors.some((supervisor) => String(supervisor.id) === String(this.supervisor))) {
            this.supervisor = '';
        }
    },
}));

Alpine.start();
