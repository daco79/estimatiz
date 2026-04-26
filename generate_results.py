#!/usr/bin/env python3
"""
generate_results.py — Générateur de pages statiques Estimatiz
Génère des pages HTML par adresse à partir de results.php (rendu PHP local).

Prérequis:
  pip3 install pymysql

Usage:
  python3 generate_results.py --dept 75
  python3 generate_results.py --dept 75 --year 2025 --street-only
  python3 generate_results.py --dept 75 --output /var/www/static

Options:
  --dept       Code département (ex: 75, 69, 13)  [REQUIS]
  --year       Année des mutations (défaut: 2025)
  --base-url   URL du serveur PHP local (défaut: http://localhost/estimatiz)
  --output     Répertoire de sortie (défaut: ./static)
  --delay      Délai entre requêtes en secondes (défaut: 0.05)
  --street-only  Génère uniquement les pages de rue (pas par numéro)
  --limit      Limite le nombre d'adresses traitées (pour tests)
"""

import argparse
import os
import re
import sys
import time
import unicodedata
import urllib.parse
import urllib.request
import urllib.error

try:
    import pymysql
    import pymysql.cursors
    HAS_PYMYSQL = True
except ImportError:
    HAS_PYMYSQL = False

# ── Configuration DB locale (XAMPP) ──────────────────────────────────────────
DB_CONFIG = {
    'host':     'localhost',
    'user':     'root',
    'password': '',
    'database': 'DVF_France',
    'charset':  'utf8mb4',
}

# ── Helpers ───────────────────────────────────────────────────────────────────

def slugify(text: str) -> str:
    """'RUE VOLTAIRE' → 'rue-voltaire'"""
    text = text.lower().strip()
    text = unicodedata.normalize('NFD', text)
    text = ''.join(c for c in text if unicodedata.category(c) != 'Mn')
    text = re.sub(r'[^a-z0-9]+', '-', text).strip('-')
    return text or 'adresse'


def fetch_page(url: str, retries: int = 3) -> str:
    for attempt in range(retries):
        try:
            with urllib.request.urlopen(url, timeout=30) as resp:
                return resp.read().decode('utf-8', errors='replace')
        except urllib.error.HTTPError as e:
            if e.code == 302:
                # Redirection → adresse invalide, on ignore
                raise ValueError(f"Redirect (adresse invalide?): {url}")
            raise
        except Exception as e:
            if attempt < retries - 1:
                time.sleep(1)
            else:
                raise


def save_page(url: str, out_path: str, delay: float, counters: dict) -> None:
    try:
        html = fetch_page(url)
        # Ne sauvegarder que les pages avec des résultats (pas les pages vides)
        if 'Aucune vente' in html or 'aucune mutation' in html.lower():
            counters['empty'] += 1
            return
        os.makedirs(os.path.dirname(out_path), exist_ok=True)
        with open(out_path, 'w', encoding='utf-8') as f:
            f.write(html)
        counters['ok'] += 1
        if counters['ok'] % 50 == 0:
            print(f"  … {counters['ok']} pages générées", flush=True)
    except ValueError:
        counters['skip'] += 1
    except Exception as e:
        counters['err'] += 1
        print(f"  [ERR] {out_path}: {e}", file=sys.stderr)
    finally:
        time.sleep(delay)


# ── Main ──────────────────────────────────────────────────────────────────────

