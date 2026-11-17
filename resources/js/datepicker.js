import flatpickr from 'flatpickr';
import { Indonesian } from 'flatpickr/dist/l10n/id.js';
import monthSelectPlugin from 'flatpickr/dist/plugins/monthSelect/index.js';
import 'flatpickr/dist/flatpickr.min.css';
import 'flatpickr/dist/plugins/monthSelect/style.css';
import '../css/datepicker.css';

const isIndonesian = (document.documentElement.lang || '').toLowerCase().startsWith('id');

const placeholders = isIndonesian
    ? {
        date: 'Pilih tanggal',
        month: 'Pilih bulan',
        time: 'Pilih jam',
        'datetime-local': 'Pilih tanggal & jam',
    }
    : {
        date: 'Select date',
        month: 'Select month',
        time: 'Select time',
        'datetime-local': 'Select date & time',
    };

const configs = {
    date: {
        dateFormat: 'Y-m-d',
        altFormat: 'd-m-Y',
    },
    month: {
        dateFormat: 'Y-m',
        altFormat: 'F Y',
        plugins: [monthSelectPlugin({ shorthand: true, dateFormat: 'Y-m', altFormat: 'F Y' })],
    },
    time: {
        enableTime: true,
        noCalendar: true,
        time_24hr: true,
        dateFormat: 'H:i',
        altFormat: 'H:i',
    },
    'datetime-local': {
        enableTime: true,
        time_24hr: true,
        dateFormat: 'Y-m-d\\TH:i',
        altFormat: 'd-m-Y H:i',
    },
};

/**
 * Upgrade every native date/time input to the same flatpickr experience.
 * Kept global so new forms get the modern picker without per-view setup.
 */
const enhanceInput = (input, type) => {
    if (input.dataset.datepickerReady) {
        return;
    }

    input.dataset.datepickerReady = 'true';

    flatpickr(input, {
        ...configs[type],
        altInput: true,
        altInputClass: `${input.className} datepicker-input datepicker-${type}`,
        locale: isIndonesian ? Indonesian : 'default',
        monthSelectorType: 'dropdown',
        minDate: input.getAttribute('min') || null,
        maxDate: input.getAttribute('max') || null,
        onReady(_, __, instance) {
            if (!instance.altInput) {
                return;
            }

            instance.altInput.placeholder = placeholders[type];

            const label = input.getAttribute('aria-label');
            if (label) {
                instance.altInput.setAttribute('aria-label', label);
            }
        },
    });
};

const enhancePickers = (root = document) => {
    root.querySelectorAll('input[type="date"], input[type="month"], input[type="time"], input[type="datetime-local"]')
        .forEach((input) => enhanceInput(input, input.type));
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => enhancePickers());
} else {
    enhancePickers();
}

window.enhancePickers = enhancePickers;
