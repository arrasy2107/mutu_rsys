import os
import zipfile
import xml.etree.ElementTree as ET

folder = r"C:\laragon\www\mutu_rsys\public\Sample Data\Form Pengumpul Data"
files = [f for f in sorted(os.listdir(folder)) if f.endswith(".xlsx") and not f.startswith("~$")]

print(f"Total files: {len(files)}")

def inspect_file(fname):
    fpath = os.path.join(folder, fname)
    with zipfile.ZipFile(fpath, 'r') as z:
        # shared strings
        ss = []
        if 'xl/sharedStrings.xml' in z.namelist():
            tree = ET.fromstring(z.read('xl/sharedStrings.xml'))
            for si in tree.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}si'):
                t = si.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}t')
                if t is not None and t.text:
                    ss.append(t.text)
                else:
                    parts = [r.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}t').text 
                             for r in si.findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}r') 
                             if r.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}t') is not None 
                             and r.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}t').text]
                    ss.append("".join(parts))

        # workbook sheets
        wb_tree = ET.fromstring(z.read('xl/workbook.xml'))
        sheets = [s.get('name') for s in wb_tree.find('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}sheets').findall('{http://schemas.openxmlformats.org/spreadsheetml/2006/main}sheet')]
        
        # Look for sheet with indicator info
        # Let's inspect first sheet or sheet with 'profil' or 'indikator' or 'kamus'
        info_sheets = [s for s in sheets if any(k in s.lower() for k in ['profil', 'kamus', 'indikator', 'data & analisa', 'analisa', 'form'])]
        
        return {
            'file': fname,
            'sheets': sheets,
        }

for f in files:
    res = inspect_file(f)
    print(f"[{res['file']}] Sheets: {', '.join(res['sheets'][:5])}")
