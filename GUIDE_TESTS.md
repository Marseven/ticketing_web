# Guide de Tests - Plateforme Primea Ticketing

## 📋 Table des Matières
1. [Comptes de Test](#comptes-de-test)
2. [Tests par Profil](#tests-par-profil)
3. [Scénarios de Test](#scénarios-de-test)
4. [Environnement de Test](#environnement-de-test)

---

## 🔐 Comptes de Test

### 1. Administrateur
**Accès complet au système**

```
📧 Email: admin@primea.com
🔑 Mot de passe: Admin@2025
🌐 URL: http://localhost:8000/admin
```

**Privilèges:**
- Gestion complète de la plateforme
- Gestion des utilisateurs et rôles
- Gestion des organisateurs
- Gestion des catégories
- Statistiques et rapports globaux
- Configuration système
- Gestion des bannières publicitaires
- Modération du contenu

---

### 2. Organisateur Principal
**Créateur et gestionnaire d'événements**

```
📧 Email: organizer@primea.com
🔑 Mot de passe: Organizer@2025
🌐 URL: http://localhost:8000/organizer
🏢 Organisation: Primea Events
```

**Privilèges:**
- Création d'événements
- Gestion des billets et types de tickets
- Tableau de bord des ventes
- Gestion des schedules (dates multiples)
- Scanner de tickets (QR Code)
- Statistiques de ventes
- Gestion d'équipe

---

### 3. Organisateur Secondaire
**Membre d'équipe organisateur**

```
📧 Email: team@primea.com
🔑 Mot de passe: Team@2025
🌐 URL: http://localhost:8000/organizer
🏢 Organisation: Primea Events (Membre)
```

**Privilèges:**
- Visualisation des événements
- Scanner de tickets
- Statistiques limitées
- Pas de création/modification d'événements

---

### 4. Client/Utilisateur
**Acheteur de tickets**

```
📧 Email: client@primea.com
🔑 Mot de passe: Client@2025
🌐 URL: http://localhost:8000
```

**Privilèges:**
- Navigation des événements
- Achat de tickets
- Historique des commandes
- Téléchargement de tickets
- Gestion du profil
- Favoris

---

### 5. Visiteur
**Utilisateur non authentifié**

```
🌐 URL: http://localhost:8000
```

**Privilèges:**
- Visualisation des événements publics
- Recherche d'événements
- Filtrage par catégorie
- Visualisation des détails

---

## 🧪 Tests par Profil

### Tests Administrateur

#### Connexion
1. Se rendre sur `/admin`
2. Saisir les identifiants administrateur
3. Vérifier l'accès au tableau de bord admin

#### Gestion des Événements
- [ ] Visualiser tous les événements de tous les organisateurs
- [ ] Approuver/Refuser des événements
- [ ] Modifier les informations d'un événement
- [ ] Supprimer un événement
- [ ] Voir les statistiques globales

#### Gestion des Utilisateurs
- [ ] Lister tous les utilisateurs
- [ ] Voir les détails d'un utilisateur
- [ ] Modifier le rôle d'un utilisateur
- [ ] Désactiver/Activer un compte
- [ ] Réinitialiser un mot de passe

#### Gestion des Organisateurs
- [ ] Lister tous les organisateurs
- [ ] Approuver un nouvel organisateur
- [ ] Modifier les informations d'un organisateur
- [ ] Suspendre un organisateur
- [ ] Voir les statistiques par organisateur

#### Gestion des Catégories
- [ ] Créer une nouvelle catégorie
- [ ] Modifier une catégorie existante
- [ ] Supprimer une catégorie
- [ ] Réorganiser l'ordre des catégories

#### Gestion des Bannières
- [ ] Créer une bannière publicitaire
- [ ] Upload d'image ou vidéo
- [ ] Définir la position (home, home-top)
- [ ] Activer/Désactiver une bannière
- [ ] Définir les dates d'affichage

#### Statistiques
- [ ] Voir le dashboard global
- [ ] Nombre total d'utilisateurs
- [ ] Nombre total d'événements
- [ ] Tickets vendus
- [ ] Revenus totaux (XAF)
- [ ] Top événements
- [ ] Graphiques des 7 derniers jours

---

### Tests Organisateur

#### Inscription
1. Aller sur `/organizer-choice`
2. Choisir "Créer un compte organisateur"
3. Remplir le formulaire avec:
   - Nom de l'organisation
   - Email professionnel
   - Téléphone
   - Adresse
   - Documents (RCCM, etc.)
4. Attendre l'approbation admin

#### Connexion
1. Se rendre sur `/organizer/login`
2. Saisir les identifiants
3. Vérifier l'accès au tableau de bord

#### Création d'Événement
- [ ] Cliquer sur "Créer un événement"
- [ ] Remplir les informations de base:
  * Titre
  * Description
  * Catégorie
  * Image de couverture
- [ ] Définir le lieu:
  * Nom du lieu
  * Adresse
  * Ville
  * Coordonnées GPS (optionnel)
- [ ] Créer les types de tickets:
  * Nom (VIP, Standard, etc.)
  * Prix en XAF
  * Quantité disponible
  * Description
- [ ] Définir les dates (schedules):
  * Date et heure de début
  * Date et heure de fin
  * Capacité par date
- [ ] Publier l'événement

#### Gestion des Événements
- [ ] Voir la liste de mes événements
- [ ] Modifier un événement
- [ ] Dupliquer un événement
- [ ] Archiver un événement
- [ ] Voir les statistiques d'un événement

#### Gestion des Tickets
- [ ] Voir tous les tickets vendus
- [ ] Filtrer par événement
- [ ] Filtrer par date
- [ ] Filtrer par type de ticket
- [ ] Exporter en PDF/Excel
- [ ] Rechercher un ticket par code

#### Scanner de Tickets
- [ ] Accéder au scanner (`/scanner`)
- [ ] Scanner un QR Code valide
- [ ] Vérifier le message de validation
- [ ] Tester avec un ticket déjà scanné (doublon)
- [ ] Tester avec un ticket invalide

#### Statistiques
- [ ] Dashboard organisateur
- [ ] Nombre d'événements créés
- [ ] Total tickets vendus
- [ ] Revenus générés (XAF)
- [ ] Top événements performants
- [ ] Graphique des ventes

#### Gestion d'Équipe
- [ ] Inviter un membre d'équipe
- [ ] Définir les permissions
- [ ] Retirer un membre
- [ ] Voir l'activité de l'équipe

---

### Tests Client

#### Inscription
1. Aller sur `/register`
2. Remplir le formulaire:
   - Nom complet
   - Email
   - Téléphone
   - Mot de passe
3. Vérifier l'email de confirmation
4. Cliquer sur le lien de vérification

#### Connexion
1. Aller sur `/login`
2. Saisir email et mot de passe
3. Cocher "Se souvenir de moi" (optionnel)
4. Se connecter

#### Navigation
- [ ] Page d'accueil avec événements
- [ ] Filtrer par catégorie
- [ ] Rechercher un événement
- [ ] Voir tous les événements (`/events`)
- [ ] Voir les détails d'un événement

#### Achat de Tickets

**Étape 1: Sélection**
- [ ] Choisir un événement
- [ ] Cliquer sur "Prendre un ticket"
- [ ] Aller sur la page checkout

**Étape 2: Checkout**
- [ ] Voir l'image et les infos de l'événement
- [ ] Voir le compte à rebours
- [ ] Sélectionner un type de ticket
- [ ] Choisir la quantité
- [ ] Voir le récapitulatif (sous-total, frais, total)
- [ ] Cliquer sur "Procéder au paiement"

**Étape 3: Informations**
- [ ] Vérifier les infos pré-remplies
- [ ] Modifier si nécessaire:
  * Nom complet
  * Email
  * Téléphone
- [ ] Cliquer sur "Continuer"

**Étape 4: Paiement**
- [ ] Choisir le mode de paiement:
  * Mobile Money (Orange Money, Moov Money)
  * Carte bancaire
- [ ] Saisir les informations de paiement
- [ ] Valider le paiement

**Étape 5: Confirmation**
- [ ] Voir la page de succès
- [ ] Recevoir l'email de confirmation
- [ ] Télécharger le ticket (PDF avec QR Code)

#### Gestion du Compte
- [ ] Accéder au profil
- [ ] Modifier les informations personnelles
- [ ] Changer le mot de passe
- [ ] Voir l'historique des commandes
- [ ] Télécharger un ancien ticket
- [ ] Ajouter des événements aux favoris

#### Récupération de Ticket
1. Aller sur `/retrieve-ticket`
2. Saisir l'email utilisé lors de l'achat
3. Saisir le numéro de commande
4. Cliquer sur "Récupérer mon ticket"
5. Télécharger le ticket

---

### Tests Visiteur

#### Navigation Publique
- [ ] Accéder à la page d'accueil
- [ ] Voir les événements en cours
- [ ] Voir les événements passés (grisés)
- [ ] Filtrer par catégorie
- [ ] Rechercher un événement
- [ ] Voir les détails d'un événement

#### Restrictions
- [ ] Tenter d'acheter un ticket → Redirection vers login
- [ ] Accéder au profil → Redirection vers login
- [ ] Accéder à l'historique → Redirection vers login

---

## 🎯 Scénarios de Test

### Scénario 1: Cycle Complet d'Achat
**Objectif:** Tester tout le flux d'achat de A à Z

1. **Client** s'inscrit et vérifie son email
2. **Client** navigue et trouve un événement
3. **Client** achète un ticket VIP (5000 XAF)
4. **Client** effectue le paiement via Orange Money
5. **Client** reçoit l'email avec le ticket (QR Code)
6. **Client** télécharge le PDF du ticket
7. **Organisateur** scanne le ticket à l'entrée
8. **Système** valide le ticket et l'invalide
9. **Organisateur** voit les statistiques mises à jour
10. **Admin** voit les revenus globaux mis à jour

### Scénario 2: Événement avec Dates Multiples
**Objectif:** Tester les schedules multiples

1. **Organisateur** crée un événement "Festival 3 jours"
2. **Organisateur** ajoute 3 schedules:
   - Vendredi 20h00
   - Samedi 18h00
   - Dimanche 16h00
3. **Client** achète un ticket pour Samedi
4. **Système** vérifie la disponibilité pour Samedi uniquement
5. **Client** reçoit le ticket avec la date spécifiée

### Scénario 3: Gestion d'Équipe
**Objectif:** Tester la collaboration entre organisateurs

1. **Organisateur principal** invite un membre d'équipe
2. **Membre** reçoit l'invitation par email
3. **Membre** accepte et crée son compte
4. **Membre** accède au dashboard (lecture seule)
5. **Membre** scanne des tickets à l'événement
6. **Principal** voit l'activité du membre
7. **Principal** retire le membre

### Scénario 4: Modération Admin
**Objectif:** Tester le contrôle qualité

1. **Organisateur** crée un événement
2. **Admin** reçoit une notification
3. **Admin** examine l'événement
4. **Admin** approuve ou refuse
5. Si approuvé → événement visible publiquement
6. Si refusé → **Organisateur** reçoit une notification avec raison

### Scénario 5: Événement Complet
**Objectif:** Tester la gestion des capacités

1. **Organisateur** crée un événement avec 100 places
2. **Clients** achètent jusqu'à 100 tickets
3. **Système** marque l'événement comme "Complet"
4. **Nouveau client** tente d'acheter → Message "Complet"
5. **Admin** peut augmenter la capacité si nécessaire

### Scénario 6: Ticket Perdu
**Objectif:** Tester la récupération de ticket

1. **Client** achète un ticket mais perd l'email
2. **Client** va sur `/retrieve-ticket`
3. **Client** saisit email + numéro de commande
4. **Système** vérifie et renvoie le ticket
5. **Client** télécharge à nouveau le PDF

---

## 🛠️ Environnement de Test

### Prérequis
- MAMP/LAMP installé et configuré
- Base de données MySQL créée
- Node.js et NPM installés
- Composer installé

### Configuration
```bash
# 1. Cloner le projet
cd /Applications/MAMP/htdocs/Ticketing/ticketing_web

# 2. Copier .env
cp .env.example .env

# 3. Configurer la base de données dans .env
DB_DATABASE=ticketing_db
DB_USERNAME=root
DB_PASSWORD=root

# 4. Installer les dépendances
composer install
npm install

# 5. Générer la clé
php artisan key:generate

# 6. Migrer et seeder
php artisan migrate:fresh --seed

# 7. Créer le lien storage
php artisan storage:link

# 8. Builder les assets
npm run build

# 9. Démarrer le serveur
php artisan serve
```

### URLs d'Accès
```
🏠 Page d'accueil:     http://localhost:8000
👤 Espace Client:      http://localhost:8000/account
🏢 Espace Organisateur: http://localhost:8000/organizer
👑 Espace Admin:       http://localhost:8000/admin
📱 Scanner:            http://localhost:8000/scanner
```

### Données de Test Initiales
Après le seeding, vous aurez automatiquement:
- ✅ 1 compte Admin
- ✅ 2 comptes Organisateurs
- ✅ 5 comptes Clients
- ✅ 3 Catégories d'événements
- ✅ 5-10 Événements de démonstration
- ✅ Types de tickets variés
- ✅ Quelques commandes de test

---

## 📧 Emails de Test

Pour tester les emails localement, utiliser **Mailtrap** ou **MailHog**:

### Configuration Mailtrap
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
```

### Emails à Vérifier
- [ ] Email de bienvenue (inscription)
- [ ] Email de vérification
- [ ] Email de confirmation de commande
- [ ] Email avec ticket PDF
- [ ] Email de récupération de mot de passe
- [ ] Email d'approbation organisateur
- [ ] Email d'invitation d'équipe

---

## 💳 Paiements de Test

### Mobile Money (Sandbox)
```
Orange Money Test:
📱 Numéro: +237 6XX XX XX XX
🔢 Code: 1234
💰 Solde test: 50,000 XAF

Moov Money Test:
📱 Numéro: +237 6XX XX XX XX
🔢 Code: 5678
💰 Solde test: 50,000 XAF
```

### Cartes Bancaires de Test
```
Visa Success:
💳 Numéro: 4242 4242 4242 4242
📅 Expiration: 12/25
🔒 CVV: 123

Mastercard Success:
💳 Numéro: 5555 5555 5555 4444
📅 Expiration: 12/25
🔒 CVV: 123
```

---

## 🐛 Reporting de Bugs

### Template de Bug Report
```markdown
## Description
[Description claire du bug]

## Étapes pour Reproduire
1. Aller sur [URL]
2. Cliquer sur [élément]
3. Saisir [données]
4. Observer [comportement]

## Comportement Attendu
[Ce qui devrait se passer]

## Comportement Actuel
[Ce qui se passe réellement]

## Environnement
- Navigateur: [Chrome 120 / Firefox 121 / Safari 17]
- OS: [macOS 14 / Windows 11 / Ubuntu 22.04]
- Rôle: [Admin / Organisateur / Client]

## Screenshots
[Ajouter des captures d'écran]

## Console Errors
[Copier les erreurs de la console browser]
```

---

## ✅ Checklist de Tests Complets

### Fonctionnalités Critiques
- [ ] Inscription utilisateur
- [ ] Connexion/Déconnexion
- [ ] Création d'événement
- [ ] Achat de ticket
- [ ] Paiement Mobile Money
- [ ] Génération de QR Code
- [ ] Scan de ticket
- [ ] Téléchargement PDF
- [ ] Email de confirmation

### Performance
- [ ] Page d'accueil charge < 2s
- [ ] Recherche répond < 1s
- [ ] Images optimisées (WebP)
- [ ] Build assets minifiés
- [ ] Cache navigateur actif

### Sécurité
- [ ] CSRF protection
- [ ] XSS prevention
- [ ] SQL injection protection
- [ ] Password hashing (bcrypt)
- [ ] Email verification
- [ ] Rate limiting sur login

### Compatibilité
- [ ] Chrome (dernière version)
- [ ] Firefox (dernière version)
- [ ] Safari (dernière version)
- [ ] Edge (dernière version)
- [ ] Mobile iOS
- [ ] Mobile Android

### Responsive Design
- [ ] iPhone SE (375px)
- [ ] iPhone 12 Pro (390px)
- [ ] iPad (768px)
- [ ] Desktop (1024px+)
- [ ] Large Desktop (1440px+)

---

## 📞 Support

En cas de problème ou question:
- 📧 Email: support@primea.com
- 💬 WhatsApp: +237 XXX XXX XXX
- 🌐 Documentation: /docs

---

**Dernière mise à jour:** 2025-01-04
**Version de la plateforme:** 1.0.0
**Guide maintenu par:** Équipe Primea Development
