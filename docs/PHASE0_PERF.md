# Phase 0 — Performance & résilience à coût zéro (hébergement mutualisé)

Objectif : encaisser les **rafales d'ouverture de ventes** (partages WhatsApp/Facebook)
sans changer d'hébergement. Deux volets : ce qui est **dans le code** (déjà livré, il
suffit de déployer) et ce que **seul l'admin du lab peut faire** (`.env`, Cloudflare).

## 1. Livré dans le code (déploiement = `git pull`)

| Chantier | Effet |
|---|---|
| Cache 60 s de la **liste** et de la **fiche** publiques d'événement (`Client\EventController`) | Sous un pic, la BD n'est plus sollicitée par chaque visite ; la clé de la fiche inclut `updated_at` → invalidation automatique à la modification |
| **Limiteurs de débit** nommés (`AppServiceProvider`) branchés sur les routes sensibles | `auth` 10/min/IP · `payments` 10/min/IP · `lookup` 30/min/IP (récupération, suivi public) · `scan` 180/min/agent → réponse **429** au-delà |
| **Index composites** BD (`tickets(ticket_type_id,status)`, `tickets(event_id,status)`, `checkins(ticket_id,result)`) | Comptages de ventes, stats et anti-double-scan sans balayage |
| **En-têtes de cache** `.htaccess` | Assets Vite (hashés) : 1 an immutable · images/polices : 30 j · `index.php`/`sw.js` : jamais en cache |

> Effet de bord assumé : les compteurs de places d'une fiche peuvent avoir ≤ 60 s de
> retard (le checkout revalide la disponibilité de toute façon).

## 2. À faire sur le lab (une fois)

### 2.1 Déployer
```bash
cd /chemin/vers/primea_web
git pull origin main
/usr/bin/php artisan migrate --force        # index composites
/usr/bin/php artisan optimize               # config + routes + vues en cache
/usr/bin/php artisan queue:restart          # le worker cron recharge le code
/usr/bin/php artisan images:optimize        # variantes des affiches déjà en ligne
```

> ⚠️ **Le worker de file devient critique.** Les e-mails transactionnels
> (confirmation de commande, paiement reçu, billets prêts) partent désormais par
> la file : si le cron `queue:work` ne tourne pas, **plus aucun e-mail n'est
> envoyé**. Vérifier après déploiement : passer une commande de test et contrôler
> que la table `jobs` se vide et que le mail arrive (≤ 1 min).

> `images:optimize` ne touche pas aux originaux ; ajouter `--shrink` pour réduire
> aussi les masters de plus de 1600 px (irréversible — faire une copie de
> `storage/app/public` avant), ou `--dry-run` pour simuler.

### 1 bis. Ce que le déploiement change pour les visiteurs

