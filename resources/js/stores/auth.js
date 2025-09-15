import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('auth_token'))
  const loading = ref(false)
  
  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const isOrganizer = computed(() => user.value?.is_organizer || false)
  const isAdmin = computed(() => user.value?.is_admin || false)
  const activeTicketsCount = computed(() => user.value?.active_tickets_count || 0)
  
  // Configuration axios
  if (token.value) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
  }

  // Initialisation automatique au démarrage
  const initialize = async () => {
    if (token.value && !user.value && !loading.value) {
      await fetchUser()
    }
    // Authentification réelle uniquement
  }
  
  const login = async (credentials) => {
    try {
      // Convertir phone en login pour l'API
      const loginData = {
        login: credentials.phone || credentials.email || credentials.login,
        password: credentials.password
      }

      const response = await axios.post('/api/login', loginData, {
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      
      if (response.data.success) {
        token.value = response.data.token
        user.value = response.data.user
        
        localStorage.setItem('auth_token', token.value)
        axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
        
        return { 
          success: true,
          user: response.data.user,
          access_level: response.data.access_level
        }
      } else {
        return { 
          success: false, 
          message: response.data.message || 'Erreur de connexion' 
        }
      }
    } catch (error) {
      console.error('Login error:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Erreur de connexion' 
      }
    }
  }
  
  const register = async (userData) => {
    try {
      const response = await axios.post('/api/register', userData)
      
      token.value = response.data.token
      user.value = response.data.user
      
      localStorage.setItem('auth_token', token.value)
      axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
      
      return { success: true }
    } catch (error) {
      return { 
        success: false, 
        message: error.response?.data?.message || 'Erreur lors de l\'inscription' 
      }
    }
  }
  
  const logout = async () => {
    try {
      await axios.post('/api/logout')
    } catch (error) {
      console.error('Erreur lors de la déconnexion:', error)
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('auth_token')
      delete axios.defaults.headers.common['Authorization']
    }
  }
  
  const fetchUser = async () => {
    if (!token.value) return
    
    loading.value = true
    try {
      const response = await axios.get('/api/me')
      user.value = response.data
    } catch (error) {
      console.error('Erreur lors de la récupération de l\'utilisateur:', error)
      await logout()
    } finally {
      loading.value = false
    }
  }
  
  // Méthode de développement supprimée - authentification réelle uniquement

  return {
    user,
    token,
    loading,
    isAuthenticated,
    isOrganizer,
    isAdmin,
    activeTicketsCount,
    login,
    register,
    logout,
    fetchUser,
    initialize
  }
})