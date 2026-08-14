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

    HRIS.crud({
        baseUrl: window.HRIS_URLS.base,
        filters: ['#searchInput', '#locationFilter', '#shiftFilter', '#statusFilter'],
        labels: { create: 'Tambah Assignment', edit: 'Ubah Assignment' },
        defaults: { status: 'ACTIVE' },
        emptyMessage: 'Belum ada assignment.',

        // Employee, location and shift live on joined tables, so they are read
        // only here — the backend allow-list deliberately excludes them.
        columns: [
            {
                data: 'employee_name',
                orderable: false,
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
                    return '<span class="badge text-bg-light border">' + HRIS.esc(row.shift_code) + '</span> ' +
                        HRIS.esc(value);
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
            { data: 'status', className: 'text-center', render: HRIS.statusBadge },
            {
                data: null,
                orderable: false,
                className: 'text-end text-nowrap',
                render: function (row) {
                    return HRIS.rowActions(row.id, 'assignment ' + row.employee_code);
                }
            }
        ]
    });
});
