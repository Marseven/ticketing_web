<template>
  <div class="image-upload-component">
    <!-- Affichage de l'image actuelle -->
    <div v-if="currentImageUrl" class="current-image mb-4">
      <img :src="currentImageUrl" :alt="altText" :class="imageClasses" />
      <button type="button" @click="removeImage" class="remove-btn" v-if="!disabled">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
        </svg>
      </button>
    </div>

    <!-- Options de sélection -->
    <div class="upload-options" v-if="!disabled">
      <div class="option-tabs mb-4">
        <button 
          type="button"
          @click="activeTab = 'upload'" 
          :class="['tab-btn', activeTab === 'upload' ? 'active' : '']">
          Uploader une image
        </button>
        <button 
          type="button"
          @click="activeTab = 'url'" 
          :class="['tab-btn', activeTab === 'url' ? 'active' : '']">
          Utiliser une URL
        </button>
      </div>

      <!-- Onglet Upload -->
      <div v-if="activeTab === 'upload'" class="upload-tab">
        <div class="upload-area" :class="{ 'dragover': isDragOver }" 
             @drop="handleDrop" @dragover.prevent @dragenter.prevent 
             @dragleave="isDragOver = false" @dragover="isDragOver = true">
          <input 
            ref="fileInput" 
            type="file" 
            accept="image/*" 
            @change="handleFileSelect" 
            style="display: none"
          />
          
          <div class="upload-content" @click="$refs.fileInput.click()">
            <svg class="upload-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
            </svg>
            <p class="upload-text">Cliquez pour sélectionner ou glissez-déposez une image</p>
            <p class="upload-subtext">PNG, JPG, GIF jusqu'à 5MB</p>
          </div>
        </div>
        
        <!-- Prévisualisation du fichier sélectionné -->
        <div v-if="selectedFile" class="file-preview mt-4">
          <img :src="previewUrl" alt="Prévisualisation" class="preview-image" />
          <div class="file-info">
            <p class="file-name">{{ selectedFile.name }}</p>
            <p class="file-size">{{ formatFileSize(selectedFile.size) }}</p>
          </div>
          <button type="button" @click="uploadFile" :disabled="uploading" class="upload-btn">
            <span v-if="uploading">Upload en cours...</span>
            <span v-else>Confirmer l'upload</span>
          </button>
        </div>
      </div>

      <!-- Onglet URL -->
      <div v-if="activeTab === 'url'" class="url-tab">
        <div class="url-input-group">
          <input 
            v-model="imageUrl" 
            type="url" 
            placeholder="https://exemple.com/image.jpg"
            class="url-input"
            @blur="validateImageUrl"
          />
          <button type="button" @click="validateImageUrl" :disabled="validating" class="validate-btn">
            <span v-if="validating">Validation...</span>
            <span v-else>Valider</span>
          </button>
        </div>
        
        <!-- Prévisualisation de l'URL -->
        <div v-if="urlPreview" class="url-preview mt-4">
          <img :src="imageUrl" alt="Prévisualisation URL" class="preview-image" @error="urlError = true" />
          <button type="button" @click="confirmUrl" class="confirm-btn">Utiliser cette image</button>
        </div>
        
        <!-- Erreur URL -->
        <div v-if="urlError" class="error-message mt-2">
          L'URL ne pointe pas vers une image valide ou accessible.
        </div>
      </div>
    </div>

    <!-- Messages d'erreur -->
    <div v-if="error" class="error-message mt-2">
      {{ error }}
    </div>

    <!-- Loading global -->
    <div v-if="loading" class="loading-overlay">
      <div class="loading-spinner"></div>
    </div>
  </div>
</template>

<script>
import { ref, computed, watch } from 'vue'

