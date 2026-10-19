<?php
require_once __DIR__ . '/../config/database.php';

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = getPDO();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return (bool) $stmt->fetch();
    }

    public function usernameExists(string $username): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        return (bool) $stmt->fetch();
    }

    public function create(array $data): array
    {
        if ($this->emailExists($data['email'])) {
            return ['success' => false, 'message' => 'Cet email est déjà utilisé.'];
        }
        if ($this->usernameExists($data['username'])) {
            return ['success' => false, 'message' => 'Ce nom d\'utilisateur est déjà pris.'];
        }

        $hash = password_hash($data['password'], PASSWORD_DEFAULT);

        $stmt = $this->db->prepare(
            'INSERT INTO users (username, fullname, email, password_hash, phone, date_of_birth, gender, role)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $ok = $stmt->execute([
            $data['username'],
            $data['fullname'],
            $data['email'],
            $hash,
            $data['phone'] ?? null,
            $data['date_of_birth'] ?? null,
            $data['gender'] ?? null,
            $data['role'] ?? 'patient'
        ]);

        return [
            'success' => $ok,
            'message' => $ok ? 'Inscription réussie.' : 'Erreur lors de l\'inscription.',
            'user_id' => $ok ? (int) $this->db->lastInsertId() : null
        ];
    }

    public function login(string $email, string $password): array
    {
        $user = $this->findByEmail($email);

        if (!$user) {
            return ['success' => false, 'message' => 'Email ou mot de passe incorrect.'];
        }

        if (!(int) $user['is_active']) {
            return ['success' => false, 'message' => 'Ce compte est désactivé.'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Email ou mot de passe incorrect.'];
        }

        // Mise à jour de la dernière connexion
        $this->db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')
            ->execute([$user['id']]);

        return [
            'success' => true,
            'message' => 'Connexion réussie.',
            'user' => [
                'id'       => (int) $user['id'],
                'username' => $user['username'],
                'fullname' => $user['fullname'],
                'email'    => $user['email'],
                'role'     => $user['role'],
            ]
        ];
    }
}
