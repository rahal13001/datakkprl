@script
<script>
    Alpine.data('kkprlProfileMap', (wire, reference, locked) => {
        // Keep Leaflet objects outside Alpine proxies and clean them up when the topic changes.
        let map = null, layers = null, resizeObserver = null;
        return {
            startLat: wire.$entangle('profileStartLat'), startLng: wire.$entangle('profileStartLng'),
            endLat: wire.$entangle('profileEndLat'), endLng: wire.$entangle('profileEndLng'),
            mode: 'A', disposed: false,
            mapMessage: 'Memuat peta. Anda juga dapat mengisi koordinat desimal.',
            async init() {
                for (const key of ['startLat', 'startLng', 'endLat', 'endLng']) this.$watch(key, () => this.draw());
                try {
                    if (!window.L) {
                        window.kkprlLeafletReady ??= new Promise((resolve, reject) => {
                            const css = document.createElement('link');
                            Object.assign(css, {rel: 'stylesheet', href: 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', integrity: 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=', crossOrigin: ''});
                            document.head.appendChild(css);
                            const script = document.createElement('script');
                            Object.assign(script, {src: 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', integrity: 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=', crossOrigin: ''});
                            script.onload = resolve;
                            script.onerror = () => reject(new Error('Map script unavailable'));
                            document.head.appendChild(script);
                        });
                        await window.kkprlLeafletReady;
                    }
                    if (this.disposed) return;
                    map = L.map(this.$refs.map, {scrollWheelZoom: false, zoomAnimation: false, markerZoomAnimation: false, fadeAnimation: false}).setView(reference[0] ?? [-2.5489, 118.0149], reference.length ? 14 : 5);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                    }).on('tileerror', () => { this.mapMessage = 'Sebagian peta belum termuat. Anda tetap dapat memakai koordinat desimal di bawah.'; }).addTo(map);
                    for (const [index, point] of reference.entries()) {
                        L.circleMarker(point, {radius: 5, color: '#475569', fillOpacity: .6}).addTo(map).bindTooltip('Lokasi Bab 1 — Titik ' + (index + 1));
                    }
                    if (reference.length > 1) map.fitBounds(reference, {padding: [30, 30], maxZoom: 15});
                    layers = L.featureGroup().addTo(map);
                    map.on('click', event => {
                        if (locked) return;
                        const which = this.mode;
                        this.setPoint(which, event.latlng.lat, event.latlng.lng);
                        if (which === 'A') this.mode = 'B';
                        this.mapMessage = which === 'A' ? 'Titik A dipilih. Klik lokasi titik B.' : 'Lintasan A–B dipilih. Seret penanda untuk menyesuaikan garis, lalu buat grafik.';
                    });
                    resizeObserver = new ResizeObserver(() => map?.invalidateSize());
                    resizeObserver.observe(this.$refs.map);
                    this.mapMessage = 'Klik peta untuk memilih titik A, lalu titik B. Anda juga dapat mengisi koordinat desimal.';
                    this.draw();
                } catch (error) {
                    this.mapMessage = 'Peta belum dapat dimuat. Isi koordinat A dan B pada form di bawah untuk membuat penampang.';
                }
            },
            point(lat, lng) {
                if (String(lat).trim() === '' || String(lng).trim() === '') return null;
                const a = Number(lat), b = Number(lng);
                return Number.isFinite(a) && Number.isFinite(b) && Math.abs(a) <= 85 && Math.abs(b) <= 180 ? [a, b] : null;
            },
            setPoint(which, lat, lng) {
                if (locked || !this.point(lat, lng)) return;
                const part = which === 'A' ? 'Start' : 'End';
                wire.$set('profile' + part + 'Lat', lat.toFixed(6), false);
                wire.$set('profile' + part + 'Lng', lng.toFixed(6), false);
                this.draw();
            },
            draw() {
                if (!layers) return;
                layers.clearLayers();
                const a = this.point(this.startLat, this.startLng), b = this.point(this.endLat, this.endLng);
                for (const [which, point] of [['A', a], ['B', b]]) {
                    if (!point) continue;
                    const icon = L.divIcon({className: '', html: '<span style="display:block;background:#2563eb;color:white;border:2px solid white;border-radius:50%;width:28px;height:28px;text-align:center;font:bold 15px/24px sans-serif">' + which + '</span>', iconSize: [28,28], iconAnchor: [14,14]});
                    L.marker(point, {icon, draggable: !locked, title: 'Titik ' + which, alt: 'Titik ' + which})
                        .on('dragend', event => this.setPoint(which, event.target.getLatLng().lat, event.target.getLatLng().lng)).addTo(layers);
                }
                if (a && b) L.polyline([a, b], {color: '#2563eb', weight: 3}).addTo(layers);
            },
            clear() {
                if (locked) return;
                for (const key of ['profileStartLat', 'profileStartLng', 'profileEndLat', 'profileEndLng']) wire.$set(key, '', false);
                this.mode = 'A';
                this.mapMessage = 'Lintasan dibersihkan. Pilih titik A untuk mulai lagi. Grafik yang sudah tersimpan tetap tersedia.';
                this.draw();
            },
            destroy() {
                this.disposed = true;
                resizeObserver?.disconnect();
                map?.remove();
                map = null;
                layers = null;
            }
        };
    });
</script>
@endscript
