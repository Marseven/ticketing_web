// Utilitaire pour gérer l'authentification et la migration des données

export const authUtils = {
  // Vérifier et migrer les données d'authentification
  checkAndMigrateAuth() {
    const token = localStorage.getItem('token') || localStorage.getItem('auth_token');
    const userRole = localStorage.getItem('userRole');
    const userName = localStorage.getItem('userName');
    const userEmail = localStorage.getItem('userEmail');
    
    // Si on a un token mais pas de rôle, essayer de déduire le rôle
    if (token && !userRole) {
      // Vérifier si c'est un token de test
      if (token.startsWith('test-token-')) {
        const role = token.replace('test-token-', '');
        localStorage.setItem('userRole', role);
        return role;
      }
      
      // Si on ne peut pas déduire le rôle, forcer la reconnexion
      this.clearAuth();
      return null;
    }
    
    return userRole;
  },
  
  // Nettoyer toutes les données d'authentification
  clearAuth() {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('token');
    localStorage.removeItem('userRole');
    localStorage.removeItem('userName');
    localStorage.removeItem('userEmail');
  },
  
  // Sauvegarder les données d'authentification
  saveAuth(token, user, accessLevel) {
    localStorage.setItem('auth_token', token);
    localStorage.setItem('token', token);
    localStorage.setItem('userRole', accessLevel);
    localStorage.setItem('userName', user.name || '');
    localStorage.setItem('userEmail', user.email || '');
  },
  
  // Obtenir le rôle actuel
  getCurrentRole() {
    return localStorage.getItem('userRole');
  },
  
  // Vérifier si l'utilisateur est admin
  isAdmin() {
    return this.getCurrentRole() === 'admin';
  },
  
  // Vérifier si l'utilisateur est organisateur
  isOrganizer() {
    return this.getCurrentRole() === 'organizer';
  },
  
  // Obtenir le token
  getToken() {
    return localStorage.getItem('token') || localStorage.getItem('auth_token');
  }
};

export default authUtils;