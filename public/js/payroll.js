/*
 * Proses & Daftar Payroll (spec §38, §41-§44).
 *
 * All arithmetic shown here comes from the server. The browser only formats it,
 * so a stale page can never present a number payroll never computed.
 */
jQuery(function ($) {
    'use strict';

    var period = null;
    var currentPayroll = null;

    var deductionModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('deductionModal'));

    /* ── Table ──────────────────────────────────────────────────────── */

    /* Server-side: the period header and the totals arrive on the same response
       as the rows, so the tiles above the table can never describe a different
       period than the rows below it. */
    var table = HRIS.dataTable('#payrollTable', {
        // Nothing to fetch until a period is chosen.
        deferLoading: 0,
        ajax: {
            url: window.HRIS_URLS.base,
            dataSrc: function (json) {
                period = json.period;

                renderPeriod(json);
                renderTotals(json.summary);

                return json.data || [];
            }
        },
        params: function () {
            return { search: $('#searchInput').val() || undefined };
        },
        language: $.extend({}, HRIS.dtLanguage, {
            emptyTable: 'Belum ada baris payroll pada periode ini.'
        }),
        columns: [
            {
                data: 'employee_name',
                render: function (value, type, row) {
                    return '<div class="fw-semibold small">' + HRIS.esc(value) + '</div>' +
                        '<div class="text-body-secondary" style="font-size:.72rem">' +
                        HRIS.esc(row.employee_code) + '</div>';
                }
            },
            { data: 'present_days', orderable: false, className: 'text-center text-tabular' },
            { data: 'late_days', orderable: false, className: 'text-center text-tabular' },
            { data: 'working_days', className: 'text-center text-tabular fw-semibold' },
            { data: 'daily_rate', className: 'text-end text-tabular small', render: HRIS.formatRupiah },
            { data: 'gross_salary', className: 'text-end text-tabular', render: HRIS.formatRupiah },
            {
                data: 'total_deduction',
                className: 'text-end text-tabular',
                render: function (value) {
                    return value > 0
                        ? '<span class="text-danger">− ' + HRIS.formatRupiah(value) + '</span>'
                        : '−';
                }
            },
            { data: 'net_salary', className: 'text-end text-tabular fw-semibold', render: HRIS.formatRupiah },
            {
                data: null,
                orderable: false,
                className: 'text-end',
                render: function (row) {
                    var buttons = '';

                    // Deductions only while the period is open; printing stays
                    // available after it closes, which is when slips go out.
                    if (period && period.editable) {
                        buttons += '<button type="button" class="btn btn-sm btn-icon js-deduction" data-id="' +
                            row.id + '" title="Kelola potongan" aria-label="Kelola potongan">' +
                            '<i class="bi bi-dash-circle"></i></button>';
                    }

                    if (window.HRIS_URLS.slip) {
                        buttons += '<button type="button" class="btn btn-sm btn-icon js-slip" data-id="' +
                            row.id + '" title="Cetak slip gaji" aria-label="Cetak slip gaji ' +
                            HRIS.esc(row.employee_name) + '"><i class="bi bi-printer"></i></button>';
                    }

                    return buttons ? HRIS.actionGroup(buttons) : '<span class="text-body-secondary small">−</span>';
                }
            }
        ]
    });

    /* ── Period selector ────────────────────────────────────────────── */

    HRIS.lookups()
        .done(function (data) {
            var periods = (data.periods || []).map(function (p) {
                return { id: p.id, code: p.code, label: p.label + ' (' + p.status + ')' };
            });

            HRIS.fillSelect($('#periodSelect'), periods, periods.length ? null : 'Belum ada periode');

            if (window.HRIS_SELECTED_PERIOD) $('#periodSelect').val(window.HRIS_SELECTED_PERIOD);

            if ($('#periodSelect').val()) load(true);
        })
        .fail(HRIS.handleError);

    $('#periodSelect').on('change', function () { load(true); });
    $('#searchInput').on('input', HRIS.debounce(function () { load(true); }, 400));

    /**
     * Points the table at the selected period and draws it.
     *
     * @param {boolean} [resetPaging]
     */
    function load(resetPaging) {
        var periodId = $('#periodSelect').val();
        if (!periodId) return;

        table.ajax.url(window.HRIS_URLS.base + '/' + periodId).load(null, resetPaging !== false);
    }

    function renderPeriod(json) {
        if (!period) return;

        var editable = period.editable;
        var processed = period.status === 'PROCESSED';

        $('#generateButton').prop('disabled', !editable);
        $('#closeButton').prop('disabled', !processed);
        $('#reopenButton').toggleClass('d-none', !(window.HRIS_URLS.isAdmin && period.status === 'CLOSED'));

        var message = period.period_name + ' · ' +
            HRIS.formatDate(period.start_date) + ' — ' + HRIS.formatDate(period.end_date) +
            ' · status ' + period.status;

        if (!editable) {
            message += ' — periode terkunci, jalankan "Buka Kembali" untuk menghitung ulang.';
        } else if (!json.recordsTotal) {
            message += ' — belum digenerate. Klik "Generate Payroll".';
        }

        $('#periodInfo').removeClass('d-none').text(message);
    }

    function renderTotals(summary) {
        $('[data-total]').each(function () {
            var key = $(this).data('total');
            var value = summary ? summary[key] : 0;

            $(this).text($(this).data('money') ? HRIS.formatRupiah(value) : HRIS.formatNumber(value));
        });
    }

    /* ── Actions ────────────────────────────────────────────────────── */

    $('#generateButton').on('click', function () {
        var $button = $(this);

        HRIS.confirm({
            title: 'Generate payroll',
            message: 'Hitung ulang payroll untuk semua karyawan aktif pada periode ' + period.period_name +
                '? Potongan yang sudah diinput tetap dipertahankan.',
            confirmLabel: 'Ya, generate',
            variant: 'primary'
        }).done(function () {
            HRIS.busy($button, true, 'Menghitung…');

            HRIS.api({ url: window.HRIS_URLS.base + '/' + period.id + '/generate', type: 'POST' })
                .done(function (summary) {
                    HRIS.toast('Payroll digenerate untuk ' + summary.employees + ' karyawan.');
                    load();
                })
                .fail(function (error) { HRIS.toast(error.message, 'danger'); })
                .always(function () { HRIS.busy($button, false); });
        });
    });

    $('#closeButton').on('click', function () {
        var $button = $(this);

        HRIS.confirm({
            title: 'Tutup periode',
            message: 'Tutup periode ' + period.period_name +
                '? Setelah ditutup payroll tidak dapat dihitung ulang tanpa dibuka kembali oleh ADMIN.',
            confirmLabel: 'Ya, tutup periode'
        }).done(function () {
            HRIS.busy($button, true, 'Menutup…');

            HRIS.api({ url: window.HRIS_URLS.base + '/' + period.id + '/close', type: 'POST' })
                .done(function () {
                    HRIS.toast('Periode payroll ditutup.');
                    load();
                })
                .fail(function (error) { HRIS.toast(error.message, 'danger'); })
                .always(function () { HRIS.busy($button, false); });
        });
    });

    $('#reopenButton').on('click', function () {
        var $button = $(this);

        HRIS.confirm({
            title: 'Buka kembali periode',
            message: 'Buka kembali periode ' + period.period_name + ' agar dapat dihitung ulang?',
            confirmLabel: 'Ya, buka kembali',
            variant: 'warning'
        }).done(function () {
            HRIS.busy($button, true, 'Membuka…');

            HRIS.api({ url: window.HRIS_URLS.base + '/' + period.id + '/reopen', type: 'POST' })
                .done(function () {
                    HRIS.toast('Periode dibuka kembali.');
                    load();
                })
                .fail(function (error) { HRIS.toast(error.message, 'danger'); })
                .always(function () { HRIS.busy($button, false); });
        });
    });

    /* ── Payslip ─────────────────────────────────────────────────── */

    // A new tab rather than a modal: the slip is meant for the printer, and
    // the print dialog should not drag this page's sidebar and table along.
    $('#payrollTable').on('click', '.js-slip', function () {
        window.open(window.HRIS_URLS.slip.replace('__ID__', $(this).data('id')), '_blank', 'noopener');
    });

    /* ── Deductions ─────────────────────────────────────────────────── */

    $('#payrollTable').on('click', '.js-deduction', function () {
        // The row's data comes from DataTables rather than a local copy, so it
        // is always the payload the current draw was rendered from.
        currentPayroll = table.row($(this).closest('tr')).data();

        if (!currentPayroll) return;

        $('#deductionContext').text(
            currentPayroll.employee_code + ' — ' + currentPayroll.employee_name +
            ' · Gross ' + HRIS.formatRupiah(currentPayroll.gross_salary)
        );

        renderDeductions();
        $('#deductionForm')[0].reset();
        deductionModal.show();
    });

    function renderDeductions() {
        var details = currentPayroll.details || [];

        if (!details.length) {
            $('#deductionList').html(
                '<tr><td colspan="3" class="text-center text-body-secondary py-3">Belum ada potongan.</td></tr>'
            );
            return;
        }

        $('#deductionList').html(details.map(function (d) {
            return '<tr>' +
                '<td>' + HRIS.esc(d.description) + '</td>' +
                '<td class="text-end text-tabular">' + HRIS.formatRupiah(d.amount) + '</td>' +
                '<td class="text-end">' +
                '<button type="button" class="btn btn-sm btn-icon btn-icon-danger js-remove-deduction"' +
                ' data-id="' + d.id + '" title="Hapus potongan" aria-label="Hapus potongan">' +
                '<i class="bi bi-x-lg"></i></button></td>' +
                '</tr>';
        }).join(''));
    }

    /**
     * Keeps the modal and the table behind it in step after a deduction changes.
     *
     * The row and the period totals are redrawn from the server rather than
     * recomputed here: with the table paginated, the totals strip covers the
     * whole period and cannot be summed from the rows on screen.
     */
    function applyUpdatedPayroll(updated) {
        currentPayroll = updated;

        renderDeductions();
        load(false);
    }

    $('#deductionForm').on('submit', function (event) {
        event.preventDefault();

        var $button = $('.js-add-deduction');
        HRIS.busy($button, true, '…');

        HRIS.api({
            url: window.HRIS_URLS.base + '/' + currentPayroll.id + '/deduction',
            type: 'POST',
            data: {
                description: $('#deduction_description').val(),
                amount: $('#deduction_amount').val()
            }
        })
            .done(function (updated) {
                HRIS.toast('Potongan ditambahkan.');
                $('#deductionForm')[0].reset();
                applyUpdatedPayroll(updated);
            })
            .fail(function (error) {
                HRIS.showErrors($('#deductionForm'), error.errors);
                HRIS.toast(error.message, 'danger');
            })
            .always(function () { HRIS.busy($button, false); });
    });

    $('#deductionList').on('click', '.js-remove-deduction', function () {
        var id = $(this).data('id');

        HRIS.api({
            url: window.HRIS_URLS.base + '/detail/' + id,
            type: 'POST',
            data: { _method: 'DELETE' }
        })
            .done(function (updated) {
                HRIS.toast('Potongan dihapus.');
                applyUpdatedPayroll(updated);
            })
            .fail(function (error) { HRIS.toast(error.message, 'danger'); });
    });
});
