import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'
import authUtils from '../utils/auth'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('auth_token') || localStorage.getItem('token'))
  const userRole = ref(localStorage.getItem('userRole'))
  const loading = ref(false)
  
  const isAuthenticated = computed(() => !!token.value)
  const isOrganizer = computed(() => user.value?.is_organizer || userRole.value === 'organizer')
  const isAdmin = computed(() => user.value?.is_admin || userRole.value === 'admin')
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
        
        // Déterminer le rôle et l'access level
        const accessLevel = response.data.access_level || 
                          (user.value?.is_admin ? 'admin' : 
                           user.value?.is_organizer ? 'organizer' : 'client')
        
        // Utiliser authUtils pour stocker les informations
        authUtils.saveAuth(token.value, user.value, accessLevel)
        
        userRole.value = accessLevel
        axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`
        
        return { 
          success: true,
          user: response.data.user,
          access_level: accessLevel
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
      userRole.value = null
      authUtils.clearAuth()
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