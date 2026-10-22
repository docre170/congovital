const ENDPOINTS_BASE = '../../backend/endpoints';

document.addEventListener('DOMContentLoaded', function () {
    const loginForm    = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const alertBox     = document.getElementById('alert-container');

    function showAlert(message, type = 'error') {
        if (!alertBox) return;
        const cls = type === 'success' ? 'alert-success' : 'alert-error';
        const alert = document.createElement('div');
        alert.className = `alert ${cls}`;
        alert.textContent = message;
        alertBox.replaceChildren(alert);
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function clearAlert() {
        if (alertBox) alertBox.innerHTML = '';
    }

    // ===== CONNEXION =====
    if (loginForm) {
        loginForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            clearAlert();

            const identifier = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;

            if (!identifier || !password) {
                showAlert('Veuillez remplir tous les champs.');
                return;
            }

            const btn = loginForm.querySelector('.btn-submit');
            btn.disabled = true;
            btn.textContent = 'Connexion...';

            try {
                const res  = await fetch(`${ENDPOINTS_BASE}/login.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ identifier, password })
                });
                const data = await res.json();

                if (data.success) {
                    localStorage.setItem('user', JSON.stringify(data.user));
                    localStorage.setItem('user_id', data.user.id);
                    localStorage.setItem('user_role', data.user.role);
                    showAlert('Connexion réussie. Redirection...', 'success');
                    window.location.assign(new URL('../../dashboard.php', window.location.href));
                } else {
                    showAlert(data.message || 'Identifiants incorrects.');
                }
            } catch (err) {
                showAlert('Erreur de connexion au serveur.');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Se connecter';
            }
        });
    }

    // ===== INSCRIPTION =====
    if (registerForm) {
        registerForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            clearAlert();

            const username = document.getElementById('username').value.trim();
            const fullname = document.getElementById('fullname').value.trim();
            const email    = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirm  = document.getElementById('confirm-password').value;

            if (!username || !fullname || !email || !password || !confirm) {
                showAlert('Veuillez remplir tous les champs obligatoires.');
                return;
            }
            if (password !== confirm) {
                showAlert('Les mots de passe ne correspondent pas.');
                return;
            }
            if (password.length < 8) {
                showAlert('Le mot de passe doit contenir au moins 8 caractères.');
                return;
            }

            const btn = registerForm.querySelector('.btn-submit');
            btn.disabled = true;
            btn.textContent = 'Inscription...';

            try {
                const res  = await fetch(`${ENDPOINTS_BASE}/register.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        username,
                        fullname,
                        email,
                        password,
                        phone: document.getElementById('phone').value.trim() || null,
                        date_of_birth: document.getElementById('date-of-birth').value || null,
                        gender: document.getElementById('gender').value || null
                    })
                });
                const data = await res.json();

                if (data.success) {
                    showAlert('Inscription réussie. Redirection vers la connexion...', 'success');
                    setTimeout(() => {
                        window.location.href = './abonne.html';
                    }, 1500);
                } else {
                    showAlert(data.message || 'Erreur lors de l\'inscription.');
                }
            } catch (err) {
                showAlert('Erreur de connexion au serveur.');
            } finally {
                btn.disabled = false;
                btn.textContent = 'S\'inscrire';
            }
        });
    }
});