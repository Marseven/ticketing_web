# Bascule finale MyTicketO (legacy) → Primea — Runbook

Objectif : basculer sans **perdre les ventes de dernière minute** ni casser la
**scannabilité** des billets déjà vendus/imprimés. Fenêtre prévue : **~1 heure**
(gel des écritures legacy + migration + tests automatiques).

## Principe : gel court plutôt que resync incrémentale

Le legacy `leweb_ticket` **n'a pas d'`updated_at`** : impossible de détecter de
façon fiable les **changements de statut** (billets scannés après un 1er dump).
Une resync « en continu » laisserait donc échapper ces changements.

→ On prend une **coupure courte des écritures legacy** (mode maintenance). Une
fois le legacy gelé, le **dump final est l'état complet et figé** : un
ré-import `--fresh` reproduit exactement la réalité. Pas de delta partiel à
calculer — le dump final EST le delta. L'import est **idempotent** (upsert par
`legacy_id`, `ref` préservé comme `code`), donc rejouable sans doublon.

⚠️ Le ré-import `--fresh` **purge et remplace** les données métier Primea. Ce
n'est sûr que parce que, pendant la fenêtre de bascule, **Primea ne prend pas
encore de ventes** (le trafic public est toujours sur le legacy jusqu'au switch
DNS final). Ne jamais lancer `--fresh` après avoir ouvert les ventes sur Primea.

## Pré-requis (avant le jour J)

1. Import initial déjà fait et testé (mapping, scans physiques + numériques).
2. Cron opérationnel sur le lab (`* * * * *`, `/usr/bin/php`) — cf.
   [lab deploy]. La file de jobs traite l'import en arrière-plan.
3. `php artisan storage:link` fait (images).
4. Accès au mode maintenance du legacy (myticket-o.net) pour geler les écritures.

## Déroulé de la fenêtre (~1 h)

1. **Geler le legacy** : mettre myticket-o.net en maintenance (plus aucune
   vente ni scan côté legacy). Noter l'heure.
2. **Export final** : dumper la base legacy complète (`leweb_*`).
3. **Charger le dump** dans Primea via l'admin « Import MyTicketO » (ou
   `mysql < dump.sql`).
4. **Import complet** (remplacement depuis le dump figé), en taggant le stock
   physique :
   ```bash
   php artisan legacy:import --fresh --all --physical-events=156
   ```
   (ou via le bouton « Lancer l'import » ; pour le tag physique sur des données
   déjà importées sans ré-import : `php artisan legacy:tag-physical 156`.)
5. **Images** : bouton « Importer les images » (ou `legacy:import-images`).
6. **Vérification automatique (garde go/no-go)** :
   ```bash
   php artisan legacy:verify --all
   ```
   - Compare les comptes legacy vs importés.
   - **Bloque (exit ≠ 0)** si un seul billet legacy n'est pas scannable en
     Primea (`ref` absente comme `code`). Ne pas basculer tant que ce n'est pas
     vert.
7. **Tests de scan** : `php artisan test --filter=TicketScan` + un scan réel
   d'un billet physique et d'un billet numérique depuis l'app mobile.
8. **Bascule DNS** myticket-o.net → Primea. Lever la maintenance.
9. **Post-bascule** : surveiller les premiers scans + la file de paiements.

## Risques et parades

| Risque | Parade |
|---|---|
| Ventes pendant l'export→bascule | Gel des écritures legacy (maintenance) sur la fenêtre |
| Billet vendu non scannable | `legacy:verify` bloque si un `ref` manque |
| Double-import / doublons | Idempotence par `legacy_id` ; `--fresh` purge d'abord |
| Statut de scan perdu (pas d'`updated_at`) | Dump **final figé** = état complet, pas de resync partielle |
| Physique vs numérique en repporting | `--physical-events` / `legacy:tag-physical` |
| Validation d'accès dépendante du compte user | Non : scan par `code`+event+statut (service unifié) |

## Points à valider avec les vraies données

- L'ID legacy exact du (des) événement(s) à stock **physique** (ici 156).
- Le périmètre : `--all` (tout l'historique) vs à-venir seulement.
- Que le format QR des billets physiques imprimés correspond bien au `code`
  stocké (test de scan réel avant bascule).
