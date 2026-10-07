// Informational spherical geometry; never writes proposal data or authorizes an action.
const EARTH_RADIUS = 6371008.8;
const RADIANS = Math.PI / 180;
const numberFormat = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

function decimal(value) {
    if (typeof value !== 'number' && typeof value !== 'string') return null;
    if (typeof value === 'string' && ! /^[+-]?(?:\d+(?:\.\d*)?|\.\d+)$/.test(value.trim())) return null;
    const result = Number(value);
    return Number.isFinite(result) ? result : null;
}

function longitudeDifference(from, to) {
    const difference = to - from;
    return difference > 180 ? difference - 360 : difference < -180 ? difference + 360 : difference;
}

function segmentLength(from, to) {
    const latitude = (to.lat - from.lat) * RADIANS;
    const longitude = longitudeDifference(from.lng, to.lng) * RADIANS;
    const haversine = Math.sin(latitude / 2) ** 2
        + Math.cos(from.lat * RADIANS) * Math.cos(to.lat * RADIANS) * Math.sin(longitude / 2) ** 2;
    return 2 * EARTH_RADIUS * Math.asin(Math.sqrt(Math.min(1, Math.max(0, haversine))));
}

function ringArea(points) {
    let sum = 0;
    for (let index = 0; index < points.length; index++) {
        const from = points[index];
        const to = points[(index + 1) % points.length];
        sum += longitudeDifference(from.lng, to.lng) * RADIANS
            * (Math.sin(from.lat * RADIANS) + Math.sin(to.lat * RADIANS));
    }
    return Math.abs(sum) * EARTH_RADIUS ** 2 / 2;
}

function cross(a, b, c) {
    return (b.lng - a.lng) * (c.lat - a.lat) - (b.lat - a.lat) * (c.lng - a.lng);
}

function onSegment(a, b, c) {
    return cross(a, b, c) === 0
        && c.lng >= Math.min(a.lng, b.lng) && c.lng <= Math.max(a.lng, b.lng)
        && c.lat >= Math.min(a.lat, b.lat) && c.lat <= Math.max(a.lat, b.lat);
}

function segmentsIntersect(a, b, c, d) {
    const abC = cross(a, b, c);
    const abD = cross(a, b, d);
    const cdA = cross(c, d, a);
    const cdB = cross(c, d, b);
    return (Math.sign(abC) * Math.sign(abD) < 0 && Math.sign(cdA) * Math.sign(cdB) < 0)
        || onSegment(a, b, c) || onSegment(a, b, d) || onSegment(c, d, a) || onSegment(c, d, b);
}

function selfIntersects(points) {
    // Unwrap longitudes so a small ring across the antimeridian stays local.
    const ring = points.map(point => ({ ...point }));
    for (let index = 1; index < ring.length; index++) {
        ring[index].lng = ring[index - 1].lng + longitudeDifference(points[index - 1].lng, points[index].lng);
    }
    for (let index = 0; index < ring.length; index++) {
        for (let other = index + 2; other < ring.length; other++) {
            if (index === 0 && other === ring.length - 1) continue;
            if (segmentsIntersect(ring[index], ring[(index + 1) % ring.length], ring[other], ring[(other + 1) % ring.length])) return true;
        }
    }
    return false;
}

function hundredths(value) {
    return Math.round((value + Number.EPSILON) * 100);
}

function format(value) {
    return numberFormat.format(hundredths(value) / 100);
}

export function coordinateMeasurement(shape, rawPoints, enteredValue, enteredUnit) {
    const result = { status: 'incomplete', value: null, formatted: '', unit: '', warning: '', message: '', enteredFormatted: '' };
    if (shape === 'point') return { ...result, status: 'point' };
    if (! ['polygon', 'line'].includes(shape) || ! Array.isArray(rawPoints)) return result;

    const points = rawPoints.map(point => ({ lat: decimal(point?.lat_dd), lng: decimal(point?.lng_dd) }));
    if (points.some(point => point.lat === null || point.lng === null || Math.abs(point.lat) > 90 || Math.abs(point.lng) > 180)) return result;
    if (shape === 'polygon' && points.length > 1 && points[0].lat === points.at(-1).lat && points[0].lng === points.at(-1).lng) points.pop();
    if (points.length < (shape === 'polygon' ? 3 : 2)) return result;

    let value;
    if (shape === 'polygon') {
        value = ringArea(points) / 10000;
        if (value <= Number.EPSILON || selfIntersects(points)) return { ...result, status: 'invalid' };
    } else {
        value = points.slice(1).reduce((total, point, index) => total + segmentLength(points[index], point), 0) / 1000;
    }

    result.status = 'ready';
    result.value = value;
    result.formatted = format(value);
    result.unit = shape === 'polygon' ? 'ha' : 'km';
    if (enteredValue === null || enteredValue === undefined || String(enteredValue).trim() === '') return result;

    const expectedUnit = shape === 'polygon' ? 'Hektar (Ha)' : 'Kilometer (Km)';
    if (enteredUnit !== expectedUnit) {
        return { ...result, warning: 'unit', message: `Untuk membandingkan ukuran peta, pilih satuan ${expectedUnit} pada isian Dimensi (Luas / Panjang).` };
    }
    const entered = decimal(enteredValue);
    if (entered === null || entered < 0) {
        return { ...result, warning: 'input', message: 'Ukuran isian belum dapat dibandingkan. Gunakan angka 0 atau lebih dan titik sebagai pemisah desimal.' };
    }
    result.enteredFormatted = format(entered);
    if (hundredths(value) !== hundredths(entered)) {
        result.warning = 'mismatch';
        result.message = `Ukuran peta ${result.formatted} ${result.unit} berbeda dari isian Anda ${result.enteredFormatted} ${result.unit}. Periksa koordinat atau isian dimensi.`;
    }
    return result;
}