def main():
    parser = argparse.ArgumentParser(
        description='Générateur de pages statiques Estimatiz',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog=__doc__,
    )
    parser.add_argument('--dept',        required=True,  help='Code département (ex: 75)')
    parser.add_argument('--year',        type=int, default=2025, help='Année (défaut: 2025)')
    parser.add_argument('--base-url',    default='http://localhost/estimatiz',
                        help='URL base du serveur PHP local')
    parser.add_argument('--output',      default='./static', help='Répertoire de sortie')
    parser.add_argument('--delay',       type=float, default=0.05,
                        help='Délai entre requêtes (secondes)')
    parser.add_argument('--street-only', action='store_true',
                        help='Pages de rue uniquement (pas par numéro)')
    parser.add_argument('--limit',       type=int, default=0,
                        help='Limite le nombre d\'adresses (0 = illimité, pour tests)')
    args = parser.parse_args()

    if not HAS_PYMYSQL:
        print("[ERREUR] pymysql n'est pas installé. Lancez : pip3 install pymysql")
        sys.exit(1)

    dept     = args.dept.strip()
    year     = args.year
    base_url = args.base_url.rstrip('/')
    out_root = args.output

    # ── Connexion DB ──────────────────────────────────────────────────────────
    print(f"[DB] Connexion à {DB_CONFIG['database']}@{DB_CONFIG['host']}…")
    try:
        conn = pymysql.connect(
            **DB_CONFIG,
            cursorclass=pymysql.cursors.DictCursor,
        )
    except Exception as e:
        print(f"[ERREUR] Connexion DB impossible: {e}")
        sys.exit(1)

    # ── Requête des adresses distinctes ──────────────────────────────────────
    print(f"[DB] Récupération des adresses, dept={dept}, année={year}…")
    with conn.cursor() as cur:
        sql = """
            SELECT DISTINCT
                adresse_code_voie   AS code_voie,
                code_postal         AS cp,
                nom_commune         AS commune,
                adresse_nom_voie    AS voie,
                adresse_numero      AS no_voie
            FROM dvf_france
            WHERE code_postal LIKE %s
              AND YEAR(date_mutation) = %s
              AND adresse_code_voie IS NOT NULL
              AND adresse_nom_voie  IS NOT NULL
            ORDER BY code_postal, adresse_nom_voie, adresse_numero
        """
        cur.execute(sql, (dept + '%', year))
        rows = cur.fetchall()
    conn.close()

    total = len(rows)
    if args.limit:
        rows = rows[:args.limit]
    print(f"[DB] {total} adresses trouvées" + (f" (limité à {args.limit})" if args.limit else "") + ".")

    # ── Génération des pages ──────────────────────────────────────────────────
    counters = {'ok': 0, 'err': 0, 'empty': 0, 'skip': 0}
    streets_done: set = set()

    print(f"[GEN] Génération vers {os.path.abspath(out_root)} …\n")

    for row in rows:
        code_voie = (row['code_voie'] or '').strip()
        cp        = (row['cp']        or '').strip()
        commune   = (row['commune']   or '').strip()
        voie      = (row['voie']      or '').strip()
        no_voie   = (row['no_voie']   or '').strip()

        if not (code_voie and cp and commune and voie):
            continue

        street_slug = slugify(voie)
        street_key  = (code_voie, cp)

        # ── Page de rue (une fois par rue) ───────────────────────────────────
        if street_key not in streets_done:
            streets_done.add(street_key)
            params = urllib.parse.urlencode({
                'code_voie': code_voie,
                'commune':   commune,
                'cp':        cp,
                'voie':      voie,
                'year':      year,
            })
            url      = f"{base_url}/results?{params}"
            out_path = os.path.join(out_root, str(year), cp, street_slug, 'index.html')
            save_page(url, out_path, args.delay, counters)

        # ── Page par numéro ───────────────────────────────────────────────────
        if args.street_only or not no_voie:
            continue

        no_slug  = slugify(no_voie) or no_voie
        params   = urllib.parse.urlencode({
            'code_voie': code_voie,
            'commune':   commune,
            'cp':        cp,
            'voie':      voie,
            'no_voie':   no_voie,
            'year':      year,
        })
        url      = f"{base_url}/results?{params}"
        out_path = os.path.join(out_root, str(year), cp, street_slug, no_slug, 'index.html')
        save_page(url, out_path, args.delay, counters)

    # ── Résumé ────────────────────────────────────────────────────────────────
    print(f"\n{'─'*50}")
    print(f"[OK]    Pages sauvegardées : {counters['ok']}")
    print(f"[VIDE]  Pages sans résultat: {counters['empty']}")
    print(f"[SKIP]  Redirections ignorées: {counters['skip']}")
    print(f"[ERR]   Erreurs            : {counters['err']}")
    print(f"[OUT]   {os.path.abspath(out_root)}/")
    print(f"{'─'*50}")


if __name__ == '__main__':
    main()
