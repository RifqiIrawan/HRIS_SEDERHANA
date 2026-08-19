/*
 * "Detail Shift" row action on the Assignment screen.
 *
 * The row you click is one cycle of a rotation, so the dialog deliberately
 * widens to the employee and a whole month rather than showing that single
 * assignment back to you. Two tables sit side by side because they answer
 * different questions and are allowed to disagree: the assignment periods are
 * what the rotation decided, the daily schedule is what each date actually
 * holds — rest days, corrections made on the Shift Roster screen, and days
 * already clocked in on.
 */
jQuery(function ($) {
    'use strict';

    var $modal = $('#shiftDetailModal');

    if (!$modal.length) return;

    var modal = bootstrap.Modal.getOrCreateInstance($modal[0]);
    var employeeId = null;

    var MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    /* ── Dates ──────────────────────────────────────────────────────── */

    function toInput(date) {
        var month = date.getMonth() + 1;
        var day = date.getDate();

        return date.getFullYear() + '-' + (month < 10 ? '0' : '') + month + '-' + (day < 10 ? '0' : '') + day;
    }

    function parseInput(value) {
        var date = new Date(String(value || '') + 'T00:00:00');

        return isNaN(date.getTime()) ? new Date() : date;
    }

    /** "2026-08-01" → "01 Agu". */
    function shortDate(value) {
        var parts = String(value || '').split('-');

        if (parts.length !== 3) return HRIS.esc(value);

        return parts[2] + ' ' + MONTHS[parseInt(parts[1], 10) - 1];
    }

    /** The calendar month a given date falls in, as [start, end] inputs. */
    function monthOf(date) {
        return [
            toInput(new Date(date.getFullYear(), date.getMonth(), 1)),
            toInput(new Date(date.getFullYear(), date.getMonth() + 1, 0))
        ];
    }

    function shiftMonth(offset) {
        var current = parseInput($('#detail_start_date').val());
        var range = monthOf(new Date(current.getFullYear(), current.getMonth() + offset, 1));

        $('#detail_start_date').val(range[0]);
        $('#detail_end_date').val(range[1]);

        load();
    }

    /* ── Rendering ──────────────────────────────────────────────────── */

    function emptyBox(message) {
        return '<div class="text-body-secondary small p-3">' + HRIS.esc(message) + '</div>';
    }

    function renderAssignments(rows) {
        if (!rows.length) return emptyBox('Tidak ada assignment pada rentang ini.');

        var body = rows.map(function (row) {
            var period = shortDate(row.start_date) + ' → ' +
                (row.end_date ? shortDate(row.end_date) : 'seterusnya');

            return '<tr>' +
                '<td><span class="badge text-bg-primary">' + HRIS.esc(row.shift_code) + '</span></td>' +
                '<td class="small">' + period +
                '<div class="text-body-secondary" style="font-size:.72rem">' +
                HRIS.esc(row.location_name) + '</div></td>' +
                '<td class="text-center">' + HRIS.statusBadge(row.status) + '</td>' +
                '<td class="text-end text-nowrap">' +
                '<button class="btn btn-sm btn-outline-secondary js-cycle-edit me-1" data-id="' + row.id +
                '" title="Ubah siklus ini"><i class="bi bi-pencil"></i></button>' +
                '<button class="btn btn-sm btn-outline-danger js-cycle-delete" data-id="' + row.id +
                '" data-label="' + HRIS.esc(row.shift_code + ' ' + period) +
                '" title="Hapus siklus ini"><i class="bi bi-trash"></i></button>' +
                '</td></tr>';
        }).join('');

        return '<table class="table table-sm table-hover align-middle mb-0">' +
            '<thead class="table-light"><tr>' +
            '<th style="width:70px">Shift</th><th>Periode</th>' +
            '<th class="text-center" style="width:80px">Status</th>' +
            '<th style="width:80px"></th>' +
            '</tr></thead><tbody>' + body + '</tbody></table>';
    }

    function renderRosters(rows) {
        if (!rows.length) {
            return emptyBox('Belum ada jadwal harian. Gunakan tombol Generate Shift Harian untuk membuatnya.');
        }

        var body = rows.map(function (row) {
            var isOff = row.status === 'OFF';

            var shift = isOff
                ? '<span class="badge text-bg-secondary">OFF</span>'
                : row.shift_code
                    ? '<span class="badge text-bg-primary">' + HRIS.esc(row.shift_code) + '</span>'
                    : '<span class="badge text-bg-dark">' + HRIS.esc(row.status) + '</span>';

            var hours = isOff || !row.start
                ? '<span class="text-body-secondary">—</span>'
                : HRIS.esc(row.start) + ' → ' + HRIS.esc(row.end);

            var attendance = row.attendance_status
                ? '<span class="badge text-bg-success">' + HRIS.esc(row.attendance_status) + '</span>'
                : '<span class="text-body-secondary">—</span>';

            return '<tr' + (isOff ? ' class="table-light"' : '') + '>' +
                '<td class="small text-nowrap">' + shortDate(row.date) +
                '<div class="text-body-secondary" style="font-size:.72rem">' + HRIS.esc(row.weekday) + '</div></td>' +
                '<td class="text-center">' + shift + '</td>' +
                '<td class="small text-nowrap">' + hours + '</td>' +
                '<td class="small">' + HRIS.esc(row.location_name) + '</td>' +
                '<td class="text-center small">' + attendance + '</td>' +
                '</tr>';
        }).join('');

        return '<table class="table table-sm table-hover align-middle mb-0">' +
            '<thead class="table-light sticky-top"><tr>' +
            '<th style="width:80px">Tanggal</th><th class="text-center" style="width:70px">Shift</th>' +
            '<th style="width:130px">Jam</th><th>Lokasi</th>' +
            '<th class="text-center" style="width:90px">Absensi</th>' +
            '</tr></thead><tbody>' + body + '</tbody></table>';
    }

    function renderSummary(summary) {
        return '<span class="badge text-bg-primary">' + summary.scheduled + ' hari kerja</span>' +
            '<span class="badge text-bg-secondary">' + summary.off + ' libur</span>' +
            '<span class="badge text-bg-success">' + summary.attended + ' absen</span>';
    }

    /* ── Loading ────────────────────────────────────────────────────── */

    function load() {
        if (!employeeId) return;

        $('#shiftDetailAssignments').html(emptyBox('Memuat…'));
        $('#shiftDetailRosters').html(emptyBox('Memuat…'));

        HRIS.api({
            url: window.HRIS_URLS.employeeShifts + '/' + employeeId + '/shifts',
            data: {
                start_date: $('#detail_start_date').val(),
                end_date: $('#detail_end_date').val()
            }
        })
            .done(function (data) {
                $('#shiftDetailName').text('Detail Shift — ' + data.employee.name);
                $('#shiftDetailMeta').text(
                    data.employee.code + ' · ' + shortDate(data.range.start) + ' → ' + shortDate(data.range.end)
                );

                // The server clamps the window, so echo back what it actually
                // read rather than leaving the inputs claiming a wider range.
                $('#detail_start_date').val(data.range.start);
                $('#detail_end_date').val(data.range.end);

                $('#shiftDetailSummary').html(renderSummary(data.summary));
                $('#shiftDetailAssignments').html(renderAssignments(data.assignments || []));
                $('#shiftDetailRosters').html(renderRosters(data.rosters || []));
            })
            .fail(function (error) {
                $('#shiftDetailAssignments').html(emptyBox(error.message));
                $('#shiftDetailRosters').html('');
                $('#shiftDetailSummary').html('');
            });
    }

    /* ── Wiring ─────────────────────────────────────────────────────── */

    // Delegated from the document: DataTables replaces the row markup on every
    // draw, so a handler bound to the buttons themselves would not survive one.
    $(document).on('click', '.js-shift-detail', function () {
        var $button = $(this);

        employeeId = $button.data('employee');

        // Open on the month the clicked cycle starts in — that is the period
        // the user was looking at when they asked for the detail.
        var range = monthOf(parseInput($button.data('start')));

        $('#detail_start_date').val(range[0]);
        $('#detail_end_date').val(range[1]);

        $('#shiftDetailName').text('Detail Shift — ' + $button.data('name'));
        $('#shiftDetailMeta').text($button.data('code'));
        $('#shiftDetailSummary').html('');

        modal.show();
        load();
    });

    /* ── Per-cycle actions ──────────────────────────────────────────── */

    /** Keeps the list behind the dialog in step with what was just changed. */
    function reloadList() {
        if (window.HRIS_ASSIGNMENT_CRUD) window.HRIS_ASSIGNMENT_CRUD.reload();
    }

    // The list row covers a whole rotation, so a single cycle can only be
    // reached from here — these two buttons are the only way to correct one.
    $('#shiftDetailAssignments').on('click', '.js-cycle-edit', function () {
        var id = $(this).data('id');

        // Two modals open at once would leave the backdrop behind when the
        // inner one closes, so the detail steps aside first.
        $modal.one('hidden.bs.modal', function () {
            if (window.HRIS_ASSIGNMENT_CRUD) window.HRIS_ASSIGNMENT_CRUD.openEdit(id);
        });

        modal.hide();
    });

    $('#shiftDetailAssignments').on('click', '.js-cycle-delete', function () {
        var $button = $(this);

        HRIS.confirm({
            title: 'Hapus siklus',
            message: 'Hapus assignment ' + $button.data('label') + '?',
            confirmLabel: 'Ya, hapus'
        }).done(function () {
            HRIS.api({
                url: window.HRIS_URLS.base + '/' + $button.data('id'),
                type: 'POST',
                data: { _method: 'DELETE' }
            })
                .done(function () {
                    HRIS.toast('Assignment berhasil dihapus.');
                    load();
                    reloadList();
                })
                .fail(HRIS.handleError);
        });
    });

    $('#detail_start_date, #detail_end_date').on('change', load);
    $('#detailPrevMonth').on('click', function () { shiftMonth(-1); });
    $('#detailNextMonth').on('click', function () { shiftMonth(1); });
});
