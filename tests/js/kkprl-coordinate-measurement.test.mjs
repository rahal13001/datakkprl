import test from 'node:test';
import assert from 'node:assert/strict';
import { coordinateMeasurement } from '../../resources/js/kkprl-coordinate-measurement.js';

const point = (lat, lng) => ({ lat_dd: lat, lng_dd: lng });
const square = [point(0, 0), point(0, 0.001), point(0.001, 0.001), point(0.001, 0)];

test('polygon reports a known small equatorial area in hectares with two decimals', () => {
    const result = coordinateMeasurement('polygon', square, '', '');
    assert.equal(result.status, 'ready');
    assert.ok(Math.abs(result.value - 1.2364345868) < 1e-8);
    assert.equal(result.formatted, '1,24');
    assert.equal(result.unit, 'ha');
    assert.equal(result.warning, '');
});

test('line sums every segment in kilometres rather than the endpoint distance', () => {
    const result = coordinateMeasurement('line', [point(0, 0), point(0, 0.01), point(0.01, 0.01)], '', '');
    assert.ok(Math.abs(result.value - 2.22390160467) < 1e-9);
    assert.equal(result.formatted, '2,22');
    assert.equal(result.unit, 'km');
});

test('comparison uses the displayed precision and names both mismatched values', () => {
    assert.equal(coordinateMeasurement('polygon', square, '1.24', 'Hektar (Ha)').warning, '');
    assert.equal(coordinateMeasurement('polygon', square, '1.239', 'Hektar (Ha)').warning, '');
    const result = coordinateMeasurement('polygon', square, '2', 'Hektar (Ha)');
    assert.equal(result.warning, 'mismatch');
    assert.equal(result.enteredFormatted, '2,00');
    assert.match(result.message, /1,24 ha/);
    assert.match(result.message, /2,00 ha/);
});

test('line compares kilometres and warns about incompatible units', () => {
    const points = [point(0, 0), point(0, 0.01)];
    assert.equal(coordinateMeasurement('line', points, '1.11', 'Kilometer (Km)').warning, '');
    assert.equal(coordinateMeasurement('line', points, '1.11', 'Hektar (Ha)').warning, 'unit');
    assert.equal(coordinateMeasurement('polygon', square, '1.24', 'Kilometer (Km)').warning, 'unit');
});

test('empty values are not zero, real zero compares, malformed dimensions get guidance', () => {
    assert.equal(coordinateMeasurement('polygon', square, '', 'Hektar (Ha)').warning, '');
    assert.equal(coordinateMeasurement('polygon', square, null, 'Hektar (Ha)').warning, '');
    assert.equal(coordinateMeasurement('polygon', square, '0', 'Hektar (Ha)').warning, 'mismatch');
    for (const value of ['abc', '1,24', '-1', 'Infinity', '1e3']) {
        assert.equal(coordinateMeasurement('polygon', square, value, 'Hektar (Ha)').warning, 'input');
    }
});

test('partial, nonfinite and out-of-range coordinates do not display a stale measurement', () => {
    for (const value of ['', null, '12junk', 'Infinity', 91]) {
        assert.equal(coordinateMeasurement('line', [point(0, 0), point(value, 1)], '1', 'Kilometer (Km)').status, 'incomplete');
    }
    assert.equal(coordinateMeasurement('polygon', square.slice(0, 2), '', '').status, 'incomplete');
    assert.equal(coordinateMeasurement('line', [point(0, 181), point(0, 1)], '', '').status, 'incomplete');
    assert.equal(coordinateMeasurement('polygon', [point('', '')], '', '').status, 'incomplete');
});

test('point has no measurement and zero-length line remains a real zero', () => {
    assert.equal(coordinateMeasurement('point', [point(0, 0)], '', '').status, 'point');
    const result = coordinateMeasurement('line', [point(0, 0), point(0, 0)], '0', 'Kilometer (Km)');
    assert.equal(result.formatted, '0,00');
    assert.equal(result.warning, '');
});

test('closed and reversed polygon rings have the same area without mutating points', () => {
    const saved = JSON.stringify(square);
    const expected = coordinateMeasurement('polygon', square, '', '').value;
    assert.equal(coordinateMeasurement('polygon', [...square].reverse(), '', '').value, expected);
    assert.equal(coordinateMeasurement('polygon', [...square, square[0]], '', '').value, expected);
    assert.equal(JSON.stringify(square), saved);
});

test('self-intersecting and degenerate polygons do not claim a usable area', () => {
    assert.equal(coordinateMeasurement('polygon', [square[0], square[2], square[1], square[3]], '', '').status, 'invalid');
    assert.equal(coordinateMeasurement('polygon', [point(0, 0), point(0, 0.01), point(0, 0.02)], '', '').status, 'invalid');
});

test('antimeridian uses short longitude differences for both shapes', () => {
    const line = coordinateMeasurement('line', [point(0, 179.999), point(0, -179.999)], '', '');
    assert.ok(Math.abs(line.value - 0.222390160467) < 1e-9);
    const polygon = coordinateMeasurement('polygon', [point(0, 179.999), point(0, -179.999), point(0.001, -179.999), point(0.001, 179.999)], '', '');
    assert.equal(polygon.status, 'ready');
    assert.ok(Math.abs(polygon.value - 2.4728691736) < 1e-7);
});
