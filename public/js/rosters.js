/*
 * Shift Roster (spec §15-§17).
 *
 * The grid is employees × dates. Each cell is one shift_rosters row; clicking
 * an existing cell opens it for correction, and the Generate dialog fills a
 * whole range from a repeating pattern.
 */
jQuery(function ($) {
    'use strict';

    var $table = $('#rosterTable');
    var $header = $('#rosterHeader');

    var employees = [];
    var table = null;
    // The date range the current columns were built for, so a rebuild only
    // happens when the range actually moves — not on every page or search.
    var builtRange = null;

    var cellModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('cellModal'));
    var generateModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('generateModal'));
    var editingCellId = null;

    /* ── Lookups ────────────────────────────────────────────────────── */

    HRIS.lookups()
        .done(function (data) {
            employees = data.employees || [];

            HRIS.fillSelect($('#locationFilter'), data.locations, 'Semua Lokasi');
            HRIS.fillSelect($('#gen_location_id'), data.locations, 'Pilih lokasi…');
            HRIS.fillSelect($('#cell_location_id'), data.locations, 'Pilih lokasi…');
            HRIS.fillSelect($('#cell_shift_id'), data.shifts, 'OFF / tidak ada shift');

            renderEmployeeChecklist();
        })
        .fail(HRIS.handleError);

    /* ── Grid ───────────────────────────────────────────────────────── */

    function params() {
        return {
            start_date: $('#startDate').val(),
            end_date: $('#endDate').val(),
            location_id: $('#locationFilter').val() || undefined,
            search: $('#searchInput').val() || undefined
        };
    }

    /**
     * The grid is employees × dates, so its column count changes with the
     * selected range. DataTables fixes its columns at construction, which means
     * a new range needs a new table — but only a new range: paging, sorting and
     * searching all reuse the one that is already built.
     *
     * @param {object[]} dates  [{date, day, weekday, is_weekend}]
     */
    function buildTable(dates) {
        var key = dates.length ? dates[0].date + '|' + dates[dates.length - 1].date : '';

        if (table && builtRange === key) return false;

        builtRange = key;

        $header.html('<th style="min-width:190px">Karyawan</th>' + dates.map(function (d) {
            return '<th class="roster-cell ' + (d.is_weekend ? 'table-warning' : '') + '">' +
                HRIS.esc(d.day) + '<div class="fw-normal text-body-secondary" style="font-size:.65rem">' +
                HRIS.esc(d.weekday) + '</div></th>';
        }).join(''));

        if (table) {
            table.destroy();
            $table.find('tbody').empty();
        }

        var columns = [{
            data: 'employee_name',
            render: function (value, type, row) {
                return '<div class="fw-semibold small">' + HRIS.esc(value) + '</div>' +
                    '<div class="text-body-secondary" style="font-size:.72rem">' +
                    HRIS.esc(row.employee_code) + '</div>';
            }
        }];

        dates.forEach(function (d) {
            columns.push({
                data: null,
                orderable: false,
                className: 'roster-cell p-1',
                render: function (row) { return renderCell(row.cells[d.date], row, d.date); }
            });
        });

        table = HRIS.dataTable($table, {
            ajax: {
                url: window.HRIS_URLS.base,
                dataSrc: function (json) {
                    // The server is the authority on the range: if it resolved a
                    // different one than the columns were built for, rebuild
                    // against its answer and let the new table fetch the rows.
                    if (buildTable(json.dates || [])) return [];

                    return json.data || [];
                }
            },
            params: params,
            language: $.extend({}, HRIS.dtLanguage, {
                emptyTable: 'Tidak ada karyawan yang cocok.',
                info: 'Menampilkan _START_–_END_ dari _TOTAL_ karyawan'
            }),
            columns: columns
        });

        return true;
    }

    function renderCell(cell, row, date) {
        if (!cell) {
            return '<span class="text-body-secondary">−</span>';
        }

        var variant = cell.status === 'SCHEDULED' ? 'primary'
            : cell.status === 'OFF' ? 'secondary' : 'dark';

        var label = cell.shift_code || cell.status;

        var title = cell.status === 'SCHEDULED' && cell.start
            ? cell.start + ' → ' + cell.end + ' @ ' + cell.location_name
            : cell.status + ' @ ' + cell.location_name;

        return '<button type="button" class="btn btn-sm btn-' + variant + ' w-100 py-0 px-1 js-cell"' +
            ' style="font-size:.7rem" data-id="' + cell.id + '"' +
            ' data-employee="' + HRIS.esc(row.employee_name) + '"' +
            ' data-date="' + HRIS.esc(date) + '"' +
            ' title="' + HRIS.esc(title) + '">' + HRIS.esc(label) + '</button>';
    }

    /** Reloads the grid, rebuilding the columns first when the range moved. */
    function load(resetPaging) {
        var dates = datesBetween($('#startDate').val(), $('#endDate').val());

        // A rebuild fetches on its own; only reload when the columns still fit.
        if (!buildTable(dates)) {
            table.ajax.reload(null, resetPaging !== false);
        }
    }

    /**
     * The day columns for a range, derived the same way the server derives them
     * so the first draw needs no extra round-trip to learn its own headers.
     *
     * @return {object[]}
     */
    function datesBetween(start, end) {
        var from = start ? new Date(start + 'T00:00:00') : startOfMonth();
        var to = end ? new Date(end + 'T00:00:00') : endOfMonth();

        if (isNaN(from.getTime()) || isNaN(to.getTime())) return [];
        if (to < from) { var swap = from; from = to; to = swap; }

        var weekdays = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        var out = [];

        for (var d = new Date(from); d <= to; d.setDate(d.getDate() + 1)) {
            out.push({
                date: d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()),
                day: pad(d.getDate()),
                weekday: weekdays[d.getDay()],
                is_weekend: d.getDay() === 0 || d.getDay() === 6
            });
        }

        return out;
    }

    function pad(value) { return (value < 10 ? '0' : '') + value; }

    function startOfMonth() {
        var now = new Date();
        return new Date(now.getFullYear(), now.getMonth(), 1);
    }

    function endOfMonth() {
        var now = new Date();
        return new Date(now.getFullYear(), now.getMonth() + 1, 0);
    }

    load();

    $('#startDate, #endDate, #locationFilter').on('change', function () { load(true); });
    $('#searchInput').on('input', HRIS.debounce(function () { load(true); }, 400));

    /* ── Cell editing ───────────────────────────────────────────────── */

    $table.on('click', '.js-cell', function () {
        var $button = $(this);
        editingCellId = $button.data('id');

        HRIS.api({ url: window.HRIS_URLS.base + '/' + editingCellId })
            .done(function (item) {
                if (item.has_attendance) {
                    HRIS.toast('Jadwal ini sudah memiliki absensi dan tidak dapat diubah.', 'warning');
                    return;
                }

                $('#cellContext').text(item.employee_label + ' · ' + item.roster_date);
                $('#cell_location_id').val(item.location_id);
                $('#cell_shift_id').val(item.shift_id || '');
                $('#cell_status').val(item.status);

                cellModal.show();
            })
            .fail(HRIS.handleError);
    });

    $('#cellForm').on('submit', function (event) {
        event.preventDefault();

        var $save = $('.js-save-cell');
        HRIS.busy($save, true, 'Menyimpan…');

        HRIS.api({
            url: window.HRIS_URLS.base + '/' + editingCellId,
            type: 'POST',
            data: {
                _method: 'PUT',
                location_id: $('#cell_location_id').val(),
                shift_id: $('#cell_shift_id').val(),
                status: $('#cell_status').val()
            }
        })
            .done(function () {
                cellModal.hide();
                HRIS.toast('Jadwal berhasil diperbarui.');
                load(false);
            })
            .fail(function (error) {
                HRIS.toast(error.message, 'danger');
            })
            .always(function () { HRIS.busy($save, false); });
    });

    $('#deleteCell').on('click', function () {
        HRIS.confirm({ title: 'Hapus jadwal', message: 'Hapus jadwal ini?', confirmLabel: 'Ya, hapus' })
            .done(function () {
                HRIS.api({ url: window.HRIS_URLS.base + '/' + editingCellId, type: 'POST', data: { _method: 'DELETE' } })
                    .done(function () {
                        cellModal.hide();
                        HRIS.toast('Jadwal berhasil dihapus.');
                        load(false);
                    })
                    .fail(HRIS.handleError);
            });
    });

    /* ── Generate ───────────────────────────────────────────────────── */

    function renderEmployeeChecklist() {
        var term = ($('#employeeSearch').val() || '').toLowerCase();
        var selected = selectedEmployeeIds();

        var visible = employees.filter(function (e) {
            return !term || e.label.toLowerCase().indexOf(term) !== -1;
        });

        if (!visible.length) {
            $('#employeeList').html('<div class="text-body-secondary small p-2">Tidak ada karyawan yang cocok.</div>');
            return;
        }

        $('#employeeList').html(visible.map(function (e) {
            var checked = selected.indexOf(String(e.id)) !== -1 ? ' checked' : '';
            return '<div class="form-check">' +
                '<input class="form-check-input js-employee" type="checkbox" name="employee_ids[]" ' +
                'value="' + e.id + '" id="emp_' + e.id + '"' + checked + '>' +
                '<label class="form-check-label small" for="emp_' + e.id + '">' + HRIS.esc(e.label) + '</label>' +
                '</div>';
        }).join(''));

        updateSelectedCount();
    }

    function selectedEmployeeIds() {
        return $('#employeeList .js-employee:checked').map(function () { return String(this.value); }).get();
    }

    function updateSelectedCount() {
        $('#selectedCount').text(selectedEmployeeIds().length);
    }

    // Re-rendering the list on search would drop ticks for rows that scroll out
    // of view, so the current selection is carried across renders.
    $('#employeeSearch').on('input', HRIS.debounce(renderEmployeeChecklist, 250));
    $('#employeeList').on('change', '.js-employee', updateSelectedCount);

    $('#selectAllEmployees').on('click', function () {
        $('#employeeList .js-employee').prop('checked', true);
        updateSelectedCount();
    });

    $('#clearEmployees').on('click', function () {
        $('#employeeList .js-employee').prop('checked', false);
        updateSelectedCount();
    });

    function refreshPreview() {
        var pattern = $('#gen_pattern').val();
        var start = $('#gen_start_date').val();
        var end = $('#gen_end_date').val();

        if (!pattern || !start || !end) {
            $('#patternPreview').text('Isi pola dan rentang tanggal untuk melihat pratinjau.');
            return;
        }

        HRIS.api({
            url: window.HRIS_URLS.preview,
            type: 'POST',
            data: { pattern: pattern, start_date: start, end_date: end }
        })
            .done(function (rows) {
                var chips = rows.map(function (r) {
                    var isOff = r.token === 'OFF';
                    return '<span class="badge text-bg-' + (isOff ? 'secondary' : 'primary') + ' me-1 mb-1">' +
                        HRIS.esc(r.date.slice(8)) + ': ' + HRIS.esc(r.token) + '</span>';
                }).join('');

                $('#patternPreview').html(
                    '<div class="mb-1 fw-semibold">Pratinjau ' + rows.length + ' hari pertama:</div>' + chips
                );
            })
            .fail(function (error) {
                $('#patternPreview').text(error.message);
            });
    }

    $('#gen_pattern, #gen_start_date, #gen_end_date').on('change', HRIS.debounce(refreshPreview, 300));

    $('#generateModal').on('show.bs.modal', function () {
        if (!$('#gen_start_date').val()) $('#gen_start_date').val($('#startDate').val());
        if (!$('#gen_end_date').val()) $('#gen_end_date').val($('#endDate').val());
        renderEmployeeChecklist();
        refreshPreview();
    });

    $('#generateForm').on('submit', function (event) {
        event.preventDefault();

        var ids = selectedEmployeeIds();

        if (!ids.length) {
            HRIS.toast('Pilih minimal satu karyawan.', 'warning');
            return;
        }

        var $button = $('.js-generate');
        HRIS.busy($button, true, 'Membuat jadwal…');

        HRIS.api({
            url: window.HRIS_URLS.generate,
            type: 'POST',
            data: {
                employee_ids: ids,
                location_id: $('#gen_location_id').val(),
                start_date: $('#gen_start_date').val(),
                end_date: $('#gen_end_date').val(),
                pattern: $('#gen_pattern').val(),
                overwrite: $('#gen_overwrite').is(':checked') ? 1 : 0
            }
        })
            .done(function (result) {
                generateModal.hide();
                HRIS.toast(
                    result.created + ' jadwal dibuat, ' + result.updated + ' diperbarui, ' +
                    result.skipped + ' dilewati.',
                    'success'
                );

                (result.messages || []).slice(0, 3).forEach(function (message) {
                    HRIS.toast(message, 'warning');
                });

                load(true);
            })
            .fail(function (error) {
                HRIS.showErrors($('#generateForm'), error.errors);
                HRIS.toast(error.message, 'danger');
            })
            .always(function () { HRIS.busy($button, false); });
    });

});
