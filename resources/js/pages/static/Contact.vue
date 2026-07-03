<template>
  <div class="min-h-screen bg-white py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="text-center mb-12">
        <h1 class="text-3xl sm:text-4xl font-bold text-primea-blue mb-4">
          Nous contacter
        </h1>
        <p class="text-lg text-gray-600 max-w-2xl mx-auto">
          Une question, une suggestion ou un probleme ? Notre equipe est la pour vous aider.
          Remplissez le formulaire ci-dessous ou ecrivez-nous directement par email.
        </p>
      </div>

      <div class="grid md:grid-cols-3 gap-10">
        <!-- Contact Info -->
        <div class="md:col-span-1 space-y-6">
          <div class="bg-gray-50 rounded-xl p-6">
            <h3 class="font-bold text-primea-blue mb-4">Coordonnees</h3>
            <div class="space-y-4">
              <div>
                <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-1">Email</p>
                <a
                  href="mailto:contact@primea.ga"
                  class="text-primea-blue font-medium hover:text-primea-yellow transition-colors"
                >
                  contact@primea.ga
                </a>
              </div>
              <div>
                <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-1">Site web</p>
                <a
                  href="https://primea.ga"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="text-primea-blue font-medium hover:text-primea-yellow transition-colors"
                >
                  https://primea.ga
                </a>
              </div>
              <div>
                <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-1">Localisation</p>
                <p class="text-gray-700">Libreville, Gabon</p>
              </div>
            </div>
          </div>

          <div class="bg-gray-50 rounded-xl p-6">
            <h3 class="font-bold text-primea-blue mb-3">Delai de reponse</h3>
            <p class="text-gray-600 text-sm leading-relaxed">
              Nous nous engageons a repondre a toutes les demandes dans un delai
              de 24 a 48 heures ouvrables. Pour les urgences liees a un evenement
              imminent, veuillez le preciser dans l'objet de votre message.
            </p>
          </div>
        </div>

        <!-- Contact Form -->
        <div class="md:col-span-2">
          <form class="space-y-6" @submit.prevent="submitForm">
            <!-- Email -->
            <div>
              <label for="email" class="block text-sm font-semibold text-primea-blue mb-2">
                Adresse email <span class="text-red-500">*</span>
              </label>
              <input
                id="email"
                v-model="form.email"
                type="email"
                required
                placeholder="votre@email.com"
                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-primea-blue focus:ring-2 focus:ring-primea-blue/20 outline-none transition-all"
              />
            </div>

            <!-- Name -->
            <div>
              <label for="name" class="block text-sm font-semibold text-primea-blue mb-2">
                Nom complet
              </label>
              <input
                id="name"
                v-model="form.name"
                type="text"
                placeholder="Votre nom"
                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-primea-blue focus:ring-2 focus:ring-primea-blue/20 outline-none transition-all"
              />
            </div>

            <!-- Subject -->
            <div>
              <label for="subject" class="block text-sm font-semibold text-primea-blue mb-2">
                Objet <span class="text-red-500">*</span>
              </label>
              <select
                id="subject"
                v-model="form.subject"
                required
                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-primea-blue focus:ring-2 focus:ring-primea-blue/20 outline-none transition-all bg-white"
              >
                <option value="" disabled>Selectionnez un sujet</option>
                <option value="achat">Question sur un achat</option>
                <option value="paiement">Probleme de paiement</option>
                <option value="billet">Recuperation de billet</option>
                <option value="remboursement">Demande de remboursement</option>
                <option value="organisateur">Espace organisateur</option>
                <option value="technique">Probleme technique</option>
                <option value="partenariat">Partenariat / Collaboration</option>
                <option value="autre">Autre</option>
              </select>
            </div>

            <!-- Order Reference -->
            <div>
              <label for="reference" class="block text-sm font-semibold text-primea-blue mb-2">
                Reference de commande
                <span class="text-gray-400 font-normal">(si applicable)</span>
              </label>
              <input
                id="reference"
                v-model="form.reference"
                type="text"
                placeholder="Ex: ORD-XXXXXX"
                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-primea-blue focus:ring-2 focus:ring-primea-blue/20 outline-none transition-all"
              />
            </div>

            <!-- Message -->
            <div>
              <label for="message" class="block text-sm font-semibold text-primea-blue mb-2">
                Message <span class="text-red-500">*</span>
              </label>
              <textarea
                id="message"
                v-model="form.message"
                required
                rows="6"
                placeholder="Decrivez votre demande en detail..."
                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-primea-blue focus:ring-2 focus:ring-primea-blue/20 outline-none transition-all resize-vertical"
              ></textarea>
            </div>

            <!-- Submit -->
            <div>
              <button
                type="submit"
                :disabled="sending"
                class="w-full sm:w-auto bg-primea-blue text-white font-semibold px-10 py-3 rounded-lg hover:bg-primea-blue/90 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ sending ? 'Envoi en cours...' : 'Envoyer le message' }}
              </button>
            </div>
          </form>
        </div>
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
import Swal from 'sweetalert2';

export default {
  name: 'Contact',
  data() {
    return {
      sending: false,
      form: {
        email: '',
        name: '',
        subject: '',
        reference: '',
        message: '',
      },
    };
  },
  methods: {
    async submitForm() {
      this.sending = true;

      // Simulate a brief delay
      await new Promise((resolve) => setTimeout(resolve, 800));

      this.sending = false;

      await Swal.fire({
        icon: 'success',
        title: 'Message envoye !',
        text: 'Nous avons bien recu votre message et vous repondrons dans les plus brefs delais.',
        confirmButtonText: 'Fermer',
        confirmButtonColor: '#004B5E',
      });

      // Reset form
      this.form = {
        email: '',
        name: '',
        subject: '',
        reference: '',
        message: '',
      };
    },
  },
};
</script>

<style scoped>
</style>
