<template>
  <div class="min-h-screen bg-white">
    <!-- En-tête -->
    <section class="bg-primea-blue/5 border-b border-primea-blue/10 py-14 px-4 sm:px-6 lg:px-8">
      <div class="max-w-4xl mx-auto text-center">
        <h1 class="text-3xl sm:text-4xl font-bold text-primea-blue mb-4">
          Comment ça marche ?
        </h1>
        <p class="text-lg text-gray-600 max-w-2xl mx-auto leading-relaxed">
          Trouver un événement, payer avec son téléphone, entrer avec son QR code.
          Voici le parcours complet, côté public comme côté organisateur.
        </p>

        <div class="flex flex-wrap justify-center gap-3 mt-8">
          <router-link
            to="/events"
            class="inline-flex items-center gap-2 bg-primea-blue text-white px-6 py-3 rounded-xl font-semibold hover:bg-primea-blue/90 transition-colors"
          >
            Voir les événements
          </router-link>
          <a
            href="#organisateurs"
            class="inline-flex items-center gap-2 bg-white text-primea-blue border-2 border-primea-blue px-6 py-3 rounded-xl font-semibold hover:bg-primea-blue/5 transition-colors"
          >
            J'organise un événement
          </a>
        </div>
      </div>
    </section>

    <div class="max-w-4xl mx-auto py-14 px-4 sm:px-6 lg:px-8">
      <!-- ================= ACHETER ================= -->
      <h2 class="text-2xl font-bold text-primea-blue mb-2">Acheter un billet</h2>
      <p class="text-gray-600 mb-10">Trois étapes, sans créer de compte si vous ne le souhaitez pas.</p>

      <div class="space-y-12">
        <div
          v-for="step in buyerSteps"
          :key="step.number"
          class="flex flex-col sm:flex-row gap-6"
        >
          <div class="flex-shrink-0 w-16 h-16 rounded-full bg-primea-blue flex items-center justify-center mx-auto sm:mx-0">
            <span class="text-2xl font-bold text-primea-yellow">{{ step.number }}</span>
          </div>
          <div class="flex-1 text-center sm:text-left">
            <h3 class="text-xl font-bold text-primea-blue mb-2">{{ step.title }}</h3>
            <p class="text-gray-600 leading-relaxed mb-3">{{ step.body }}</p>
            <ul v-if="step.points" class="space-y-1.5">
              <li
                v-for="point in step.points"
                :key="point"
                class="flex items-start gap-2 text-sm text-gray-600 justify-center sm:justify-start"
              >
                <span class="text-primea-yellow font-bold mt-0.5">•</span>
                <span class="text-left">{{ point }}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- ================= PAIEMENT ================= -->
      <section class="mt-16 bg-gray-50 rounded-2xl p-6 sm:p-8">
        <h2 class="text-2xl font-bold text-primea-blue mb-2">Moyens de paiement</h2>
        <p class="text-gray-600 mb-6">
          Le paiement se fait depuis votre téléphone, en francs CFA. Vos coordonnées bancaires
          ne transitent jamais par Primea : elles sont traitées par la passerelle de paiement.
        </p>

        <div class="grid sm:grid-cols-3 gap-4">
          <div
            v-for="method in paymentMethods"
            :key="method.name"
            class="bg-white rounded-xl border border-gray-200 p-4"
          >
            <h3 class="font-bold text-primea-blue mb-1">{{ method.name }}</h3>
            <p class="text-sm text-gray-600 leading-relaxed">{{ method.detail }}</p>
          </div>
        </div>

        <p class="text-sm text-gray-500 mt-5 leading-relaxed">
          Un paiement mobile se confirme par un code reçu sur votre téléphone. Si la confirmation
          tarde, ne recommencez pas tout de suite : nous interrogeons la passerelle toutes les
          cinq minutes et votre billet est émis dès que le paiement est constaté.
        </p>
      </section>

      <!-- ================= JOUR J ================= -->
      <section class="mt-16">
        <h2 class="text-2xl font-bold text-primea-blue mb-2">Le jour de l'événement</h2>
        <p class="text-gray-600 mb-6">
          Présentez votre QR code à l'entrée, sur l'écran de votre téléphone ou imprimé.
          Un agent le scanne, la validation est immédiate.
        </p>

        <div class="grid sm:grid-cols-2 gap-4">
          <div
            v-for="tip in entryTips"
            :key="tip.title"
            class="border border-gray-200 rounded-xl p-5"
          >
            <h3 class="font-bold text-primea-blue mb-2">{{ tip.title }}</h3>
            <p class="text-sm text-gray-600 leading-relaxed">{{ tip.body }}</p>
          </div>
        </div>
      </section>

      <!-- ================= BILLET PERDU ================= -->
      <section class="mt-16 bg-primea-yellow/10 border border-primea-yellow/40 rounded-2xl p-6 sm:p-8">
        <h2 class="text-2xl font-bold text-primea-blue mb-3">Billet perdu ou message effacé ?</h2>
        <p class="text-gray-600 leading-relaxed mb-5">
          Rien n'est perdu. Retrouvez votre billet avec votre nom et le numéro de téléphone
          utilisé lors de l'achat. Le numéro de votre compte fonctionne aussi, si vous en avez un.
          Les billets restent accessibles jusqu'au lendemain de l'événement, et si l'organisateur
          reporte la date, votre billet redevient disponible.
        </p>
        <router-link
          :to="{ name: 'ticket-retrieve' }"
          class="inline-flex items-center gap-2 bg-primea-blue text-white px-6 py-3 rounded-xl font-semibold hover:bg-primea-blue/90 transition-colors"
        >
          Récupérer mon billet
        </router-link>
      </section>

      <!-- ================= ORGANISATEURS ================= -->
      <section id="organisateurs" class="mt-20 scroll-mt-24">
        <h2 class="text-2xl font-bold text-primea-blue mb-2">Vous organisez un événement</h2>
        <p class="text-gray-600 mb-10">
          De la création de l'événement au versement de vos recettes.
        </p>

        <div class="space-y-12">
          <div
            v-for="step in organizerSteps"
            :key="step.number"
            class="flex flex-col sm:flex-row gap-6"
          >
            <div class="flex-shrink-0 w-16 h-16 rounded-full bg-primea-yellow flex items-center justify-center mx-auto sm:mx-0">
              <span class="text-2xl font-bold text-primea-blue">{{ step.number }}</span>
            </div>
            <div class="flex-1 text-center sm:text-left">
              <h3 class="text-xl font-bold text-primea-blue mb-2">{{ step.title }}</h3>
              <p class="text-gray-600 leading-relaxed mb-3">{{ step.body }}</p>
              <ul v-if="step.points" class="space-y-1.5">
                <li
                  v-for="point in step.points"
                  :key="point"
                  class="flex items-start gap-2 text-sm text-gray-600 justify-center sm:justify-start"
                >
                  <span class="text-primea-blue font-bold mt-0.5">•</span>
                  <span class="text-left">{{ point }}</span>
                </li>
              </ul>
            </div>
          </div>
        </div>

        <div class="mt-10 text-center">
          <router-link
            :to="{ name: 'organizer-choice' }"
            class="inline-flex items-center gap-2 bg-primea-blue text-white px-8 py-4 rounded-xl font-semibold hover:bg-primea-blue/90 transition-colors"
          >
            Devenir organisateur
          </router-link>
        </div>
      </section>

      <!-- ================= QUESTIONS ================= -->
      <section class="mt-20">
        <h2 class="text-2xl font-bold text-primea-blue mb-6">Questions fréquentes</h2>

        <div class="space-y-3">
          <details
            v-for="item in faq"
            :key="item.question"
            class="group border border-gray-200 rounded-xl overflow-hidden"
          >
            <summary class="cursor-pointer list-none px-5 py-4 font-semibold text-primea-blue flex items-center justify-between gap-4 hover:bg-gray-50">
              <span>{{ item.question }}</span>
              <span class="text-primea-yellow text-xl leading-none transition-transform group-open:rotate-45">+</span>
            </summary>
            <p class="px-5 pb-4 text-gray-600 leading-relaxed">{{ item.answer }}</p>
          </details>
        </div>

        <p class="text-sm text-gray-500 mt-6">
          Vous ne trouvez pas votre réponse ?
          <router-link to="/help" class="text-primea-blue font-semibold hover:text-primea-yellow">Consultez l'aide</router-link>
          ou
          <router-link to="/contact" class="text-primea-blue font-semibold hover:text-primea-yellow">écrivez-nous</router-link>.
        </p>
      </section>

      <!-- ================= APPEL FINAL ================= -->
      <section class="mt-16 text-center bg-primea-blue rounded-2xl p-8 sm:p-12">
        <h2 class="text-2xl font-bold text-white mb-3">Prêt à réserver votre place ?</h2>
        <p class="text-white/80 mb-6 max-w-xl mx-auto leading-relaxed">
          Concerts, festivals, spectacles, conférences : découvrez ce qui se passe près de chez vous.
        </p>
        <router-link
          to="/events"
          class="inline-flex items-center gap-2 bg-primea-yellow text-primea-blue px-8 py-4 rounded-xl font-bold hover:bg-primea-yellow/90 transition-colors"
        >
          Voir les événements
        </router-link>
      </section>

      <div class="mt-12 text-center text-sm text-gray-500">
        <router-link to="/terms" class="hover:text-primea-blue">Conditions d'utilisation</router-link>
        <span class="mx-2">·</span>
        <router-link to="/sales-terms" class="hover:text-primea-blue">Conditions de vente</router-link>
        <span class="mx-2">·</span>
        <router-link to="/privacy" class="hover:text-primea-blue">Confidentialité</router-link>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'HowItWorks',

  setup () {
    // Le contenu est décrit en données plutôt qu'en balisage : les étapes se
    // ressemblent toutes, et une liste évite d'en recopier la mise en forme.
    const buyerSteps = [
      {
        number: 1,
        title: "Trouvez votre événement",
        body: "Parcourez les événements au Gabon, filtrez par catégorie et ouvrez la fiche qui vous intéresse : lieu, date, horaires, description et tarifs y figurent.",
        points: [
          "Certains événements sont annoncés avant l'ouverture des ventes : un compte à rebours indique le moment exact où l'achat s'ouvrira.",
          "Le prix affiché est celui du moment. Une prévente peut coûter moins cher que le tarif du jour.",
        ],
      },
      {
        number: 2,
        title: 'Payez depuis votre téléphone',
        body: "Choisissez la catégorie de billet et la quantité, puis réglez par mobile money ou par carte. Vous pouvez acheter en tant qu'invité, sans créer de compte.",
        points: [
          "Indiquez un numéro de téléphone valide : c'est lui qui permettra de retrouver votre billet.",
          "Selon l'événement, des frais de service de 2,5 % sont ajoutés au montant ou pris en charge par la plateforme. Le total à payer est toujours affiché avant validation.",
        ],
      },
      {
        number: 3,
        title: 'Recevez votre billet avec son QR code',
        body: "Dès le paiement confirmé, votre billet est émis avec un QR code unique. Téléchargez-le en image ou en PDF, gardez-le sur votre téléphone ou imprimez-le.",
        points: [
          "Le billet vous est aussi envoyé par courriel.",
          "Si vous avez un compte, vous le retrouvez à tout moment dans « Mes billets ».",
        ],
      },
    ]

    const paymentMethods = [
      { name: 'Airtel Money', detail: "Confirmez le paiement avec le code reçu sur votre téléphone." },
      { name: 'Moov Money', detail: "Même principe : validez la demande qui s'affiche sur votre mobile." },
      { name: 'Carte bancaire', detail: 'Paiement sécurisé par la passerelle, Visa et Mastercard.' },
    ]

    const entryTips = [
      {
        title: 'Un QR code, une entrée',
        body: "Chaque billet ne passe qu'une fois. Au second scan, il est refusé. Ne partagez donc jamais votre QR code : la première personne à le présenter entrerait à votre place.",
      },
      {
        title: 'Arrivez un peu en avance',
        body: "Le contrôle prend quelques secondes par personne. Préparez votre QR code à l'écran, luminosité au maximum, pour que le scan soit immédiat.",
      },
      {
        title: 'Pas de réseau sur place ?',
        body: "Ce n'est pas un problème. Téléchargez votre billet avant de partir : le contrôle fonctionne même sans connexion, et les scans se synchronisent ensuite.",
      },
      {
        title: 'Billet imprimé',
        body: "Un billet imprimé vaut le billet numérique. Veillez simplement à ce que le QR code soit net et non plié en son centre.",
      },
    ]

    const organizerSteps = [
      {
        number: 1,
        title: 'Créez votre événement',
        body: "Renseignez le titre, l'affiche, le lieu, les dates et vos catégories de billets avec leur prix et leur capacité.",
        points: [
          "Plusieurs dates possibles pour un même événement, chacune avec son propre suivi.",
          "Vous pouvez programmer l'ouverture des ventes : l'événement est visible avant, avec un compte à rebours, mais personne ne peut acheter avant l'heure.",
        ],
      },
      {
        number: 2,
        title: 'Votre événement est validé',
        body: "Notre équipe vérifie chaque événement avant sa mise en ligne, et convient avec vous du taux de commission appliqué à vos ventes.",
        points: [
          "La commission est fixée événement par événement, pas une fois pour toutes.",
          "Vous choisissez qui supporte les frais de service : vos acheteurs, ou vous.",
        ],
      },
      {
        number: 3,
        title: 'Suivez vos ventes en direct',
        body: "Un lien de suivi vous est remis : il montre les billets vendus, les recettes et les entrées scannées, sans avoir à vous connecter.",
        points: [
          "Ce lien se partage avec votre équipe ou votre partenaire.",
          "Les statistiques distinguent les billets émis, scannés et non encore présentés.",
        ],
      },
      {
        number: 4,
        title: 'Contrôlez les entrées',
        body: "Votre équipe scanne les billets avec l'application mobile Primea. Chaque scan affiche le porteur, sa catégorie et son tarif avant de laisser entrer.",
        points: [
          "L'anti-double-scan refuse tout billet déjà présenté.",
          "Le contrôle fonctionne sans réseau et se synchronise au retour de la connexion.",
        ],
      },
      {
        number: 5,
        title: 'Recevez vos recettes',
        body: "Selon ce que vous avez choisi à la création, les fonds vous sont versés à chaque vente sur le numéro que vous avez indiqué, ou à la fin de l'événement.",
        points: [
          "La commission de la plateforme est déduite au moment du versement.",
          "Chaque versement est tracé et consultable dans votre espace.",
        ],
      },
    ]

    const faq = [
      {
        question: "Faut-il créer un compte pour acheter ?",
        answer: "Non. L'achat en tant qu'invité suffit, avec votre nom, votre courriel et votre numéro de téléphone. Un compte sert surtout à retrouver l'ensemble de vos commandes et billets au même endroit.",
      },
      {
        question: "J'ai payé mais je n'ai pas reçu mon billet.",
        answer: "La confirmation d'un paiement mobile met parfois quelques minutes. Nous interrogeons la passerelle toutes les cinq minutes et votre billet est émis dès que le paiement est constaté. Passé un quart d'heure, utilisez « Récupérer mon billet » avec votre nom et votre numéro, ou contactez-nous.",
      },
      {
        question: "Pourquoi l'achat est-il impossible alors que l'événement est affiché ?",
        answer: "Parce que sa billetterie n'a pas encore ouvert. La date et l'heure d'ouverture sont indiquées sur la page, avec un compte à rebours. L'achat s'active tout seul à l'échéance, sans recharger la page.",
      },
      {
        question: "Puis-je me faire rembourser ?",
        answer: "Un billet d'événement daté ne donne pas de droit de rétractation. Le remboursement intervient si l'organisateur annule ou reporte l'événement. Les conditions complètes figurent dans les conditions générales de vente.",
      },
      {
        question: "Puis-je acheter plusieurs billets en une fois ?",
        answer: "Oui. Choisissez la quantité voulue avant de payer. Chaque billet reçoit son propre QR code, et vous pouvez les transmettre individuellement aux personnes concernées.",
      },
      {
        question: "Que valent les frais de service ?",
        answer: "Ils représentent 2,5 % et couvrent le traitement du paiement. Selon l'événement, ils sont ajoutés au montant que vous payez ou pris en charge par la plateforme. Le total est toujours affiché avant que vous ne validiez.",
      },
    ]

    return { buyerSteps, paymentMethods, entryTips, organizerSteps, faq }
  },
}
</script>
