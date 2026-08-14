/* Master User (spec §9). */
jQuery(function ($) {
    'use strict';

    HRIS.lookups()
        .done(function (data) {
            HRIS.fillSelect($('#roleFilter'), data.roles, 'Semua Role');
            HRIS.fillSelect($('#role_id'), data.roles, 'Pilih role…');
            HRIS.fillSelect($('#employee_id'), data.employees, 'Tidak terhubung');
        })
        .fail(HRIS.handleError);

    /** On create the password is mandatory; on edit it means "change it". */
    function setPasswordMode(isEdit) {
        $('.js-password-required').toggleClass('d-none', isEdit);
        $('.js-password-hint').toggleClass('d-none', !isEdit);
        $('#password').prop('required', !isEdit);
    }

    HRIS.crud({
        baseUrl: window.HRIS_URLS.base,
        filters: ['#searchInput', '#roleFilter', '#statusFilter'],
        labels: { create: 'Tambah User', edit: 'Ubah User' },
        defaults: { status: 'ACTIVE' },
        emptyMessage: 'Belum ada user.',

        onCreate: function () {
            setPasswordMode(false);
        },

        fill: function ($form) {
            setPasswordMode(true);
            // Never round-trip a password hash into a form field.
            $form.find('#password, #password_confirmation').val('');
        },

        columns: [
            { data: 'name', className: 'fw-semibold', render: HRIS.esc },
            { data: 'email', className: 'small', render: HRIS.esc },
            {
                data: 'role_code',
                orderable: false,
                render: function (value) {
                    return '<span class="badge text-bg-primary">' + HRIS.esc(value || '−') + '</span>';
                }
            },
            {
                data: 'employee_label',
                orderable: false,
                className: 'small',
                render: function (value) { return HRIS.esc(value || '−'); }
            },
            {
                data: 'last_login_at',
                className: 'small text-body-secondary',
                render: function (value) { return HRIS.esc(value || 'Belum pernah'); }
            },
            { data: 'status', className: 'text-center', render: HRIS.statusBadge },
            {
                data: null,
                orderable: false,
                className: 'text-end text-nowrap',
                render: function (row) {
                    return HRIS.rowActions(row.id, 'user ' + row.email);
                }
            }
        ]
    });
});
