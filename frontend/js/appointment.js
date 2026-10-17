// Gestion du formulaire de rendez-vous multi-étapes
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.appointment-form');
    const fieldsets = form.querySelectorAll('fieldset');
    const nextBtn = document.getElementById('next-btn');
    const prevBtn = document.getElementById('prev-btn');
    const submitBtn = document.getElementById('submit-btn');
    
    let currentStep = 0;

    // Afficher la première étape
    showStep(currentStep);

    // Bouton Suivant
    nextBtn.addEventListener('click', function(e) {
        e.preventDefault();
        
        if (validateStep(currentStep)) {
            if (currentStep < fieldsets.length - 1) {
                currentStep++;
                showStep(currentStep);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }
    });

    // Bouton Précédent
    prevBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (currentStep > 0) {
            currentStep--;
            showStep(currentStep);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });

    // Afficher/Masquer les étapes
    function showStep(n) {
        fieldsets.forEach((fieldset, index) => {
            if (index === n) {
                fieldset.classList.add('active');
            } else {
                fieldset.classList.remove('active');
            }
        });

        // Gérer les boutons
        if (n === 0) {
            prevBtn.style.display = 'none';
        } else {
            prevBtn.style.display = 'block';
        }

        if (n === fieldsets.length - 1) {
            nextBtn.style.display = 'none';
            submitBtn.style.display = 'block';
        } else {
            nextBtn.style.display = 'block';
            submitBtn.style.display = 'none';
        }
    }

    // Valider l'étape actuelle
    function validateStep(n) {
        const inputs = fieldsets[n].querySelectorAll('input[required], select[required], textarea[required]');
        let isValid = true;

        inputs.forEach(input => {
            if (!input.value.trim()) {
                input.style.borderColor = '#d32f2f';
                isValid = false;
            } else {
                input.style.borderColor = '#ddd';
            }
        });

        if (!isValid) {
            alert('Veuillez remplir tous les champs obligatoires.');
        }

        return isValid;
    }

    // Soumettre le formulaire
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (validateStep(currentStep)) {
            // Afficher un message de succès
            alert('Votre rendez-vous a été réservé avec succès! Vous recevrez une confirmation par email.');
            // Réinitialiser le formulaire
            form.reset();
            currentStep = 0;
            showStep(currentStep);
        }
    });
});
