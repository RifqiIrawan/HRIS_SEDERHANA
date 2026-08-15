/*
 * Absensi — GPS + Leaflet + kamera (spec §20-§29, §51).
 *
 * Division of responsibility, restated because it is the whole security model:
 * this file collects raw sensor output (coordinates, the device's own accuracy
 * figure, a photo) and shows the employee an *estimate* of their distance.
 * It never decides whether an attendance is valid. Laravel recomputes the
 * Haversine distance from the location row and makes that call on submit
 * (spec §21, §24, AC-006).
 */
jQuery(function ($) {
    'use strict';

    var context = null;          // server-provided roster + location
    var position = null;         // latest GeolocationPosition coords
    var photoBlob = null;        // captured JPEG awaiting upload
    var stream = null;           // active MediaStream, if any
    var watchId = null;
    var clockTimer = null;
    var serverTime = null;

    var map = null;
    var locationMarker = null;
    var radiusCircle = null;
    var userMarker = null;
    var accuracyCircle = null;

    var addressRequest = null;   // in-flight reverse-geocode lookup
    var addressAnchor = null;    // coords the shown address was resolved for

    var $accuracy = $('#readoutAccuracy');
    var $distance = $('#readoutDistance');
    var $status = $('#readoutStatus');
    var $address = $('#readoutAddress');
    var $hint = $('#gpsHint');
    var $checkIn = $('#checkInButton');
    var $checkOut = $('#checkOutButton');

    /* ─────────────────────────────────────────────────────────────────
     * Context
     * ───────────────────────────────────────────────────────────────── */

    function loadContext() {
        return HRIS.api({ url: window.HRIS_URLS.context })
            .done(function (data) {
                context = data;
                applyState();
            })
            .fail(function (error) {
                showState('danger', error.message);
                HRIS.handleError(error);
            });
    }

    function applyState() {
        var roster = context.roster;
        var location = context.location;

        startClock(context.server_time);

        if (roster) {
            $('#rosterTitle').text(
                (roster.shift_name || 'OFF') +
                (roster.shift_code ? ' (' + roster.shift_code + ')' : '')
            );
            $('#rosterSubtitle').text(
                location
                    ? location.name + ' · ' + (roster.start || '−') + ' → ' + (roster.end || '−') +
                      (roster.cross_day ? ' (lintas hari)' : '')
                    : ''
            );
        } else {
            $('#rosterTitle').text('Tidak ada jadwal aktif');
            $('#rosterSubtitle').text('');
        }

        if (context.state === 'NO_ROSTER') {
            showState('warning',
                'Tidak ada jadwal shift yang aktif untuk Anda saat ini. ' +
                'Halaman absensi terbuka mulai 2 jam sebelum shift dimulai.');
            $('#attendancePanel').addClass('d-none');
            renderResult(context.attendance);
            return;
        }

        if (context.state === 'DONE') {
            showState('success', 'Absensi untuk shift ini sudah lengkap. Terima kasih!');
            $('#attendancePanel').addClass('d-none');
            renderResult(context.attendance);
            return;
        }

        $('#stateAlert').addClass('d-none');
        $('#attendancePanel').removeClass('d-none');

        // Deliberately loud and unconditional: an open geofence is a testing
        // configuration, and nobody should reach this screen in that state
        // without being told.
        $('#geofenceWarning').toggleClass('d-none', enforcesGeofence());

        var isCheckOut = context.state === 'AWAITING_CHECK_OUT';

        $checkIn.toggleClass('d-none', isCheckOut);
        $checkOut.toggleClass('d-none', !isCheckOut);

        $('#actionHint').text(isCheckOut
            ? 'Anda sudah check-in. Ambil foto dan pastikan GPS akurat untuk check-out.'
            : (roster && roster.late_threshold
                ? 'Batas tepat waktu: ' + roster.late_threshold + '. Lewat dari itu tercatat TERLAMBAT.'
                : ''));

        renderResult(context.attendance);

        initializeMap();
        getCurrentLocation();
    }

    function showState(variant, message) {
        $('#stateAlert')
            .removeClass('d-none alert-success alert-warning alert-danger alert-info')
            .addClass('alert-' + variant)
            .text(message);
    }

    /* ─────────────────────────────────────────────────────────────────
     * Server clock (spec §54 — the browser clock is never authoritative)
     * ───────────────────────────────────────────────────────────────── */

    function startClock(initial) {
        if (!initial) return;

        serverTime = new Date(initial.replace(' ', 'T'));
        clearInterval(clockTimer);

        clockTimer = setInterval(function () {
            serverTime = new Date(serverTime.getTime() + 1000);
            $('#serverClock').text(serverTime.toTimeString().slice(0, 8));
        }, 1000);

        $('#serverClock').text(serverTime.toTimeString().slice(0, 8));
    }

    /* ─────────────────────────────────────────────────────────────────
     * Map (spec §20)
     * ───────────────────────────────────────────────────────────────── */

    function initializeMap() {
        if (!context.location || map) return;

        var centre = [context.location.latitude, context.location.longitude];

        map = L.map('attendanceMap', { scrollWheelZoom: false }).setView(centre, 18);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 20,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        locationMarker = L.circleMarker(centre, {
            radius: 8, color: '#dc2626', fillColor: '#ef4444', fillOpacity: 1, weight: 2
        }).addTo(map).bindTooltip(context.location.name, { permanent: false });

        radiusCircle = L.circle(centre, {
            radius: context.location.radius_meter,
            color: '#dc2626', weight: 2, fillColor: '#ef4444', fillOpacity: 0.08
        }).addTo(map);
    }

    function updateUserMarker(coords) {
        if (!map) return;

        var point = [coords.latitude, coords.longitude];

        if (!userMarker) {
            userMarker = L.circleMarker(point, {
                radius: 7, color: '#1d4ed8', fillColor: '#3b82f6', fillOpacity: 1, weight: 2
            }).addTo(map).bindTooltip('Posisi Anda');

            accuracyCircle = L.circle(point, {
                radius: coords.accuracy,
                color: '#3b82f6', weight: 1, fillColor: '#60a5fa', fillOpacity: 0.12
            }).addTo(map);
        } else {
            userMarker.setLatLng(point);
            accuracyCircle.setLatLng(point).setRadius(coords.accuracy);
        }

        // Keep both the target and the employee in view.
        map.fitBounds(
            L.latLngBounds([point, [context.location.latitude, context.location.longitude]]).pad(0.6),
            { maxZoom: 19 }
        );
    }

    /* ─────────────────────────────────────────────────────────────────
     * GPS (spec §21, §22)
     * ───────────────────────────────────────────────────────────────── */

    function getCurrentLocation() {
        if (!navigator.geolocation) {
            $hint.attr('class', 'alert alert-danger small mt-3 mb-0')
                .text('Browser ini tidak mendukung Geolocation. Absensi tidak dapat dilakukan.');
            return;
        }

        $hint.attr('class', 'alert alert-light border small mt-3 mb-0')
            .html('<span class="spinner-border spinner-border-sm me-2"></span>Mencari sinyal GPS…');

        if (watchId !== null) navigator.geolocation.clearWatch(watchId);

        // watchPosition rather than getCurrentPosition: accuracy usually
        // improves over the first few seconds, and the employee can watch the
        // reading tighten instead of guessing when to retry.
        watchId = navigator.geolocation.watchPosition(
            function (pos) {
                position = pos.coords;
                updateUserMarker(pos.coords);
                updateReadout();
                refreshAddress();
            },
            function (error) {
                position = null;
                clearAddress();
                updateReadout();

                var messages = {
                    1: 'Izin lokasi ditolak. Aktifkan izin lokasi untuk browser ini, lalu muat ulang halaman.',
                    2: 'Lokasi tidak tersedia. Pastikan GPS perangkat aktif.',
                    3: 'Pencarian GPS habis waktu. Coba lagi di area terbuka.'
                };

                $hint.attr('class', 'alert alert-danger small mt-3 mb-0')
                    .text(messages[error.code] || 'Gagal mendapatkan lokasi.');
            },
            { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 }
        );
    }

    /**
     * Display-only distance (spec §51). The identical Haversine formula runs in
     * GeofenceService, and that server-side result is the one that counts.
     */
    function calculateDisplayDistance(lat1, lng1, lat2, lng2) {
        var earthRadius = 6371000;
        var toRad = function (deg) { return deg * Math.PI / 180; };

        var dLat = toRad(lat2 - lat1);
        var dLng = toRad(lng2 - lng1);

        var a = Math.pow(Math.sin(dLat / 2), 2) +
            Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.pow(Math.sin(dLng / 2), 2);

        return 2 * earthRadius * Math.asin(Math.min(1, Math.sqrt(a)));
    }

    function updateReadout() {
        if (!position || !context.location) {
            $accuracy.text('—');
            $distance.text('—');
            $status.html('<span class="text-body-secondary">—</span>');
            refreshActionButtons();
            return;
        }

        var accuracy = position.accuracy;
        var distance = calculateDisplayDistance(
            position.latitude, position.longitude,
            context.location.latitude, context.location.longitude
        );

        var accuracyOk = accuracy <= context.location.gps_accuracy_limit;
        var withinRadius = distance <= context.location.radius_meter;

        $accuracy.text(accuracy.toFixed(1) + ' m')
            .attr('class', 'readout-value ' + (accuracyOk ? 'text-success' : 'text-danger'));

        $distance.text(distance.toFixed(1) + ' m')
            .attr('class', 'readout-value ' + (withinRadius ? 'text-success' : 'text-danger'));

        // With enforcement off the readings are still shown and still coloured
        // against the thresholds — they just no longer stop a submit, and the
        // hint says which of the two situations the employee is in.
        var relaxed = !enforcesGeofence();

        if (!accuracyOk) {
            $status.html('<span class="text-danger">AKURASI RENDAH</span>');
            $hint.attr('class', 'alert alert-warning small mt-3 mb-0').text(
                'GPS kurang akurat (' + accuracy.toFixed(1) + ' m, batas ' +
                context.location.gps_accuracy_limit + ' m). ' +
                (relaxed
                    ? 'Penegakan geofence sedang dinonaktifkan, absensi tetap dapat dikirim.'
                    : 'Silakan coba kembali di area terbuka.')
            );
        } else if (!withinRadius) {
            $status.html('<span class="text-danger">DI LUAR RADIUS</span>');
            $hint.attr('class', 'alert alert-warning small mt-3 mb-0').text(
                'Anda berada ' + distance.toFixed(1) + ' m dari titik lokasi (maksimal ' +
                context.location.radius_meter + ' m). ' +
                (relaxed
                    ? 'Penegakan geofence sedang dinonaktifkan, absensi tetap dapat dikirim.'
                    : 'Mendekatlah ke titik absensi.')
            );
        } else {
            $status.html('<span class="text-success">VALID</span>');
            $hint.attr('class', 'alert alert-success small mt-3 mb-0').text(
                'Posisi dan akurasi GPS memenuhi syarat. Ambil foto lalu kirim absensi.'
            );
        }

        refreshActionButtons();
    }

    /* ─────────────────────────────────────────────────────────────────
     * Reverse geocoding — the address behind the current reading
     * ───────────────────────────────────────────────────────────────── */

    /**
     * Shown for the employee's benefit so they can confirm the device is
     * reporting the right place. It is never submitted: the check-in request
     * carries coordinates only, and Laravel resolves the address it stores
     * from those, so a tampered display cannot reach the database.
     *
     * watchPosition fires continuously, so a lookup only goes out when the
     * reading has actually moved — the provider is rate-limited and the answer
     * would not change over a few metres of GPS jitter.
     */
    function refreshAddress() {
        if (!position || !window.HRIS_URLS.geocode) return;

        if (addressAnchor && calculateDisplayDistance(
            addressAnchor.latitude, addressAnchor.longitude,
            position.latitude, position.longitude
        ) < 25) {
            return;
        }

        if (addressRequest) return;

        var coords = { latitude: position.latitude, longitude: position.longitude };

        $address.html('<span class="text-body-secondary">Mencari alamat…</span>');

        addressRequest = HRIS.api({
            url: window.HRIS_URLS.geocode,
            data: { latitude: coords.latitude, longitude: coords.longitude }
        })
            .done(function (data) {
                addressAnchor = coords;

                $address.text(data.address || 'Alamat tidak ditemukan untuk titik ini.')
                    .toggleClass('text-body-secondary', !data.address);
            })
            .fail(function () {
                // The lookup is optional; a failure must not read as an error
                // the employee has to act on.
                $address.html('<span class="text-body-secondary">Alamat tidak tersedia.</span>');
            })
            .always(function () {
                addressRequest = null;
            });
    }

    function clearAddress() {
        addressAnchor = null;
        $address.text('—').addClass('text-body-secondary');
    }

    /**
     * The button is a convenience gate only — a stale reading that slips past
     * it still gets rejected by the backend, which is the actual guarantee.
     */
    function refreshActionButtons() {
        var ready = !!position && !!photoBlob && !!context.location &&
            (!enforcesGeofence() || position.accuracy <= context.location.gps_accuracy_limit);

        $checkIn.prop('disabled', !ready);
        $checkOut.prop('disabled', !ready);
    }

    /**
     * The server tells the page whether it is enforcing distance and accuracy.
     * When it is not, holding the button shut on accuracy would block a submit
     * the backend would have accepted. Absent flag means enforced — an older
     * response must never be read as permission.
     */
    function enforcesGeofence() {
        return !context || context.enforce_geofence !== false;
    }

    // An explicit refresh means "re-read everything", so the address is looked
    // up again even if the device has not moved.
    $('#refreshGps').on('click', function () {
        clearAddress();
        getCurrentLocation();
    });

    /* ─────────────────────────────────────────────────────────────────
     * Camera (spec §25)
     * ───────────────────────────────────────────────────────────────── */

    function openCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            HRIS.toast('Kamera tidak tersedia di browser ini. Gunakan tombol "Ambil dari Kamera Perangkat".', 'warning');
            return;
        }

        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false })
            .then(function (mediaStream) {
                stream = mediaStream;

                var video = document.getElementById('cameraVideo');
                video.srcObject = mediaStream;
                video.play();

                $('#cameraVideo').removeClass('d-none');
                $('#photoPreview').addClass('d-none');
                $('#cameraPlaceholder').addClass('d-none');
                $('#openCamera').addClass('d-none');
                $('#capturePhoto').removeClass('d-none');
                $('#retakePhoto').addClass('d-none');
            })
            .catch(function () {
                HRIS.toast(
                    'Akses kamera ditolak. Berikan izin kamera, atau gunakan tombol "Ambil dari Kamera Perangkat".',
                    'danger'
                );
            });
    }

    function stopCamera() {
        if (!stream) return;

        stream.getTracks().forEach(function (track) { track.stop(); });
        stream = null;

        $('#cameraVideo').addClass('d-none');
    }

    function capturePhoto() {
        var video = document.getElementById('cameraVideo');

        if (!video.videoWidth) {
            HRIS.toast('Kamera belum siap, coba lagi sesaat.', 'warning');
            return;
        }

        // Downscale before encoding: a 4K selfie is ~8 MB of JPEG for no gain
        // in identifying who checked in, and would trip the 5 MB limit.
        var maxWidth = 1024;
        var scale = Math.min(1, maxWidth / video.videoWidth);

        var canvas = document.createElement('canvas');
        canvas.width = Math.round(video.videoWidth * scale);
        canvas.height = Math.round(video.videoHeight * scale);
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

        canvas.toBlob(function (blob) {
            if (!blob) {
                HRIS.toast('Gagal mengambil foto. Coba lagi.', 'danger');
                return;
            }

            setPhoto(blob);
            stopCamera();
        }, 'image/jpeg', 0.85);
    }

    function setPhoto(blob) {
        photoBlob = blob;

        var url = URL.createObjectURL(blob);
        var $preview = $('#photoPreview');

        // Release the previous object URL so repeated retakes do not leak.
        var previous = $preview.attr('src');
        if (previous) URL.revokeObjectURL(previous);

        $preview.attr('src', url).removeClass('d-none');
        $('#cameraPlaceholder').addClass('d-none');
        $('#capturePhoto').addClass('d-none');
        $('#openCamera').addClass('d-none');
        $('#retakePhoto').removeClass('d-none');

        refreshActionButtons();
    }

    function clearPhoto() {
        photoBlob = null;

        var $preview = $('#photoPreview');
        var previous = $preview.attr('src');
        if (previous) URL.revokeObjectURL(previous);

        $preview.attr('src', '').addClass('d-none');
        $('#cameraPlaceholder').removeClass('d-none');
        $('#retakePhoto').addClass('d-none');
        $('#capturePhoto').addClass('d-none');
        $('#openCamera').removeClass('d-none');

        refreshActionButtons();
    }

    $('#openCamera').on('click', openCamera);
    $('#capturePhoto').on('click', capturePhoto);
    $('#retakePhoto').on('click', function () {
        clearPhoto();
        openCamera();
    });

    $('#photoFallback').on('change', function () {
        var file = this.files && this.files[0];
        if (!file) return;

        if (file.size > 5 * 1024 * 1024) {
            HRIS.toast('Ukuran foto maksimal 5 MB.', 'danger');
            this.value = '';
            return;
        }

        stopCamera();
        setPhoto(file);
        this.value = '';
    });

    /* ─────────────────────────────────────────────────────────────────
     * Submit (spec §29)
     * ───────────────────────────────────────────────────────────────── */

    function submit(url, $button, busyLabel) {
        if (!position) {
            HRIS.toast('Lokasi GPS belum tersedia.', 'warning');
            return;
        }

        if (!photoBlob) {
            HRIS.toast('Ambil foto terlebih dahulu.', 'warning');
            return;
        }

        var formData = new FormData();
        formData.append('latitude', position.latitude);
        formData.append('longitude', position.longitude);
        formData.append('accuracy', position.accuracy);
        formData.append('photo', photoBlob, 'attendance.jpg');

        HRIS.busy($button, true, busyLabel);
        $checkIn.prop('disabled', true);
        $checkOut.prop('disabled', true);

        HRIS.api({ url: url, type: 'POST', data: formData })
            .done(function (attendance) {
                HRIS.toast('Absensi berhasil dikirim.', 'success');
                clearPhoto();
                showAttendanceResult(attendance);
                loadContext();
            })
            .fail(function (error) {
                HRIS.toast(error.message, 'danger');

                // A rejection for distance or accuracy means the reading needs
                // to be retaken, not resubmitted.
                if (error.code === 'OUT_OF_RADIUS' || error.code === 'ACCURACY_TOO_LOW') {
                    getCurrentLocation();
                }

                if (error.code === 'DUPLICATE_CHECK_IN' || error.code === 'NO_ACTIVE_ROSTER' ||
                    error.code === 'OPEN_ATTENDANCE_EXISTS') {
                    loadContext();
                }

                refreshActionButtons();
            })
            .always(function () {
                HRIS.busy($button, false);
            });
    }

    $checkIn.on('click', function () { submit(window.HRIS_URLS.checkIn, $checkIn, 'Mengirim…'); });
    $checkOut.on('click', function () { submit(window.HRIS_URLS.checkOut, $checkOut, 'Mengirim…'); });

    /* ─────────────────────────────────────────────────────────────────
     * Result (spec §51 showAttendanceResult)
     * ───────────────────────────────────────────────────────────────── */

    function showAttendanceResult(attendance) {
        renderResult(attendance);
        $('#resultCard')[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function renderResult(attendance) {
        if (!attendance) {
            $('#resultCard').addClass('d-none');
            return;
        }

        var rows = [
            ['Tanggal', HRIS.formatDate(attendance.attendance_date)],
            ['Shift', attendance.shift_name || '−'],
            ['Lokasi', attendance.location_name || '−'],
            ['Check-In', attendance.check_in_at ? HRIS.formatTime(attendance.check_in_at) : '−'],
            ['Jarak Check-In', attendance.check_in_distance !== null && attendance.check_in_distance !== undefined
                ? HRIS.formatNumber(attendance.check_in_distance, 2) + ' m' : '−'],
            ['Akurasi Check-In', attendance.check_in_accuracy !== null && attendance.check_in_accuracy !== undefined
                ? HRIS.formatNumber(attendance.check_in_accuracy, 2) + ' m' : '−'],
            ['Alamat Check-In', attendance.check_in_address || '−'],
            ['Check-Out', attendance.check_out_at ? HRIS.formatTime(attendance.check_out_at) : '−'],
            ['Jarak Check-Out', attendance.check_out_distance !== null && attendance.check_out_distance !== undefined
                ? HRIS.formatNumber(attendance.check_out_distance, 2) + ' m' : '−'],
            ['Alamat Check-Out', attendance.check_out_address || '−'],
            ['Keterlambatan', attendance.late_minutes ? attendance.late_minutes + ' menit' : 'Tepat waktu'],
            ['Status', HRIS.statusBadge(attendance.status)]
        ];

        $('#resultBody').html(rows.map(function (row) {
            return '<dt class="col-5 fw-normal text-body-secondary">' + HRIS.esc(row[0]) + '</dt>' +
                '<dd class="col-7 mb-2">' + (row[0] === 'Status' ? row[1] : HRIS.esc(row[1])) + '</dd>';
        }).join(''));

        $('#resultCard').removeClass('d-none');
    }

    /* ─────────────────────────────────────────────────────────────────
     * Lifecycle
     * ───────────────────────────────────────────────────────────────── */

    $(window).on('beforeunload', function () {
        stopCamera();
        if (watchId !== null) navigator.geolocation.clearWatch(watchId);
        clearInterval(clockTimer);
    });

    loadContext();
});
