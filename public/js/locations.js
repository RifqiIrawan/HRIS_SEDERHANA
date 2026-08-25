/*
 * Master Lokasi (spec §12, §13).
 *
 * The Leaflet map here is a *picker*, not a validator: whatever point HR drops
 * becomes the location's stored coordinate, and every later geofence decision
 * is made by the backend against that stored value (spec §13).
 */
jQuery(function ($) {
    'use strict';

    var map = null;
    var marker = null;
    var circle = null;

    var $modal = $('#dataModal');
    var $latitude = $('#latitude');
    var $longitude = $('#longitude');
    var $radius = $('#radius_meter');

    /* ── Map ────────────────────────────────────────────────────────── */

    function ensureMap() {
        if (map) return map;

        map = L.map('locationMap', { scrollWheelZoom: true })
            .setView(window.PARKOPS_DEFAULTS.center, 16);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 20,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        map.on('click', function (event) {
            setPoint(event.latlng.lat, event.latlng.lng);
        });

        return map;
    }

    function setPoint(lat, lng, recentre) {
        // 7 decimals ≈ 1 cm, matching the decimal(10,7) columns.
        var latitude = parseFloat(lat.toFixed ? lat.toFixed(7) : lat);
        var longitude = parseFloat(lng.toFixed ? lng.toFixed(7) : lng);

        $latitude.val(latitude);
        $longitude.val(longitude);

        var position = [latitude, longitude];

        if (!marker) {
            marker = L.marker(position, { draggable: true })
                .addTo(map)
                .on('drag', function (event) {
                    var p = event.target.getLatLng();
                    $latitude.val(parseFloat(p.lat.toFixed(7)));
                    $longitude.val(parseFloat(p.lng.toFixed(7)));
                    circle.setLatLng(p);
                });
        } else {
            marker.setLatLng(position);
        }

        if (!circle) {
            circle = L.circle(position, {
                radius: currentRadius(),
                color: '#2563eb',
                weight: 2,
                fillColor: '#3b82f6',
                fillOpacity: 0.15
            }).addTo(map);
        } else {
            circle.setLatLng(position);
        }

        if (recentre !== false) map.setView(position, Math.max(map.getZoom(), 18));

        // A point has now been chosen, so clear any "pilih titik" error.
        ParkOps.clearErrors($('#dataForm'));
    }

    function currentRadius() {
        var value = parseFloat($radius.val());
        return isNaN(value) || value <= 0 ? window.PARKOPS_DEFAULTS.radius : value;
    }

    function clearPoint() {
        $latitude.val('');
        $longitude.val('');

        if (marker) { map.removeLayer(marker); marker = null; }
        if (circle) { map.removeLayer(circle); circle = null; }

        map.setView(window.PARKOPS_DEFAULTS.center, 16);
    }

    // Redrawing the radius live makes "maksimal 10 meter" tangible instead of
    // an abstract number in a field.
    $radius.on('input', function () {
        if (circle) circle.setRadius(currentRadius());
    });

    $('#useMyLocation').on('click', function () {
        if (!navigator.geolocation) {
            ParkOps.toast('Browser ini tidak mendukung Geolocation.', 'warning');
            return;
        }

        var $button = $(this);
        ParkOps.busy($button, true, 'Mencari…');

        navigator.geolocation.getCurrentPosition(
            function (position) {
                ParkOps.busy($button, false);
                ensureMap();
                setPoint(position.coords.latitude, position.coords.longitude);
                ParkOps.toast('Titik diambil dari GPS perangkat ini (akurasi ±' +
                    Math.round(position.coords.accuracy) + ' m).', 'info');
            },
            function () {
                ParkOps.busy($button, false);
                ParkOps.toast('Gagal mengambil lokasi. Pastikan izin lokasi diaktifkan.', 'danger');
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    });

    /* ── Modal lifecycle ────────────────────────────────────────────── */

    // Leaflet measures its container on init; inside a hidden modal that reads
    // as 0×0, so the map is sized only once the modal is actually visible.
    $modal.on('shown.bs.modal', function () {
        ensureMap();
        map.invalidateSize();

        if ($latitude.val() && $longitude.val()) {
            setPoint(parseFloat($latitude.val()), parseFloat($longitude.val()));
        }
    });

    /* ── CRUD ───────────────────────────────────────────────────────── */

    ParkOps.crud({
        baseUrl: window.PARKOPS_URLS.base,
        filters: ['#searchInput', '#statusFilter'],
        labels: { create: 'Tambah Lokasi', edit: 'Ubah Lokasi' },
        defaults: {
            radius_meter: window.PARKOPS_DEFAULTS.radius,
            gps_accuracy_limit: window.PARKOPS_DEFAULTS.accuracy,
            status: 'ACTIVE'
        },
        emptyMessage: 'Belum ada lokasi absensi.',

        onCreate: function () {
            if (map) clearPoint();
        },

        fill: function ($form, item) {
            if (map && item.latitude && item.longitude) {
                setPoint(parseFloat(item.latitude), parseFloat(item.longitude));
            }
        },

        beforeSubmit: function ($form) {
            if (!$latitude.val() || !$longitude.val()) {
                ParkOps.toast('Tentukan titik lokasi pada peta terlebih dahulu.', 'warning');
                return false;
            }
        },

        columns: [
            {
                data: 'location_code',
                className: 'fw-semibold',
                render: ParkOps.esc
            },
            {
                data: 'location_name',
                render: function (value, type, row) {
                    return ParkOps.esc(value) + (row.address
                        ? '<div class="small text-body-secondary">' + ParkOps.esc(row.address) + '</div>'
                        : '');
                }
            },
            {
                data: 'latitude',
                orderable: false,
                className: 'small text-tabular',
                render: function (value, type, row) {
                    return ParkOps.esc(value) + ', ' + ParkOps.esc(row.longitude);
                }
            },
            {
                data: 'radius_meter',
                className: 'text-center text-tabular',
                render: function (value) { return ParkOps.esc(value) + ' m'; }
            },
            {
                data: 'gps_accuracy_limit',
                className: 'text-center text-tabular',
                render: function (value) { return ParkOps.esc(value) + ' m'; }
            },
            {
                data: 'status',
                className: 'text-center',
                render: ParkOps.statusBadge
            },
            {
                data: null,
                orderable: false,
                className: 'text-end text-nowrap',
                render: function (row) {
                    var coords = encodeURIComponent(row.latitude) + '/' + encodeURIComponent(row.longitude);

                    // Wrapping the whole cell keeps the map link on the same
                    // rhythm as the edit/delete pair inside rowActions().
                    return ParkOps.actionGroup(
                        '<a class="btn btn-sm btn-icon" target="_blank" rel="noopener"' +
                        ' title="Buka di peta" aria-label="Buka di peta"' +
                        ' href="https://www.openstreetmap.org/?mlat=' +
                        encodeURIComponent(row.latitude) + '&mlon=' + encodeURIComponent(row.longitude) +
                        '#map=19/' + coords + '"><i class="bi bi-map"></i></a>' +
                        ParkOps.rowActions(row.id, 'lokasi ' + row.location_name)
                    );
                }
            }
        ]
    });
});
