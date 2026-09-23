<template>
  <div class="supervision p-4 sm:p-6 bg-gray-50 min-h-screen">

    <!-- ===================== En-tête ===================== -->
    <div class="mb-6 sm:mb-8">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-1">Supervision</h1>
          <p class="text-gray-600 text-sm sm:text-base">
            Santé technique, trafic et flux en cours de la plateforme
          </p>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
          <!-- Pastille d'état global : on préfère un état neutre explicite plutôt
               qu'un vert par défaut quand le diagnostic n'a pas pu être récupéré. -->
          <div class="flex items-center gap-2 bg-white rounded-lg shadow px-3 py-2 min-h-[44px]">
            <span class="relative flex h-3 w-3 shrink-0">
              <span v-if="statutGlobal === 'ok'"
                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-60"></span>
              <span class="relative inline-flex rounded-full h-3 w-3" :class="classePastille(statutGlobal)"></span>
            </span>
            <span class="text-sm font-semibold text-gray-900">{{ libelleStatut(statutGlobal) }}</span>
          </div>

          <button @click="chargerTout"
                  :disabled="chargementEnCours"
                  class="inline-flex items-center justify-center gap-2 min-h-[44px] px-4 py-2 rounded-lg bg-primea-blue text-white font-medium hover:bg-primea-blue/90 disabled:opacity-60 disabled:cursor-not-allowed transition-colors">
            <svg class="w-5 h-5" :class="{ 'animate-spin': chargementEnCours }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Actualiser
          </button>
        </div>
      </div>

      <p class="mt-3 text-xs sm:text-sm text-gray-500">
        <span v-if="santeChargement">Contrôle en cours…</span>
        <span v-else-if="sante?.checked_at">Dernier contrôle : {{ formaterHorodatage(sante.checked_at) }}</span>
        <span v-else>Dernier contrôle : indisponible</span>
      </p>
    </div>

    <!-- ===================== État de santé ===================== -->
    <section class="mb-8">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg sm:text-xl font-bold text-gray-900">État de santé</h2>
      </div>

      <!-- Squelettes : la page ne doit jamais rester vide sans explication -->
      <div v-if="santeChargement" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="n in 3" :key="`sq-sante-${n}`" class="bg-white rounded-lg shadow p-5 animate-pulse">
          <div class="flex items-center gap-3 mb-3">
            <div class="h-3 w-3 rounded-full bg-gray-200"></div>
            <div class="h-4 w-32 bg-gray-200 rounded"></div>
          </div>
          <div class="h-3 w-full bg-gray-100 rounded mb-2"></div>
          <div class="h-3 w-2/3 bg-gray-100 rounded"></div>
        </div>
      </div>

      <div v-else-if="santeErreur" class="bg-white rounded-lg shadow border-l-4 border-red-500 p-5">
        <div class="flex items-start gap-3">
          <svg class="w-6 h-6 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
          </svg>
          <div class="min-w-0">
            <p class="font-semibold text-gray-900">État de santé indisponible</p>
            <p class="text-sm text-gray-600 mt-1">{{ santeErreur.message }}</p>
            <pre v-if="santeErreur.commande"
                 class="mt-3 text-xs bg-gray-900 text-gray-100 rounded-lg p-3 overflow-x-auto"><code>{{ santeErreur.commande }}</code></pre>
          </div>
        </div>
      </div>

      <div v-else-if="!controles.length" class="bg-white rounded-lg shadow p-6 text-center text-gray-500">
        Aucun contrôle de santé n'est configuré.
      </div>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <div v-for="controle in controles" :key="controle.key"
             class="bg-white rounded-lg shadow p-5 border-l-4" :class="classeBordure(controle.status)">
          <div class="flex items-start gap-3">
            <span class="inline-flex rounded-full h-3 w-3 shrink-0 mt-1.5" :class="classePastille(controle.status)"></span>
            <div class="min-w-0 flex-1">
              <!-- Le libellé passe à la ligne plutôt que d'être tronqué :
                   « Stockage des fichiers » coupé en « Stocka… » ne dit plus
                   de quoi la carte parle, et c'est l'information principale. -->
              <div class="flex items-start justify-between gap-2 flex-wrap">
                <h3 class="font-semibold text-gray-900 leading-snug">{{ controle.label || controle.key }}</h3>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full shrink-0" :class="classeBadge(controle.status)">
                  {{ libelleStatut(controle.status) }}
                </span>
              </div>
              <p class="text-sm text-gray-600 mt-1 break-words">{{ controle.detail || '—' }}</p>
              <!-- Le « hint » est l'action à mener : on le distingue visuellement du constat -->
              <p v-if="controle.hint" class="mt-2 text-sm text-primea-blue flex items-start gap-1.5">
                <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="break-words">{{ controle.hint }}</span>
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ===================== Trafic ===================== -->
    <section class="mb-8">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
        <h2 class="text-lg sm:text-xl font-bold text-gray-900">Trafic</h2>

        <div class="inline-flex rounded-lg bg-white shadow p-1 self-start" role="group" aria-label="Période du trafic">
          <button v-for="option in optionsJours" :key="`j-${option.valeur}`"
                  @click="changerPeriodeTrafic(option.valeur)"
                  :aria-pressed="traficJours === option.valeur"
                  class="min-h-[44px] px-4 py-2 text-sm font-medium rounded-md transition-colors"
                  :class="traficJours === option.valeur
                    ? 'bg-primea-blue text-white'
                    : 'text-gray-600 hover:bg-gray-100'">
            {{ option.libelle }}
          </button>
        </div>
      </div>

      <div v-if="traficChargement" class="space-y-4">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <div v-for="n in 4" :key="`sq-tuile-${n}`" class="bg-white rounded-lg shadow p-5 animate-pulse">
            <div class="h-3 w-24 bg-gray-100 rounded mb-3"></div>
            <div class="h-7 w-16 bg-gray-200 rounded"></div>
          </div>
        </div>
        <div class="bg-white rounded-lg shadow p-5 animate-pulse">
          <div class="h-4 w-40 bg-gray-200 rounded mb-4"></div>
          <div class="h-40 w-full bg-gray-100 rounded"></div>
        </div>
      </div>

      <div v-else-if="traficErreur" class="bg-white rounded-lg shadow border-l-4 border-red-500 p-5">
        <div class="flex items-start gap-3">
          <svg class="w-6 h-6 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
          </svg>
          <div class="min-w-0">
            <p class="font-semibold text-gray-900">Statistiques de trafic indisponibles</p>
            <p class="text-sm text-gray-600 mt-1">{{ traficErreur.message }}</p>
            <pre v-if="traficErreur.commande"
                 class="mt-3 text-xs bg-gray-900 text-gray-100 rounded-lg p-3 overflow-x-auto"><code>{{ traficErreur.commande }}</code></pre>
          </div>
        </div>
      </div>

      <div v-else class="space-y-4">
        <!-- Quatre tuiles de chiffres -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <div class="bg-white rounded-lg shadow p-5">
            <p class="text-xs sm:text-sm text-gray-600">Visiteurs ({{ traficJours }} j)</p>
            <p class="text-2xl sm:text-3xl font-bold text-primea-blue mt-1">{{ formaterNombre(trafic?.totals?.visitors) }}</p>
          </div>
          <div class="bg-white rounded-lg shadow p-5">
            <p class="text-xs sm:text-sm text-gray-600">Pages vues ({{ traficJours }} j)</p>
            <p class="text-2xl sm:text-3xl font-bold text-gray-900 mt-1">{{ formaterNombre(trafic?.totals?.views) }}</p>
          </div>
          <div class="bg-white rounded-lg shadow p-5">
            <p class="text-xs sm:text-sm text-gray-600">Pages par visiteur</p>
            <p class="text-2xl sm:text-3xl font-bold text-gray-900 mt-1">{{ formaterDecimal(trafic?.totals?.views_per_visitor) }}</p>
          </div>
          <div class="bg-white rounded-lg shadow p-5">
            <p class="text-xs sm:text-sm text-gray-600">Visiteurs aujourd'hui</p>
            <p class="text-2xl sm:text-3xl font-bold text-primea-blue mt-1">{{ formaterNombre(trafic?.totals?.today_visitors) }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ formaterNombre(trafic?.totals?.today_views) }} pages vues</p>
          </div>
        </div>

        <!-- Graphique en barres : SVG écrit à la main, aucune bibliothèque -->
        <div class="bg-white rounded-lg shadow p-5">
          <div class="flex items-baseline justify-between gap-3 mb-4 flex-wrap">
            <h3 class="font-semibold text-gray-900">Visiteurs par jour</h3>
            <p class="text-xs text-gray-500">
              Maximum : {{ formaterNombre(maxVisiteurs) }} &middot;
              du {{ formaterDateCourte(trafic?.range?.from) }} au {{ formaterDateCourte(trafic?.range?.to) }}
            </p>
          </div>

          <div v-if="!barresTrafic.length" class="py-10 text-center text-gray-500 text-sm">
            Aucune donnée de trafic sur cette période.
          </div>

          <template v-else>
            <!--
              preserveAspectRatio="none" étire le dessin sur toute la largeur disponible :
              les barres s'adaptent à l'écran sans recalcul au redimensionnement.
              Conséquence : tout ce qui est dans le SVG est déformé horizontalement,
              donc pas de texte ni de coins arrondis ici — les repères de date sont
              écrits en HTML sous le graphique, et les traits utilisent
              vector-effect="non-scaling-stroke" pour rester fins.
            -->
            <svg :viewBox="`0 0 ${largeurGraphe} ${hauteurGraphe}`"
                 preserveAspectRatio="none"
                 class="w-full h-40 sm:h-48 block overflow-visible"
                 role="img"
                 :aria-label="`Visiteurs par jour sur ${traficJours} jours`">
              <line v-for="n in 3" :key="`grille-${n}`"
                    x1="0" :y1="(hauteurGraphe / 4) * n"
                    :x2="largeurGraphe" :y2="(hauteurGraphe / 4) * n"
                    stroke="#e5e7eb" stroke-width="1" vector-effect="non-scaling-stroke" />

              <rect v-for="barre in barresTrafic" :key="barre.date"
                    :x="barre.x" :y="barre.y" :width="barre.largeur" :height="barre.hauteur"
                    fill="#272d63"
                    class="supervision-barre">
                <!-- <title> natif SVG : infobulle au survol sans JavaScript ni dépendance -->
                <title>{{ formaterDateLongue(barre.date) }} — {{ formaterNombre(barre.visitors) }} visiteurs, {{ formaterNombre(barre.views) }} pages vues</title>
              </rect>

              <line x1="0" :y1="hauteurGraphe" :x2="largeurGraphe" :y2="hauteurGraphe"
                    stroke="#d1d5db" stroke-width="1" vector-effect="non-scaling-stroke" />
            </svg>

            <div class="flex justify-between mt-2 text-[11px] sm:text-xs text-gray-500">
              <span v-for="(repere, index) in reperesDates" :key="`repere-${index}`">{{ repere }}</span>
            </div>
          </template>
        </div>

        <!-- Trois listes de répartition -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
          <div class="bg-white rounded-lg shadow p-5">
            <h3 class="font-semibold text-gray-900 mb-4">Pages les plus vues</h3>
            <p v-if="!pagesVues.length" class="text-sm text-gray-500">Aucune page enregistrée.</p>
            <ul v-else class="space-y-3">
              <li v-for="page in pagesVues" :key="page.path">
                <div class="flex items-baseline justify-between gap-3 mb-1">
                  <span class="text-sm text-gray-700 truncate" :title="page.path">{{ page.path }}</span>
                  <span class="text-sm font-semibold text-gray-900 shrink-0">{{ formaterNombre(page.views) }}</span>
                </div>
                <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                  <div class="h-full bg-primea-blue rounded-full" :style="{ width: `${page.pourcentage}%` }"></div>
                </div>
              </li>
            </ul>
          </div>

          <div class="bg-white rounded-lg shadow p-5">
            <h3 class="font-semibold text-gray-900 mb-4">Provenances</h3>
            <p v-if="!provenances.length" class="text-sm text-gray-500">Aucune provenance enregistrée.</p>
            <ul v-else class="space-y-3">
              <li v-for="source in provenances" :key="source.source">
                <div class="flex items-baseline justify-between gap-3 mb-1">
                  <span class="text-sm text-gray-700 truncate">{{ libelleSource(source.source) }}</span>
                  <span class="text-sm font-semibold text-gray-900 shrink-0">{{ formaterNombre(source.views) }}</span>
                </div>
                <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                  <div class="h-full bg-primea-yellow rounded-full" :style="{ width: `${source.pourcentage}%` }"></div>
                </div>
              </li>
            </ul>
          </div>

          <div class="bg-white rounded-lg shadow p-5">
            <h3 class="font-semibold text-gray-900 mb-4">Appareils</h3>
            <p v-if="!appareils.length" class="text-sm text-gray-500">Aucun appareil enregistré.</p>
            <ul v-else class="space-y-3">
              <li v-for="appareil in appareils" :key="appareil.device">
                <div class="flex items-baseline justify-between gap-3 mb-1">
                  <span class="text-sm text-gray-700 truncate">{{ libelleAppareil(appareil.device) }}</span>
                  <span class="text-sm font-semibold text-gray-900 shrink-0">{{ formaterNombre(appareil.views) }}</span>
                </div>
                <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                  <div class="h-full bg-gray-700 rounded-full" :style="{ width: `${appareil.pourcentage}%` }"></div>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <!-- ===================== Flux en cours ===================== -->
    <section>
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
        <h2 class="text-lg sm:text-xl font-bold text-gray-900">Flux en cours</h2>

        <div class="inline-flex rounded-lg bg-white shadow p-1 self-start" role="group" aria-label="Fenêtre d'observation des flux">
          <button v-for="option in optionsHeures" :key="`h-${option.valeur}`"
                  @click="changerFenetreFlux(option.valeur)"
                  :aria-pressed="fluxHeures === option.valeur"
                  class="min-h-[44px] px-4 py-2 text-sm font-medium rounded-md transition-colors"
                  :class="fluxHeures === option.valeur
                    ? 'bg-primea-blue text-white'
                    : 'text-gray-600 hover:bg-gray-100'">
            {{ option.libelle }}
          </button>
        </div>
      </div>

      <div v-if="fluxChargement" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div v-for="n in 4" :key="`sq-flux-${n}`" class="bg-white rounded-lg shadow p-5 animate-pulse">
          <div class="h-4 w-28 bg-gray-200 rounded mb-4"></div>
          <div v-for="m in 3" :key="`sq-flux-${n}-${m}`" class="h-4 w-full bg-gray-100 rounded mb-3"></div>
        </div>
      </div>

      <div v-else-if="fluxErreur" class="bg-white rounded-lg shadow border-l-4 border-red-500 p-5">
        <div class="flex items-start gap-3">
          <svg class="w-6 h-6 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
          </svg>
          <div class="min-w-0">
            <p class="font-semibold text-gray-900">Flux indisponibles</p>
            <p class="text-sm text-gray-600 mt-1">{{ fluxErreur.message }}</p>
            <pre v-if="fluxErreur.commande"
                 class="mt-3 text-xs bg-gray-900 text-gray-100 rounded-lg p-3 overflow-x-auto"><code>{{ fluxErreur.commande }}</code></pre>
          </div>
        </div>
      </div>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <!-- Paiements -->
        <div class="bg-white rounded-lg shadow p-5">
          <h3 class="font-semibold text-gray-900 mb-4">Paiements</h3>
          <dl class="space-y-1">
            <div class="flex items-baseline justify-between gap-3 py-1.5">
              <dt class="text-sm text-gray-600">Initiés</dt>
              <dd class="text-lg font-bold text-gray-900">{{ formaterNombre(flux?.payments?.initiated) }}</dd>
            </div>
            <div class="flex items-baseline justify-between gap-3 py-1.5">
              <dt class="text-sm text-gray-600">Réussis</dt>
              <dd class="text-lg font-bold text-green-600">{{ formaterNombre(flux?.payments?.success) }}</dd>
            </div>
            <div class="flex items-baseline justify-between gap-3 py-1.5 px-2 -mx-2 rounded"
                 :class="alertes.paiementsEchoues ? 'bg-amber-50' : ''">
              <dt class="text-sm text-gray-600">Échoués</dt>
              <dd class="text-lg font-bold" :class="classeValeur(alertes.paiementsEchoues)">
                {{ formaterNombre(flux?.payments?.failed) }}
              </dd>
            </div>
            <div class="flex items-baseline justify-between gap-3 py-1.5 px-2 -mx-2 rounded"
                 :class="alertes.paiementsBloques ? 'bg-red-50' : ''">
              <dt class="text-sm text-gray-600 flex items-center gap-1.5">
                Bloqués
                <span v-if="alertes.paiementsBloques" class="text-xs font-semibold text-red-600">à vérifier</span>
              </dt>
              <dd class="text-lg font-bold" :class="classeValeur(alertes.paiementsBloques, 'danger')">
                {{ formaterNombre(flux?.payments?.stuck) }}
              </dd>
            </div>
          </dl>
        </div>

        <!-- Commandes -->
        <div class="bg-white rounded-lg shadow p-5">
          <h3 class="font-semibold text-gray-900 mb-4">Commandes</h3>
          <dl class="space-y-1">
            <div class="flex items-baseline justify-between gap-3 py-1.5 px-2 -mx-2 rounded"
                 :class="alertes.commandesEnAttente ? 'bg-amber-50' : ''">
              <dt class="text-sm text-gray-600">En attente</dt>
              <dd class="text-lg font-bold" :class="classeValeur(alertes.commandesEnAttente)">
                {{ formaterNombre(flux?.orders?.pending) }}
              </dd>
            </div>
            <div class="flex items-baseline justify-between gap-3 py-1.5">
              <dt class="text-sm text-gray-600">Payées</dt>
              <dd class="text-lg font-bold text-green-600">{{ formaterNombre(flux?.orders?.paid) }}</dd>
            </div>
            <div class="flex items-baseline justify-between gap-3 py-1.5">
              <dt class="text-sm text-gray-600">Annulées</dt>
              <dd class="text-lg font-bold text-gray-900">{{ formaterNombre(flux?.orders?.cancelled) }}</dd>
            </div>
            <p v-if="alertes.commandesEnAttente" class="text-xs text-amber-700 pt-1">
              Au-delà de {{ SEUIL_COMMANDES_EN_ATTENTE }} commandes en attente, vérifier les retours de paiement.
            </p>
          </dl>
        </div>

        <!-- Billets -->
        <div class="bg-white rounded-lg shadow p-5">
          <h3 class="font-semibold text-gray-900 mb-4">Billets</h3>
          <dl class="space-y-1">
            <div class="flex items-baseline justify-between gap-3 py-1.5">
              <dt class="text-sm text-gray-600">Émis</dt>
              <dd class="text-lg font-bold text-primea-blue">{{ formaterNombre(flux?.tickets?.issued) }}</dd>
            </div>
            <div class="flex items-baseline justify-between gap-3 py-1.5">
              <dt class="text-sm text-gray-600">Utilisés</dt>
              <dd class="text-lg font-bold text-gray-900">{{ formaterNombre(flux?.tickets?.used) }}</dd>
            </div>
          </dl>
          <div class="mt-3">
            <div class="flex items-baseline justify-between gap-3 mb-1">
              <span class="text-xs text-gray-500">Taux d'utilisation</span>
              <span class="text-xs font-semibold text-gray-700">{{ tauxUtilisationBillets }} %</span>
            </div>
            <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
              <div class="h-full bg-primea-yellow rounded-full" :style="{ width: `${tauxUtilisationBillets}%` }"></div>
            </div>
          </div>
        </div>

        <!-- File d'attente et scans -->
        <div class="bg-white rounded-lg shadow p-5">
          <h3 class="font-semibold text-gray-900 mb-4">File d'attente et scans</h3>
          <dl class="space-y-1">
            <div class="flex items-baseline justify-between gap-3 py-1.5">
              <dt class="text-sm text-gray-600">Tâches en attente</dt>
              <dd class="text-lg font-bold text-gray-900">{{ formaterNombre(flux?.queue?.pending) }}</dd>
            </div>
            <div class="flex items-baseline justify-between gap-3 py-1.5 px-2 -mx-2 rounded"
                 :class="alertes.tachesEchouees ? 'bg-red-50' : ''">
              <dt class="text-sm text-gray-600">Tâches échouées</dt>
              <dd class="text-lg font-bold" :class="classeValeur(alertes.tachesEchouees, 'danger')">
                {{ formaterNombre(flux?.queue?.failed) }}
              </dd>
            </div>
            <div class="flex items-baseline justify-between gap-3 py-1.5">
              <dt class="text-sm text-gray-600">Plus ancienne</dt>
              <dd class="text-sm font-semibold text-gray-900">{{ formaterDuree(flux?.queue?.oldest_pending_seconds) }}</dd>
            </div>
          </dl>

          <div class="mt-4 pt-3 border-t border-gray-100">
            <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Scans</p>
            <dl class="grid grid-cols-3 gap-2 text-center">
              <div>
                <dt class="text-xs text-gray-500">Valides</dt>
                <dd class="text-lg font-bold text-green-600">{{ formaterNombre(flux?.scans?.valid) }}</dd>
              </div>
              <div>
                <dt class="text-xs text-gray-500">Doublons</dt>
                <dd class="text-lg font-bold" :class="classeValeur(alertes.scansDoublons)">
                  {{ formaterNombre(flux?.scans?.duplicate) }}
                </dd>
              </div>
              <div>
                <dt class="text-xs text-gray-500">Invalides</dt>
                <dd class="text-lg font-bold" :class="classeValeur(alertes.scansInvalides, 'danger')">
                  {{ formaterNombre(flux?.scans?.invalid) }}
                </dd>
              </div>
            </dl>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'

