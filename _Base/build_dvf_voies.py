#!/usr/bin/env python3
"""
build_dvf_voies.py — Construit la table dvf_voies depuis dvf_france.
dvf_voies contient les rues uniques (voie+cp+commune) pour l'autocomplete.
À relancer après chaque import annuel.
"""

import mysql.connector
import time
import sys

sys.path.insert(0, __file__.rsplit('/', 1)[0])
from import_dvf_france import DB_CONFIG

def main():
    conn = mysql.connector.connect(**DB_CONFIG)
    conn.autocommit = True
    c = conn.cursor()

    # Augmenter le buffer pool pour l'opération
    c.execute('SET GLOBAL innodb_buffer_pool_size = 536870912')
    print('Buffer pool → 512MB')

    c.execute('TRUNCATE TABLE dvf_voies')
    print('Table dvf_voies vidée. Streaming dvf_france...')

    cstream = conn.cursor(buffered=False)
    cstream.execute("""
        SELECT adresse_nom_voie, code_postal, code_commune, nom_commune, adresse_code_voie,
               SUBSTRING(id_parcelle, 9, 2), id_mutation
        FROM dvf_france
        WHERE adresse_nom_voie IS NOT NULL AND adresse_nom_voie != ''
    """)

    voies = {}
    t0 = time.time()
    n = 0
    for row in cstream:
        nom, cp, cc, commune, code_voie, section, id_mut = row
        key = (nom, cp or '', cc or '', commune or '', code_voie or '')
        if key not in voies:
            voies[key] = [code_voie or '', section or '', 0]
        voies[key][2] += 1
        n += 1
        if n % 1_000_000 == 0:
            print(f'  {n:,} lus, {len(voies):,} voies uniques  ({time.time()-t0:.0f}s)', flush=True)

    cstream.close()
    print(f'Lecture : {n:,} lignes → {len(voies):,} voies uniques  ({time.time()-t0:.0f}s)')

    cins = conn.cursor()
    batch = []
    inserted = 0
    for (nom, cp, cc, commune, code_voie), (cv, sec, nb) in voies.items():
        batch.append((nom, cp, cc, commune, cv, sec, nb))
        if len(batch) >= 5000:
            cins.executemany(
                'INSERT INTO dvf_voies (adresse_nom_voie, code_postal, code_commune, nom_commune, adresse_code_voie, section, nb_mutations) VALUES (%s,%s,%s,%s,%s,%s,%s)',
                batch
            )
            inserted += len(batch)
            batch = []
            if inserted % 200_000 == 0:
                print(f'  {inserted:,} insérés...', flush=True)

    if batch:
        cins.executemany(
            'INSERT INTO dvf_voies (adresse_nom_voie, code_postal, code_commune, nom_commune, adresse_code_voie, section, nb_mutations) VALUES (%s,%s,%s,%s,%s,%s,%s)',
            batch
        )
        inserted += len(batch)

    print(f'Total inséré : {inserted:,} lignes en {time.time()-t0:.0f}s')

    c.execute('SET GLOBAL innodb_buffer_pool_size = 134217728')
    print('Buffer pool remis à 128MB')
    conn.close()

if __name__ == '__main__':
    main()
