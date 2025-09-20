<template>
  <div class="organizer-profile min-h-screen" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%)">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-primea-blue font-primea">Mon Profil Organisateur</h1>
      <p class="text-gray-600 mt-1 font-primea">Gérez vos informations personnelles et celles de votre organisation</p>
    </div>

    <!-- Loading state -->
    <div v-if="loading" class="flex justify-center items-center h-64">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primea-blue"></div>
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <!-- Informations personnelles -->
      <div class="bg-white rounded-primea shadow-primea p-6">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-semibold text-primea-blue font-primea">Informations Personnelles</h2>
          <button 
            @click="toggleEditPersonal"
            class="text-primea-blue hover:text-primea-yellow transition-colors"
          >
            <PencilIcon class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="updatePersonalInfo" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 font-primea mb-1">Nom complet</label>
            <input 
              v-model="personalForm.name"
              type="text" 
              :disabled="!editPersonal"
              class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue disabled:bg-gray-50 font-primea"
              placeholder="Votre nom complet"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 font-primea mb-1">Email</label>
            <input 
              v-model="personalForm.email"
              type="email" 
              :disabled="!editPersonal"
              class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue disabled:bg-gray-50 font-primea"
              placeholder="votre@email.com"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 font-primea mb-1">Téléphone</label>
            <input 
              v-model="personalForm.phone"
              type="tel" 
              :disabled="!editPersonal"
              class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue disabled:bg-gray-50 font-primea"
              placeholder="+225 XX XX XX XX XX"
            />
          </div>

          <div v-if="editPersonal" class="flex space-x-3 pt-4">
            <button 
              type="submit" 
              :disabled="updatingPersonal"
              class="bg-primea-blue text-white px-4 py-2 rounded-primea hover:bg-primea-yellow hover:text-primea-blue font-semibold font-primea transition-all duration-200 disabled:opacity-50"
            >
              {{ updatingPersonal ? 'Enregistrement...' : 'Enregistrer' }}
            </button>
            <button 
              type="button"
              @click="cancelEditPersonal"
              class="border border-gray-300 text-gray-700 px-4 py-2 rounded-primea hover:bg-gray-50 font-primea transition-colors"
            >
              Annuler
            </button>
          </div>
        </form>
      </div>

      <!-- Informations de l'organisation -->
      <div class="bg-white rounded-primea shadow-primea p-6">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-semibold text-primea-blue font-primea">Mon Organisation</h2>
          <button 
            @click="toggleEditOrganization"
            class="text-primea-blue hover:text-primea-yellow transition-colors"
          >
            <PencilIcon class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="updateOrganizationInfo" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 font-primea mb-1">Nom de l'organisation</label>
            <input 
              v-model="organizationForm.name"
              type="text" 
              :disabled="!editOrganization"
              class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue disabled:bg-gray-50 font-primea"
              placeholder="Nom de votre organisation"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 font-primea mb-1">Description</label>
            <textarea 
              v-model="organizationForm.description"
              :disabled="!editOrganization"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue disabled:bg-gray-50 font-primea resize-none"
              placeholder="Décrivez votre organisation..."
            ></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 font-primea mb-1">Site web</label>
            <input 
              v-model="organizationForm.website_url"
              type="url" 
              :disabled="!editOrganization"
              class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue disabled:bg-gray-50 font-primea"
              placeholder="https://votresite.com"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 font-primea mb-1">Email de contact</label>
            <input 
              v-model="organizationForm.contact_email"
              type="email" 
              :disabled="!editOrganization"
              class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue disabled:bg-gray-50 font-primea"
              placeholder="contact@organisation.com"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 font-primea mb-1">Téléphone de contact</label>
            <input 
              v-model="organizationForm.contact_phone"
              type="tel" 
              :disabled="!editOrganization"
              class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue disabled:bg-gray-50 font-primea"
              placeholder="+225 XX XX XX XX XX"
            />
          </div>

          <div v-if="editOrganization" class="flex space-x-3 pt-4">
            <button 
              type="submit" 
              :disabled="updatingOrganization"
              class="bg-primea-blue text-white px-4 py-2 rounded-primea hover:bg-primea-yellow hover:text-primea-blue font-semibold font-primea transition-all duration-200 disabled:opacity-50"
            >
              {{ updatingOrganization ? 'Enregistrement...' : 'Enregistrer' }}
            </button>
            <button 
              type="button"
              @click="cancelEditOrganization"
              class="border border-gray-300 text-gray-700 px-4 py-2 rounded-primea hover:bg-gray-50 font-primea transition-colors"
            >
              Annuler
            </button>
          </div>
        </form>
      </div>

      <!-- Changement de mot de passe -->
      <div class="bg-white rounded-primea shadow-primea p-6">
        <h2 class="text-xl font-semibold text-primea-blue font-primea mb-6">Sécurité</h2>
        
        <form @submit.prevent="updatePassword" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 font-primea mb-1">Mot de passe actuel</label>
            <input 
              v-model="passwordForm.current_password"
              type="password" 
              class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea"
              placeholder="Votre mot de passe actuel"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 font-primea mb-1">Nouveau mot de passe</label>
            <input 
              v-model="passwordForm.password"
              type="password" 
              class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea"
              placeholder="Nouveau mot de passe"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 font-primea mb-1">Confirmer le mot de passe</label>
            <input 
              v-model="passwordForm.password_confirmation"
              type="password" 
              class="w-full px-3 py-2 border border-gray-300 rounded-primea focus:ring-2 focus:ring-primea-blue focus:border-primea-blue font-primea"
              placeholder="Confirmer le nouveau mot de passe"
            />
          </div>

          <button 
            type="submit" 
            :disabled="updatingPassword"
            class="bg-primea-blue text-white px-4 py-2 rounded-primea hover:bg-primea-yellow hover:text-primea-blue font-semibold font-primea transition-all duration-200 disabled:opacity-50"
          >
            {{ updatingPassword ? 'Mise à jour...' : 'Changer le mot de passe' }}
          </button>
        </form>
      </div>

      <!-- Statistiques et statut -->
      <div class="bg-white rounded-primea shadow-primea p-6">
        <h2 class="text-xl font-semibold text-primea-blue font-primea mb-6">Statut du Compte</h2>
        
        <div class="space-y-4">
          <div class="flex items-center justify-between p-3 bg-green-50 rounded-primea">
            <div class="flex items-center space-x-3">
              <CheckCircleIcon class="w-5 h-5 text-green-600" />
              <span class="text-sm font-medium text-green-800 font-primea">Compte vérifié</span>
            </div>
            <span class="text-xs text-green-600 font-primea">{{ formatDate(user?.email_verified_at) }}</span>
          </div>

          <div v-if="organization?.verified_at" class="flex items-center justify-between p-3 bg-blue-50 rounded-primea">
            <div class="flex items-center space-x-3">
              <ShieldCheckIcon class="w-5 h-5 text-blue-600" />
              <span class="text-sm font-medium text-blue-800 font-primea">Organisation certifiée</span>
            </div>
            <span class="text-xs text-blue-600 font-primea">{{ formatDate(organization.verified_at) }}</span>
          </div>

          <div class="grid grid-cols-2 gap-4 pt-4">
            <div class="text-center p-3 border rounded-primea">
              <p class="text-2xl font-bold text-primea-blue font-primea">{{ stats.events || 0 }}</p>
              <p class="text-sm text-gray-600 font-primea">Événements créés</p>
            </div>
            <div class="text-center p-3 border rounded-primea">
              <p class="text-2xl font-bold text-primea-yellow font-primea">{{ stats.tickets || 0 }}</p>
              <p class="text-sm text-gray-600 font-primea">Billets vendus</p>
            </div>
          </div>
        </div>
      </div>
    </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { 
  PencilIcon, 
  CheckCircleIcon, 
  ShieldCheckIcon 
} from '@heroicons/vue/24/outline';

