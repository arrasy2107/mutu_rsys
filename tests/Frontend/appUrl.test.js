import assert from 'node:assert/strict';
import test from 'node:test';
import { appUrl } from '../../resources/js/Utils/appUrl.js';

test('keeps application URLs under a configured subpath', () => {
    assert.equal(appUrl('/storage/evidence.pdf', '/mutu/'), '/mutu/storage/evidence.pdf');
    assert.equal(appUrl('images/logo_rsys.png', '/mutu'), '/mutu/images/logo_rsys.png');
});

test('keeps application URLs at the root when no subpath is configured', () => {
    assert.equal(appUrl('/images/logo_rsys.png', '/'), '/images/logo_rsys.png');
});
