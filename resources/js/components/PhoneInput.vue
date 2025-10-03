<template>
  <div class="phone-input-wrapper">
    <div class="phone-input-container">
      <!-- Sélecteur de pays -->
      <div class="country-selector" @click="!disabled && toggleDropdown" :class="{ 'disabled': disabled }">
        <div class="selected-country">
          <img 
            :src="`https://flagcdn.com/w40/${selectedCountry.code.toLowerCase()}.png`" 
            :alt="selectedCountry.name"
            class="country-flag"
          />
          <span class="country-code">{{ selectedCountry.dial }}</span>
          <svg class="dropdown-arrow" :class="{ 'rotate': showDropdown }" width="12" height="8" viewBox="0 0 12 8" fill="none">
            <path d="M1 1.5L6 6.5L11 1.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </div>
      </div>

      <!-- Champ de saisie -->
      <input
        type="tel"
        :id="id"
        v-model="phoneNumber"
        @input="handleInput"
        @blur="handleBlur"
        :placeholder="placeholder"
        :required="required"
        :disabled="disabled"
        class="phone-input"
        :class="inputClass"
      />

      <!-- Dropdown des pays -->
      <transition name="dropdown-fade">
        <div v-if="showDropdown" class="country-dropdown" ref="dropdown">
          <div class="country-search">
            <input
              type="text"
              v-model="searchQuery"
              placeholder="Rechercher un pays..."
              class="search-input"
              @click.stop
            />
          </div>
          <div class="country-list">
            <div
              v-for="country in filteredCountries"
              :key="country.code"
              @click="selectCountry(country)"
              class="country-item"
              :class="{ 'selected': country.code === selectedCountry.code }"
            >
              <img 
                :src="`https://flagcdn.com/w40/${country.code.toLowerCase()}.png`" 
                :alt="country.name"
                class="country-flag"
              />
              <span class="country-name">{{ country.name }}</span>
              <span class="country-dial">{{ country.dial }}</span>
            </div>
          </div>
        </div>
      </transition>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'

export default {
  name: 'PhoneInput',
  props: {
    modelValue: {
      type: String,
      default: ''
    },
    id: {
      type: String,
      required: true
    },
    placeholder: {
      type: String,
      default: 'Numéro de téléphone'
    },
    required: {
      type: Boolean,
      default: false
    },
    defaultCountry: {
      type: String,
      default: 'GA' // Gabon par défaut
    },
    inputClass: {
      type: String,
      default: ''
    },
    disabled: {
      type: Boolean,
      default: false
    }
  },
  emits: ['update:modelValue'],
  setup(props, { emit }) {
    // Liste des pays avec leurs codes
    const countries = [
      { code: 'GA', name: 'Gabon', dial: '+241' },
      { code: 'FR', name: 'France', dial: '+33' },
      { code: 'BE', name: 'Belgique', dial: '+32' },
      { code: 'CH', name: 'Suisse', dial: '+41' },
      { code: 'CA', name: 'Canada', dial: '+1' },
      { code: 'US', name: 'États-Unis', dial: '+1' },
      { code: 'CM', name: 'Cameroun', dial: '+237' },
      { code: 'CD', name: 'RD Congo', dial: '+243' },
      { code: 'CG', name: 'Congo', dial: '+242' },
      { code: 'CI', name: 'Côte d\'Ivoire', dial: '+225' },
      { code: 'SN', name: 'Sénégal', dial: '+221' },
      { code: 'ML', name: 'Mali', dial: '+223' },
      { code: 'BF', name: 'Burkina Faso', dial: '+226' },
      { code: 'TG', name: 'Togo', dial: '+228' },
      { code: 'BJ', name: 'Bénin', dial: '+229' },
      { code: 'MA', name: 'Maroc', dial: '+212' },
      { code: 'TN', name: 'Tunisie', dial: '+216' },
      { code: 'DZ', name: 'Algérie', dial: '+213' },
      { code: 'NG', name: 'Nigeria', dial: '+234' },
      { code: 'GH', name: 'Ghana', dial: '+233' },
    ].sort((a, b) => a.name.localeCompare(b.name))

    const showDropdown = ref(false)
    const searchQuery = ref('')
    const phoneNumber = ref('')
    const dropdown = ref(null)

    const selectedCountry = ref(
      countries.find(c => c.code === props.defaultCountry) || countries[0]
    )

    const filteredCountries = computed(() => {
      if (!searchQuery.value) return countries
      
      const query = searchQuery.value.toLowerCase()
      return countries.filter(country => 
        country.name.toLowerCase().includes(query) ||
        country.dial.includes(query) ||
        country.code.toLowerCase().includes(query)
      )
    })

    const fullPhoneNumber = computed(() => {
      if (!phoneNumber.value) return ''
      return `${selectedCountry.value.dial}${phoneNumber.value.replace(/^0/, '')}`
    })

    const toggleDropdown = () => {
      showDropdown.value = !showDropdown.value
      if (showDropdown.value) {
        searchQuery.value = ''
      }
    }

    const selectCountry = (country) => {
      selectedCountry.value = country
      showDropdown.value = false
      updateValue()
    }

    const handleInput = (event) => {
      // Nettoyer le numéro (garder seulement les chiffres et espaces)
      phoneNumber.value = event.target.value.replace(/[^\d\s]/g, '')
      updateValue()
    }

    const handleBlur = () => {
      // Fermer le dropdown si ouvert
      setTimeout(() => {
        showDropdown.value = false
      }, 200)
    }

    const updateValue = () => {
      emit('update:modelValue', fullPhoneNumber.value)
    }

    // Gestion du clic en dehors
    const handleClickOutside = (event) => {
      if (dropdown.value && !dropdown.value.contains(event.target) && 
          !event.target.closest('.country-selector')) {
        showDropdown.value = false
      }
    }

    // Initialiser avec la valeur du modèle si elle existe
    watch(() => props.modelValue, (newValue) => {
      if (newValue && !phoneNumber.value) {
        // Extraire le code pays et le numéro
        for (const country of countries) {
          if (newValue.startsWith(country.dial)) {
            selectedCountry.value = country
            phoneNumber.value = newValue.substring(country.dial.length)
            break
          }
        }
      }
    }, { immediate: true })

    onMounted(() => {
      document.addEventListener('click', handleClickOutside)
    })

    onUnmounted(() => {
      document.removeEventListener('click', handleClickOutside)
    })

    return {
      countries,
      showDropdown,
      searchQuery,
      phoneNumber,
      selectedCountry,
      filteredCountries,
      fullPhoneNumber,
      dropdown,
      toggleDropdown,
      selectCountry,
      handleInput,
      handleBlur
    }
  }
}
</script>