const authStore = useAuthStore();

// État réactif
const loading = ref(false);
const editPersonal = ref(false);
const editOrganization = ref(false);
const updatingPersonal = ref(false);
const updatingOrganization = ref(false);
const updatingPassword = ref(false);

// Données utilisateur et organisation
const user = ref(null);
const organization = ref(null);
const stats = ref({ events: 0, tickets: 0 });

// Formulaires
const personalForm = reactive({
  name: '',
  email: '',
  phone: ''
});

const organizationForm = reactive({
  name: '',
  description: '',
  website_url: '',
  contact_email: '',
  contact_phone: ''
});

const passwordForm = reactive({
  current_password: '',
  password: '',
  password_confirmation: ''
});

// Copies pour l'annulation
const personalFormBackup = reactive({});
const organizationFormBackup = reactive({});

// Méthodes
const toggleEditPersonal = () => {
  if (!editPersonal.value) {
    // Sauvegarder avant édition
    Object.assign(personalFormBackup, personalForm);
  }
  editPersonal.value = !editPersonal.value;
};

const toggleEditOrganization = () => {
  if (!editOrganization.value) {
    // Sauvegarder avant édition
    Object.assign(organizationFormBackup, organizationForm);
  }
  editOrganization.value = !editOrganization.value;
};

