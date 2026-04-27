#!/usr/bin/env python3
"""
generate_results.py — Générateur de rapports SEO Estimatiz (V2 — un rapport par RUE)

CHANGEMENT MAJEUR vs V1 :
- ❌ V1 générait un rapport par NUMÉRO sur la rue → 80 rapports quasi-identiques
     pour la rue de Rivoli = thin content, risque de pénalité Google Panda.
- ✅ V2 génère UN rapport par RUE → contenu unique, plus dense, évite la duplication.

Usage :
  python generate_results.py --voie "RUE VOLTAIRE" --commune "Paris"
  python generate_results.py --dept 75 --min-trans 10

  Pour le moment on a generer le 75, 92, 93,94
"""

import argparse
import os
import sys
import mysql.connector
import numpy as np
import requests
from datetime import datetime, date
from xml.etree import ElementTree as ET

# ── Configuration ────────────────────────────────────────────────────────────
DB_HOST     = 'localhost'
DB_NAME     = 'DVF_France'
DB_USER     = 'root'
DB_PASSWORD = ''
DB_TABLE    = 'dvf_france'

API_URL      = 'http://localhost/estimatiz/api/save-rapport-seo'
PROD_BASE    = 'https://www.estimatiz.fr'
LOCAL_PREFIX = 'http://localhost/estimatiz'
MIN_TRANS    = 10    # transactions minimum après filtrage IQR
MAX_ROWS     = 25    # lignes affichées dans le tableau du rapport

SITEMAP_FILE = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'sitemap-rapports.xml')
SITEMAP_NS   = 'http://www.sitemaps.org/schemas/sitemap/0.9'

# ── Sitemap ──────────────────────────────────────────────────────────────────
def to_prod_url(local_url: str) -> str:
    if local_url.startswith(LOCAL_PREFIX):
        return PROD_BASE + local_url[len(LOCAL_PREFIX):]
    return local_url

def update_sitemap(new_urls: list) -> None:
    ns = SITEMAP_NS
    ET.register_namespace('', ns)

    existing_locs: set[str] = set()
    if os.path.exists(SITEMAP_FILE):
        try:
            tree = ET.parse(SITEMAP_FILE)
            root = tree.getroot()
            for url_el in root.findall(f'{{{ns}}}url'):
                loc = url_el.find(f'{{{ns}}}loc')
                if loc is not None and loc.text:
                    existing_locs.add(loc.text)
        except ET.ParseError:
            root = ET.Element('urlset')
            root.set('xmlns', ns)
            tree = ET.ElementTree(root)
    else:
        root = ET.Element('urlset')
        root.set('xmlns', ns)
        tree = ET.ElementTree(root)

    today = datetime.now().strftime('%Y-%m-%d')
    added = 0
    for local_url in new_urls:
        prod_url = to_prod_url(local_url)
        if prod_url not in existing_locs:
            url_el = ET.SubElement(root, 'url')
            ET.SubElement(url_el, 'loc').text        = prod_url
            ET.SubElement(url_el, 'lastmod').text    = today
            ET.SubElement(url_el, 'changefreq').text = 'monthly'
            ET.SubElement(url_el, 'priority').text   = '0.7'
            existing_locs.add(prod_url)
            added += 1

    if added > 0:
        ET.indent(tree, space='  ')
        tree.write(SITEMAP_FILE, encoding='UTF-8', xml_declaration=True)
        print(f"  → sitemap-rapports.xml mis à jour : +{added} URL (total {len(existing_locs)})")

# ── Connexion DB ─────────────────────────────────────────────────────────────
def get_db():
    return mysql.connector.connect(
        host=DB_HOST, database=DB_NAME,
        user=DB_USER, password=DB_PASSWORD,
        charset='utf8mb4'
    )

