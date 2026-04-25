#!/usr/bin/env python3
import csv, os, sys, time
from collections import Counter

BASE_DIR = os.path.dirname(os.path.abspath(__file__))

NORMALIZE = {
    'CHEM':'CHE','CHEMIN':'CHE','HAMEAU':'HAM','DOMAINE':'DOM',
    'ROUTE':'RTE','ALLEE':'ALL','FAUBOURG':'FG','PLACE':'PL',
    'IMPASSE':'IMP','AVENUE':'AV','MONTEE':'MTE','COURS':'CRS',
    'RUELLE':'RLE','SENTIER':'SEN','VILLA':'VLA','SQUARE':'SQ',
}

def normalize_voie(nom):
    if not nom: return nom
    parts = nom.split(' ', 1)
    first = parts[0].upper()
    if first in NORMALIZE:
        return NORMALIZE[first] + (' ' + parts[1] if len(parts) > 1 else '')
    return nom

def process_file(fpath):
    t0 = time.time()
    with open(fpath, encoding='utf-8', newline='') as f:
        reader = csv.DictReader(f)
        fieldnames = reader.fieldnames
        rows = list(reader)
    changes = Counter()
    for row in rows:
        voie = row.get('adresse_nom_voie', '') or ''
        new_voie = normalize_voie(voie)
        if new_voie != voie:
            changes[voie.split()[0]] += 1
            row['adresse_nom_voie'] = new_voie
    tmp = fpath + '.tmp'
    with open(tmp, 'w', encoding='utf-8', newline='') as f:
        writer = csv.DictWriter(f, fieldnames=fieldnames)
        writer.writeheader()
        writer.writerows(rows)
    os.replace(tmp, fpath)
    return {'rows': len(rows), 'changes': sum(changes.values()), 'detail': changes, 'duration': time.time()-t0}

files = sorted(f for f in os.listdir(BASE_DIR)
               if f.startswith('ValeursFoncieres-') and f.endswith('_v6.csv'))
print(f'{"="*60}\n  normalize_voies.py\n{"="*60}\n{len(files)} fichiers\n')
total = 0
t_global = time.time()
for fname in files:
    fpath = os.path.join(BASE_DIR, fname)
    size = os.path.getsize(fpath)/1_048_576
    print(f'  {fname} ({size:.0f} Mo)...', end=' ', flush=True)
    r = process_file(fpath)
    total += r['changes']
    print(f'{r["changes"]:,} corrections ({r["duration"]:.1f}s)')
    for v, n in r['detail'].most_common():
        print(f'      {v} -> {NORMALIZE[v]}  x{n:,}')
print(f'\n{"="*60}\n  Total : {total:,} corrections en {time.time()-t_global:.0f}s\n{"="*60}')
