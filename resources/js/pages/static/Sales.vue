<template>
  <div class="min-h-screen bg-white py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="text-center mb-12">
        <h1 class="text-3xl sm:text-4xl font-bold text-primea-blue mb-4">
          Conditions générales de vente
        </h1>
        <p class="text-sm text-gray-500">
          Dernière mise à jour : 23 septembre 2026
        </p>
      </div>

      <!-- Content -->
      <div class="prose-container space-y-8 text-gray-700 leading-relaxed">
        <!-- Préambule -->
        <section>
          <p>
            Les présentes Conditions générales de vente (ci-après les « CGV ») régissent
            exclusivement la vente de billets d'événements réalisée par l'intermédiaire de la
            plateforme Primea, accessible à l'adresse https://primea.ga. Elles complètent les
            <router-link to="/terms" class="text-primea-blue font-medium hover:text-primea-yellow">
              Conditions générales d'utilisation
            </router-link>,
            qui encadrent, elles, la simple utilisation de la plateforme. En cas de contradiction
            entre les deux documents pour ce qui concerne une commande, les présentes CGV prévalent.
          </p>
          <p class="mt-3">
            Toute commande passée sur la plateforme suppose l'acceptation préalable, pleine et
            entière, des présentes CGV. Cette acceptation est recueillie par voie électronique,
            conformément à la loi n° 025/2021 du 28 décembre 2021 portant réglementation des
            transactions électroniques en République gabonaise.
          </p>
        </section>

        <!-- Article 1 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            Article 1 - Qualité des parties
          </h2>
          <p class="mb-3">
            La vente d'un billet fait intervenir trois parties dont les rôles doivent être
            clairement distingués :
          </p>
          <ul class="list-disc list-inside space-y-2 ml-4">
            <li>
              <strong>L'organisateur :</strong> personne physique ou morale qui conçoit, produit et
              tient l'événement. Il est le vendeur du billet, il en fixe le prix, les conditions
              d'accès et il est seul responsable de la tenue effective de l'événement.
            </li>
            <li>
              <strong>Primea :</strong> intermédiaire technique de billetterie. Primea met à
              disposition la plateforme, encaisse le prix pour le compte de l'organisateur, émet
              le billet électronique et en assure le contrôle à l'entrée. Primea n'est ni le
              producteur ni le coorganisateur de l'événement.
            </li>
            <li>
              <strong>L'acheteur :</strong> personne qui commande un ou plusieurs billets, en tant
              qu'invité ou depuis un compte utilisateur.
            </li>
          </ul>
          <p class="mt-3">
            Le contrat de vente du billet est ainsi formé entre l'acheteur et l'organisateur.
            Les relations commerciales entre Primea et les organisateurs s'inscrivent dans le
            cadre de l'Acte uniforme OHADA relatif au droit commercial général.
          </p>
        </section>

        <!-- Article 2 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            Article 2 - Produits proposés
          </h2>
          <p>
            Les produits vendus sur la plateforme sont des billets électroniques donnant accès à
            un événement déterminé, à une date et dans un lieu annoncés. Chaque billet est associé
            à un type de billet (par exemple standard, VIP) et, le cas échéant, à une date précise
            lorsque l'événement se déroule sur plusieurs dates. Les caractéristiques essentielles
            de l'événement et du billet sont présentées sur la page de l'événement avant la
            validation de la commande.
          </p>
          <p class="mt-3">
            Les billets sont proposés dans la limite des quantités mises en vente par
            l'organisateur. Primea se réserve le droit d'interrompre la vente lorsque la capacité
            annoncée est atteinte.
          </p>
        </section>

        <!-- Article 3 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            Article 3 - Prix, frais de service et commission
          </h2>
          <p class="mb-3">
            Les prix sont exprimés en Francs CFA (XAF) et s'entendent toutes taxes comprises
            lorsque des taxes sont applicables. Le prix du billet est fixé librement par
            l'organisateur. Il peut évoluer dans le temps lorsque l'organisateur a mis en place
            des paliers tarifaires, par exemple une prévente suivie d'un tarif plein à compter
            d'une date et d'une heure annoncées. Le prix appliqué est celui affiché au moment de
            la validation de la commande.
          </p>
          <p class="mb-3">Deux éléments distincts s'ajoutent au prix du billet ou en sont déduits :</p>
          <ul class="list-disc list-inside space-y-2 ml-4">
            <li>
              <strong>Les frais de service de la passerelle de paiement,</strong> d'un montant de
              2,5 % du montant de la transaction. Selon le choix fait par l'organisateur pour son
              événement, ces frais sont soit ajoutés au montant payé par l'acheteur, soit absorbés
              par la plateforme. Lorsqu'ils sont à la charge de l'acheteur, ils sont affichés de
              manière séparée et explicite dans le récapitulatif de commande, avant tout paiement.
            </li>
            <li>
              <strong>La commission de la plateforme,</strong> prélevée sur le produit des ventes
              revenant à l'organisateur. Son taux est fixé événement par événement et convenu avec
              l'organisateur. Cette commission est déduite du reversement à l'organisateur ; elle
              n'est pas ajoutée au prix payé par l'acheteur.
            </li>
          </ul>
          <p class="mt-3">
            Le montant total effectivement dû par l'acheteur est affiché avant la validation
            définitive de la commande. Aucun montant supplémentaire ne peut être exigé après cette
            validation.
          </p>
        </section>

        <!-- Article 4 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            Article 4 - Ouverture de la billetterie
          </h2>
          <p>
            L'organisateur peut choisir d'ouvrir la billetterie de son événement à une date et à
            une heure annoncées à l'avance. Dans ce cas, l'événement est visible sur la plateforme
            avant cette date, accompagné d'un compte à rebours, mais aucun achat n'est possible
            tant que l'ouverture n'est pas intervenue. La présence d'un événement sur la
            plateforme avant l'ouverture de sa billetterie ne vaut ni offre de vente ni réservation
            et ne confère aucune priorité d'accès.
          </p>
        </section>

        <!-- Article 5 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            Article 5 - Passation de la commande
          </h2>
          <p class="mb-3">
            La commande peut être passée en tant qu'invité, sans création de compte, ou depuis un
            compte utilisateur. Elle se déroule selon les étapes suivantes :
          </p>
          <ul class="list-decimal list-inside space-y-2 ml-4">
            <li>Choix de l'événement, de la date le cas échéant, du type et du nombre de billets.</li>
            <li>
              Saisie des informations nécessaires à l'émission du billet : nom, adresse e-mail et
              numéro de téléphone.
            </li>
            <li>
              Affichage du récapitulatif détaillé indiquant le prix des billets, les frais de
              service lorsqu'ils sont à la charge de l'acheteur, et le montant total à payer.
            </li>
            <li>Acceptation des présentes CGV et choix du moyen de paiement.</li>
            <li>Paiement, puis confirmation de la commande et émission du ou des billets.</li>
          </ul>
          <p class="mt-3">
            L'acheteur est invité à vérifier l'exactitude des informations saisies avant de
            valider. Une adresse e-mail ou un numéro de téléphone erroné peut empêcher la
            réception et la récupération du billet ; Primea ne saurait en être tenue responsable.
            La vente est parfaite dès la confirmation du paiement par la passerelle.
          </p>
        </section>

        <!-- Article 6 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            Article 6 - Moyens de paiement
          </h2>
          <p>
            Le paiement s'effectue en ligne, au moment de la commande, par mobile money
            (Airtel Money, Moov Money) ou par carte. Ces paiements sont opérés par la passerelle
            e-billing / SHAP, prestataire de services de paiement partenaire de la plateforme.
          </p>
          <p class="mt-3">
            Les données de paiement de l'acheteur — numéro de carte, codes de confirmation mobile
            money, identifiants bancaires — ne transitent jamais par Primea et ne sont à aucun
            moment stockées sur ses serveurs. Elles sont saisies et traitées directement par la
            passerelle de paiement, dans un environnement sécurisé. La sécurisation des
            transactions électroniques s'inscrit dans le cadre de la loi n° 025/2021 du
            28 décembre 2021 portant réglementation des transactions électroniques en République
            gabonaise.
          </p>
          <p class="mt-3">
            Aucun billet n'est émis tant que le paiement n'a pas été effectivement confirmé par la
            passerelle. Un paiement refusé, interrompu ou expiré entraîne l'annulation automatique
            de la commande, sans frais pour l'acheteur.
          </p>
        </section>

        <!-- Article 7 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            Article 7 - Délivrance et récupération du billet
          </h2>
          <p>
            Le billet est émis sous forme électronique dès la confirmation du paiement. Il est mis
            à disposition de l'acheteur sur la plateforme et adressé à l'adresse e-mail indiquée
            lors de la commande. Chaque billet comporte un QR code unique, strictement personnel,
            qui constitue le seul titre d'accès à l'événement.
          </p>
          <p class="mt-3">
            En cas de perte du courriel ou de difficulté d'accès, l'acheteur peut à tout moment
            récupérer son billet depuis la rubrique « Récupérer mon ticket », en renseignant son
            nom et le numéro de téléphone utilisé lors du paiement ou associé à son compte. Cette
            fonctionnalité ne délivre aucun nouveau billet : elle redonne accès au billet déjà émis.
          </p>
        </section>

        <!-- Article 8 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            Article 8 - Contrôle d'accès et usage unique du billet
          </h2>
          <p>
            Le QR code du billet est scanné à l'entrée de l'événement. Un billet ne peut être
            utilisé qu'une seule fois : un billet déjà scanné est automatiquement refusé, quelle
            que soit la personne qui le présente. Il appartient donc à l'acheteur de conserver son
            billet confidentiel et de ne pas le diffuser, le photographier publiquement ni le
            transmettre à des tiers.
          </p>
          <p class="mt-3">
            Toute reproduction, falsification, duplication ou utilisation frauduleuse d'un billet
            est interdite et peut donner lieu au refus d'accès sans remboursement, ainsi qu'à des
            poursuites. L'accès à l'événement peut en outre être subordonné au respect du règlement
            intérieur du lieu et des conditions fixées par l'organisateur.
          </p>
        </section>

        <!-- Article 9 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            Article 9 - Absence de droit de rétractation
          </h2>
          <p>
            Un billet d'événement est une prestation de loisir fournie à une date déterminée. À ce
            titre, <strong>aucun droit de rétractation ne peut être exercé</strong> après l'achat :
            la commande est ferme et définitive dès la confirmation du paiement. L'acheteur qui ne
            se présente pas à l'événement ne peut prétendre à aucun remboursement ni à aucun avoir.
          </p>
          <p class="mt-3">
            Cette exclusion, expressément portée à la connaissance de l'acheteur avant la
            validation de sa commande, ne fait pas obstacle aux cas de remboursement prévus à
            l'article 10.
          </p>
        </section>

        <!-- Article 10 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            Article 10 - Annulation ou report de l'événement
          </h2>
          <p class="mb-3">
            Le remboursement d'un billet n'est possible que dans les cas suivants :
          </p>
          <ul class="list-disc list-inside space-y-2 ml-4">
            <li>
              <strong>Annulation de l'événement par l'organisateur :</strong> l'acheteur est
              remboursé du prix des billets. Le remboursement est opéré sur le moyen de paiement
              utilisé lors de la commande, ou sur le numéro mobile money ayant servi au paiement.
            </li>
            <li>
              <strong>Report de l'événement par l'organisateur :</strong> le billet reste valable
              pour la nouvelle date. Si celle-ci ne convient pas à l'acheteur, celui-ci peut
              demander le remboursement du prix de son billet dans le délai indiqué lors de
              l'annonce du report.
            </li>
          </ul>
          <p class="mt-3">
            En dehors de ces deux hypothèses, aucun remboursement n'est dû. Les frais de service
            de la passerelle, lorsqu'ils ont été supportés par l'acheteur, suivent le sort du
            remboursement principal lorsque l'annulation est du fait de l'organisateur.
          </p>
          <p class="mt-3">
            Primea intervient au titre du remboursement en qualité d'intermédiaire technique,
            pour le compte de l'organisateur. Les délais de restitution effective des fonds
            dépendent notamment des opérateurs de paiement concernés.
          </p>
        </section>

        <!-- Article 11 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            Article 11 - Responsabilité
          </h2>
          <p>
            L'organisateur est seul responsable de la tenue de l'événement, de son contenu, de sa
            programmation, de sa conformité aux autorisations requises, de la sécurité des lieux
            et de l'accueil du public. Primea, en sa qualité d'intermédiaire technique de
            billetterie, ne peut être tenue responsable de l'annulation, du report, de la
            modification ou du déroulement d'un événement, ni des dommages qui en résulteraient.
          </p>
          <p class="mt-3">
            La responsabilité de Primea est limitée au bon fonctionnement du service de
            billetterie qu'elle fournit : mise en vente, encaissement, émission du billet et
            contrôle d'accès. Elle ne saurait excéder le montant des sommes effectivement
            encaissées au titre de la commande concernée.
          </p>
        </section>

        <!-- Article 12 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            Article 12 - Preuve des opérations
          </h2>
          <p>
            Les enregistrements conservés par Primea et par la passerelle de paiement — journaux
            de commande, confirmations de paiement, journaux de scan des billets — constituent la
            preuve des transactions et de l'utilisation des billets. Les parties reconnaissent la
            valeur probatoire de ces écrits électroniques dans les conditions prévues par la loi
            n° 025/2021 du 28 décembre 2021 portant réglementation des transactions électroniques
            en République gabonaise.
          </p>
        </section>

        <!-- Article 13 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            Article 13 - Données personnelles
          </h2>
          <p>
            Les données collectées à l'occasion d'une commande sont traitées conformément à la
            loi n° 001/2011 du 25 septembre 2011 relative à la protection des données à caractère
            personnel, modifiée par la loi n° 025/2023 du 12 juillet 2023, et dans les conditions
            détaillées par notre
            <router-link to="/privacy" class="text-primea-blue font-medium hover:text-primea-yellow">
              Politique de confidentialité
            </router-link>.
          </p>
        </section>

        <!-- Article 14 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            Article 14 - Force majeure
          </h2>
          <p>
            Aucune des parties ne peut être tenue responsable d'un manquement à ses obligations
            résultant d'un cas de force majeure, c'est-à-dire d'un événement imprévisible,
            irrésistible et extérieur. Sont notamment visées les interruptions des réseaux de
            télécommunication ou des services des opérateurs de paiement, ainsi que les décisions
            des autorités publiques faisant obstacle à la tenue de l'événement.
          </p>
        </section>

        <!-- Article 15 -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            Article 15 - Réclamations, droit applicable et litiges
          </h2>
          <p>
            Toute réclamation relative à une commande doit être adressée à Primea à l'adresse
            indiquée ci-dessous, en précisant la référence de la commande. Les réclamations
            portant sur le déroulement de l'événement relèvent de l'organisateur.
          </p>
          <p class="mt-3">
            Les présentes CGV sont soumises au droit gabonais, complété, pour les aspects
            commerciaux, par l'Acte uniforme OHADA relatif au droit commercial général. En cas de
            litige, les parties s'efforceront de trouver une solution amiable. À défaut, les
            tribunaux compétents de Libreville (Gabon) seront seuls compétents.
          </p>
        </section>

        <!-- Contact -->
        <section class="bg-gray-50 rounded-xl p-6">
          <h2 class="text-xl font-bold text-primea-blue mb-3">
            Contact
          </h2>
          <p>
            Pour toute question relative aux présentes conditions générales de vente ou à une
            commande, contactez-nous à :
            <a href="mailto:client@example.test" class="text-primea-blue font-medium hover:text-primea-yellow">
              client@example.test
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
  name: 'Sales',
};
</script>

<style scoped>
</style>
