/* Master Shift (spec §14, §17, §34). */
jQuery(function ($) {
    'use strict';

    var $start = $('#start_time');
    var $end = $('#end_time');
    var $crossDay = $('#cross_day');
    var $tolerance = $('#late_tolerance_minutes');
    var $preview = $('#shiftPreview');

    /**
     * Spells out what the entered times actually mean, including the next-day
     * end for a night shift and the exact late threshold from spec §34.
     */
    function renderPreview() {
        var start = $start.val();
        var end = $end.val();

        if (!start || !end) {
            $preview.text('Isi jam mulai dan jam selesai untuk melihat ringkasan.');
            return;
        }

        var crosses = $crossDay.is(':checked');
        var tolerance = parseInt($tolerance.val(), 10) || 0;

        var startMinutes = toMinutes(start);
        var endMinutes = toMinutes(end) + (crosses ? 1440 : 0);
        var duration = endMinutes - startMinutes;

        var threshold = fromMinutes(startMinutes + tolerance);

        $preview.html(
            '<i class="bi bi-info-circle me-1"></i>' +
            'Shift berjalan <strong>' + HRIS.esc(start) + '</strong> → <strong>' + HRIS.esc(end) + '</strong>' +
            (crosses ? ' <span class="badge text-bg-dark">hari berikutnya</span>' : '') +
            ' · durasi <strong>' + formatDuration(duration) + '</strong>' +
            ' · dianggap terlambat setelah <strong>' + HRIS.esc(threshold) + '</strong>.'
        );
    }

    function toMinutes(value) {
        var parts = value.split(':');
        return (parseInt(parts[0], 10) * 60) + parseInt(parts[1], 10);
    }

    function fromMinutes(total) {
        var normalised = ((total % 1440) + 1440) % 1440;
        return pad(Math.floor(normalised / 60)) + ':' + pad(normalised % 60);
    }

    function pad(value) {
        return (value < 10 ? '0' : '') + value;
    }

    function formatDuration(minutes) {
        if (minutes <= 0) return '—';
        return Math.floor(minutes / 60) + ' jam' + (minutes % 60 ? ' ' + (minutes % 60) + ' menit' : '');
    }

    // Auto-tick "lintas hari" the moment the times imply it, so the server-side
    // guard in ShiftRequest rarely has to fire.
    function syncCrossDay() {
        if (!$start.val() || !$end.val()) return;
        $crossDay.prop('checked', $end.val() <= $start.val());
    }

    $start.add($end).on('change', function () {
        syncCrossDay();
        renderPreview();
    });

    $crossDay.add($tolerance).on('change input', renderPreview);

    HRIS.crud({
        baseUrl: window.HRIS_URLS.base,
        filters: ['#searchInput', '#statusFilter'],
        labels: { create: 'Tambah Shift', edit: 'Ubah Shift' },
        defaults: { late_tolerance_minutes: 15, status: 'ACTIVE', cross_day: false },
        emptyMessage: 'Belum ada shift.',

        onCreate: renderPreview,
        fill: function () { renderPreview(); },

        columns: [
            { data: 'shift_code', className: 'fw-semibold', render: HRIS.esc },
            { data: 'shift_name', render: HRIS.esc },
            { data: 'start_time', className: 'text-center text-tabular', render: HRIS.esc },
            { data: 'end_time', className: 'text-center text-tabular', render: HRIS.esc },
            {
                data: 'cross_day',
                orderable: false,
                className: 'text-center',
                render: function (value) {
                    return value
                        ? '<span class="badge text-bg-dark">Ya</span>'
                        : '<span class="text-body-secondary">—</span>';
                }
            },
            {
                data: 'late_tolerance_minutes',
                className: 'text-center text-tabular',
                render: function (value) { return HRIS.esc(value) + ' mnt'; }
            },
            { data: 'status', className: 'text-center', render: HRIS.statusBadge },
            {
                data: null,
                orderable: false,
                className: 'text-end text-nowrap',
                render: function (row) {
                    return HRIS.rowActions(row.id, 'shift ' + row.shift_code);
                }
            }
        ]
    });
});