| Avant | Après |
|---|---|
| Accueil mobile : **5,3 Mo** (bundle admin + vidéo 3 Mo + 2 familles de polices) | **0,7 Mo**, la vidéo ne se charge plus que sur grand écran et bonne connexion |
| Affiches servies en pleine résolution (jusqu'à 5 Mo par événement) | Master 1600 px + variante 800 px dans les listes (~90 Ko) |
| E-mails envoyés pendant la requête (webhook de paiement compris) | Envoyés par la file, la réponse au prestataire n'attend plus le SMTP |
| Dernières places vendables plusieurs fois en simultané | Verrou + décompte des paiements en cours |

### 2.2 `.env` : sortir cache et sessions de la base de données
Aujourd'hui `CACHE_STORE`, `SESSION_DRIVER` et `QUEUE_CONNECTION` valent `database` :
chaque visite écrit dans MySQL, qui devient le goulot unique sous charge. Sur du
mutualisé (pas de Redis), le driver **fichier** est nettement plus léger :

```env
CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database     # inchangé : le worker tourne via le cron
APP_DEBUG=false               # à vérifier — jamais true en prod
```
Puis `/usr/bin/php artisan optimize`. Conséquence unique : les sessions actives sont
perdues au basculement (les utilisateurs connectés se reconnectent une fois).

### 2.3 Cloudflare (gratuit) devant le site
1. Créer un compte Cloudflare, ajouter le domaine `primea.ga`, copier les 2 serveurs
   de noms indiqués **chez le registrar** du domaine (propagation ≤ 24 h, en pratique
   quelques minutes). Laisser le nuage **orange (proxy)** sur les enregistrements web.
2. **SSL/TLS → Full (strict)** (le lab a déjà un certificat).
3. **Speed → Optimisation** : Brotli **on** ; Auto Minify **off** (les assets sont
   déjà minifiés) ; Rocket Loader **off** (SPA).
4. **Caching → Cache Rules** (2 règles) :
   - `URI Path starts with "/build/assets/"` → *Eligible for cache*, Edge TTL **1 an**
     (respecte aussi l'en-tête `immutable` du `.htaccess`).
   - `URI Path starts with "/api/"` → *Bypass cache* (les réponses API sont gérées par
     le cache applicatif ; ne jamais mettre en cache paiement/scan/session au bord).
   - Optionnel, pour absorber un très gros pic : `URI Path starts with "/api/client/events"`
     → Edge TTL **30 s** (lecture seule, publique).
5. **Security → WAF** : *Managed rules* on ; **Bots → Bot Fight Mode** on.
6. Ne pas activer « Always Online » ni « Email Obfuscation » (inutiles / effets de bord).

## 3. Vérifier après déploiement
- Une fiche événement chargée deux fois de suite : la 2ᵉ est instantanée (cache 60 s).
- `curl -sI https://primea.ga/build/assets/<un-fichier>.js | grep -i cache-control`
  → `max-age=31536000, immutable` (et `cf-cache-status: HIT` au 2ᵉ appel si Cloudflare).
- 11 tentatives de login ratées en < 1 min → la 11ᵉ répond **429**.
- Scanner mobile : un agent scanne normalement (limite 180/min, très au-dessus du réel).

## 4. Ce que la Phase 0 ne règle pas (→ Phase 1, VPS)
Le plafond CPU du mutualisé, l'absence de Redis (cache/queue/sessions en RAM) et le
worker de file d'attente lancé par le cron chaque minute. Voir l'analyse infra :
VPS 2 vCPU + Redis + worker permanent + Cloudflare ≈ 80-95 000 XAF/an.

---

## Réconciliation des commandes créditées à tort

Le webhook e-billing a longtemps traité **toute notification reçue** comme un
paiement abouti (corrigé le 21 sept. 2026). Des commandes ont donc été marquées
payées, et des billets émis, sans que le client ait validé le paiement.

```bash
# 1. Voir l'ampleur, sans rien modifier
/usr/bin/php artisan payments:reconcile-ebilling --dry-run

# 2. Annuler les commandes non payées et relâcher leurs places
/usr/bin/php artisan payments:reconcile-ebilling
```

Options : `--since=2026-09-01`, `--event=chill-expo-1` (id ou slug), `--limit=500`.

La commande interroge e-billing pour chaque commande payée et n'annule que
celles dont la facture n'a jamais été réglée. Elle ne touche **jamais** :

- une commande qu'elle n'a pas pu vérifier (facture inconnue, passerelle
  injoignable) — elle les liste à part ;
- un billet déjà **scanné** : la personne est entrée, la commande est annulée
  mais l'historique du contrôle d'accès n'est pas réécrit. Ces cas sont
  signalés explicitement, à traiter à la main.

Commencer par `--dry-run` : le rapport donne référence, montant, état réel de la
facture et nombre de billets concernés.

---

## Bascule du fuseau horaire vers Libreville (22 sept. 2026)

L'application tournait en **UTC** alors que le Gabon est à **UTC+1**. Une heure
saisie « 18:00 » par un organisateur était stockée telle quelle, relue comme de
l'UTC, puis convertie par le navigateur du visiteur : **19:00 affiché**.

`config/app.php` utilise désormais `env('APP_TIMEZONE', 'Africa/Libreville')`.

**Aucune migration de données n'est nécessaire** pour les dates *saisies*
(horaires d'événements, ouverture de billetterie) : elles étaient enregistrées
en heure locale et sont maintenant relues comme telles — l'affichage se corrige
tout seul.

⚠️ **En revanche, les horodatages générés par la machine** (`created_at`,
`placed_at`, `paid_at`, `issued_at`, `scanned_at`…) avaient été écrits en UTC.
Ils seront désormais lus comme de l'heure de Libreville, donc **affichés une
heure plus tôt que la réalité** pour tout ce qui précède la bascule. Les écarts
entre deux horodatages restent justes ; seules les heures absolues de
l'historique sont décalées.

Après déploiement :

```bash
/usr/bin/php artisan optimize:clear && /usr/bin/php artisan optimize
```

La config étant mise en cache, sans cela le fuseau reste à l'ancienne valeur.
