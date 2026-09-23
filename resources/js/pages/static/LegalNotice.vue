<template>
  <div class="min-h-screen bg-white py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="text-center mb-12">
        <h1 class="text-3xl sm:text-4xl font-bold text-primea-blue mb-4">
          Mentions légales
        </h1>
        <p class="text-sm text-gray-500">
          Dernière mise à jour : 23 septembre 2026
        </p>
      </div>

      <!-- Content -->
      <div class="prose-container space-y-8 text-gray-700 leading-relaxed">
        <!-- Avertissement : informations manquantes -->
        <section class="bg-amber-50 border-2 border-amber-400 rounded-xl p-6">
          <h2 class="text-xl font-bold text-amber-900 mb-3">
            Informations à compléter avant la mise en ligne
          </h2>
          <p class="text-amber-900 mb-3">
            Les éléments d'identification de l'éditeur du site ne sont pas renseignés à ce jour.
            Ils sont obligatoires et doivent impérativement être complétés, à l'identique des
            documents officiels de la société, <strong>avant toute publication</strong> de cette
            page. Chaque champ manquant apparaît ci-dessous sous la forme
            <code class="bg-amber-100 px-1 rounded">[À COMPLÉTER : …]</code>.
          </p>
          <p class="text-amber-900 mb-2 font-semibold">Liste des informations manquantes :</p>
          <ul class="list-disc list-inside space-y-1 ml-4 text-amber-900">
            <li>Dénomination sociale (raison sociale exacte)</li>
            <li>Forme juridique (SARL, SA, SAS, entreprise individuelle…)</li>
            <li>Montant du capital social</li>
            <li>Adresse complète du siège social</li>
            <li>Numéro d'immatriculation au Registre du commerce et du crédit mobilier (RCCM)</li>
            <li>Numéro d'identification fiscale (NIF)</li>
            <li>Numéro de téléphone de contact</li>
            <li>Nom et qualité du directeur de la publication</li>
            <li>Nom et qualité du responsable de la rédaction, s'il est distinct</li>
            <li>Dénomination, adresse et téléphone de l'hébergeur du site</li>
            <li>
              Référence de la déclaration ou de l'autorisation de traitement des données auprès de
              l'autorité de protection des données, si elle a été délivrée
            </li>
          </ul>
        </section>

        <!-- Section 1 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            1. Éditeur du site
          </h2>
          <p class="mb-3">
            Le site Primea, accessible à l'adresse https://primea.ga, est édité par :
          </p>
          <ul class="list-none space-y-2 ml-0">
            <li><strong>Dénomination sociale :</strong> [À COMPLÉTER : dénomination sociale de l'éditeur]</li>
            <li><strong>Forme juridique :</strong> [À COMPLÉTER : forme juridique]</li>
            <li><strong>Capital social :</strong> [À COMPLÉTER : montant du capital social en XAF]</li>
            <li><strong>Siège social :</strong> [À COMPLÉTER : adresse complète du siège social]</li>
            <li><strong>RCCM :</strong> [À COMPLÉTER : numéro RCCM]</li>
            <li><strong>NIF :</strong> [À COMPLÉTER : numéro d'identification fiscale]</li>
            <li><strong>Téléphone :</strong> [À COMPLÉTER : numéro de téléphone]</li>
            <li>
              <strong>Adresse électronique :</strong>
              <a href="mailto:client@example.test" class="text-primea-blue font-medium hover:text-primea-yellow">
                client@example.test
              </a>
            </li>
          </ul>
        </section>

        <!-- Section 2 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            2. Direction de la publication
          </h2>
          <ul class="list-none space-y-2 ml-0">
            <li><strong>Directeur de la publication :</strong> [À COMPLÉTER : nom, prénom et qualité]</li>
            <li><strong>Responsable de la rédaction :</strong> [À COMPLÉTER : nom, prénom et qualité, si distinct du directeur de la publication]</li>
          </ul>
        </section>

        <!-- Section 3 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            3. Hébergement
          </h2>
          <p class="mb-3">Le site est hébergé par :</p>
          <ul class="list-none space-y-2 ml-0">
            <li><strong>Dénomination de l'hébergeur :</strong> [À COMPLÉTER : nom de l'hébergeur]</li>
            <li><strong>Adresse :</strong> [À COMPLÉTER : adresse complète de l'hébergeur]</li>
            <li><strong>Téléphone :</strong> [À COMPLÉTER : téléphone de l'hébergeur]</li>
          </ul>
        </section>

        <!-- Section 4 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            4. Activité et cadre juridique
          </h2>
          <p>
            Primea est une plateforme de billetterie en ligne. Elle permet à des organisateurs de
            mettre en vente des billets pour des événements se tenant au Gabon, et à des acheteurs
            de les acquérir, en tant qu'invité ou depuis un compte utilisateur. Primea intervient
            en qualité d'intermédiaire technique : la responsabilité de la tenue de l'événement
            incombe à l'organisateur.
          </p>
          <p class="mt-3">
            L'activité commerciale de l'éditeur s'inscrit dans le cadre de l'Acte uniforme OHADA
            relatif au droit commercial général. Les opérations de commerce électronique réalisées
            sur le site, la conclusion des contrats par voie électronique et la sécurisation des
            transactions relèvent de la loi n° 025/2021 du 28 décembre 2021 portant réglementation
            des transactions électroniques en République gabonaise.
          </p>
        </section>

        <!-- Section 5 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            5. Paiements
          </h2>
          <p>
            Les paiements réalisés sur le site sont opérés par mobile money (Airtel Money, Moov
            Money) et par carte, par l'intermédiaire de la passerelle de paiement e-billing / SHAP.
            Les données de paiement ne transitent jamais par les serveurs de Primea. Des frais de
            service de 2,5 % sont appliqués par la passerelle ; selon l'événement, ils sont soit
            ajoutés au montant payé par l'acheteur, soit absorbés par la plateforme. Une commission
            de la plateforme, dont le taux est fixé par événement, est prélevée sur les ventes
            revenant à l'organisateur.
          </p>
        </section>

        <!-- Section 6 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            6. Protection des données à caractère personnel
          </h2>
          <p>
            Les traitements de données à caractère personnel mis en œuvre sur le site sont soumis
            à la loi n° 001/2011 du 25 septembre 2011 relative à la protection des données à
            caractère personnel, modifiée par la loi n° 025/2023 du 12 juillet 2023. L'autorité de
            contrôle compétente est la Commission nationale pour la protection des données à
            caractère personnel (CNPDCP), devenue l'Autorité pour la protection des données à
            caractère personnel et de la vie privée (APDPVP).
          </p>
          <p class="mt-3">
            <strong>Responsable de traitement :</strong>
            [À COMPLÉTER : dénomination sociale de l'éditeur, en sa qualité de responsable de traitement]
          </p>
          <p class="mt-1">
            <strong>Référence de déclaration ou d'autorisation auprès de l'autorité :</strong>
            [À COMPLÉTER : référence de la formalité accomplie, le cas échéant]
          </p>
          <p class="mt-3">
            Les finalités, les catégories de données traitées, les durées de conservation et les
            modalités d'exercice de vos droits sont détaillées dans notre
            <router-link to="/privacy" class="text-primea-blue font-medium hover:text-primea-yellow">
              Politique de confidentialité
            </router-link>.
          </p>
        </section>

        <!-- Section 7 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            7. Propriété intellectuelle
          </h2>
          <p>
            L'ensemble des éléments composant le site — marque, logo, textes, graphismes,
            photographies, interfaces, code source et bases de données — est protégé par le droit
            de la propriété intellectuelle. Toute reproduction, représentation, adaptation ou
            exploitation, totale ou partielle, sans autorisation écrite préalable de l'éditeur,
            est interdite.
          </p>
          <p class="mt-3">
            Les visuels, affiches et descriptifs des événements sont publiés sous la responsabilité
            des organisateurs, qui garantissent détenir les droits nécessaires à leur diffusion.
          </p>
        </section>

        <!-- Section 8 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            8. Liens hypertextes et contenus de tiers
          </h2>
          <p>
            Le site peut contenir des liens vers des sites tiers, notamment ceux des organisateurs
            ou des partenaires de paiement. L'éditeur n'exerce aucun contrôle sur ces sites et
            décline toute responsabilité quant à leur contenu et à leurs pratiques en matière de
            données personnelles.
          </p>
        </section>

        <!-- Section 9 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            9. Signalement d'un contenu
          </h2>
          <p>
            Toute personne estimant qu'un contenu publié sur le site porte atteinte à ses droits
            peut le signaler à l'adresse de contact figurant ci-dessous, en précisant l'adresse de
            la page concernée, la nature de l'atteinte invoquée et les éléments justificatifs
            correspondants.
          </p>
        </section>

        <!-- Contact -->
        <section class="bg-gray-50 rounded-xl p-6">
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            10. Contact
          </h2>
          <p>
            Pour toute question relative aux présentes mentions légales :
          </p>
          <p class="mt-2">
            <strong>Email :</strong>
            <a href="mailto:client@example.test" class="text-primea-blue font-medium hover:text-primea-yellow">
              client@example.test
            </a>
          </p>
          <p class="mt-1">
            <strong>Plateforme :</strong>
            <a href="https://primea.ga" class="text-primea-blue font-medium hover:text-primea-yellow">
              https://primea.ga
            </a>
          </p>
        </section>

        <!-- Documents liés -->
        <section class="border-t border-gray-200 pt-6">
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            Documents liés
          </h2>
          <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm">
            <router-link to="/terms" class="text-primea-blue font-medium hover:text-primea-yellow">
              Conditions générales d'utilisation
            </router-link>
            <router-link to="/privacy" class="text-primea-blue font-medium hover:text-primea-yellow">
              Politique de confidentialité
            </router-link>
            <router-link to="/sales-terms" class="text-primea-blue font-medium hover:text-primea-yellow">
              Conditions générales de vente
            </router-link>
            <router-link to="/legal-notice" class="text-primea-blue font-medium hover:text-primea-yellow">
              Mentions légales
            </router-link>
          </div>
        </section>

        <!-- Avertissement juridique -->
        <section>
          <p class="text-xs text-gray-400 italic border-l-2 border-gray-200 pl-4">
            Ce document constitue un projet rédigé au regard des textes en vigueur en République
            gabonaise. Il doit être revu et validé par un conseil juridique gabonais avant sa
            publication définitive.
          </p>
        </section>
      </div>

      <!-- Back link -->
      <div class="mt-12 text-center">
        <router-link
          to="/"
          class="inline-flex items-center text-primea-blue font-medium hover:text-primea-yellow transition-colors"
        >
          &larr; Retour à l'accueil
        </router-link>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'LegalNotice',
};
</script>

<style scoped>
</style>