<style scoped>
.phone-input-wrapper {
  position: relative;
  width: 100%;
}

.phone-input-container {
  position: relative;
  display: flex;
  align-items: center;
  width: 100%;
}

.country-selector {
  position: relative;
  display: flex;
  align-items: center;
  padding: 0.75rem;
  background: #f5f5f5;
  border: 2px solid #e5e7eb;
  border-right: none;
  border-radius: 16px 0 0 16px;
  cursor: pointer;
  transition: all 0.2s;
  user-select: none;
}

.country-selector:hover {
  background: #ebebeb;
}

.selected-country {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.country-flag {
  width: 24px;
  height: 16px;
  object-fit: cover;
  border-radius: 2px;
  box-shadow: 0 0 0 1px rgba(0,0,0,0.1);
}

.country-code {
  font-family: 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  font-weight: 600;
  color: #272d63;
  font-size: 0.875rem;
}

.dropdown-arrow {
  margin-left: 0.25rem;
  transition: transform 0.2s;
  color: #272d63;
}

.dropdown-arrow.rotate {
  transform: rotate(180deg);
}

.phone-input {
  flex: 1;
  padding: 0.75rem 1rem;
  border: 2px solid #e5e7eb;
  border-left: none;
  border-radius: 0 16px 16px 0;
  font-family: 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  font-size: 1rem;
  transition: all 0.2s;
  background: rgba(255, 255, 255, 0.9);
}

.phone-input:focus {
  outline: none;
  border-color: #272d63;
  box-shadow: 0 0 0 3px rgba(250, 181, 17, 0.2);
}

.country-dropdown {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  right: 0;
  background: white;
  border: 2px solid #e5e7eb;
  border-radius: 16px;
  box-shadow: 0 10px 40px rgba(39, 45, 99, 0.15);
  z-index: 1000;
  max-height: 400px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.country-search {
  padding: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.search-input {
  width: 100%;
  padding: 0.5rem 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-family: 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  font-size: 0.875rem;
  transition: all 0.2s;
}

.search-input:focus {
  outline: none;
  border-color: #272d63;
}

.country-list {
  flex: 1;
  overflow-y: auto;
  max-height: 300px;
}

.country-item {
  display: flex;
  align-items: center;
  padding: 0.75rem 1rem;
  cursor: pointer;
  transition: all 0.2s;
  gap: 0.75rem;
}

.country-item:hover {
  background: #f5f5f5;
}

.country-item.selected {
  background: rgba(250, 181, 17, 0.1);
}

.country-name {
  flex: 1;
  font-family: 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  color: #272d63;
  font-size: 0.875rem;
}

.country-dial {
  font-family: 'Myriad Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  color: #6b7280;
  font-size: 0.875rem;
  font-weight: 600;
}

/* Animation du dropdown */
.dropdown-fade-enter-active,
.dropdown-fade-leave-active {
  transition: all 0.2s ease;
}

.dropdown-fade-enter-from,
.dropdown-fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* Scrollbar personnalisée */
.country-list::-webkit-scrollbar {
  width: 6px;
}

.country-list::-webkit-scrollbar-track {
  background: #f5f5f5;
}

.country-list::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 3px;
}

.country-list::-webkit-scrollbar-thumb:hover {
  background: #9ca3af;
}

/* Disabled state */
.country-selector.disabled {
  opacity: 0.6;
  pointer-events: none;
  background: #f9fafb;
}

.phone-input:disabled {
  background: #f9fafb;
  cursor: not-allowed;
}
</style>