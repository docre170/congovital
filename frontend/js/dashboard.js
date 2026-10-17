// Gestion du tableau de bord
document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.nav-link');
    const sections = document.querySelectorAll('.dashboard-section');

    // Afficher la section d'aperçu par défaut
    showSection('overview');

    // Ajouter les écouteurs d'événements aux liens de navigation
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            const sectionId = this.getAttribute('data-section');
            
            if (sectionId) {
                showSection(sectionId);
            } else if (this.classList.contains('logout')) {
                // Gérer la déconnexion
                handleLogout();
            }
            
            // Mettre à jour la classe active
            navLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // Fonction pour afficher une section
    function showSection(sectionId) {
        sections.forEach(section => {
            if (section.id === sectionId) {
                section.classList.add('active');
            } else {
                section.classList.remove('active');
            }
        });
    }

    // Fonction de déconnexion
    function handleLogout() {
        if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
            // Rediriger vers la page d'accueil
            window.location.href = '../../index.html';
        }
    }

    // Gestion des onglets de rendez-vous
    const tabButtons = document.querySelectorAll('.tab-btn');
    tabButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            tabButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Ici, vous pouvez ajouter la logique pour filtrer les rendez-vous
            console.log('Onglet sélectionné:', this.getAttribute('data-tab'));
        });
    });

    // Gestion des boutons d'action rapide
    const actionButtons = document.querySelectorAll('.actions-card .btn-small');
    actionButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            console.log('Action rapide:', this.textContent);
        });
    });

    // Gestion des formulaires de paramètres
    const settingsForms = document.querySelectorAll('.settings-section form');
    settingsForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Les paramètres ont été mis à jour avec succès!');
        });
    });

    // Gestion des boutons de modification/annulation de rendez-vous
    const appointmentActions = document.querySelectorAll('.appointment-actions .btn-small');
    appointmentActions.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (this.classList.contains('btn-danger')) {
                if (confirm('Êtes-vous sûr de vouloir annuler ce rendez-vous ?')) {
                    this.parentElement.parentElement.remove();
                    alert('Le rendez-vous a été annulé.');
                }
            } else {
                alert('Redirection vers la modification du rendez-vous...');
                // Rediriger vers la page de modification
            }
        });
    });
});
