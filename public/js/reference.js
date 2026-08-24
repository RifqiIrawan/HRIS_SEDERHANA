/*
 * The three Karyawan reference masters (Status Kepegawaian, Tipe Kepegawaian,
 * Status Karyawan). Each has its own URL and table; the screen is identical,
 * so one script drives all three from window.HRIS_REF.
 */
jQuery(function ($) {
    'use strict';

    var ref = window.HRIS_REF || { title: 'Data', entity: 'data' };

    var $code = $('#code');
    var $status = $('#status');
    var $systemNote = $('.js-system-note');

    /**
     * A system row is compared against by literal in PHP, so the form locks the
     * two fields the server would refuse anyway rather than letting the save
     * fail after the fact.
     */
    function setSystemMode(isSystem) {
        $code.prop('readonly', !!isSystem);
        $status.prop('disabled', false);
        $status.find('option[value="INACTIVE"]').prop('disabled', !!isSystem);
        $systemNote.toggleClass('d-none', !isSystem);
    }

    // Typing lower case is the common case; the server upper-cases anyway, so
    // showing it immediately keeps the field honest about what will be stored.
    $code.on('input', function () {
        var start = this.selectionStart;
        $(this).val($(this).val().toUpperCase());
        this.setSelectionRange(start, start);
    });

    HRIS.crud({
        baseUrl: window.HRIS_URLS.base,
        filters: ['#searchInput', '#statusFilter'],
        labels: { create: 'Tambah ' + ref.title, edit: 'Ubah ' + ref.title },
        defaults: { status: 'ACTIVE', sort_order: 0 },
        emptyMessage: 'Belum ada ' + ref.entity + '.',

        onCreate: function () { setSystemMode(false); },
        fill: function ($form, item) { setSystemMode(item.is_system); },

        columns: [
            {
                data: 'code',
                className: 'fw-semibold text-nowrap',
                render: function (value, type, row) {
                    return HRIS.esc(value) + (row.is_system
                        ? ' <i class="bi bi-shield-lock text-body-secondary" title="Baris sistem"></i>'
                        : '');
                }
            },
            { data: 'name', render: HRIS.esc },
            {
                data: 'description',
                orderable: false,
                className: 'small text-body-secondary',
                render: function (value) { return HRIS.esc(value || '−'); }
            },
            { data: 'sort_order', className: 'text-center text-tabular', render: HRIS.esc },
            {
                data: 'used_by',
                orderable: false,
                className: 'text-center text-tabular',
                render: function (value) {
                    return value
                        ? HRIS.esc(value) + ' <span class="text-body-secondary small">karyawan</span>'
                        : '<span class="text-body-secondary">−</span>';
                }
            },
            { data: 'status', className: 'text-center', render: HRIS.statusBadge },
            {
                data: null,
                orderable: false,
                className: 'text-end text-nowrap',
                render: function (row) {
                    // A system row has no delete: the server refuses it, so
                    // offering the button would only produce an error toast.
                    return HRIS.rowActions(row.id, ref.entity + ' ' + row.code, { remove: !row.is_system });
                }
            }
        ]
    });
});
