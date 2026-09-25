<?php
$content = file_get_contents('resources/views/livewire/kkprl-proposal-wizard.blade.php');
$start = strpos($content, '<script>');
$end = strpos($content, '</script>') + 9;
$newScript = <<<HTML
<script>
    function coordinateManager(initialRaw, initialShape, wire) {
        return {
            points: [],
            shapeType: initialShape || 'polygon',
            map: null,
            layerGroup: null,
            syncTimeout: null,
            isDrawing: false,

            init() {
                try {
                    let initialData = initialRaw;
                    if (typeof initialData === 'string') initialData = JSON.parse(initialData);
                    
                    if (Array.isArray(initialData) && initialData.length > 0) {
                        this.points = initialData;
                    } else {
                        this.addPoint();
                    }
                } catch(e) {
                    this.addPoint();
                }
                
                this.\$watch('points', (value) => {
                    if(!this.isDrawing) this.drawOnMap();
                    
                    let raw = JSON.stringify(value);
                    let text = this.formatPointsText(value);
                    
                    if (this.syncTimeout) clearTimeout(this.syncTimeout);
                    this.syncTimeout = setTimeout(() => {
                        wire.updateCoordinates(raw, text, this.shapeType);
                    }, 500);
                }, { deep: true });

                this.\$watch('shapeType', (value) => {
                    this.drawOnMap();
                    let raw = JSON.stringify(this.points);
                    let text = this.formatPointsText(this.points);
                    wire.updateCoordinates(raw, text, this.shapeType);
                });

                setTimeout(() => {
                    this.initMap();
                }, 200);
            },

            initMap() {
                if (typeof L === 'undefined') return;
                
                let center = [-2.5489, 118.0149];
                let zoom = 5;

                this.map = L.map('coordinate-map').setView(center, zoom);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap'
                }).addTo(this.map);
                
                this.layerGroup = L.featureGroup().addTo(this.map);

                this.map.on('click', (e) => {
                    this.addPointFromMap(e.latlng.lat, e.latlng.lng);
                });

                this.drawOnMap();

                setTimeout(() => {
                    if (this.layerGroup.getLayers().length > 0) {
                        this.map.fitBounds(this.layerGroup.getBounds(), { padding: [30, 30], maxZoom: 14 });
                    }
                }, 500);
            },

            addPointFromMap(lat, lng) {
                this.isDrawing = true;
                
                let dmsLat = this.calcDMS(lat, true);
                let dmsLng = this.calcDMS(lng, false);
                
                let newPoint = {
                    lat_dd: lat.toFixed(6), lat_d: dmsLat.d, lat_m: dmsLat.m, lat_s: dmsLat.s, lat_dir: dmsLat.dir,
                    lng_dd: lng.toFixed(6), lng_d: dmsLng.d, lng_m: dmsLng.m, lng_s: dmsLng.s, lng_dir: dmsLng.dir
                };

                let isDefaultEmpty = this.points.length === 1 && this.points[0].lat_dd === '' && this.points[0].lng_dd === '';
                
                if (isDefaultEmpty) {
                    this.points[0] = newPoint;
                } else {
                    this.points.push(newPoint);
                }
                
                this.drawOnMap();
                this.isDrawing = false;
            },

            updatePointFromMap(index, lat, lng) {
                this.isDrawing = true;
                let dmsLat = this.calcDMS(lat, true);
                let dmsLng = this.calcDMS(lng, false);
                
                this.points[index].lat_dd = lat.toFixed(6);
                this.points[index].lat_d = dmsLat.d;
                this.points[index].lat_m = dmsLat.m;
                this.points[index].lat_s = dmsLat.s;
                this.points[index].lat_dir = dmsLat.dir;

                this.points[index].lng_dd = lng.toFixed(6);
                this.points[index].lng_d = dmsLng.d;
                this.points[index].lng_m = dmsLng.m;
                this.points[index].lng_s = dmsLng.s;
                this.points[index].lng_dir = dmsLng.dir;
                
                this.drawOnMap();
                this.isDrawing = false;
            },

            drawOnMap() {
                if (!this.map || !this.layerGroup) return;
                this.layerGroup.clearLayers();
                
                let latlngs = [];
                this.points.forEach((p, index) => {
                    let lat = parseFloat(p.lat_dd);
                    let lng = parseFloat(p.lng_dd);
                    if (!isNaN(lat) && !isNaN(lng)) {
                        latlngs.push([lat, lng]);
                        
                        let marker = L.marker([lat, lng], { draggable: true }).addTo(this.layerGroup);
                        marker.bindTooltip("Titik " + (index + 1), {permanent: true, direction: 'top', className: "bg-blue-600 text-white font-bold text-[10px] border-0 rounded px-1.5 py-0.5", offset: [0, -35] });
                        
                        marker.on('dragend', (e) => {
                            let newPos = e.target.getLatLng();
                            this.updatePointFromMap(index, newPos.lat, newPos.lng);
                        });
                    }
                });

                if (latlngs.length > 1) {
                    if (this.shapeType === 'polygon' && latlngs.length >= 3) {
                        L.polygon(latlngs, { color: '#ef4444', fillColor: '#ef4444', fillOpacity: 0.3, weight: 3 }).addTo(this.layerGroup);
                    } else if (this.shapeType === 'line' || (this.shapeType === 'polygon' && latlngs.length === 2)) {
                        L.polyline(latlngs, { color: '#ef4444', weight: 4 }).addTo(this.layerGroup);
                    }
                }
            },

            clearMap() {
                this.points = [{
                    lat_dd: '', lat_d: '', lat_m: '', lat_s: '', lat_dir: 'S',
                    lng_dd: '', lng_d: '', lng_m: '', lng_s: '', lng_dir: 'E'
                }];
                if (this.map) this.map.setView([-2.5489, 118.0149], 5);
            },

            calcDMS(dd, isLat) {
                let dir = dd < 0 ? (isLat ? 'S' : 'W') : (isLat ? 'N' : 'E');
                let absDd = Math.abs(dd);
                let d = Math.floor(absDd);
                let minFloat = (absDd - d) * 60;
                let m = Math.floor(minFloat);
                let s = ((minFloat - m) * 60).toFixed(4);
                return { d, m, s, dir };
            },

            addPoint() {
                this.points.push({
                    lat_dd: '', lat_d: '', lat_m: '', lat_s: '', lat_dir: 'S',
                    lng_dd: '', lng_d: '', lng_m: '', lng_s: '', lng_dir: 'E'
                });
            },
            removePoint(index) {
                this.points.splice(index, 1);
                if (this.points.length === 0) {
                    this.addPoint();
                }
            },
            syncFromDD(index, isLat) {
                let point = this.points[index];
                let dd = parseFloat(isLat ? point.lat_dd : point.lng_dd);
                
                if (isNaN(dd)) {
                    if (isLat) { point.lat_d = ''; point.lat_m = ''; point.lat_s = ''; }
                    else { point.lng_d = ''; point.lng_m = ''; point.lng_s = ''; }
                    return;
                }
                
                let dms = this.calcDMS(dd, isLat);
                
                if (isLat) {
                    point.lat_d = dms.d; point.lat_m = dms.m; point.lat_s = dms.s; point.lat_dir = dms.dir;
                } else {
                    point.lng_d = dms.d; point.lng_m = dms.m; point.lng_s = dms.s; point.lng_dir = dms.dir;
                }
            },
            syncFromDMS(index, isLat) {
                let point = this.points[index];
                
                let d = parseFloat(isLat ? point.lat_d : point.lng_d) || 0;
                let m = parseFloat(isLat ? point.lat_m : point.lng_m) || 0;
                let s = parseFloat(isLat ? point.lat_s : point.lng_s) || 0;
                let dir = isLat ? point.lat_dir : point.lng_dir;
                
                let dd = d + (m / 60) + (s / 3600);
                if (dir === 'S' || dir === 'W') {
                    dd = dd * -1;
                }
                
                if (isLat) {
                    point.lat_dd = dd.toFixed(6);
                } else {
                    point.lng_dd = dd.toFixed(6);
                }
            },
            formatPointsText(pts) {
                return pts.map((p, i) => {
                    if (!p.lat_dd && !p.lng_dd) return null;
                    return `Titik ${i+1}: Latitude ${p.lat_d}° ${p.lat_m}' ${p.lat_s}" ${p.lat_dir} (${p.lat_dd}), Longitude ${p.lng_d}° ${p.lng_m}' ${p.lng_s}" ${p.lng_dir} (${p.lng_dd})`;
                }).filter(Boolean).join('\n');
            }
        }
    }
</script>
HTML;

$newContent = substr($content, 0, $start) . $newScript . substr($content, $end);
file_put_contents('resources/views/livewire/kkprl-proposal-wizard.blade.php', $newContent);
echo "Done";
