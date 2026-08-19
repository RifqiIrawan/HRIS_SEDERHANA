/* Assignment (spec §18). */
jQuery(function ($) {
    'use strict';

    // Filters and form selects share one lookup response.
    HRIS.lookups()
        .done(function (data) {
            HRIS.fillSelect($('#locationFilter'), data.locations, 'Semua Lokasi');
            HRIS.fillSelect($('#shiftFilter'), data.shifts, 'Semua Shift');

            HRIS.fillSelect($('#employee_id'), data.employees, 'Pilih karyawan…');
            HRIS.fillSelect($('#location_id'), data.locations, 'Pilih lokasi…');
            HRIS.fillSelect($('#shift_id'), data.shifts, 'Pilih shift…');
        })
        .fail(HRIS.handleError);

    // Exposed so the Detail Shift dialog can open one cycle for editing: the
    // list row is a whole rotation now, so a single assignment can only be
    // reached from the breakdown.
    window.HRIS_ASSIGNMENT_CRUD = HRIS.crud({
        baseUrl: window.HRIS_URLS.base,
        filters: ['#searchInput', '#locationFilter', '#shiftFilter', '#statusFilter'],
        labels: { create: 'Tambah Assignment', edit: 'Ubah Assignment' },
        defaults: { status: 'ACTIVE' },
        emptyMessage: 'Belum ada assignment.',

        // One row per employee, not per assignment: the shift shown is the one
        // the rotation opens on and the dates span the whole run, so the fields
        // describing a single cycle are summarised rather than listed.
        columns: [
            {
                data: 'employee_name',
                render: function (value, type, row) {
                    return '<div class="fw-semibold">' + HRIS.esc(value) + '</div>' +
                        '<div class="small text-body-secondary">' + HRIS.esc(row.employee_code) + '</div>';
                }
            },
            { data: 'location_name', orderable: false, render: HRIS.esc },
            {
                data: 'shift_name',
                orderable: false,
                render: function (value, type, row) {
                    // The badge is the shift the rotation opens on; the line
                    // under it names the cycle it belongs to, so a summarised
                    // row never reads as a single fixed assignment.
                    var cycles = row.cycles > 1
                        ? '<div class="small text-body-secondary">' + row.cycles + ' siklus · ' +
                          HRIS.esc((row.rotation || []).join(' → ')) + '</div>'
                        : '';

                    return '<span class="badge text-bg-primary">' + HRIS.esc(row.shift_code) + '</span> ' +
                        HRIS.esc(value) + cycles;
                }
            },
            {
                data: 'start_date',
                className: 'text-center small',
                render: HRIS.formatDate
            },
            {
                data: 'end_date',
                className: 'text-center small',
                render: function (value) {
                    return value
                        ? HRIS.formatDate(value)
                        : '<span class="text-body-secondary">seterusnya</span>';
                }
            },
            { data: 'status', orderable: false, className: 'text-center', render: HRIS.statusBadge },
            {
                data: null,
                orderable: false,
                className: 'text-end text-nowrap',
                render: function (row) {
                    // Both actions are employee-scoped, because the row is. A
                    // per-cycle edit lives inside Detail Shift, where there is
                    // a single assignment to point at.
                    return '<button class="btn btn-sm btn-outline-info js-shift-detail me-1"' +
                        ' data-employee="' + row.employee_id + '"' +
                        ' data-name="' + HRIS.esc(row.employee_name) + '"' +
                        ' data-code="' + HRIS.esc(row.employee_code) + '"' +
                        ' data-start="' + HRIS.esc(row.start_date) + '"' +
                        ' title="Detail Shift"><i class="bi bi-calendar-week me-1"></i>Detail Shift</button>' +
                        '<button class="btn btn-sm btn-outline-danger js-delete-rotation"' +
                        ' data-employee="' + row.employee_id + '"' +
                        ' data-name="' + HRIS.esc(row.employee_name) + '"' +
                        ' data-cycles="' + row.cycles + '"' +
                        ' title="Hapus semua assignment"><i class="bi bi-trash"></i></button>';
                }
            }
        ]
    });

    /**
     * Deleting a summary row deletes the cycles it summarises — anything less
     * would leave rows behind under a heading that no longer describes them, so
     * the confirmation names the count instead of saying "data ini".
     */
    $('#dataTable').on('click', '.js-delete-rotation', function () {
        var $button = $(this);
        var cycles = parseInt($button.data('cycles'), 10) || 0;

        HRIS.confirm({
            title: 'Hapus assignment',
            message: 'Hapus ' + cycles + ' assignment milik ' + $button.data('name') +
                ' beserta jadwal hariannya di Shift Roster? ' +
                'Hari yang sudah memiliki absensi tidak akan dihapus.',
            confirmLabel: 'Ya, hapus'
        }).done(function () {
            HRIS.api({
                url: window.HRIS_URLS.employeeShifts + '/' + $button.data('employee'),
                type: 'POST',
                data: { _method: 'DELETE' }
            })
                .done(function (result) {
                    HRIS.toast(
                        result.assignments + ' assignment dan ' + result.rosters +
                        ' jadwal harian dihapus.'
                    );

                    if (result.kept_with_attendance > 0) {
                        HRIS.toast(
                            result.kept_with_attendance + ' hari dipertahankan karena sudah ada absensi.',
                            'warning'
                        );
                    }

                    window.HRIS_ASSIGNMENT_CRUD.reload();
                })
                .fail(HRIS.handleError);
        });
    });
});
