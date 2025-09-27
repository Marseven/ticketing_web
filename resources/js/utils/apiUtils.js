/**
 * Utilitaires pour les appels API avec gestion automatique du CSRF
 */

/**
 * Configuration par défaut des headers pour les requêtes
 */
const getDefaultHeaders = () => {
  const headers = {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest'
  }

  // Ajouter le token d'authentification
  const token = localStorage.getItem('token') || localStorage.getItem('auth_token')
  if (token) {
    headers['Authorization'] = `Bearer ${token}`
  }

  // Ajouter le token CSRF
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
  if (csrfToken) {
    headers['X-CSRF-TOKEN'] = csrfToken
  }

  return headers
}

/**
 * Wrapper pour fetch avec gestion automatique des headers
 */
export const apiRequest = async (url, options = {}) => {
  const defaultOptions = {
    headers: getDefaultHeaders(),
    ...options
  }

  // Fusionner les headers personnalisés avec les headers par défaut
  if (options.headers) {
    defaultOptions.headers = {
      ...defaultOptions.headers,
      ...options.headers
    }
  }

  try {
    const response = await fetch(url, defaultOptions)
    const data = await response.json()

    if (!response.ok) {
      throw new Error(data.message || `HTTP ${response.status}`)
    }

    return data
  } catch (error) {
    console.error('API Request Error:', error)
    throw error
  }
}

/**
 * Méthodes de convenance pour les différents types de requêtes
 */
export const api = {
  get: (url, options = {}) => apiRequest(url, { method: 'GET', ...options }),
  
  post: (url, data = {}, options = {}) => apiRequest(url, {
    method: 'POST',
    body: JSON.stringify(data),
    ...options
  }),
  
  put: (url, data = {}, options = {}) => apiRequest(url, {
    method: 'PUT',
    body: JSON.stringify(data),
    ...options
  }),
  
  patch: (url, data = {}, options = {}) => apiRequest(url, {
    method: 'PATCH',
    body: JSON.stringify(data),
    ...options
  }),
  
  delete: (url, options = {}) => apiRequest(url, { method: 'DELETE', ...options })
}

/**
 * Gestionnaire d'erreurs pour les réponses API
 */
export const handleApiError = (error) => {
  if (error.response?.status === 401) {
    // Token expiré - rediriger vers login
    localStorage.removeItem('token')
    localStorage.removeItem('auth_token')
    localStorage.removeItem('user')
    window.location.href = '/login'
    return
  }

  if (error.response?.status === 403) {
    Swal.fire({
      icon: 'error',
      title: 'Accès refusé',
      text: 'Vous n\'avez pas les permissions nécessaires pour cette action.'
    })
    return
  }

  if (error.response?.status === 422) {
    // Erreurs de validation
    const errors = error.response.data?.errors
    if (errors) {
      const errorMessages = Object.values(errors).flat().join(', ')
      Swal.fire({
        icon: 'error',
        title: 'Données invalides',
        text: errorMessages
      })
      return
    }
  }

  // Erreur générique
  Swal.fire({
    icon: 'error',
    title: 'Erreur',
    text: error.message || 'Une erreur inattendue s\'est produite.'
  })
}

/**
 * Fonction utilitaire pour afficher les messages de succès
 */
export const showSuccess = (message) => {
  Toast.fire({
    icon: 'success',
    title: message
  })
}

/**
 * Fonction utilitaire pour afficher les confirmations
 */
export const showConfirm = (title, text, confirmText = 'Oui', cancelText = 'Annuler') => {
  return confirmAction(title, text, confirmText, cancelText)
}

export default api