export default {
  name: 'ImageUpload',
  props: {
    modelValue: {
      type: Object,
      default: () => ({})
    },
    entityType: {
      type: String,
      required: true,
      validator: value => ['events', 'venues', 'users', 'organizers'].includes(value)
    },
    size: {
      type: String,
      default: 'medium'
    },
    disabled: {
      type: Boolean,
      default: false
    },
    altText: {
      type: String,
      default: 'Image'
    }
  },
  emits: ['update:modelValue', 'change'],
  setup(props, { emit }) {
    const activeTab = ref('upload')
    const selectedFile = ref(null)
    const previewUrl = ref('')
    const imageUrl = ref('')
    const urlPreview = ref(false)
    const urlError = ref(false)
    const isDragOver = ref(false)
    const uploading = ref(false)
    const validating = ref(false)
    const loading = ref(false)
    const error = ref('')

    // Image actuelle
    const currentImageUrl = computed(() => {
      if (props.modelValue?.url) return props.modelValue.url
      if (props.modelValue?.filename) {
        return `/storage/images/${props.entityType}/${props.size}_${props.modelValue.filename}`
      }
      return null
    })

    // Classes CSS pour l'image
    const imageClasses = computed(() => {
      const baseClasses = 'object-cover rounded-lg shadow-sm'
      const sizeClasses = {
        thumbnail: 'w-20 h-20',
        medium: 'w-32 h-32',
        large: 'w-48 h-48'
      }
      return `${baseClasses} ${sizeClasses[props.size] || sizeClasses.medium}`
    })

    // Gestion du drop de fichier
    const handleDrop = (e) => {
      e.preventDefault()
      isDragOver.value = false
      const files = e.dataTransfer.files
      if (files.length > 0) {
        handleFile(files[0])
      }
    }

    // Sélection de fichier
    const handleFileSelect = (e) => {
      const files = e.target.files
      if (files.length > 0) {
        handleFile(files[0])
      }
    }

    // Traitement du fichier sélectionné
    const handleFile = (file) => {
      error.value = ''
      
      // Validation du type de fichier
      if (!file.type.startsWith('image/')) {
        error.value = 'Veuillez sélectionner un fichier image valide.'
        return
      }
      
      // Validation de la taille (5MB max)
      if (file.size > 5 * 1024 * 1024) {
        error.value = 'L\'image ne doit pas dépasser 5MB.'
        return
      }
      
      selectedFile.value = file
      
      // Créer une prévisualisation
      const reader = new FileReader()
      reader.onload = (e) => {
        previewUrl.value = e.target.result
      }
      reader.readAsDataURL(file)
    }

    // Upload du fichier
    const uploadFile = async () => {
      if (!selectedFile.value) return
      
      uploading.value = true
      loading.value = true
      error.value = ''
      
      try {
        const formData = new FormData()
        formData.append('image', selectedFile.value)
        formData.append('type', props.entityType)
        
        const response = await fetch('/api/v1/images/upload', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: formData
        })
        
        const data = await response.json()
        
        if (data.success) {
          const imageData = {
            filename: data.data.filename,
            urls: data.data.urls
          }
          emit('update:modelValue', imageData)
          emit('change', imageData)
          
          // Reset
          selectedFile.value = null
          previewUrl.value = ''
        } else {
          error.value = data.message || 'Erreur lors de l\'upload'
        }
      } catch (err) {
        error.value = 'Erreur de connexion lors de l\'upload'
      } finally {
        uploading.value = false
        loading.value = false
      }
    }

    // Validation de l'URL
    const validateImageUrl = async () => {
      if (!imageUrl.value) return
      
      validating.value = true
      loading.value = true
      error.value = ''
      urlError.value = false
      
      try {
        const response = await fetch('/api/v1/images/validate-url', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            url: imageUrl.value,
            type: props.entityType
          })
        })
        
        const data = await response.json()
        
        if (data.success) {
          urlPreview.value = true
        } else {
          urlError.value = true
          error.value = data.message
        }
      } catch (err) {
        urlError.value = true
        error.value = 'Erreur lors de la validation de l\'URL'
      } finally {
        validating.value = false
        loading.value = false
      }
    }

    // Confirmer l'URL
    const confirmUrl = () => {
      const imageData = {
        url: imageUrl.value
      }
      emit('update:modelValue', imageData)
      emit('change', imageData)
      
      // Reset
      imageUrl.value = ''
      urlPreview.value = false
    }

    // Supprimer l'image
    const removeImage = () => {
      emit('update:modelValue', {})
      emit('change', {})
    }

    // Formater la taille du fichier
    const formatFileSize = (bytes) => {
      if (bytes === 0) return '0 Bytes'
      const k = 1024
      const sizes = ['Bytes', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
    }

    // Watch pour reset les erreurs
    watch([activeTab], () => {
      error.value = ''
      urlError.value = false
    })

    return {
      activeTab,
      selectedFile,
      previewUrl,
      imageUrl,
      urlPreview,
      urlError,
      isDragOver,
      uploading,
      validating,
      loading,
      error,
      currentImageUrl,
      imageClasses,
      handleDrop,
      handleFileSelect,
      uploadFile,
      validateImageUrl,
      confirmUrl,
      removeImage,
      formatFileSize
    }
  }
}
</script>

<style scoped>
.image-upload-component {
  position: relative;
}

.current-image {
  position: relative;
  display: inline-block;
}

.remove-btn {
  position: absolute;
  top: -8px;
  right: -8px;
  background: #ef4444;
  color: white;
  border: none;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background-color 0.2s;
}

.remove-btn:hover {
  background: #dc2626;
}

.option-tabs {
  display: flex;
  border-bottom: 2px solid #e5e7eb;
}

.tab-btn {
  padding: 8px 16px;
  border: none;
  background: none;
  cursor: pointer;
  border-bottom: 2px solid transparent;
  transition: all 0.2s;
}

.tab-btn.active {
  border-bottom-color: #3b82f6;
  color: #3b82f6;
}

.upload-area {
  border: 2px dashed #d1d5db;
  border-radius: 8px;
  padding: 32px;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s;
}

.upload-area:hover,
.upload-area.dragover {
  border-color: #3b82f6;
  background-color: #f8fafc;
}

.upload-icon {
  width: 48px;
  height: 48px;
  margin: 0 auto 16px;
  color: #6b7280;
}

.upload-text {
  font-size: 16px;
  color: #374151;
  margin-bottom: 4px;
}

.upload-subtext {
  font-size: 14px;
  color: #6b7280;
}

.file-preview,
.url-preview {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
}

.preview-image {
  width: 64px;
  height: 64px;
  object-fit: cover;
  border-radius: 8px;
}

.file-info {
  flex: 1;
}

.file-name {
  font-weight: 500;
  margin-bottom: 4px;
}

.file-size {
  font-size: 14px;
  color: #6b7280;
}

.url-input-group {
  display: flex;
  gap: 8px;
}

.url-input {
  flex: 1;
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.upload-btn,
.validate-btn,
.confirm-btn {
  padding: 8px 16px;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  transition: background-color 0.2s;
}

.upload-btn:hover,
.validate-btn:hover,
.confirm-btn:hover {
  background: #2563eb;
}

.upload-btn:disabled,
.validate-btn:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}

.error-message {
  color: #ef4444;
  font-size: 14px;
  margin-top: 8px;
}

.loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
}

.loading-spinner {
  width: 32px;
  height: 32px;
  border: 3px solid #e5e7eb;
  border-top: 3px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>