# ── Requêtes ─────────────────────────────────────────────────────────────────
def fetch_transactions(cursor, voie: str, commune_prefix: str) -> list:
    sql = """
        SELECT
            TRIM(CONCAT(
                COALESCE(adresse_numero, ''), ' ',
                COALESCE(adresse_suffixe, ''), ' ',
                adresse_nom_voie, ', ', nom_commune
            )) AS adresse,
            valeur_fonciere,
            COALESCE(lot1_surface_carrez, surface_reelle_bati) AS surface,
            nombre_pieces_principales AS nb_pieces,
            date_mutation,
            adresse_code_voie,
            code_postal,
            nom_commune,
            code_departement
        FROM dvf_france
        WHERE adresse_nom_voie = %s
          AND nom_commune LIKE %s
          AND type_local IN ('Appartement', 'Maison')
          AND valeur_fonciere >= 10000
          AND COALESCE(lot1_surface_carrez, surface_reelle_bati) > 0
        ORDER BY date_mutation DESC
    """
    cursor.execute(sql, (voie.upper(), commune_prefix + '%'))
    return cursor.fetchall()

def fetch_streets_by_dept(cursor, dept: str, min_trans: int) -> list:
    sql = """
        SELECT adresse_nom_voie, nom_commune, code_postal,
               COUNT(*) AS n
        FROM dvf_france
        WHERE code_departement = %s
          AND type_local IN ('Appartement', 'Maison')
          AND valeur_fonciere >= 10000
          AND COALESCE(lot1_surface_carrez, surface_reelle_bati) > 0
          AND adresse_nom_voie IS NOT NULL
        GROUP BY adresse_nom_voie, nom_commune, code_postal
        HAVING COUNT(*) >= %s
        ORDER BY n DESC
    """
    cursor.execute(sql, (dept, min_trans))
    return cursor.fetchall()

# ── Calculs statistiques ─────────────────────────────────────────────────────
def add_prix_m2(rows: list) -> list:
    result = []
    for r in rows:
        surf = float(r['surface']) if r['surface'] else 0
        val  = float(r['valeur_fonciere']) if r['valeur_fonciere'] else 0
        if surf > 0 and val > 0:
            r['prix_m2'] = round(val / surf, 2)
            result.append(r)
    return result

def filter_iqr(rows: list, k: float = 1.5) -> list:
    if len(rows) < 4:
        return rows
    pm2 = [r['prix_m2'] for r in rows]
    q1, q3 = np.percentile(pm2, [25, 75])
    iqr = q3 - q1
    return [r for r in rows if q1 - k * iqr <= r['prix_m2'] <= q3 + k * iqr]

def compute_estimation(rows: list) -> dict:
    pm2 = [r['prix_m2'] for r in rows]
    n = len(pm2)
    p20 = float(np.percentile(pm2, 20))
    p50 = float(np.percentile(pm2, 50))
    p80 = float(np.percentile(pm2, 80))
    conf = 85 if n >= 30 else (65 if n >= 15 else 40)
    return {
        'p20': round(p20),
        'p50': round(p50),
        'p80': round(p80),
        'conf': conf,
        'count': n,
    }

# ── Construction du payload ──────────────────────────────────────────────────
# CHANGEMENT V2 : suppression du paramètre `numero` — un rapport par RUE
def build_payload(voie: str, rows: list, est: dict) -> dict:
    first     = rows[0]
    cp        = str(first['code_postal']) if first['code_postal'] else ''
    commune   = str(first['nom_commune']) if first['nom_commune'] else ''
    code_voie = str(first['adresse_code_voie']) if first['adresse_code_voie'] else ''
    dept      = str(first.get('code_departement', '')) or (cp[:2] if cp else '')

    # Label = "Rue Voltaire, Paris 11e Arrondissement" (pas de numéro)
    label = f"{voie.title()}, {commune}"

    payload_rows = []
    for r in rows[:MAX_ROWS]:
        dt = r['date_mutation']
        date_str = dt.strftime('%Y-%m-%d') if isinstance(dt, (datetime, date)) else str(dt)
        payload_rows.append({
            'adresse':         str(r['adresse']).strip(),
            'valeur_fonciere': float(r['valeur_fonciere']),
            'surface':         float(r['surface']),
            'prix_m2':         r['prix_m2'],
            'nb_pieces':       int(r['nb_pieces']) if r['nb_pieces'] else None,
            'date_mutation':   date_str,
        })

    return {
        'label':      label,
        'surface':    None,
        'pieces':     None,
        'surfaceMin': None,
        'surfaceMax': None,
        'suggestion': {
            'cp':        cp,
            'voie':      voie,
            'commune':   commune,
            'code_voie': code_voie,
            'dept':      dept,
        },
        'estimation': est,
        'rows':       payload_rows,
    }

