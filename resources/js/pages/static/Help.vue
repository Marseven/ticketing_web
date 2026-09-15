<template>
  <div class="min-h-screen bg-white py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="text-center mb-12">
        <h1 class="text-3xl sm:text-4xl font-bold text-primea-blue mb-4">
          Centre d'aide
        </h1>
        <p class="text-lg text-gray-600 max-w-2xl mx-auto">
          Retrouvez les reponses aux questions les plus frequentes concernant
          l'utilisation de la plateforme Primea.
        </p>
      </div>

      <!-- FAQ Sections -->
      <div class="space-y-10">
        <!-- Section: Achat de billets -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-4 pb-2 border-b-2 border-primea-yellow">
            Achat de billets
          </h2>
          <div class="space-y-4">
            <div
              v-for="(faq, index) in purchaseFaqs"
              :key="'purchase-' + index"
              class="bg-gray-50 rounded-lg p-5"
            >
              <button
                class="w-full text-left flex justify-between items-center"
                @click="toggle('purchase', index)"
              >
                <span class="font-semibold text-primea-blue">{{ faq.question }}</span>
                <span class="text-primea-yellow text-xl ml-4 flex-shrink-0">
                  {{ isOpen('purchase', index) ? '−' : '+' }}
                </span>
              </button>
              <p
                v-if="isOpen('purchase', index)"
                class="mt-3 text-gray-600 leading-relaxed"
              >
                {{ faq.answer }}
              </p>
            </div>
          </div>
        </section>

        <!-- Section: Paiement -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-4 pb-2 border-b-2 border-primea-yellow">
            Paiement
          </h2>
          <div class="space-y-4">
            <div
              v-for="(faq, index) in paymentFaqs"
              :key="'payment-' + index"
              class="bg-gray-50 rounded-lg p-5"
            >
              <button
                class="w-full text-left flex justify-between items-center"
                @click="toggle('payment', index)"
              >
                <span class="font-semibold text-primea-blue">{{ faq.question }}</span>
                <span class="text-primea-yellow text-xl ml-4 flex-shrink-0">
                  {{ isOpen('payment', index) ? '−' : '+' }}
                </span>
              </button>
              <p
                v-if="isOpen('payment', index)"
                class="mt-3 text-gray-600 leading-relaxed"
              >
                {{ faq.answer }}
              </p>
            </div>
          </div>
        </section>

        <!-- Section: Recuperation des billets -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-4 pb-2 border-b-2 border-primea-yellow">
            Recuperation des billets
          </h2>
          <div class="space-y-4">
            <div
              v-for="(faq, index) in ticketFaqs"
              :key="'ticket-' + index"
              class="bg-gray-50 rounded-lg p-5"
            >
              <button
                class="w-full text-left flex justify-between items-center"
                @click="toggle('ticket', index)"
              >
                <span class="font-semibold text-primea-blue">{{ faq.question }}</span>
                <span class="text-primea-yellow text-xl ml-4 flex-shrink-0">
                  {{ isOpen('ticket', index) ? '−' : '+' }}
                </span>
              </button>
              <p
                v-if="isOpen('ticket', index)"
                class="mt-3 text-gray-600 leading-relaxed"
              >
                {{ faq.answer }}
              </p>
            </div>
          </div>
        </section>

        <!-- Section: Remboursements -->
        <section>
          <h2 class="text-xl font-bold text-primea-blue mb-4 pb-2 border-b-2 border-primea-yellow">
            Politique de remboursement
          </h2>
          <div class="space-y-4">
            <div
              v-for="(faq, index) in refundFaqs"
              :key="'refund-' + index"
              class="bg-gray-50 rounded-lg p-5"
            >
              <button
                class="w-full text-left flex justify-between items-center"
                @click="toggle('refund', index)"
              >
                <span class="font-semibold text-primea-blue">{{ faq.question }}</span>
                <span class="text-primea-yellow text-xl ml-4 flex-shrink-0">
                  {{ isOpen('refund', index) ? '−' : '+' }}
                </span>
              </button>
              <p
                v-if="isOpen('refund', index)"
                class="mt-3 text-gray-600 leading-relaxed"
              >
                {{ faq.answer }}
              </p>
            </div>
          </div>
        </section>
      </div>

      <!-- Contact CTA -->
      <div class="mt-12 text-center bg-gray-50 rounded-2xl p-8">
        <h3 class="text-xl font-bold text-primea-blue mb-3">
          Vous ne trouvez pas la reponse a votre question ?
        </h3>
        <p class="text-gray-600 mb-5">
          Notre equipe est disponible pour vous aider.
        </p>
        <router-link
          to="/contact"
          class="inline-block bg-primea-yellow text-primea-blue font-semibold px-8 py-3 rounded-lg hover:opacity-90 transition-opacity"
        >
          Nous contacter
        </router-link>
      </div>

      <!-- Back link -->
      <div class="mt-12 text-center">
        <router-link
          to="/"
          class="text-primea-blue hover:text-primea-yellow font-medium transition-colors"
        >
          &larr; Retour a l'accueil
        </router-link>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Help',
  data() {
    return {
      openItems: {},
      purchaseFaqs: [
        {
          question: 'Comment acheter un billet sur Primea ?',
          answer:
            'Rendez-vous sur la page de l\'evenement qui vous interesse, selectionnez le type de billet et la quantite souhaitee, puis procedez au paiement. Vous pouvez acheter en tant qu\'invite ou en vous connectant a votre compte.',
        },
        {
          question: 'Dois-je creer un compte pour acheter un billet ?',
          answer:
            'Non, vous pouvez acheter en tant qu\'invite en renseignant simplement votre nom, email et numero de telephone. Cependant, un compte vous permet de retrouver facilement vos commandes et billets.',
        },
        {
          question: 'Puis-je acheter plusieurs billets en une seule commande ?',
          answer:
            'Oui, vous pouvez selectionner la quantite souhaitee pour chaque type de billet lors de votre commande. Chaque billet aura son propre QR code unique.',
        },
        {
          question: 'Les billets sont-ils nominatifs ?',
          answer:
            'Les billets sont lies a la commande et non a une personne specifique. Vous pouvez donc les transmettre a un proche en partageant le QR code ou le PDF du billet.',
        },
      ],
      paymentFaqs: [
        {
          question: 'Quels moyens de paiement sont acceptes ?',
          answer:
            'Primea accepte plusieurs moyens de paiement : Airtel Money, Moov Money, carte bancaire (Visa/Mastercard) et E-Billing (paiement USSD). Les moyens disponibles peuvent varier selon l\'evenement.',
        },
        {
          question: 'Le paiement est-il securise ?',
          answer:
            'Oui, tous les paiements sont traites de maniere securisee via nos partenaires de paiement certifies. Aucune donnee bancaire n\'est stockee sur nos serveurs.',
        },
        {
          question: 'Mon paiement a echoue, que faire ?',
          answer:
            'Si votre paiement a echoue, verifiez votre solde et reessayez. Si le montant a ete debite sans confirmation, contactez-nous a contact@primea.ga avec la reference de votre commande. Nous traiterons votre demande dans les plus brefs delais.',
        },
        {
          question: 'Puis-je payer en plusieurs fois ?',
          answer:
            'Non, le paiement fractionne n\'est pas disponible pour le moment. Le montant total doit etre regle en une seule fois.',
        },
      ],
      ticketFaqs: [
        {
          question: 'Comment recuperer mon billet apres l\'achat ?',
          answer:
            'Apres confirmation du paiement, votre billet est disponible immediatement. Vous pouvez le telecharger en PDF depuis la page de confirmation, le retrouver dans votre espace personnel (si vous avez un compte), ou utiliser le lien de recuperation envoye par email.',
        },
        {
          question: 'Je n\'ai pas recu mon billet par email, que faire ?',
          answer:
            'Verifiez d\'abord votre dossier de courriers indesirables (spam). Si vous ne trouvez toujours pas l\'email, utilisez la fonction "Recuperer mon billet" sur le site en renseignant votre reference de commande et votre email. Vous pouvez aussi nous contacter a contact@primea.ga.',
        },
        {
          question: 'Puis-je utiliser mon billet sur telephone ?',
          answer:
            'Oui, vous pouvez presenter le QR code directement depuis votre telephone. Assurez-vous simplement que l\'ecran est suffisamment lumineux pour permettre le scan a l\'entree.',
        },
        {
          question: 'Que se passe-t-il si je perds mon billet ?',
          answer:
            'Pas de panique ! Vous pouvez toujours recuperer votre billet depuis votre espace personnel ou via la page de recuperation avec votre reference de commande. Le QR code reste valide tant qu\'il n\'a pas ete scanne.',
        },
      ],
      refundFaqs: [
        {
          question: 'Puis-je me faire rembourser un billet ?',
          answer:
            'La politique de remboursement depend de chaque organisateur. En regle generale, les remboursements sont possibles jusqu\'a 48 heures avant l\'evenement, sous reserve des conditions fixees par l\'organisateur. Contactez-nous pour toute demande.',
        },
        {
          question: 'Comment demander un remboursement ?',
          answer:
            'Envoyez un email a contact@primea.ga en precisant votre reference de commande, le motif de votre demande et vos coordonnees. Nous traiterons votre demande dans un delai de 5 jours ouvrables.',
        },
        {
          question: 'Que se passe-t-il si un evenement est annule ?',
          answer:
            'En cas d\'annulation d\'un evenement par l\'organisateur, vous serez rembourse integralement. Le remboursement sera effectue sur le meme moyen de paiement utilise lors de l\'achat dans un delai de 7 a 14 jours ouvrables.',
        },
        {
          question: 'Les frais de service sont-ils remboursables ?',
          answer:
            'En cas d\'annulation par l\'organisateur, les frais de service sont integralement rembourses. Pour une annulation a votre initiative, les frais de service ne sont generalement pas remboursables.',
        },
      ],
    };
  },
  methods: {
    toggle(section, index) {
      const key = section + '-' + index;
      this.openItems = { ...this.openItems, [key]: !this.openItems[key] };
    },
    isOpen(section, index) {
      return !!this.openItems[section + '-' + index];
    },
  },
};
</script>

<style scoped>
</style>