// Dimensions du repère interne du graphique SVG. Ce ne sont pas des pixels :
// le viewBox est étiré à la largeur réelle du conteneur par le navigateur.
const LARGEUR_GRAPHE = 600
const HAUTEUR_GRAPHE = 160

// Au-delà de ce nombre de commandes encore « en attente » sur la fenêtre observée,
// il ne s'agit probablement plus de paniers en cours mais de paiements qui ne se
// dénouent pas (webhook manquant, gateway muette) : on le signale en ambre.
const SEUIL_COMMANDES_EN_ATTENTE = 10

// Panne déjà rencontrée en production : quand le cache des routes est obsolète,
// Laravel ne reconnaît pas l'URL et renvoie du HTML (page d'erreur ou index de la SPA).
// `response.json()` échouerait alors sur un message incompréhensible : on intercepte
// en amont via le content-type pour donner la vraie marche à suivre.
const MESSAGE_ROUTES = "Le serveur n'a pas reconnu l'adresse : il a renvoyé une page au lieu de données JSON. La route de supervision est probablement absente du cache des routes."
const COMMANDE_ROUTES = 'php artisan optimize:clear && php artisan optimize'

export default {
  name: 'Supervision',
  setup() {
    // --- État : chaque section a son propre trio (données / chargement / erreur)
    // pour que l'échec de l'une n'empêche jamais l'affichage des deux autres.
    const sante = ref(null)
    const santeChargement = ref(true)
    const santeErreur = ref(null)

    const trafic = ref(null)
    const traficChargement = ref(true)
    const traficErreur = ref(null)
    const traficJours = ref(30)

    const flux = ref(null)
    const fluxChargement = ref(true)
    const fluxErreur = ref(null)
    const fluxHeures = ref(24)

    const optionsJours = [
      { valeur: 7, libelle: '7 jours' },
      { valeur: 30, libelle: '30 jours' },
      { valeur: 90, libelle: '90 jours' }
    ]

    const optionsHeures = [
      { valeur: 24, libelle: '24 h' },
      { valeur: 72, libelle: '72 h' },
      { valeur: 168, libelle: '7 jours' }
    ]

    // --- Appel API mutualisé -------------------------------------------------
    const appelerApi = async (url) => {
      const reponse = await fetch(url, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        }
      })

      // Les codes d'autorisation sont testés avant le content-type : une page 403
      // renvoyée en HTML reste d'abord un problème de droits, pas de routage.
      if (reponse.status === 403) {
        throw { message: 'Réservé aux super administrateurs' }
      }
      if (reponse.status === 401) {
        throw { message: 'Session expirée. Reconnectez-vous pour consulter la supervision.' }
      }

      const typeContenu = reponse.headers.get('content-type') || ''
      if (!typeContenu.includes('application/json')) {
        throw { message: MESSAGE_ROUTES, commande: COMMANDE_ROUTES }
      }

      const donnees = await reponse.json()

      if (!reponse.ok || donnees.success === false) {
        throw {
          message: donnees.message || `Le serveur a répondu avec le code ${reponse.status}.`
        }
      }

      return donnees.data
    }

    // Normalise n'importe quelle exception (réseau, parsing, rejet volontaire)
    // en un objet affichable { message, commande }.
    const normaliserErreur = (erreur) => {
      if (erreur && typeof erreur === 'object' && erreur.message) {
        return { message: erreur.message, commande: erreur.commande || null }
      }
      return { message: 'Impossible de joindre le serveur. Vérifiez votre connexion.', commande: null }
    }

    // --- Chargements ---------------------------------------------------------
    const chargerSante = async () => {
      santeChargement.value = true
      santeErreur.value = null
      try {
        sante.value = await appelerApi('/api/v1/admin/supervision/health')
      } catch (erreur) {
        santeErreur.value = normaliserErreur(erreur)
        sante.value = null
      } finally {
        santeChargement.value = false
      }
    }

    const chargerTrafic = async () => {
      traficChargement.value = true
      traficErreur.value = null
      try {
        trafic.value = await appelerApi(`/api/v1/admin/supervision/traffic?days=${traficJours.value}`)
      } catch (erreur) {
        traficErreur.value = normaliserErreur(erreur)
        trafic.value = null
      } finally {
        traficChargement.value = false
      }
    }

    const chargerFlux = async () => {
      fluxChargement.value = true
      fluxErreur.value = null
      try {
        flux.value = await appelerApi(`/api/v1/admin/supervision/flows?hours=${fluxHeures.value}`)
      } catch (erreur) {
        fluxErreur.value = normaliserErreur(erreur)
        flux.value = null
      } finally {
        fluxChargement.value = false
      }
    }

    // Les trois appels partent en parallèle : ils sont indépendants et aucun
    // n'attend le résultat d'un autre.
    const chargerTout = () => {
      chargerSante()
      chargerTrafic()
      chargerFlux()
    }

    const changerPeriodeTrafic = (jours) => {
      if (traficJours.value === jours) return
      traficJours.value = jours
      chargerTrafic()
    }

    const changerFenetreFlux = (heures) => {
      if (fluxHeures.value === heures) return
      fluxHeures.value = heures
      chargerFlux()
    }

    const chargementEnCours = computed(
      () => santeChargement.value || traficChargement.value || fluxChargement.value
    )

    // --- Santé ---------------------------------------------------------------
    const controles = computed(() => sante.value?.checks || [])

    // Sans diagnostic exploitable, on affiche « inconnu » : un vert par défaut
    // donnerait une fausse assurance sur l'état de la plateforme.
    const statutGlobal = computed(() => sante.value?.status || 'inconnu')

    const classePastille = (statut) => ({
      ok: 'bg-green-500',
      warning: 'bg-amber-500',
      down: 'bg-red-500'
    }[statut] || 'bg-gray-400')

    const classeBordure = (statut) => ({
      ok: 'border-green-500',
      warning: 'border-amber-500',
      down: 'border-red-500'
    }[statut] || 'border-gray-300')

    const classeBadge = (statut) => ({
      ok: 'bg-green-100 text-green-800',
      warning: 'bg-amber-100 text-amber-800',
      down: 'bg-red-100 text-red-800'
    }[statut] || 'bg-gray-100 text-gray-700')

    const libelleStatut = (statut) => ({
      ok: 'Opérationnel',
      warning: 'À surveiller',
      down: 'Incident'
    }[statut] || 'État inconnu')

    // --- Trafic : graphique --------------------------------------------------
    const joursTrafic = computed(() => trafic.value?.daily || [])

    const maxVisiteurs = computed(() => {
      if (!joursTrafic.value.length) return 0
      return Math.max(...joursTrafic.value.map((jour) => jour.visitors || 0))
    })

    const barresTrafic = computed(() => {
      const jours = joursTrafic.value
      if (!jours.length) return []

      // Plancher à 1 : évite une division par zéro sur une période sans visite.
      const echelle = Math.max(1, maxVisiteurs.value)
      const largeurCase = LARGEUR_GRAPHE / jours.length
      // Gouttière proportionnelle au nombre de jours : lisible sur 7 jours,
      // elle ne mange pas toute la barre sur 90 jours.
      const gouttiere = Math.min(4, largeurCase * 0.25)

      return jours.map((jour, index) => {
        const visiteurs = jour.visitors || 0
        const hauteur = (visiteurs / echelle) * HAUTEUR_GRAPHE

        return {
          date: jour.date,
          visitors: visiteurs,
          views: jour.views || 0,
          x: index * largeurCase + gouttiere / 2,
          largeur: Math.max(0.5, largeurCase - gouttiere),
          // L'origine du SVG est en haut à gauche : la barre descend depuis le bas.
          y: HAUTEUR_GRAPHE - hauteur,
          // Une journée avec au moins un visiteur reste visible même à faible valeur.
          hauteur: visiteurs > 0 ? Math.max(1, hauteur) : 0
        }
      })
    })

    // Quatre repères répartis sur la période, écrits en HTML sous le SVG
    // (le texte placé dans un SVG étiré serait déformé).
    const reperesDates = computed(() => {
      const jours = joursTrafic.value
      if (jours.length < 2) return jours.map((jour) => formaterDateCourte(jour.date))

      const nombre = Math.min(4, jours.length)
      const pas = (jours.length - 1) / (nombre - 1)
      const repères = []
      for (let i = 0; i < nombre; i++) {
        repères.push(formaterDateCourte(jours[Math.round(i * pas)].date))
      }
      return repères
    })

    // --- Trafic : listes de répartition --------------------------------------
    // Le pourcentage est relatif au plus gros élément de la liste (et non au total)
    // pour que la première barre soit toujours pleine et la comparaison lisible.
    const avecProportion = (liste) => {
      if (!Array.isArray(liste) || !liste.length) return []
      const maximum = Math.max(1, ...liste.map((element) => element.views || 0))
      return liste.map((element) => ({
        ...element,
        pourcentage: Math.round(((element.views || 0) / maximum) * 100)
      }))
    }

    const pagesVues = computed(() => avecProportion(trafic.value?.top_pages))
    const provenances = computed(() => avecProportion(trafic.value?.referrers))
    const appareils = computed(() => avecProportion(trafic.value?.devices))

    const libelleAppareil = (appareil) => ({
      mobile: 'Mobile',
      desktop: 'Ordinateur',
      tablet: 'Tablette',
      bot: 'Robot',
      unknown: 'Inconnu'
    }[appareil] || appareil || 'Inconnu')

    const libelleSource = (source) => {
      if (!source) return 'Inconnue'
      if (source === 'direct') return 'Accès direct'
      return source.charAt(0).toUpperCase() + source.slice(1)
    }

    // --- Flux ----------------------------------------------------------------
    const alertes = computed(() => ({
      paiementsBloques: (flux.value?.payments?.stuck || 0) > 0,
      paiementsEchoues: (flux.value?.payments?.failed || 0) > 0,
      commandesEnAttente: (flux.value?.orders?.pending || 0) > SEUIL_COMMANDES_EN_ATTENTE,
      tachesEchouees: (flux.value?.queue?.failed || 0) > 0,
      scansDoublons: (flux.value?.scans?.duplicate || 0) > 0,
      scansInvalides: (flux.value?.scans?.invalid || 0) > 0
    }))

    const classeValeur = (enAlerte, gravite = 'alerte') => {
      if (!enAlerte) return 'text-gray-900'
      return gravite === 'danger' ? 'text-red-600' : 'text-amber-600'
    }

    const tauxUtilisationBillets = computed(() => {
      const emis = flux.value?.tickets?.issued || 0
      const utilises = flux.value?.tickets?.used || 0
      if (!emis) return 0
      return Math.min(100, Math.round((utilises / emis) * 100))
    })

    // --- Formatage -----------------------------------------------------------
    const formaterNombre = (valeur) => new Intl.NumberFormat('fr-FR').format(Number(valeur) || 0)

    const formaterDecimal = (valeur) => (Number(valeur) || 0).toFixed(1).replace('.', ',')

    const formaterDateCourte = (date) => {
      if (!date) return '—'
      return new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' })
    }

    const formaterDateLongue = (date) => {
      if (!date) return '—'
      return new Date(date).toLocaleDateString('fr-FR', {
        weekday: 'short',
        day: '2-digit',
        month: 'long',
        year: 'numeric'
      })
    }

    const formaterHorodatage = (horodatage) => {
      if (!horodatage) return '—'
      return new Date(horodatage).toLocaleString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    // `oldest_pending_seconds` peut être nul : la file est alors vide, pas en panne.
    const formaterDuree = (secondes) => {
      if (secondes === null || secondes === undefined) return 'Aucune'
      const total = Number(secondes) || 0
      if (total < 60) return `${Math.round(total)} s`
      if (total < 3600) return `${Math.floor(total / 60)} min`
      if (total < 86400) return `${Math.floor(total / 3600)} h ${Math.floor((total % 3600) / 60)} min`
      return `${Math.floor(total / 86400)} j`
    }

    onMounted(chargerTout)

    return {
      // État
      sante,
      santeChargement,
      santeErreur,
      trafic,
      traficChargement,
      traficErreur,
      traficJours,
      flux,
      fluxChargement,
      fluxErreur,
      fluxHeures,
      optionsJours,
      optionsHeures,
      chargementEnCours,

      // Constantes du graphique et des seuils
      largeurGraphe: LARGEUR_GRAPHE,
      hauteurGraphe: HAUTEUR_GRAPHE,
      SEUIL_COMMANDES_EN_ATTENTE,

      // Santé
      controles,
      statutGlobal,
      classePastille,
      classeBordure,
      classeBadge,
      libelleStatut,

      // Trafic
      barresTrafic,
      reperesDates,
      maxVisiteurs,
      pagesVues,
      provenances,
      appareils,
      libelleAppareil,
      libelleSource,

      // Flux
      alertes,
      classeValeur,
      tauxUtilisationBillets,

      // Méthodes
      chargerTout,
      changerPeriodeTrafic,
      changerFenetreFlux,

      // Formatage
      formaterNombre,
      formaterDecimal,
      formaterDateCourte,
      formaterDateLongue,
      formaterHorodatage,
      formaterDuree
    }
  }
}
</script>

<style scoped>
.supervision {
  font-family: 'Inter', sans-serif;
}

/* Le survol d'une barre la met en avant ; l'infobulle native <title> du SVG
   affiche la valeur exacte sans code JavaScript supplémentaire. */
.supervision-barre {
  transition: opacity 0.15s ease;
}

.supervision-barre:hover {
  opacity: 0.65;
}
</style>
