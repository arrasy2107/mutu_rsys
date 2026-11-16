import assert from 'node:assert/strict';
import test from 'node:test';
import {
    createSpreadsheetDraftKey,
    getWorkbookSnapshotSize,
    isValidSpreadsheetDraft,
    MAX_WORKBOOK_SNAPSHOT_BYTES,
    deleteSpreadsheetDraft,
    loadSpreadsheetDraft,
    saveSpreadsheetDraft,
} from '../../resources/js/Components/FormRenderers/spreadsheetDraftStorage.js';

const createDraftKey = (overrides = {}) =>
    createSpreadsheetDraftKey({
        userId: 7,
        unitId: 2,
        indicatorId: 15,
        year: 2026,
        month: 1,
        schemaVersion: 3,
        entryId: 11,
        entryUpdatedAt: '2026-11-12T08:00:00.000000Z',
        ...overrides,
    });

test('isolates spreadsheet drafts by user, period, and saved entry revision', () => {
    const key = createDraftKey();

    assert.notEqual(key, createDraftKey({ userId: 8 }));
    assert.notEqual(key, createDraftKey({ month: 2 }));
    assert.notEqual(key, createDraftKey({ entryUpdatedAt: 'newer-revision' }));
});

test('only accepts a matching draft with its referenced worksheet', () => {
    const key = createDraftKey();
    const draft = {
        version: 1,
        key,
        records: [{ numerator: 1 }],
        workbookSnapshot: {
            sheetOrder: ['sheet-1'],
            sheets: { 'sheet-1': { cellData: {} } },
        },
    };

    assert.equal(isValidSpreadsheetDraft(draft, key), true);
    assert.equal(
        isValidSpreadsheetDraft(draft, createDraftKey({ month: 2 })),
        false,
    );
    assert.equal(
        isValidSpreadsheetDraft(
            {
                ...draft,
                workbookSnapshot: { sheetOrder: ['missing'], sheets: {} },
            },
            key,
        ),
        false,
    );
});

test('measures serialized workbook bytes and rejects snapshots above the server limit', () => {
    assert.equal(getWorkbookSnapshotSize({ cell: 'Triase ✓' }), 21);
    assert.throws(
        () =>
            getWorkbookSnapshotSize({
                cell: 'x'.repeat(MAX_WORKBOOK_SNAPSHOT_BYTES),
            }),
        /melebihi batas penyimpanan 6 MB/,
    );
});

test('reports unavailable browser storage instead of silently losing a draft', async () => {
    const descriptor = Object.getOwnPropertyDescriptor(globalThis, 'indexedDB');

    try {
        Object.defineProperty(globalThis, 'indexedDB', {
            configurable: true,
            value: undefined,
        });

        await assert.rejects(
            saveSpreadsheetDraft('test-key', { version: 1 }),
            /Penyimpanan draft lokal tidak tersedia/,
        );
    } finally {
        if (descriptor) {
            Object.defineProperty(globalThis, 'indexedDB', descriptor);
        } else {
            delete globalThis.indexedDB;
        }
    }
});

test('stores, reads, and removes drafts through IndexedDB transactions', async () => {
    const descriptor = Object.getOwnPropertyDescriptor(globalThis, 'indexedDB');
    const storedDrafts = new Map();
    let hasStore = false;
    const database = {
        objectStoreNames: {
            contains: (name) => hasStore && name === 'drafts',
        },
        createObjectStore: () => {
            hasStore = true;
        },
        transaction: () => {
            const transaction = {};
            transaction.objectStore = () => ({
                get: (key) =>
                    createFakeRequest(transaction, () =>
                        structuredClone(storedDrafts.get(key)),
                    ),
                put: (draft, key) =>
                    createFakeRequest(transaction, () => {
                        storedDrafts.set(key, structuredClone(draft));
                        return key;
                    }),
                delete: (key) =>
                    createFakeRequest(transaction, () => {
                        storedDrafts.delete(key);
                    }),
            });

            return transaction;
        },
    };
    const fakeIndexedDB = {
        open: () => {
            const request = {};
            queueMicrotask(() => {
                request.result = database;
                request.onupgradeneeded?.();
                request.onsuccess?.();
            });
            return request;
        },
    };

    try {
        Object.defineProperty(globalThis, 'indexedDB', {
            configurable: true,
            value: fakeIndexedDB,
        });

        const draft = { version: 1, records: [{ numerator: 1 }] };
        await saveSpreadsheetDraft('test-key', draft);
        assert.deepEqual(await loadSpreadsheetDraft('test-key'), draft);
        await deleteSpreadsheetDraft('test-key');
        assert.equal(await loadSpreadsheetDraft('test-key'), undefined);
    } finally {
        if (descriptor) {
            Object.defineProperty(globalThis, 'indexedDB', descriptor);
        } else {
            delete globalThis.indexedDB;
        }
    }
});

const createFakeRequest = (transaction, operation) => {
    const request = {};
    queueMicrotask(() => {
        try {
            request.result = operation();
            request.onsuccess?.();
        } catch (error) {
            request.error = error;
            request.onerror?.();
        }

        queueMicrotask(() => transaction.oncomplete?.());
    });

    return request;
};
