const DATABASE_NAME = 'mutu-rsys-spreadsheet-drafts';
const DATABASE_VERSION = 1;
const STORE_NAME = 'drafts';

export const MAX_WORKBOOK_SNAPSHOT_BYTES = 6 * 1024 * 1024;

let databasePromise;

export const createSpreadsheetDraftKey = ({
    userId,
    unitId,
    indicatorId,
    year,
    month,
    schemaVersion,
    entryId,
    entryUpdatedAt,
}) =>
    `data-entry:${JSON.stringify([
        userId,
        unitId,
        indicatorId,
        year,
        month,
        schemaVersion,
        entryId,
        entryUpdatedAt,
    ])}`;

export const isValidSpreadsheetDraft = (draft, key) => {
    const sheetId = draft?.workbookSnapshot?.sheetOrder?.[0];
    const sheets = draft?.workbookSnapshot?.sheets;

    return (
        draft?.version === 1 &&
        draft.key === key &&
        draft.records !== null &&
        typeof draft.records === 'object' &&
        draft.workbookSnapshot !== null &&
        typeof draft.workbookSnapshot === 'object' &&
        Array.isArray(draft.workbookSnapshot.sheetOrder) &&
        draft.workbookSnapshot.sheetOrder.length === 1 &&
        typeof sheetId === 'string' &&
        sheets !== null &&
        typeof sheets === 'object' &&
        Object.prototype.hasOwnProperty.call(sheets, sheetId)
    );
};

export const getWorkbookSnapshotSize = (snapshot) => {
    let serializedSnapshot;

    try {
        serializedSnapshot = JSON.stringify(snapshot);
    } catch (error) {
        throw new Error(
            'Snapshot spreadsheet tidak dapat diserialisasi. Periksa kembali perubahan terakhir.',
            { cause: error },
        );
    }

    if (typeof serializedSnapshot !== 'string') {
        throw new Error('Snapshot spreadsheet kosong atau tidak valid.');
    }

    const snapshotSize = new TextEncoder().encode(
        serializedSnapshot,
    ).byteLength;
    if (snapshotSize > MAX_WORKBOOK_SNAPSHOT_BYTES) {
        throw new Error(
            'Ukuran workbook melebihi batas penyimpanan 6 MB. Kurangi data atau elemen spreadsheet sebelum menyimpan.',
        );
    }

    return snapshotSize;
};

const openDatabase = () => {
    if (typeof indexedDB === 'undefined') {
        return Promise.reject(
            new Error('Penyimpanan draft lokal tidak tersedia di browser ini.'),
        );
    }

    if (!databasePromise) {
        databasePromise = new Promise((resolve, reject) => {
            const request = indexedDB.open(DATABASE_NAME, DATABASE_VERSION);

            request.onupgradeneeded = () => {
                const database = request.result;
                if (!database.objectStoreNames.contains(STORE_NAME)) {
                    database.createObjectStore(STORE_NAME);
                }
            };
            request.onsuccess = () => resolve(request.result);
            request.onerror = () =>
                reject(
                    request.error ||
                        new Error('Database draft lokal gagal dibuka.'),
                );
            request.onblocked = () =>
                reject(
                    new Error(
                        'Database draft lokal sedang digunakan oleh tab lain.',
                    ),
                );
        }).catch((error) => {
            databasePromise = null;
            throw error;
        });
    }

    return databasePromise;
};

const runRequest = async (mode, createRequest) => {
    const database = await openDatabase();

    return new Promise((resolve, reject) => {
        const transaction = database.transaction(STORE_NAME, mode);
        const store = transaction.objectStore(STORE_NAME);
        let request;
        let result;

        try {
            request = createRequest(store);
        } catch (error) {
            reject(error);
            return;
        }

        request.onsuccess = () => {
            result = request.result;
        };
        request.onerror = () =>
            reject(request.error || new Error('Operasi draft lokal gagal.'));
        transaction.oncomplete = () => resolve(result);
        transaction.onerror = () =>
            reject(
                transaction.error ||
                    request.error ||
                    new Error('Transaksi draft lokal gagal.'),
            );
        transaction.onabort = () =>
            reject(
                transaction.error ||
                    request.error ||
                    new Error('Transaksi draft lokal dibatalkan.'),
            );
    });
};

export const loadSpreadsheetDraft = (key) =>
    runRequest('readonly', (store) => store.get(key));

export const saveSpreadsheetDraft = (key, draft) =>
    runRequest('readwrite', (store) => store.put(draft, key));

export const deleteSpreadsheetDraft = (key) =>
    runRequest('readwrite', (store) => store.delete(key));
