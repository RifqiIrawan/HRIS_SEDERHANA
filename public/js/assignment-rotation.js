/*
 * Rotating-shift generator on the Assignment screen.
 *
 * The dialog never guesses what the server will do: the preview and the commit
 * post identical payloads to two endpoints backed by the same planner, so the
 * table HR approves is the table that gets written. That matters here because
 * the rotation's starting point is derived server-side from each employee's
 * existing assignment — it is not something the form could work out on its own.
 */
jQuery(function ($) {
    'use strict';

    var $modal = $('#rotationModal');

    // The screen only carries the generator when the markup is present, so a
    // page that reuses assignments.js without it stays untouched.
    if (!$modal.length) return;

    var modal = bootstrap.Modal.getOrCreateInstance($modal[0]);
    var resultModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('rotationResultModal'));

    var employees = [];

    // Lookups returns shifts already ordered by start_time, which is the same
    // order the rotation steps through — so the checkbox order is the cycle
    // order, and no separate sort is needed.
    var shifts = [];

    /* ── Lookups ────────────────────────────────────────────────────── */

    HRIS.lookups()
        .done(function (data) {
            employees = data.employees || [];
            shifts = data.shifts || [];

            HRIS.fillSelect($('#rot_location_id'), data.locations, 'Pilih lokasi…');

            renderShiftPicker();
            renderEmployeeChecklist();
        })
        .fail(HRIS.handleError);

    /* ── Shift picker ───────────────────────────────────────────────── */

    /** Every shift takes part until HR says otherwise. */
    function renderShiftPicker() {
        if (!shifts.length) {
            $('#rotShiftList').html('<span class="text-danger small">' +
                'Belum ada shift aktif. Buat minimal 2 shift di menu Shift.</span>');
            return;
        }

        $('#rotShiftList').html(shifts.map(function (shift) {
            return '<div class="form-check">' +
                '<input class="form-check-input js-rot-shift" type="checkbox" value="' + shift.id +
                '" id="rot_shift_' + shift.id + '" checked>' +
                '<label class="form-check-label small" for="rot_shift_' + shift.id + '">' +
                '<span class="badge text-bg-light border me-1">' + HRIS.esc(shift.code) + '</span>' +
                HRIS.esc(shift.label) + '</label>' +
                '</div>';
        }).join(''));

        syncStartShiftOptions();
    }

    function selectedShiftIds() {
        return $('#rotShiftList .js-rot-shift:checked').map(function () { return String(this.value); }).get();
    }

    /**
     * "Shift awal" may only offer shifts that are actually in the cycle —
     * otherwise a choice outside it would be silently ignored by the planner
     * and the dialog would be lying about what it is going to do.
     */
    function syncStartShiftOptions() {
        var chosen = selectedShiftIds();
        var options = shifts.filter(function (shift) {
            return chosen.indexOf(String(shift.id)) !== -1;
        });

        HRIS.fillSelect($('#rot_start_shift_id'), options, 'Bagi rata otomatis');
    }

    $('#rotShiftList').on('change', '.js-rot-shift', syncStartShiftOptions);

    $('#rotShiftAll').on('click', function () {
        $('#rotShiftList .js-rot-shift').prop('checked', true);
        syncStartShiftOptions();
        refreshPreview();
    });

    $('#rotShiftNone').on('click', function () {
        $('#rotShiftList .js-rot-shift').prop('checked', false);
        syncStartShiftOptions();
    });

    /* ── Employee picker ────────────────────────────────────────────── */

    function renderEmployeeChecklist() {
        var term = ($('#rotEmployeeSearch').val() || '').toLowerCase();
        var selected = selectedIds();

        var visible = employees.filter(function (e) {
            return !term || e.label.toLowerCase().indexOf(term) !== -1;
        });

        if (!visible.length) {
            $('#rotEmployeeList').html('<div class="text-body-secondary small p-2">Tidak ada karyawan yang cocok.</div>');
            return;
        }

        $('#rotEmployeeList').html(visible.map(function (e) {
            var checked = selected.indexOf(String(e.id)) !== -1 ? ' checked' : '';
            return '<div class="form-check">' +
                '<input class="form-check-input js-rot-employee" type="checkbox" value="' + e.id +
                '" id="rot_emp_' + e.id + '"' + checked + '>' +
                '<label class="form-check-label small" for="rot_emp_' + e.id + '">' + HRIS.esc(e.label) + '</label>' +
                '</div>';
        }).join(''));

        updateCount();
    }

    function selectedIds() {
        return $('#rotEmployeeList .js-rot-employee:checked').map(function () { return String(this.value); }).get();
    }

    function updateCount() {
        $('#rotSelectedCount').text(selectedIds().length);
    }

    // Filtering re-renders the list, which would drop ticks that scrolled out
    // of view, so the current selection is carried across renders.
    $('#rotEmployeeSearch').on('input', HRIS.debounce(renderEmployeeChecklist, 250));
    $('#rotEmployeeList').on('change', '.js-rot-employee', updateCount);

    $('#rotSelectAll').on('click', function () {
        $('#rotEmployeeList .js-rot-employee').prop('checked', true);
        updateCount();
    });

    $('#rotClearAll').on('click', function () {
        $('#rotEmployeeList .js-rot-employee').prop('checked', false);
        updateCount();
    });

    /* ── Form state ─────────────────────────────────────────────────── */

    /** Fixed rest days are only meaningful when they were actually chosen. */
    function syncOffControls() {
        var days = parseInt($('#rot_off_days').val(), 10) || 0;
        var fixed = $('#rot_off_mode').val() === 'FIXED';

        $('#rot_off_mode').prop('disabled', days === 0);
        $('#rot_off_weekdays_wrap').toggleClass('d-none', !(fixed && days > 0));
    }

    $('#rot_off_days, #rot_off_mode').on('change', syncOffControls);

    function payload() {
        return {
            employee_ids: selectedIds(),
            location_id: $('#rot_location_id').val(),
            start_date: $('#rot_start_date').val(),
            end_date: $('#rot_end_date').val(),
            cycle_days: $('#rot_cycle_days').val(),
            direction: $('#rot_direction').val(),
            start_shift_id: $('#rot_start_shift_id').val() || '',
            shift_ids: selectedShiftIds(),
            off_days_per_cycle: $('#rot_off_days').val(),
            off_day_mode: $('#rot_off_mode').val(),
            off_weekdays: $('.js-off-weekday:checked').map(function () { return this.value; }).get(),
            replace: $('#rot_replace').is(':checked') ? 1 : 0,
            with_roster: $('#rot_with_roster').is(':checked') ? 1 : 0
        };
    }

    /* ── Rendering ──────────────────────────────────────────────────── */

    /** "2026-08-01" → "01 Agu". */
    function shortDate(value) {
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        var parts = String(value || '').split('-');

        if (parts.length !== 3) return HRIS.esc(value);

        return parts[2] + ' ' + months[parseInt(parts[1], 10) - 1];
    }

    function blockChip(block) {
        var range = shortDate(block.start_date) +
            (block.start_date === block.end_date ? '' : '–' + shortDate(block.end_date));

        var off = (block.off_dates || []).length
            ? ' <span class="text-warning-emphasis">· libur ' +
              block.off_dates.map(shortDate).join(', ') + '</span>'
            : '';

        return '<div class="d-flex align-items-center gap-2 mb-1">' +
            '<span class="badge text-bg-primary" style="min-width:2.6rem">' + HRIS.esc(block.shift_code) + '</span>' +
            '<span class="small">' + range + off + '</span>' +
            '</div>';
    }

    function conflictNote(row) {
        if (!row.conflicts || !row.conflicts.length) return '';

        var parts = row.conflicts.map(function (c) {
            var verb = c.action === 'TRIM' ? 'dipotong' : 'dihapus';
            return HRIS.esc(c.shift_code) + ' ' + shortDate(c.start_date) +
                ' → ' + (c.end_date ? shortDate(c.end_date) : 'seterusnya') + ' (' + verb + ')';
        });

        return '<div class="small text-body-secondary mt-1"><i class="bi bi-exclamation-triangle me-1"></i>' +
            'Assignment lama: ' + parts.join('; ') + '</div>';
    }

    /**
     * One row per employee: where they are now, when they rest, and the shift
     * each cycle lands on. Used for both the preview and the result recap —
     * the payloads have the same shape by design.
     */
    function renderPlan(plan) {
        var rows = (plan.employees || []).map(function (row) {
            var current = row.anchor_shift_code
                ? '<span class="badge text-bg-light border">' + HRIS.esc(row.anchor_shift_code) + '</span>' +
                  '<div class="text-body-secondary" style="font-size:.7rem">sejak ' + shortDate(row.anchor_start_date) + '</div>'
                : '<span class="text-body-secondary small">belum ada</span>';

            var schedule = row.skipped
                ? '<span class="text-danger small">' + HRIS.esc(row.reason || 'Dilewati.') + '</span>'
                : (row.blocks || []).map(blockChip).join('') + conflictNote(row);

            return '<tr' + (row.skipped ? ' class="table-warning"' : '') + '>' +
                '<td><div class="fw-semibold small">' + HRIS.esc(row.employee_name) + '</div>' +
                '<div class="text-body-secondary" style="font-size:.72rem">' + HRIS.esc(row.employee_code) + '</div></td>' +
                '<td class="text-center">' + current + '</td>' +
                '<td class="text-center small">' + HRIS.esc(row.off_label || '-') +
                '<div class="text-body-secondary" style="font-size:.7rem">' +
                (row.work_days || 0) + ' hari kerja</div></td>' +
                '<td>' + schedule + '</td>' +
                '</tr>';
        }).join('');

        var order = (plan.rotation || []).join(' → ');

        return '<div class="small text-body-secondary px-3 py-2 border-bottom">' +
            'Urutan rotasi: <strong>' + HRIS.esc(order) + '</strong>' +
            (plan.direction === 'UP' ? ' (naik)' : ' (turun)') +
            ' · ganti tiap ' + plan.cycle_days + ' hari</div>' +
            '<table class="table table-sm table-hover align-middle mb-0">' +
            '<thead class="table-light"><tr>' +
            '<th style="min-width:150px">Karyawan</th>' +
            '<th class="text-center" style="min-width:90px">Shift saat ini</th>' +
            '<th class="text-center" style="min-width:100px">Libur</th>' +
            '<th style="min-width:240px">Rotasi ' + shortDate(plan.start_date) + ' → ' + shortDate(plan.end_date) + '</th>' +
            '</tr></thead><tbody>' + rows + '</tbody></table>';
    }

    /* ── Preview ────────────────────────────────────────────────────── */

    function refreshPreview() {
        var data = payload();

        if (!data.employee_ids.length || !data.location_id || !data.start_date || !data.end_date) {
            $('#rotationPreview').html('<div class="text-body-secondary small p-3">' +
                'Pilih lokasi, karyawan dan rentang tanggal, lalu perbarui pratinjau.</div>');
            return;
        }

        if (data.shift_ids.length < 2) {
            $('#rotationPreview').html('<div class="text-danger small p-3">' +
                'Pilih minimal 2 shift untuk dirotasi.</div>');
            return;
        }

        $('#rotationPreview').html('<div class="text-body-secondary small p-3">Menyusun pratinjau…</div>');

        HRIS.api({ url: window.HRIS_URLS.rotationPreview, type: 'POST', data: data })
            .done(function (plan) {
                $('#rotationPreview').html(renderPlan(plan));
            })
            .fail(function (error) {
                $('#rotationPreview').html('<div class="text-danger small p-3">' + HRIS.esc(error.message) + '</div>');
            });
    }

    $('#rotRefreshPreview').on('click', refreshPreview);
    $('#rotationForm').on('change',
        '#rot_location_id, #rot_start_date, #rot_end_date, #rot_cycle_days, #rot_direction, ' +
        '#rot_off_days, #rot_off_mode, #rot_start_shift_id, .js-off-weekday',
        HRIS.debounce(refreshPreview, 400));
    $('#rotEmployeeList').on('change', '.js-rot-employee', HRIS.debounce(refreshPreview, 600));
    $('#rotShiftList').on('change', '.js-rot-shift', HRIS.debounce(refreshPreview, 400));

    /* ── Defaults ───────────────────────────────────────────────────── */

    /** Cycles line up with the calendar week when the range opens on a Monday. */
    function nextMonday() {
        var date = new Date();
        date.setHours(0, 0, 0, 0);
        date.setDate(date.getDate() + ((8 - date.getDay()) % 7 || 7));

        return date;
    }

    function toInput(date) {
        var month = date.getMonth() + 1;
        var day = date.getDate();

        return date.getFullYear() + '-' + (month < 10 ? '0' : '') + month + '-' + (day < 10 ? '0' : '') + day;
    }

    $modal.on('show.bs.modal', function () {
        if (!$('#rot_start_date').val()) {
            var start = nextMonday();
            var end = new Date(start);
            // Four cycles is the span HR usually schedules in one go.
            end.setDate(end.getDate() + 27);

            $('#rot_start_date').val(toInput(start));
            $('#rot_end_date').val(toInput(end));
        }

        syncOffControls();
        renderEmployeeChecklist();
    });

    /* ── Generate ───────────────────────────────────────────────────── */

    $('#rotationForm').on('submit', function (event) {
        event.preventDefault();

        var data = payload();

        if (!data.employee_ids.length) {
            HRIS.toast('Pilih minimal satu karyawan.', 'warning');
            return;
        }

        // jQuery drops an empty array from the payload entirely, which the
        // server would read as "no selection = every shift" — the opposite of
        // what unticking them all means. So it is caught here.
        if (data.shift_ids.length < 2) {
            HRIS.toast('Pilih minimal 2 shift untuk dirotasi.', 'warning');
            return;
        }

        var $button = $('.js-rotate');
        HRIS.busy($button, true, 'Membuat jadwal…');
        HRIS.clearErrors($('#rotationForm'));

        HRIS.api({ url: window.HRIS_URLS.rotation, type: 'POST', data: data })
            .done(function (result) {
                modal.hide();

                $('#rotationResultSummary').text(
                    result.assignments_created + ' assignment · ' +
                    (result.rosters_created + result.rosters_updated) + ' jadwal harian · ' +
                    result.off_created + ' hari libur · ' +
                    result.employees_done + ' karyawan · ' +
                    result.location_name
                );

                $('#rotationResultBody').html(renderPlan(result));
                resultModal.show();

                HRIS.toast(
                    result.assignments_created + ' assignment dibuat untuk ' +
                    result.employees_done + ' karyawan.',
                    'success'
                );

                (result.messages || []).slice(0, 3).forEach(function (message) {
                    HRIS.toast(message, 'warning');
                });

                // The list is sorted newest-first, so the fresh rows land on
                // page one — worth going back to.
                if ($.fn.DataTable.isDataTable('#dataTable')) {
                    $('#dataTable').DataTable().ajax.reload(null, true);
                }
            })
            .fail(function (error) {
                HRIS.showErrors($('#rotationForm'), error.errors);
                HRIS.toast(error.message, 'danger');
            })
            .always(function () { HRIS.busy($button, false); });
    });
});
