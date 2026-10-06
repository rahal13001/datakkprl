import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const view = readFileSync(new URL('../../resources/views/livewire/kkprl-proposal-wizard.blade.php', import.meta.url), 'utf8');
const script = view.slice(view.lastIndexOf('<script>') + 8, view.lastIndexOf('</script>'));
const context = {};
vm.runInNewContext(script, context);

function createManager() {
    const manager = context.coordinateManager('[]', 'point', {});
    manager.drawOnMap = () => {};
    return manager;
}

test('map numbering restarts at one when every coordinate row has been emptied', () => {
    const manager = createManager();
    manager.addPointFromMap(-6.2, 106.8);
    manager.addPointFromMap(-6.3, 106.9);
    for (const point of manager.points) {
        point.lat_dd = '';
        point.lng_dd = '';
    }

    manager.addPointFromMap(-7.1, 107.2);
    manager.addPointFromMap(-7.2, 107.3);

    assert.equal(manager.points.length, 2);
    assert.match(manager.formatPointsText(manager.points), /^Titik 1:.*\nTitik 2:/);
    assert.doesNotMatch(manager.formatPointsText(manager.points), /Titik [34]:/);
});

test('map clicks fill an empty row before appending another point', () => {
    const manager = createManager();
    manager.addPointFromMap(-6.2, 106.8);
    manager.addPoint();
    manager.addPointFromMap(-6.3, 106.9);

    assert.equal(manager.points.length, 2);
    assert.equal(manager.points[0].lat_dd, '-6.200000');
    assert.equal(manager.points[1].lat_dd, '-6.300000');
});

test('a partially filled coordinate is preserved when clicking the map', () => {
    const manager = createManager();
    manager.addPoint();
    manager.points[0].lat_dd = '-6.2';
    manager.addPointFromMap(-7.1, 107.2);

    assert.equal(manager.points.length, 2);
    assert.equal(manager.points[0].lat_dd, '-6.2');
    assert.equal(manager.points[0].lng_dd, '');
    assert.equal(manager.points[1].lat_dd, '-7.100000');
});

test('clear map removes all previous coordinates and resets repeated drawing', () => {
    const manager = createManager();
    for (let cycle = 0; cycle < 3; cycle++) {
        manager.addPointFromMap(-6.2, 106.8);
        manager.addPointFromMap(-6.3, 106.9);
        manager.clearMap();

        assert.equal(manager.points.length, 1);
        assert.equal(manager.formatPointsText(manager.points), '');
        manager.addPointFromMap(-7.1, 107.2);
        assert.equal(manager.points.length, 1);
        assert.match(manager.formatPointsText(manager.points), /^Titik 1:/);
    }
});

test('deleting every row resets the next point to one', () => {
    const manager = createManager();
    manager.addPointFromMap(-6.2, 106.8);
    manager.addPointFromMap(-6.3, 106.9);
    manager.removePoint(1);
    manager.removePoint(0);
    manager.addPointFromMap(-7.1, 107.2);

    assert.equal(manager.points.length, 1);
    assert.match(manager.formatPointsText(manager.points), /^Titik 1:/);
});

test('empty DMS inputs clear the decimal coordinates so drawing restarts at one', () => {
    const manager = createManager();
    manager.addPointFromMap(-6.2, 106.8);
    manager.addPointFromMap(-6.3, 106.9);
    for (const [index, point] of manager.points.entries()) {
        for (const field of ['lat_d', 'lat_m', 'lat_s', 'lng_d', 'lng_m', 'lng_s']) point[field] = '';
        manager.syncFromDMS(index, true);
        manager.syncFromDMS(index, false);
        assert.equal(point.lat_dd, '');
        assert.equal(point.lng_dd, '');
    }
    manager.addPointFromMap(-7.1, 107.2);
    assert.equal(manager.points.length, 1);
    assert.match(manager.formatPointsText(manager.points), /^Titik 1:/);
});

test('zero degrees remain a valid coordinate instead of an empty row', () => {
    const manager = createManager();
    manager.addPoint();
    manager.points[0].lat_d = '0';
    manager.points[0].lng_d = '0';
    manager.syncFromDMS(0, true);
    manager.syncFromDMS(0, false);
    manager.addPointFromMap(-7.1, 107.2);

    assert.equal(manager.points.length, 2);
    assert.equal(Number(manager.points[0].lat_dd), 0);
    assert.equal(Number(manager.points[0].lng_dd), 0);
});
