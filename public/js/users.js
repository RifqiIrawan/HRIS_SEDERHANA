/* Master User (spec §9). */
jQuery(function ($) {
    'use strict';

    var employees = [];

    function loadLookups(force) {
        return HRIS.lookups(force)
            .done(function (data) {
                employees = data.employees || [];

                HRIS.fillSelect($('#roleFilter'), data.roles, 'Semua Role');
                HRIS.fillSelect($('#role_id'), data.roles, 'Pilih role…');
            })
            .fail(HRIS.handleError);
    }

    loadLookups();

    /**
     * An employee can own at most one account, so offering one that is already
     * linked only produces a rejected save. Everything taken is dropped from
     * the list, except the row this very user is linked to.
     *
     * @param {?number} currentId    employee the edited user points at
     * @param {?string} currentLabel its label, in case the employee is INACTIVE
     *                               and therefore missing from the lookup
     */
    function fillEmployeeSelect(currentId, currentLabel) {
        var $select = $('#employee_id');

        var free = employees.filter(function (e) {
            return !e.has_user || String(e.id) === String(currentId);
        });

        HRIS.fillSelect($select, free, 'Tidak terhubung');

        if (currentId && !$select.find('option[value="' + currentId + '"]').length) {
            $select.append($('<option></option>').val(currentId).text(currentLabel || '#' + currentId));
        }

        $select.val(currentId || '');

        // An empty dropdown reads as a bug unless it says why it is empty.
        var exhausted = !free.length && !currentId;

        $('.js-employee-hint')
            .toggleClass('text-warning', exhausted)
            .text(exhausted
                ? 'Semua karyawan aktif sudah memiliki akun. Tambahkan data karyawan lebih dulu untuk membuat akun role EMPLOYEE.'
                : 'Wajib diisi untuk akun dengan role EMPLOYEE.');
    }

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
            fillEmployeeSelect(null, null);
        },

        fill: function ($form, item) {
            setPasswordMode(true);
            // Never round-trip a password hash into a form field.
            $form.find('#password, #password_confirmation').val('');
            fillEmployeeSelect(item.employee_id, item.employee_label);
        },

        // A save changes which employees are still free, so the cached lookup
        // payload is stale from here on.
        onSaved: function () {
            loadLookups(true);
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
