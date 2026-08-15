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

        function detailCell(item) {
            var photos = item.photos || {};
            var hasPhoto = !!(photos.CHECK_IN || photos.CHECK_OUT);

            // The icon still says at a glance whether the row carries photos,
            // but every row can be opened — the readings behind a row without a
            // photo are exactly the ones worth inspecting.
            return '<button class="btn btn-sm btn-outline-secondary js-detail" title="Lihat detail">' +
                '<i class="bi bi-' + (hasPhoto ? 'image' : 'eye') + '"></i></button>';
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
                // The reverse-geocoded address rides under the location name
                // rather than in a column of its own: it is long, often absent,
                // and only ever read as context for the row it belongs to.
                render: function (value, type, row) {
                    var name = HRIS.esc(value || '−');

                    if (!row.check_in_address) {
                        return name;
                    }

                    return name + '<div class="text-body-secondary" style="font-size:.72rem">' +
                        '<i class="bi bi-geo-alt me-1"></i>' + HRIS.esc(row.check_in_address) + '</div>';
                }
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
                render: detailCell
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

        /* ── Detail modal ───────────────────────────────────────────── */

        function field(name) {
            return $('#photoModal [data-detail="' + name + '"]');
        }

        function text(name, value) {
            field(name).text(value === null || value === undefined || value === '' ? '−' : value);
        }

        /** Secondary sub-line: absent means empty, not a dash. */
        function hint(name, value) {
            field(name).text(value === null || value === undefined ? '' : value);
        }

        /** Working time between the two stamps, as "7 j 25 mnt". */
        function worked(from, to) {
            if (!from || !to) return '';

            var minutes = Math.round(
                (new Date(to.replace(' ', 'T')) - new Date(from.replace(' ', 'T'))) / 60000
            );

            if (isNaN(minutes) || minutes < 0) return '';

            return 'Durasi ' + Math.floor(minutes / 60) + ' j ' + (minutes % 60) + ' mnt';
        }

        /** One of the two check-in / check-out panels, from the listing row. */
        function fillSide(side, row, photoUrl) {
            var prefix = side === 'in' ? 'check_in_' : 'check_out_';
            var stamp = row[prefix + 'at'];

            // A cross-day shift checks out on the following calendar day, so the
            // date is spelled out whenever it is not the attendance date itself.
            text(side + '.time', stamp
                ? (stamp.slice(0, 10) === row.attendance_date ? '' : HRIS.formatDate(stamp) + ' ') + HRIS.formatTime(stamp)
                : '−');
            text(side + '.distance', metre(row[prefix + 'distance']));
            text(side + '.accuracy', metre(row[prefix + 'accuracy']));
            text(side + '.address', row[prefix + 'address']);
            // Filled once the detail response lands; the listing has no coordinates.
            text(side + '.coordinates', '…');

            field(side + '.photo')
                .attr('src', photoUrl || '')
                .toggleClass('d-none', !photoUrl);
            field(side + '.photo_empty').toggleClass('d-none', !!photoUrl);
        }

        function fillCoordinates(side, latitude, longitude) {
            var $cell = field(side + '.coordinates');

            if (latitude === null || latitude === undefined || longitude === null || longitude === undefined) {
                $cell.text('−');
                return;
            }

            var pair = Number(latitude).toFixed(6) + ', ' + Number(longitude).toFixed(6);

            $cell.html(
                '<a href="https://www.google.com/maps?q=' + encodeURIComponent(latitude + ',' + longitude) + '" ' +
                'target="_blank" rel="noopener" title="Buka di Google Maps">' + HRIS.esc(pair) +
                ' <i class="bi bi-box-arrow-up-right"></i></a>'
            );
        }

        // Guards against a slow response for a row the user has already left.
        var detailRequestId = null;

        $('#dataTable').on('click', '.js-detail', function () {
            if (!photoModal) return;

            var row = list.table.row($(this).closest('tr')).data();
            if (!row) return;

            var photos = row.photos || {};

            text('date', HRIS.formatDate(row.attendance_date));
            text('employee_name', row.employee_name);
            hint('employee_code', row.employee_code);
            $('#photoModal [data-detail-block="employee"]').toggleClass('d-none', !showEmployee);

            text('shift_code', row.shift_code);
            hint('shift_name', row.shift_name);
            text('location_name', row.location_name);
            hint('radius', '');

            field('status').html(HRIS.statusBadge(row.status));
            hint('duration', worked(row.check_in_at, row.check_out_at));

            fillSide('in', row, photos.CHECK_IN);
            fillSide('out', row, photos.CHECK_OUT);

            photoModal.show();

            // Coordinates and the geofence radius only exist on the detail
            // endpoint, which the listing deliberately does not carry.
            detailRequestId = row.id;

            HRIS.api({ url: window.HRIS_URLS.detail + '/' + row.id })
                .done(function (detail) {
                    if (detailRequestId !== row.id) return;

                    fillCoordinates('in', detail.check_in_latitude, detail.check_in_longitude);
                    fillCoordinates('out', detail.check_out_latitude, detail.check_out_longitude);

                    hint('radius', detail.location
                        ? 'Radius ' + HRIS.formatNumber(detail.location.radius_meter) + ' m'
                        : '');
                })
                .fail(function () {
                    if (detailRequestId !== row.id) return;

                    text('in.coordinates', '−');
                    text('out.coordinates', '−');
                });
        });

        return list;
    };
})(jQuery, window.HRIS);
