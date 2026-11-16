import openpyxl, msoffcrypto, io

wb = openpyxl.load_workbook(r'public\Sample Data\III. Manajemen Resiko\MR RSYS 2026.xlsx', data_only=True)
words = set()
for s in wb.sheetnames:
    ws = wb[s]
    for r in range(1, min(ws.max_row+1, 50)):
        for c in range(1, min(ws.max_column+1, 25)):
            v = ws.cell(r, c).value
            if v and isinstance(v, str):
                for token in v.split():
                    clean_token = ''.join(ch for ch in token if ch.isalnum())
                    if 3 <= len(clean_token) <= 15:
                        words.add(clean_token)

print(f'Extracted {len(words)} words from MR RSYS 2026.xlsx')

with open(r'public\Sample Data\IV. HVA\HVA RSYS 2026.xlsx', 'rb') as f:
    of = msoffcrypto.OfficeFile(f)
    found = False
    for w in words:
        for cand in [w, w.lower(), w.upper(), w.title(), w + '2026', w.lower() + '2026', w.upper() + '2026']:
            try:
                of.load_key(password=cand)
                dec = io.BytesIO()
                of.decrypt(dec)
                print(f'SUCCESS! Password is: {repr(cand)}')
                found = True
                with open(r'scratch\decrypted_hva_rsys_2026.xlsx', 'wb') as out:
                    out.write(dec.getvalue())
                break
            except Exception:
                pass
        if found:
            break
    if not found:
        print('Not found among extracted words')

