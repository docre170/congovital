<?php
session_start();
require_once __DIR__ . '/backend/config/database.php';
require_once __DIR__ . '/backend/models/User.php';

// ============================================================
// SÉCURITÉ : vérifier si l'utilisateur est connecté
// ============================================================

if (!isset($_SESSION['user_id'])) {
    header('Location: frontend/html/abonne.html');
    exit;
}

$pdo = getPDO();
$userId = (int) $_SESSION['user_id'];

// Récupérer l'utilisateur connecté
$userModel = new User();
$user = $userModel->findByEmail($_SESSION['username'] . '@congovital.cd'); // fallback

// Meilleure méthode : récupérer par ID
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: frontend/html/abonne.html');
    exit;
}

$role = $user['role'];
$fullname = $user['fullname'];
$username = $user['username'];
$email = $user['email'];

// ============================================================
// TRAITEMENT DES ACTIONS (admin uniquement)
// ============================================================

$flashMessage = null;
$flashType = 'success';

if ($role === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['dashboard_action'] ?? '';

    try {
        if ($action === 'save_doctor') {
            $doctorId = (int) ($_POST['doctor_id'] ?? 0);
            $firstname = trim($_POST['fullname'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $speciality = trim($_POST['speciality'] ?? '');

            if ($firstname === '' || $email === '' || $speciality === '') {
                throw new RuntimeException('Veuillez remplir tous les champs obligatoires.');
            }

            if ($doctorId > 0) {
                $stmt = $pdo->prepare('UPDATE users SET fullname=?, email=?, speciality=? WHERE id=? AND role="doctor"');
                $stmt->execute([$fullname, $email, $speciality, $doctorId]);
                $flashMessage = 'Médecin mis à jour.';
            } else {
                $hash = password_hash('password', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('INSERT INTO users (username, fullname, email, password_hash, role, speciality) VALUES (?, ?, ?, ?, "doctor", ?)');
                $stmt->execute([
                    strtolower(explode('@', $email)[0]),
                    $fullname,
                    $email,
                    $hash,
                    $speciality
                ]);
                $flashMessage = 'Médecin ajouté.';
            }
        } elseif ($action === 'delete_doctor') {
            $stmt = $pdo->prepare('UPDATE users SET is_active = 0 WHERE id = ? AND role = "doctor"');
            $stmt->execute([(int) ($_POST['doctor_id'] ?? 0)]);
            $flashMessage = 'Médecin retiré de l\'annuaire.';
        }
    } catch (Throwable $e) {
        $flashType = 'error';
        $flashMessage = $e->getMessage();
    }
}

// ============================================================
// DONNÉES POUR LE DASHBOARD
// ============================================================

// Rendez-vous du patient
$appointments = [];
if ($role === 'patient') {
    $stmt = $pdo->prepare('
        SELECT a.*, d.fullname AS doctor_name, d.speciality
        FROM appointments a
        JOIN users d ON a.doctor_id = d.id
        WHERE a.patient_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ');
    $stmt->execute([$userId]);
    $appointments = $stmt->fetchAll();
}

// Rendez-vous du médecin
if ($role === 'doctor') {
    $stmt = $pdo->prepare('
        SELECT a.*, p.fullname AS patient_name
        FROM appointments a
        JOIN users p ON a.patient_id = p.id
        WHERE a.doctor_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ');
    $stmt->execute([$userId]);
    $appointments = $stmt->fetchAll();
}

// Vue admin : tous les rendez-vous
if ($role === 'admin') {
    $stmt = $pdo->query('
        SELECT a.*, p.fullname AS patient_name, d.fullname AS doctor_name
        FROM appointments a
        JOIN users p ON a.patient_id = p.id
        JOIN users d ON a.doctor_id = d.id
        ORDER BY a.created_at DESC
    ');
    $appointments = $stmt->fetchAll();
}

// Statistiques
$totalAppointments = count($appointments);
$pendingCount = 0;
$confirmedCount = 0;
foreach ($appointments as $a) {
    if ($a['status'] === 'pending') $pendingCount++;
    elseif ($a['status'] === 'confirmed') $confirmedCount++;
}

// Liste des médecins (pour admin et patients)
$doctors = [];
if (in_array($role, ['admin', 'patient'])) {
    $stmt = $pdo->query('SELECT id, fullname, email, speciality, is_available FROM users WHERE role = "doctor" AND is_active = 1 ORDER BY fullname');
    $doctors = $stmt->fetchAll();
}

// Liste des patients (pour admin)
$patients = [];
if ($role === 'admin') {
    $stmt = $pdo->query('SELECT id, fullname, email FROM users WHERE role = "patient" AND is_active = 1 ORDER BY fullname');
    $patients = $stmt->fetchAll();
}

// Articles
$articles = [];
$stmt = $pdo->query('SELECT id, title, category, published_at FROM articles WHERE is_published = 1 ORDER BY published_at DESC LIMIT 5');
$articles = $stmt->fetchAll();

// Initiales pour avatar
function getInitials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    if (count($parts) >= 2) {
        return strtoupper($parts[0][0] . $parts[count($parts) - 1][0]);
    }
    return strtoupper(substr($name, 0, 2));
}

$initials = getInitials($fullname);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - CongoVital</title>
    <link rel="stylesheet" href="frontend/css/style.css">
    <link rel="stylesheet" href="frontend/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>

    <!-- ===== SIDEBAR ===== -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-hospital"></i>
            <h2>CongoVital</h2>
        </div>

        <div class="user-profile-sidebar">
            <div class="user-avatar-large"><?= $initials ?></div>
            <h3><?= htmlspecialchars($fullname) ?></h3>
            <span class="user-role-badge <?= $role ?>"><?= ucfirst($role) ?></span>
        </div>

        <nav class="sidebar-nav">
            <a href="#overview" class="nav-item active" data-section="overview">
                <i class="fas fa-chart-pie"></i> Tableau de bord
            </a>
            <a href="#appointments" class="nav-item" data-section="appointments">
                <i class="fas fa-calendar"></i> Rendez-vous
            </a>

            <?php if (in_array($role, ['patient', 'admin'])): ?>
                <a href="#doctors" class="nav-item" data-section="doctors">
                    <i class="fas fa-user-md"></i> Médecins
                </a>
            <?php endif; ?>

            <?php if ($role === 'patient'): ?>
                <a href="#book" class="nav-item" data-section="book">
                    <i class="fas fa-plus"></i> Prendre RDV
                </a>
            <?php endif; ?>

            <?php if ($role === 'admin'): ?>
                <a href="#patients" class="nav-item" data-section="patients">
                    <i class="fas fa-users"></i> Patients
                </a>
            <?php endif; ?>

            <a href="#articles" class="nav-item" data-section="articles">
                <i class="fas fa-book"></i> Articles
            </a>
            <a href="frontend/html/deconnexion.php" class="nav-item logout">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </a>
        </nav>
    </aside>

    <!-- ===== MAIN ===== -->
    <main class="dashboard-main">
        <div class="dashboard-topbar">
            <h1 id="pageTitle">Tableau de bord</h1>
            <div class="topbar-user">
                <span><?= htmlspecialchars($fullname) ?></span>
                <div class="topbar-avatar"><?= $initials ?></div>
            </div>
        </div>

        <div class="dashboard-content">

            <?php if ($flashMessage): ?>
                <div class="alert-dashboard <?= $flashType ?>">
                    <?= htmlspecialchars($flashMessage) ?>
                </div>
            <?php endif; ?>

            <!-- ===== OVERVIEW ===== -->
            <section id="section-overview" class="dashboard-section active">
                <h2 class="section-title">Bienvenue, <?= htmlspecialchars($fullname) ?></h2>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon green"><i class="fas fa-calendar"></i></div>
                        <div class="stat-info">
                            <h3><?= $totalAppointments ?></h3>
                            <p>Total rendez-vous</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon orange"><i class="fas fa-clock"></i></div>
                        <div class="stat-info">
                            <h3><?= $pendingCount ?></h3>
                            <p>En attente</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon blue"><i class="fas fa-check"></i></div>
                        <div class="stat-info">
                            <h3><?= $confirmedCount ?></h3>
                            <p>Confirmés</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon purple"><i class="fas fa-book"></i></div>
                        <div class="stat-info">
                            <h3><?= count($articles) ?></h3>
                            <p>Articles</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ===== RENDEZ-VOUS ===== -->
            <section id="section-appointments" class="dashboard-section">
                <h2 class="section-title">Mes rendez-vous</h2>
                <div class="card">
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <?php if ($role !== 'patient'): ?><th>Patient</th><?php endif; ?>
                                <th>Médecin</th>
                                <th>Date</th>
                                <th>Heure</th>
                                <th>Service</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($appointments)): ?>
                                <tr>
                                    <td colspan="6" style="text-align:center;">Aucun rendez-vous</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($appointments as $a): ?>
                                    <tr>
                                        <?php if ($role !== 'patient'): ?>
                                            <td><?= htmlspecialchars($a['patient_name'] ?? '-') ?></td>
                                        <?php endif; ?>
                                        <td><?= htmlspecialchars($a['doctor_name'] ?? '-') ?></td>
                                        <td><?= date('d/m/Y', strtotime($a['appointment_date'])) ?></td>
                                        <td><?= substr($a['appointment_time'], 0, 5) ?></td>
                                        <td><?= htmlspecialchars($a['service_type']) ?></td>
                                        <td><span class="status-badge <?= $a['status'] ?>"><?= $a['status'] ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- ===== MÉDECINS ===== -->
            <?php if (in_array($role, ['patient', 'admin'])): ?>
                <section id="section-doctors" class="dashboard-section">
                    <h2 class="section-title">Annuaire des médecins</h2>
                    <div class="card">
                        <table class="dashboard-table">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Spécialité</th>
                                    <th>Email</th>
                                    <th>Disponibilité</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($doctors as $d): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($d['fullname']) ?></td>
                                        <td><?= htmlspecialchars($d['speciality']) ?></td>
                                        <td><?= htmlspecialchars($d['email']) ?></td>
                                        <td>
                                            <?= $d['is_available'] ? '<span style="color:green;">Disponible</span>' : '<span style="color:red;">Indisponible</span>' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>

            <!-- ===== PATIENTS (admin) ===== -->
            <?php if ($role === 'admin'): ?>
                <section id="section-patients" class="dashboard-section">
                    <h2 class="section-title">Patients</h2>
                    <div class="card">
                        <table class="dashboard-table">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Email</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($patients as $p): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($p['fullname']) ?></td>
                                        <td><?= htmlspecialchars($p['email']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>

            <!-- ===== PRENDRE RDV (patient) ===== -->
            <?php if ($role === 'patient'): ?>
                <section id="section-book" class="dashboard-section">
                    <h2 class="section-title">Prendre un rendez-vous</h2>
                    <div class="card">
                        <form id="book-form">
                            <div class="form-group">
                                <label for="doctor_id">Médecin</label>
                                <select id="doctor_id" required>
                                    <option value="">-- Choisir un médecin --</option>
                                    <?php foreach ($doctors as $d): ?>
                                        <option value="<?= (int)$d['id'] ?>"><?= htmlspecialchars($d['fullname']) ?> - <?= htmlspecialchars($d['speciality']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="appointment_date">Date</label>
                                <input type="date" id="appointment_date" required min="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="form-group">
                                <label for="appointment_time">Heure</label>
                                <input type="time" id="appointment_time" required>
                            </div>
                            <div class="form-group">
                                <label for="service_type">Service</label>
                                <input type="text" id="service_type" placeholder="Consultation générale" required>
                            </div>
                            <div class="form-group">
                                <label for="notes">Notes</label>
                                <textarea id="notes" rows="3"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Réserver</button>
                        </form>
                    </div>
                </section>
            <?php endif; ?>

            <!-- ===== ARTICLES ===== -->
            <section id="section-articles" class="dashboard-section">
                <h2 class="section-title">Articles de sensibilisation</h2>
                <div class="card">
                    <?php if (empty($articles)): ?>
                        <p>Aucun article disponible.</p>
                    <?php else: ?>
                        <?php foreach ($articles as $art): ?>
                            <div class="article-item">
                                <h3><?= htmlspecialchars($art['title']) ?></h3>
                                <p><small><?= htmlspecialchars($art['category']) ?> - <?= date('d/m/Y', strtotime($art['published_at'])) ?></small></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

        </div>
    </main>

    <script>
        // Navigation entre les sections
        document.querySelectorAll('.nav-item[data-section]').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const section = this.dataset.section;

                document.querySelectorAll('.nav-item[data-section]').forEach(i => i.classList.remove('active'));
                document.querySelectorAll('.dashboard-section').forEach(s => s.classList.remove('active'));

                this.classList.add('active');
                document.getElementById('section-' + section)?.classList.add('active');

                const titles = {
                    overview: 'Tableau de bord',
                    appointments: 'Rendez-vous',
                    doctors: 'Médecins',
                    book: 'Prendre un rendez-vous',
                    patients: 'Patients',
                    articles: 'Articles'
                };
                document.getElementById('pageTitle').textContent = titles[section] || 'Tableau de bord';
            });
        });

        // Formulaire de prise de RDV
        const bookForm = document.getElementById('book-form');
        if (bookForm) {
            bookForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const data = {
                    doctor_id: document.getElementById('doctor_id').value,
                    appointment_date: document.getElementById('appointment_date').value,
                    appointment_time: document.getElementById('appointment_time').value,
                    service_type: document.getElementById('service_type').value,
                    notes: document.getElementById('notes').value
                };
                try {
                    const res = await fetch('backend/endpoints/book_appointment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(data)
                    });
                    const result = await res.json();
                    alert(result.message);
                    if (result.success) location.reload();
                } catch (err) {
                    alert('Erreur de connexion au serveur.');
                }
            });
        }
    </script>

</body>

</html>