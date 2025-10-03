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

    <div v-else class="space-y-8">
      <!-- Photos et Avatar -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Photo de profil utilisateur -->
        <div class="bg-white rounded-primea shadow-primea p-6">
          <h2 class="text-xl font-semibold text-primea-blue font-primea mb-6">Photo de Profil</h2>
          
          <div class="flex flex-col items-center space-y-4">
            <div class="relative">
              <div class="w-32 h-32 rounded-full overflow-hidden bg-gray-100 border-4 border-white shadow-lg">
                <img 
                  v-if="user?.avatar_url" 
                  :src="user.avatar_url" 
                  :alt="user?.name"
                  class="w-full h-full object-cover"
                />
                <div v-else class="w-full h-full flex items-center justify-center bg-primea-blue text-white text-3xl font-bold font-primea">
                  {{ user?.name?.charAt(0)?.toUpperCase() || 'U' }}
                </div>
              </div>
              <button 
                @click="$refs.avatarInput.click()"
                :disabled="uploadingAvatar"
                class="absolute bottom-0 right-0 bg-primea-blue text-white p-2 rounded-full hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200 disabled:opacity-50"
              >
                <CameraIcon class="w-4 h-4" />
              </button>
            </div>
            
            <div class="text-center">
              <p class="text-sm text-gray-600 font-primea">{{ user?.name || 'Utilisateur' }}</p>
              <p class="text-xs text-gray-500 font-primea">Cliquez sur l'icône pour changer</p>
            </div>
            
            <input 
              ref="avatarInput"
              type="file" 
              accept="image/*" 
              @change="handleAvatarUpload" 
              class="hidden"
            />
            
            <div v-if="uploadingAvatar" class="text-center">
              <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primea-blue mx-auto"></div>
              <p class="text-sm text-gray-600 font-primea mt-2">Upload en cours...</p>
            </div>
          </div>
        </div>

        <!-- Logo de l'organisation -->
        <div class="bg-white rounded-primea shadow-primea p-6">
          <h2 class="text-xl font-semibold text-primea-blue font-primea mb-6">Logo de l'Organisation</h2>
          
          <div class="flex flex-col items-center space-y-4">
            <div class="relative">
              <div class="w-32 h-32 rounded-primea overflow-hidden bg-gray-100 border-2 border-dashed border-gray-300 flex items-center justify-center">
                <img 
                  v-if="organization?.logo_url" 
                  :src="organization.logo_url" 
                  :alt="organization?.name"
                  class="w-full h-full object-contain"
                />
                <div v-else class="text-center">
                  <PhotoIcon class="w-8 h-8 text-gray-400 mx-auto mb-2" />
                  <p class="text-xs text-gray-500 font-primea">Aucun logo</p>
                </div>
              </div>
              <button 
                @click="$refs.logoInput.click()"
                :disabled="uploadingLogo"
                class="absolute bottom-0 right-0 bg-primea-blue text-white p-2 rounded-full hover:bg-primea-yellow hover:text-primea-blue transition-all duration-200 disabled:opacity-50"
              >
                <CameraIcon class="w-4 h-4" />
              </button>
            </div>
            
            <div class="text-center">
              <p class="text-sm text-gray-600 font-primea">{{ organization?.name || 'Organisation' }}</p>
              <p class="text-xs text-gray-500 font-primea">Logo de votre organisation</p>
            </div>
            
            <input 
              ref="logoInput"
              type="file" 
              accept="image/*" 
              @change="handleLogoUpload" 
              class="hidden"
            />
            
            <div v-if="uploadingLogo" class="text-center">
              <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-primea-blue mx-auto"></div>
              <p class="text-sm text-gray-600 font-primea mt-2">Upload en cours...</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Informations -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
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
            <PhoneInput
              v-model="personalForm.phone"
              id="personal-phone"
              :disabled="!editPersonal"
              :input-class="!editPersonal ? 'disabled:bg-gray-50' : ''"
              placeholder="Numéro de téléphone"
              default-country="GA"
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
            <PhoneInput
              v-model="organizationForm.contact_phone"
              id="organization-phone"
              :disabled="!editOrganization"
              :input-class="!editOrganization ? 'disabled:bg-gray-50' : ''"
              placeholder="Numéro de téléphone de contact"
              default-country="GA"
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
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { organizerService } from '../../services/api';
import Swal from 'sweetalert2';
import PhoneInput from '../../components/PhoneInput.vue';
import { 
  PencilIcon, 
  CheckCircleIcon, 
  ShieldCheckIcon,
  PhotoIcon,
  CameraIcon
} from '@heroicons/vue/24/outline';

const authStore = useAuthStore();

