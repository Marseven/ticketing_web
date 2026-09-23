<template>
  <div class="min-h-screen bg-white py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="text-center mb-12">
        <h1 class="text-3xl sm:text-4xl font-bold text-primea-blue mb-4">
          Conditions générales d'utilisation
        </h1>
        <p class="text-sm text-gray-500">
          Dernière mise à jour : 23 septembre 2026
        </p>
      </div>

      <!-- Content -->
      <div class="prose-container space-y-8 text-gray-700 leading-relaxed">
        <!-- Article 1 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            Article 1 - Objet
          </h2>
          <p>
            Les présentes Conditions générales d'utilisation (ci-après les « CGU ») ont pour
            objet de définir les modalités et conditions d'utilisation de la plateforme Primea,
            accessible à l'adresse https://primea.ga, ainsi que les droits et obligations des
            parties dans ce cadre. Primea est une plateforme de billetterie en ligne permettant
            l'achat de billets pour des événements se tenant au Gabon.
          </p>
          <p class="mt-3">
            Les conditions propres à l'achat d'un billet — prix, frais, paiement, remboursement —
            font l'objet d'un document distinct, les
            <router-link to="/sales-terms" class="text-primea-blue font-medium hover:text-primea-yellow">
              Conditions générales de vente
            </router-link>,
            qui prévalent sur les présentes CGU pour tout ce qui concerne une commande.
          </p>
        </section>

        <!-- Article 2 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            Article 2 - Identification de l'éditeur
          </h2>
          <p>
            La plateforme Primea est éditée et exploitée au Gabon. Les informations
            d'identification complètes de l'éditeur, du directeur de la publication et de
            l'hébergeur figurent dans les
            <router-link to="/legal-notice" class="text-primea-blue font-medium hover:text-primea-yellow">
              Mentions légales
            </router-link>.
            Pour toute question ou réclamation, vous pouvez nous écrire à l'adresse suivante :
            <a href="mailto:contact@primea.ga" class="text-primea-blue font-medium hover:text-primea-yellow">
              contact@primea.ga
            </a>.
          </p>
        </section>

        <!-- Article 3 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            Article 3 - Acceptation des CGU
          </h2>
          <p>
            L'utilisation de la plateforme vaut acceptation pleine et entière des présentes CGU.
            Cette acceptation est recueillie par voie électronique, dans les conditions prévues
            par la loi n° 025/2021 du 28 décembre 2021 portant réglementation des transactions
            électroniques en République gabonaise, qui reconnaît la validité du consentement et
            de l'écrit sous forme électronique. L'utilisateur qui n'accepte pas les présentes CGU
            doit renoncer à utiliser la plateforme.
          </p>
        </section>

        <!-- Article 4 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            Article 4 - Accès à la plateforme et compte utilisateur
          </h2>
          <p>
            La plateforme est accessible gratuitement à tout utilisateur disposant d'un accès à
            Internet, les coûts de connexion restant à sa charge. L'achat de billets peut se faire
            <strong>en tant qu'invité</strong>, sans création de compte, ou <strong>depuis un
            compte utilisateur</strong>. La création d'un compte nécessite de fournir une adresse
            e-mail valide, un numéro de téléphone et un mot de passe.
          </p>
          <p class="mt-3">
            L'utilisateur s'engage à fournir des informations exactes et à les maintenir à jour.
            Il est seul responsable de la confidentialité de ses identifiants et de toute
            utilisation faite depuis son compte. Toute utilisation suspecte doit être signalée
            sans délai à Primea.
          </p>
        </section>

        <!-- Article 5 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            Article 5 - Services proposés
          </h2>
          <p class="mb-3">Primea propose les services suivants :</p>
          <ul class="list-disc list-inside space-y-2 ml-4">
            <li>Consultation du catalogue des événements disponibles</li>
            <li>Achat de billets électroniques munis d'un QR code unique</li>
            <li>Gestion des commandes et consultation de l'historique des achats et des billets</li>
            <li>Téléchargement des billets au format PDF</li>
            <li>
              Récupération d'un billet déjà émis via la rubrique « Récupérer mon ticket », à
              partir du nom et du numéro de téléphone utilisé lors du paiement ou associé au compte
            </li>
            <li>Espace organisateur pour la création et la gestion d'événements</li>
            <li>Scan et validation des billets au contrôle d'accès</li>
          </ul>
        </section>

        <!-- Article 6 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            Article 6 - Ouverture de la billetterie et compte à rebours
          </h2>
          <p>
            Un organisateur peut annoncer une date d'ouverture de la billetterie de son événement.
            Dans ce cas, l'événement est visible sur la plateforme avant cette date, accompagné
            d'un compte à rebours, mais <strong>aucun achat n'est possible tant que l'ouverture
            n'est pas intervenue</strong>. L'affichage d'un événement avant l'ouverture de sa
            billetterie ne constitue ni une offre de vente ni une réservation, et ne confère
            aucune priorité d'accès.
          </p>
        </section>

        <!-- Article 7 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            Article 7 - Achat, prix et paiement
          </h2>
          <p>
            L'achat d'un billet forme un contrat de vente entre l'acheteur et l'organisateur de
            l'événement, Primea agissant en qualité d'intermédiaire technique. Le prix du billet
            est fixé par l'organisateur et affiché en Francs CFA (XAF).
          </p>
          <p class="mt-3">
            Des frais de service de la passerelle de paiement, d'un montant de 2,5 %, sont
            applicables ; selon le choix fait par l'organisateur pour son événement, ils sont soit
            ajoutés au montant payé par l'acheteur, soit absorbés par la plateforme. Le montant
            total effectivement dû est affiché avant la validation de la commande. Une commission
            de la plateforme, dont le taux est fixé par événement, est par ailleurs prélevée sur
            les sommes revenant à l'organisateur ; elle n'est pas ajoutée au prix payé par
            l'acheteur.
          </p>
          <p class="mt-3">
            Les paiements s'effectuent par mobile money (Airtel Money, Moov Money) ou par carte,
            via la passerelle e-billing / SHAP. Les données de paiement ne transitent jamais par
            Primea et ne sont pas conservées sur ses serveurs. L'émission des billets est
            conditionnée à la confirmation effective du paiement. Les modalités détaillées
            figurent dans les
            <router-link to="/sales-terms" class="text-primea-blue font-medium hover:text-primea-yellow">
              Conditions générales de vente
            </router-link>.
          </p>
        </section>

        <!-- Article 8 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            Article 8 - Billets électroniques et contrôle d'accès
          </h2>
          <p>
            Chaque billet est muni d'un QR code unique et personnel permettant son
            authentification et sa validation à l'entrée de l'événement. <strong>Un billet ne peut
            être scanné qu'une seule fois : un billet déjà scanné est automatiquement
            refusé</strong>, quelle que soit la personne qui le présente. Chaque scan est
            enregistré (date, heure, appareil) à des fins de contrôle et de lutte contre la fraude.
          </p>
          <p class="mt-3">
            L'utilisateur est responsable de la conservation et de la confidentialité de son
            billet : il lui appartient de ne pas le diffuser publiquement. Toute reproduction,
            falsification ou utilisation frauduleuse d'un billet est strictement interdite, peut
            entraîner le refus d'accès sans remboursement et pourra faire l'objet de poursuites.
          </p>
          <p class="mt-3">
            En cas de perte du courriel de confirmation, le billet peut être récupéré à tout
            moment depuis la rubrique « Récupérer mon ticket », en renseignant le nom et le numéro
            de téléphone utilisé lors du paiement ou associé au compte.
          </p>
        </section>

        <!-- Article 9 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            Article 9 - Obligations de l'utilisateur
          </h2>
          <p class="mb-3">L'utilisateur s'interdit notamment :</p>
          <ul class="list-disc list-inside space-y-2 ml-4">
            <li>D'usurper l'identité d'un tiers ou d'utiliser un moyen de paiement dont il n'est pas titulaire</li>
            <li>De tenter d'accéder frauduleusement au système, de le perturber ou d'en contourner les mesures de sécurité</li>
            <li>D'extraire ou de réutiliser de manière systématique le contenu de la plateforme, notamment par des procédés automatisés</li>
            <li>De revendre des billets dans des conditions non autorisées par l'organisateur</li>
            <li>De publier, en tant qu'organisateur, un contenu illicite, trompeur ou portant atteinte aux droits de tiers</li>
          </ul>
          <p class="mt-3">
            En cas de manquement, Primea peut suspendre ou supprimer le compte concerné, après en
            avoir informé l'utilisateur lorsque les circonstances le permettent.
          </p>
        </section>

        <!-- Article 10 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            Article 10 - Annulation et remboursement
          </h2>
          <p class="mb-3">
            Un billet d'événement est une prestation de loisir fournie à une date déterminée :
            <strong>aucun droit de rétractation ne peut être exercé</strong> après l'achat, la
            commande étant ferme et définitive dès la confirmation du paiement. Le remboursement
            n'est possible que dans les cas suivants :
          </p>
          <ul class="list-disc list-inside space-y-2 ml-4">
            <li>
              <strong>Annulation de l'événement par l'organisateur :</strong> l'acheteur est
              remboursé du prix de ses billets, sur le moyen de paiement utilisé lors de la
              commande.
            </li>
            <li>
              <strong>Report de l'événement par l'organisateur :</strong> les billets restent
              valables pour la nouvelle date. Si celle-ci ne convient pas à l'acheteur, celui-ci
              peut demander le remboursement dans le délai indiqué lors de l'annonce du report.
            </li>
          </ul>
          <p class="mt-3">
            En dehors de ces deux hypothèses, aucun remboursement n'est dû, notamment en cas de
            non-présentation à l'événement. Les modalités complètes figurent dans les
            <router-link to="/sales-terms" class="text-primea-blue font-medium hover:text-primea-yellow">
              Conditions générales de vente
            </router-link>.
          </p>
        </section>

        <!-- Article 11 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            Article 11 - Responsabilités
          </h2>
          <p>
            Primea agit en qualité d'intermédiaire technique entre les organisateurs et les
            acheteurs. La responsabilité de l'organisation, du déroulement et du contenu de
            l'événement incombe exclusivement à l'organisateur. Primea ne saurait être tenue
            responsable de l'annulation, du report ou de la modification d'un événement, ni des
            dommages directs ou indirects pouvant en résulter.
          </p>
          <p class="mt-3">
            Primea s'engage à mettre en œuvre les moyens raisonnables pour assurer le bon
            fonctionnement technique de la plateforme, sans garantir une disponibilité
            ininterrompue. L'accès au service peut être suspendu pour des opérations de
            maintenance ou en cas de force majeure, notamment d'interruption des réseaux de
            télécommunication ou des services des opérateurs de paiement.
          </p>
        </section>

        <!-- Article 12 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            Article 12 - Propriété intellectuelle
          </h2>
          <p>
            L'ensemble des éléments de la plateforme Primea — marque, logos, textes, graphismes,
            interfaces, logiciels et bases de données — est protégé par les droits de propriété
            intellectuelle. Toute reproduction, représentation ou exploitation non autorisée de
            ces éléments est strictement interdite. Les visuels et descriptifs des événements sont
            publiés sous la responsabilité des organisateurs, qui garantissent détenir les droits
            nécessaires à leur diffusion.
          </p>
        </section>

        <!-- Article 13 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            Article 13 - Données personnelles
          </h2>
          <p>
            Le traitement des données personnelles est régi par la loi n° 001/2011 du
            25 septembre 2011 relative à la protection des données à caractère personnel,
            modifiée par la loi n° 025/2023 du 12 juillet 2023, et détaillé dans notre
            <router-link to="/privacy" class="text-primea-blue font-medium hover:text-primea-yellow">
              Politique de confidentialité
            </router-link>.
            L'autorité de contrôle compétente est la Commission nationale pour la protection des
            données à caractère personnel (CNPDCP), devenue l'Autorité pour la protection des
            données à caractère personnel et de la vie privée (APDPVP).
          </p>
        </section>

        <!-- Article 14 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            Article 14 - Preuve des opérations
          </h2>
          <p>
            Les enregistrements conservés par Primea et par la passerelle de paiement — journaux
            de connexion, de commande, de paiement et de scan des billets — constituent la preuve
            des opérations réalisées sur la plateforme. Les parties reconnaissent la valeur
            probatoire de ces écrits électroniques dans les conditions prévues par la loi
            n° 025/2021 du 28 décembre 2021 portant réglementation des transactions électroniques
            en République gabonaise.
          </p>
        </section>

        <!-- Article 15 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            Article 15 - Modification des CGU
          </h2>
          <p>
            Primea se réserve le droit de modifier les présentes CGU à tout moment. Les
            utilisateurs seront informés de toute modification substantielle. La poursuite de
            l'utilisation de la plateforme après modification vaut acceptation des nouvelles
            conditions. La version applicable à une commande est celle en vigueur au jour de
            cette commande.
          </p>
        </section>

        <!-- Article 16 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            Article 16 - Droit applicable et litiges
          </h2>
          <p>
            Les présentes CGU sont régies par le droit gabonais, complété, pour les aspects
            commerciaux, par l'Acte uniforme OHADA relatif au droit commercial général. En cas de
            litige, les parties s'engagent à rechercher une solution amiable avant toute action
            judiciaire. À défaut d'accord amiable, les tribunaux compétents de Libreville (Gabon)
            seront seuls compétents.
          </p>
        </section>

        <!-- Contact -->
        <section class="bg-gray-50 rounded-xl p-6">
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            Contact
          </h2>
          <p>
            Pour toute question relative aux présentes CGU, contactez-nous à :
            <a href="mailto:contact@primea.ga" class="text-primea-blue font-medium hover:text-primea-yellow">
              contact@primea.ga
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
          class="text-primea-blue hover:text-primea-yellow font-medium transition-colors"
        >
          &larr; Retour à l'accueil
        </router-link>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Terms',
};
</script>

<style scoped>
</style>
