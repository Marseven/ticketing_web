import axios from 'axios';
import Swal from 'sweetalert2';

window.axios = axios;
window.Swal = Swal;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Configuration par défaut de SweetAlert2
window.Toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
  didOpen: (toast) => {
    toast.addEventListener('mouseenter', Swal.stopTimer)
    toast.addEventListener('mouseleave', Swal.resumeTimer)
  }
});

// Fonction utilitaire pour les confirmations
window.confirmAction = (title, text, confirmText = 'Oui', cancelText = 'Annuler') => {
  return Swal.fire({
    title: title,
    text: text,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: confirmText,
    cancelButtonText: cancelText
  });
};