const cancelEditPersonal = () => {
  Object.assign(personalForm, personalFormBackup);
  editPersonal.value = false;
};

const cancelEditOrganization = () => {
  Object.assign(organizationForm, organizationFormBackup);
  editOrganization.value = false;
};

const updatePersonalInfo = async () => {
  updatingPersonal.value = true;
  try {
    // Appel API pour mettre à jour les infos personnelles
    console.log('Mise à jour des informations personnelles:', personalForm);
    // Simulation d'appel API
    await new Promise(resolve => setTimeout(resolve, 1000));
    editPersonal.value = false;
  } catch (error) {
    console.error('Erreur lors de la mise à jour:', error);
  } finally {
    updatingPersonal.value = false;
  }
};

const updateOrganizationInfo = async () => {
  updatingOrganization.value = true;
  try {
    // Appel API pour mettre à jour les infos de l'organisation
    console.log('Mise à jour de l\'organisation:', organizationForm);
    // Simulation d'appel API
    await new Promise(resolve => setTimeout(resolve, 1000));
    editOrganization.value = false;
  } catch (error) {
    console.error('Erreur lors de la mise à jour:', error);
  } finally {
    updatingOrganization.value = false;
  }
};

const updatePassword = async () => {
  if (passwordForm.password !== passwordForm.password_confirmation) {
    alert('Les mots de passe ne correspondent pas');
    return;
  }

  updatingPassword.value = true;
  try {
    // Appel API pour changer le mot de passe
    console.log('Changement de mot de passe');
    // Simulation d'appel API
    await new Promise(resolve => setTimeout(resolve, 1000));
    
    // Réinitialiser le formulaire
    Object.assign(passwordForm, {
      current_password: '',
      password: '',
      password_confirmation: ''
    });
  } catch (error) {
    console.error('Erreur lors du changement de mot de passe:', error);
  } finally {
    updatingPassword.value = false;
  }
};

const formatDate = (dateString) => {
  if (!dateString) return 'Non disponible';
  return new Date(dateString).toLocaleDateString('fr-FR');
};

const loadProfileData = async () => {
  loading.value = true;
  try {
    // Charger les données depuis l'API
    // Simulation avec des données d'exemple
    user.value = {
      name: 'Marie Nzougou',
      email: 'marie@example.com',
      phone: '+225 07 12 34 56 78',
      email_verified_at: '2024-01-15T10:30:00Z'
    };

    organization.value = {
      name: 'Mon Organisation',
      description: 'Organisation spécialisée dans l\'événementiel',
      website_url: 'https://monorganisation.com',
      contact_email: 'contact@monorganisation.com',
      contact_phone: '+225 07 12 34 56 79',
      verified_at: '2024-01-20T14:00:00Z'
    };

    stats.value = {
      events: 12,
      tickets: 345
    };

    // Remplir les formulaires
    Object.assign(personalForm, {
      name: user.value.name,
      email: user.value.email,
      phone: user.value.phone
    });

    Object.assign(organizationForm, {
      name: organization.value.name,
      description: organization.value.description,
      website_url: organization.value.website_url,
      contact_email: organization.value.contact_email,
      contact_phone: organization.value.contact_phone
    });

  } catch (error) {
    console.error('Erreur lors du chargement du profil:', error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  loadProfileData();
});
</script>

<style scoped>
/* Police Primea */
.font-primea {
  font-family: 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Couleurs Primea */
.text-primea-blue {
  color: #272d63;
}

.text-primea-yellow {
  color: #fab511;
}

.bg-primea-blue {
  background-color: #272d63;
}

.bg-primea-yellow {
  background-color: #fab511;
}

.hover\:text-primea-yellow:hover {
  color: #fab511;
}

.hover\:bg-primea-yellow:hover {
  background-color: #fab511;
}

.hover\:text-primea-blue:hover {
  color: #272d63;
}

/* Coins arrondis Primea */
.rounded-primea {
  border-radius: 12px;
}

/* Ombres Primea */
.shadow-primea {
  box-shadow: 0 2px 15px rgba(39, 45, 99, 0.08);
}
</style>