// État réactif
const loading = ref(false);
const editPersonal = ref(false);
const editOrganization = ref(false);
const updatingPersonal = ref(false);
const updatingOrganization = ref(false);
const updatingPassword = ref(false);
const uploadingAvatar = ref(false);
const uploadingLogo = ref(false);

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
    const response = await organizerService.updateProfile({
      type: 'personal',
      ...personalForm
    });
    
    if (response.data.success) {
      Swal.fire({
        title: 'Succès !',
        text: 'Informations personnelles mises à jour',
        icon: 'success',
        confirmButtonColor: '#272d63'
      });
      editPersonal.value = false;
      
      // Mettre à jour les données locales
      if (response.data.data.user) {
        user.value = { ...user.value, ...response.data.data.user };
      }
    }
  } catch (error) {
    console.error('Erreur lors de la mise à jour:', error);
    Swal.fire({
      title: 'Erreur',
      text: 'Impossible de mettre à jour les informations',
      icon: 'error',
      confirmButtonColor: '#272d63'
    });
  } finally {
    updatingPersonal.value = false;
  }
};

const updateOrganizationInfo = async () => {
  updatingOrganization.value = true;
  try {
    const response = await organizerService.updateProfile({
      type: 'organization',
      ...organizationForm
    });
    
    if (response.data.success) {
      Swal.fire({
        title: 'Succès !',
        text: 'Informations de l\'organisation mises à jour',
        icon: 'success',
        confirmButtonColor: '#272d63'
      });
      editOrganization.value = false;
      
      // Mettre à jour les données locales
      if (response.data.data.organization) {
        organization.value = { ...organization.value, ...response.data.data.organization };
      }
    }
  } catch (error) {
    console.error('Erreur lors de la mise à jour:', error);
    Swal.fire({
      title: 'Erreur',
      text: 'Impossible de mettre à jour les informations de l\'organisation',
      icon: 'error',
      confirmButtonColor: '#272d63'
    });
  } finally {
    updatingOrganization.value = false;
  }
};

const updatePassword = async () => {
  if (passwordForm.password !== passwordForm.password_confirmation) {
    Swal.fire({
      title: 'Erreur',
      text: 'Les mots de passe ne correspondent pas',
      icon: 'error',
      confirmButtonColor: '#272d63'
    });
    return;
  }

  updatingPassword.value = true;
  try {
    const response = await organizerService.updateProfile({
      type: 'password',
      ...passwordForm
    });
    
    if (response.data.success) {
      Swal.fire({
        title: 'Succès !',
        text: 'Mot de passe mis à jour avec succès',
        icon: 'success',
        confirmButtonColor: '#272d63'
      });
      
      // Réinitialiser le formulaire
      Object.assign(passwordForm, {
        current_password: '',
        password: '',
        password_confirmation: ''
      });
    }
  } catch (error) {
    console.error('Erreur lors du changement de mot de passe:', error);
    Swal.fire({
      title: 'Erreur',
      text: 'Impossible de changer le mot de passe',
      icon: 'error',
      confirmButtonColor: '#272d63'
    });
  } finally {
    updatingPassword.value = false;
  }
};

// Gestion des uploads d'images
const handleAvatarUpload = async (event) => {
  const file = event.target.files[0];
  if (!file) return;
  
  // Validation du fichier
  if (!file.type.startsWith('image/')) {
    Swal.fire({
      title: 'Erreur',
      text: 'Veuillez sélectionner un fichier image',
      icon: 'error',
      confirmButtonColor: '#272d63'
    });
    return;
  }
  
  if (file.size > 5 * 1024 * 1024) { // 5MB
    Swal.fire({
      title: 'Erreur',
      text: 'L\'image ne doit pas dépasser 5MB',
      icon: 'error',
      confirmButtonColor: '#272d63'
    });
    return;
  }
  
  uploadingAvatar.value = true;
  try {
    const formData = new FormData();
    formData.append('avatar', file);
    formData.append('type', 'user');
    
    const response = await organizerService.uploadAvatar(formData);
    
    if (response.data.success) {
      // Mettre à jour l'avatar dans les données locales
      user.value.avatar_url = response.data.data.avatar_url;
      
      // Mettre à jour dans l'authStore pour que le header se mette à jour
      authStore.updateUser({ avatar_url: response.data.data.avatar_url });
      
      // Forcer le rechargement de l'image
      const avatarElement = document.querySelector(`img[alt="${user.value?.name}"]`);
      if (avatarElement) {
        avatarElement.src = response.data.data.avatar_url + '?t=' + new Date().getTime();
      }
      
      Swal.fire({
        title: 'Succès !',
        text: 'Photo de profil mise à jour',
        icon: 'success',
        confirmButtonColor: '#272d63'
      });
      
      // Recharger les données du profil pour s'assurer de la synchronisation
      setTimeout(() => {
        loadProfileData();
      }, 1000);
    }
  } catch (error) {
    console.error('Erreur upload avatar:', error);
    Swal.fire({
      title: 'Erreur',
      text: 'Impossible de télécharger la photo',
      icon: 'error',
      confirmButtonColor: '#272d63'
    });
  } finally {
    uploadingAvatar.value = false;
    // Réinitialiser l'input file
    event.target.value = '';
  }
};

