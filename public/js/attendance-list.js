/*
 * Shared list behaviour for Riwayat Absensi (spec §32) and Monitoring
 * Absensi (spec §33). Both render the same row; monitoring adds the summary
 * tiles and a "hari ini" shortcut.
 */
(function ($, HRIS) {
    'use strict';

    HRIS.attendanceList = function (options) {
        var showEmployee = !!options.showEmployee;
        var isMonitoring = options.mode === 'monitoring';

        var photoModal = document.getElementById('photoModal')
            ? bootstrap.Modal.getOrCreateInstance(document.getElementById('photoModal'))
            : null;

        // Populated from /lookups when the page offers those filters.
        if ($('#locationFilter').length || $('#shiftFilter').length) {
            HRIS.lookups()
                .done(function (data) {
                    if ($('#locationFilter').length) HRIS.fillSelect($('#locationFilter'), data.locations, 'Semua');
                    if ($('#shiftFilter').length) HRIS.fillSelect($('#shiftFilter'), data.shifts, 'Semua');
                })
                .fail(HRIS.handleError);
        }

        function metre(value) {
            return value === null || value === undefined ? '−' : HRIS.formatNumber(value, 1) + ' m';
        }

        function photoCell(item) {
            var photos = item.photos || {};

            if (!photos.CHECK_IN && !photos.CHECK_OUT) {
                return '<span class="text-body-secondary">−</span>';
            }

            return '<button class="btn btn-sm btn-outline-secondary js-photo" ' +
                'data-in="' + HRIS.esc(photos.CHECK_IN || '') + '" ' +
                'data-out="' + HRIS.esc(photos.CHECK_OUT || '') + '" title="Lihat foto">' +
                '<i class="bi bi-image"></i></button>';
        }

        /* Column order must match the <thead> in the history and monitoring
           views; the employee column only exists on the HR-facing ones. */
        var columns = [
            {
                data: 'attendance_date',
                className: 'small text-nowrap',
                render: HRIS.formatDate
            }
        ];

        if (showEmployee) {
            columns.push({
                data: 'employee_name',
                orderable: false,
                render: function (value, type, row) {
                    return '<div class="fw-semibold small">' + HRIS.esc(value) + '</div>' +
                        '<div class="text-body-secondary" style="font-size:.72rem">' +
                        HRIS.esc(row.employee_code) + '</div>';
                }
            });
        }

        columns.push(
            {
                data: 'shift_code',
                orderable: false,
                className: 'small',
                render: function (value) { return HRIS.esc(value || '−'); }
            },
            {
                data: 'location_name',
                orderable: false,
                className: 'small',
                render: function (value) { return HRIS.esc(value || '−'); }
            },
            {
                data: 'check_in_at',
                className: 'text-center text-tabular',
                render: function (value) { return value ? HRIS.formatTime(value) : '−'; }
            },
            {
                data: 'check_out_at',
                className: 'text-center text-tabular',
                render: function (value) { return value ? HRIS.formatTime(value) : '−'; }
            },
            { data: 'check_in_distance', className: 'text-end text-tabular small', render: metre },
            { data: 'check_in_accuracy', className: 'text-end text-tabular small', render: metre },
            {
                data: 'status',
                className: 'text-center',
                render: function (value, type, row) {
                    return HRIS.statusBadge(value) + (row.late_minutes > 0
                        ? '<div class="small text-warning-emphasis">+' + row.late_minutes + ' mnt</div>'
                        : '');
                }
            },
            {
                data: null,
                orderable: false,
                className: 'text-center',
                render: photoCell
            }
        );

        var list = HRIS.crud({
            baseUrl: window.HRIS_URLS.base,
            columns: columns,
            // No create/edit form on these screens; pointing the modal and form
            // selectors at nothing keeps HRIS.crud in read-only mode.
            modalSelector: '#noModal',
            formSelector: '#noForm',
            filters: [
                '#startDate', '#endDate', '#searchInput',
                '#locationFilter', '#shiftFilter', '#statusFilter', '#employeeFilter'
            ].filter(function (selector) { return $(selector).length > 0; }),
            emptyMessage: 'Tidak ada data absensi pada rentang ini.',

            // The summary rides on the same response as the rows (see
            // Controller::withExtra), so the tiles and the table can never
            // describe different filters.
            onLoaded: function (json) {
                if (!isMonitoring || !json.summary) return;

                $('[data-summary]').each(function () {
                    var key = $(this).data('summary');
                    $(this).text(json.summary[key] !== undefined ? json.summary[key] : 0);
                });
            },

            table: {
                // Newest day first, matching the server's default order.
                order: [[0, 'desc']]
            }
        });

        $('#dataTable').on('click', '.js-photo', function () {
            if (!photoModal) return;

            var checkIn = $(this).data('in');
            var checkOut = $(this).data('out');

            $('#photoCheckIn').attr('src', checkIn || '').toggleClass('d-none', !checkIn);
            $('#photoCheckInEmpty').toggleClass('d-none', !!checkIn);

            $('#photoCheckOut').attr('src', checkOut || '').toggleClass('d-none', !checkOut);
            $('#photoCheckOutEmpty').toggleClass('d-none', !!checkOut);

            photoModal.show();
        });

        return list;
    };
})(jQuery, window.HRIS);
