-- ============================================================
-- CONGOVITAL - Base de données
-- MySQL 8+ / MariaDB 10.4+
-- ============================================================

CREATE DATABASE IF NOT EXISTS congovital
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE congovital;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. UTILISATEURS (patients, médecins, administrateurs)
-- ============================================================

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    username VARCHAR(50) NOT NULL UNIQUE,
    fullname VARCHAR(150) NOT NULL,
    email VARCHAR(191) NOT NULL UNIQUE,

    password_hash VARCHAR(255) NOT NULL,

    phone VARCHAR(20) NULL,
    date_of_birth DATE NULL,
    gender ENUM('M', 'F', 'other') NULL,

    role ENUM('patient', 'doctor', 'admin')
        NOT NULL DEFAULT 'patient',

    speciality VARCHAR(120) NULL,
    is_available TINYINT(1) NOT NULL DEFAULT 1,

    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at TIMESTAMP NULL DEFAULT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_users_email (email),
    INDEX idx_users_role (role),
    INDEX idx_users_active (is_active)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 2. ARTICLES DE SENSIBILISATION
-- ============================================================

CREATE TABLE articles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    author_id INT UNSIGNED NOT NULL,

    title VARCHAR(180) NOT NULL,
    category VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,

    is_published TINYINT(1) NOT NULL DEFAULT 1,
    published_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_articles_author
        FOREIGN KEY (author_id)
        REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    INDEX idx_articles_category (category),
    INDEX idx_articles_published (is_published, published_at)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 3. QUIZ (lié à un article)
-- ============================================================

CREATE TABLE quizzes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    article_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_quizzes_article
        FOREIGN KEY (article_id)
        REFERENCES articles(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    INDEX idx_quizzes_article (article_id)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 4. QUESTIONS
-- ============================================================

CREATE TABLE questions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    quiz_id INT UNSIGNED NOT NULL,
    question_text VARCHAR(255) NOT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_questions_quiz
        FOREIGN KEY (quiz_id)
        REFERENCES quizzes(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    INDEX idx_questions_quiz (quiz_id)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 5. RÉPONSES (options d'une question)
-- ============================================================

CREATE TABLE answers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    question_id INT UNSIGNED NOT NULL,
    answer_text VARCHAR(255) NOT NULL,

    is_correct TINYINT(1) NOT NULL DEFAULT 0,

    CONSTRAINT fk_answers_question
        FOREIGN KEY (question_id)
        REFERENCES questions(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    INDEX idx_answers_question (question_id)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 6. SCORES (résultats des utilisateurs aux quiz)
-- ============================================================

CREATE TABLE scores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    user_id INT UNSIGNED NOT NULL,
    quiz_id INT UNSIGNED NOT NULL,

    score INT UNSIGNED NOT NULL DEFAULT 0,
    total_questions INT UNSIGNED NOT NULL DEFAULT 0,

    completed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_scores_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_scores_quiz
        FOREIGN KEY (quiz_id)
        REFERENCES quizzes(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    UNIQUE KEY uq_score_user_quiz (user_id, quiz_id),

    INDEX idx_scores_user (user_id),
    INDEX idx_scores_quiz (quiz_id)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 7. RENDEZ-VOUS
-- ============================================================

CREATE TABLE appointments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    patient_id INT UNSIGNED NOT NULL,
    doctor_id INT UNSIGNED NOT NULL,

    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,

    service_type VARCHAR(100) NOT NULL,
    notes TEXT NULL,

    status ENUM('pending', 'confirmed', 'cancelled', 'completed')
        NOT NULL DEFAULT 'pending',

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_appointments_patient
        FOREIGN KEY (patient_id)
        REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_appointments_doctor
        FOREIGN KEY (doctor_id)
        REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    INDEX idx_appointments_patient (patient_id),
    INDEX idx_appointments_doctor (doctor_id),
    INDEX idx_appointments_date (appointment_date, appointment_time),
    INDEX idx_appointments_status (status)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 8. MESSAGES (formulaire de contact)
-- ============================================================

CREATE TABLE messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(150) NOT NULL,
    email VARCHAR(191) NOT NULL,
    phone VARCHAR(20) NULL,

    subject VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,

    is_read TINYINT(1) NOT NULL DEFAULT 0,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_messages_email (email),
    INDEX idx_messages_read (is_read),
    INDEX idx_messages_created (created_at)

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;


SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- DONNÉES DE TEST
-- Mot de passe pour tous les comptes : password
-- Hash Bcrypt de "password" :
-- $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- ============================================================

-- Administrateur
INSERT INTO users (username, fullname, email, password_hash, role) VALUES
('admin', 'Administrateur CongoVital', 'admin@congovital.cd',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Médecins
INSERT INTO users (username, fullname, email, password_hash, role, speciality, is_available) VALUES
('dr_mbala', 'Dr Jean Mbala', 'mbala@congovital.cd',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', 'Généraliste', 1),
('dr_ilunga', 'Dr Grace Ilunga', 'ilunga@congovital.cd',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', 'Pédiatrie', 1),
('dr_kanku', 'Dr Patrick Kanku', 'kanku@congovital.cd',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', 'Cardiologie', 0);

-- Patient
INSERT INTO users (username, fullname, email, password_hash, role) VALUES
('patient1', 'Patient Test', 'patient@congovital.cd',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient');


-- Article + quiz de test
INSERT INTO articles (author_id, title, category, content) VALUES
(1, 'Prévention du paludisme', 'Prévention',
 'Le paludisme se transmet par les moustiques. Dormir sous une moustiquaire imprégnée, éliminer les eaux stagnantes et consulter rapidement en cas de fièvre sont les gestes essentiels.');

INSERT INTO quizzes (article_id, title) VALUES
(1, 'Quiz - Prévention du paludisme');

INSERT INTO questions (quiz_id, question_text) VALUES
(1, 'Quel est le principal vecteur du paludisme ?'),
(1, 'Quelle mesure protège efficacement contre le paludisme ?');

INSERT INTO answers (question_id, answer_text, is_correct) VALUES
(1, 'Le moustique', 1),
(1, 'La mouche', 0),
(1, 'Le rat', 0),
(2, 'Dormir sous une moustiquaire imprégnée', 1),
(2, 'Boire beaucoup d''eau', 0),
(2, 'Éviter les fruits', 0);


-- Rendez-vous de test
INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, service_type, status) VALUES
(5, 2, '2026-11-05', '10:30:00', 'Consultation générale', 'pending');