# ── Préfixe commune ──────────────────────────────────────────────────────────
def commune_prefix(commune: str) -> str:
    c = commune.strip().lower()
    if c.startswith('paris'):      return 'Paris'
    if c.startswith('lyon'):       return 'Lyon'
    if c.startswith('marseille'):  return 'Marseille'
    return commune.strip()

# ── Génération par rue ───────────────────────────────────────────────────────
# CHANGEMENT V2 : retourne UN seul URL (pas un par numéro)
def generate_by_street(voie: str, commune: str) -> list[str]:
    prefix = commune_prefix(commune)
    db     = get_db()
    cursor = db.cursor(dictionary=True)
    rows = fetch_transactions(cursor, voie, prefix)
    cursor.close()
    db.close()

    if not rows:
        print(f"  ✗ Aucune transaction : {voie}, {commune}")
        return []

    rows = add_prix_m2(rows)
    rows = filter_iqr(rows)

    if len(rows) < MIN_TRANS:
        print(f"  ✗ Moins de {MIN_TRANS} transactions après filtrage ({len(rows)}) : {voie}, {commune}")
        return []

    est = compute_estimation(rows)
    print(f"  {est['count']} ventes | P20={est['p20']} · P50={est['p50']} · P80={est['p80']} €/m² | confiance {est['conf']}%")

    # Un seul payload par rue
    payload = build_payload(voie, rows, est)
    try:
        resp = requests.post(API_URL, json=payload, timeout=30)
        resp.raise_for_status()
        data = resp.json()
    except Exception as e:
        print(f"    ✗ Erreur HTTP : {e}")
        return []

    if not data.get('ok'):
        print(f"    ✗ Erreur API : {data.get('error')}")
        return []

    url = data['url']
    print(f"    ✓ {voie.title()}, {commune} → {url}")
    update_sitemap([url])
    return [url]

# ── Génération par département ───────────────────────────────────────────────
def generate_by_dept(dept: str, min_trans: int) -> None:
    db      = get_db()
    cursor  = db.cursor(dictionary=True)
    streets = fetch_streets_by_dept(cursor, dept, min_trans)
    cursor.close()
    db.close()

    print(f"\n  {len(streets)} rues éligibles dans le département {dept} (min {min_trans} transactions)\n")

    total_urls = 0
    for i, s in enumerate(streets, 1):
        print(f"  [{i}/{len(streets)}] {s['adresse_nom_voie']}, {s['nom_commune']} ({s['n']} ventes)")
        urls = generate_by_street(s['adresse_nom_voie'], s['nom_commune'])
        total_urls += len(urls)

    print(f"\n  ✓ {total_urls} rapports générés pour {len(streets)} rues")

# ── CLI ───────────────────────────────────────────────────────────────────────
def main():
    parser = argparse.ArgumentParser(
        description='Générateur de rapports SEO Estimatiz (V2 — un rapport par rue)',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Exemples :
  python generate_results.py --voie "RUE VOLTAIRE" --commune "Paris"
  python generate_results.py --dept 75 --min-trans 10
  python generate_results.py --dept 69 --min-trans 15

⚠️ V2 : un rapport par RUE (plus par numéro). Si tu as déjà généré des rapports
en V1, supprimer le contenu de rapports/automatique/ avant de relancer.
        """
    )
    parser.add_argument('--voie',      help='Nom de la voie  ex: "RUE VOLTAIRE"')
    parser.add_argument('--commune',   help='Commune         ex: "Paris"')
    parser.add_argument('--dept',      help='Code département ex: "75"')
    parser.add_argument('--min-trans', type=int, default=MIN_TRANS,
                        help=f'Transactions minimum (défaut : {MIN_TRANS})')
    args = parser.parse_args()

    if args.voie and args.commune:
        print(f"\n  Génération : {args.voie.upper()}, {args.commune}\n")
        urls = generate_by_street(args.voie, args.commune)
        print(f"\n  ✓ {len(urls)} rapport généré")
    elif args.dept:
        generate_by_dept(args.dept, args.min_trans)
    else:
        parser.print_help()
        sys.exit(1)

if __name__ == '__main__':
    main()