const handleLogoUpload = async (event) => {
  const file = event.target.files[0];
  if (!file) return;
  
  // Validation du fichier
  if (!file.type.startsWith('image/')) {
    Swal.fire({
      title: 'Erreur',
      text: 'Veuillez sélectionner un fichier image',
      icon: 'error',
      confirmButtonColor: '#272d63'
    });
    return;
  }
  
  if (file.size > 5 * 1024 * 1024) { // 5MB
    Swal.fire({
      title: 'Erreur',
      text: 'L\'image ne doit pas dépasser 5MB',
      icon: 'error',
      confirmButtonColor: '#272d63'
    });
    return;
  }
  
  uploadingLogo.value = true;
  try {
    const formData = new FormData();
    formData.append('logo', file);
    formData.append('type', 'organization');
    
    const response = await organizerService.uploadAvatar(formData);
    
    if (response.data.success) {
      // Mettre à jour le logo dans les données locales
      organization.value.logo_url = response.data.data.logo_url;
      
      // Forcer le rechargement de l'image
      const logoElement = document.querySelector(`img[alt="${organization.value?.name}"]`);
      if (logoElement) {
        logoElement.src = response.data.data.logo_url + '?t=' + new Date().getTime();
      }
      
      Swal.fire({
        title: 'Succès !',
        text: 'Logo de l\'organisation mis à jour',
        icon: 'success',
        confirmButtonColor: '#272d63'
      });
      
      // Recharger les données du profil pour s'assurer de la synchronisation
      setTimeout(() => {
        loadProfileData();
      }, 1000);
    }
  } catch (error) {
    console.error('Erreur upload logo:', error);
    Swal.fire({
      title: 'Erreur',
      text: 'Impossible de télécharger le logo',
      icon: 'error',
      confirmButtonColor: '#272d63'
    });
  } finally {
    uploadingLogo.value = false;
    // Réinitialiser l'input file
    event.target.value = '';
  }
};

const formatDate = (dateString) => {
  if (!dateString) return 'Non disponible';
  return new Date(dateString).toLocaleDateString('fr-FR');
};

const loadProfileData = async () => {
  loading.value = true;
  try {
    const response = await organizerService.getProfile();
    console.log('Profile API Response:', response.data);
    
    if (response.data.success) {
      const data = response.data.data;
      
      // Charger les données utilisateur
      if (data.user) {
        user.value = data.user;
        Object.assign(personalForm, {
          name: data.user.name || '',
          email: data.user.email || '',
          phone: data.user.phone || ''
        });
        
        // Mettre à jour l'authStore avec les nouvelles données incluant avatar_url
        authStore.updateUser({
          name: data.user.name,
          email: data.user.email,
          phone: data.user.phone,
          avatar_url: data.user.avatar_url
        });
      }
      
      // Charger les données organisation
      if (data.organization) {
        organization.value = data.organization;
        Object.assign(organizationForm, {
          name: data.organization.name || '',
          description: data.organization.description || '',
          website_url: data.organization.website_url || '',
          contact_email: data.organization.contact_email || '',
          contact_phone: data.organization.contact_phone || ''
        });
      }
      
      // Charger les statistiques
      if (data.stats) {
        stats.value = {
          events: data.stats.events || 0,
          tickets: data.stats.tickets || 0
        };
      }
    } else {
      console.error('Profile API Error:', response.data.message);
      Swal.fire({
        title: 'Erreur',
        text: 'Impossible de charger le profil',
        icon: 'error',
        confirmButtonColor: '#272d63'
      });
    }
  } catch (error) {
    console.error('Erreur lors du chargement du profil:', error);
    Swal.fire({
      title: 'Erreur',
      text: 'Impossible de charger le profil',
      icon: 'error',
      confirmButtonColor: '#272d63'
    });
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

/* PhoneInput disabled state */
:deep(.phone-input:disabled) {
  background-color: #f9fafb;
}

:deep(.country-selector:has(+ .phone-input:disabled)) {
  pointer-events: none;
  opacity: 0.6;
}
</style>