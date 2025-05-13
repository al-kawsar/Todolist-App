-- Database creation and Table Structure for Todo List Application

-- 1. Users Table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    profile_picture VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    role ENUM('admin', 'regular') DEFAULT 'regular'
);

-- 2. Lists Table (Collections of Tasks)
CREATE TABLE lists (
    list_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    color VARCHAR(20) DEFAULT '#3498db',
    icon VARCHAR(50) DEFAULT 'list',
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 3. Tasks Table
CREATE TABLE tasks (
    task_id INT AUTO_INCREMENT PRIMARY KEY,
    list_id INT NOT NULL,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    due_date DATETIME,
    reminder DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_deleted BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (list_id) REFERENCES lists(list_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 4. Tags Table
CREATE TABLE tags (
    tag_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    color VARCHAR(20) DEFAULT '#3498db',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 5. Task Tags (Relationship between Tasks and Tags)
CREATE TABLE task_tags (
    task_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (task_id, tag_id),
    FOREIGN KEY (task_id) REFERENCES tasks(task_id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(tag_id) ON DELETE CASCADE
);

-- 6. Subtasks Table
CREATE TABLE subtasks (
    subtask_id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    status ENUM('pending', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(task_id) ON DELETE CASCADE
);

-- 7. Comments Table
CREATE TABLE comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(task_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 8. Attachments Table
CREATE TABLE attachments (
    attachment_id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    file_size INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(task_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 9. Activity Logs Table
CREATE TABLE activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_type ENUM('create', 'update', 'delete', 'complete', 'login', 'share') NOT NULL,
    entity_type ENUM('task', 'list', 'user', 'tag', 'subtask', 'comment', 'attachment') NOT NULL,
    entity_id INT NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 10. Collaborators Table (for task/list sharing)
CREATE TABLE collaborators (
    collaboration_id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type ENUM('list', 'task') NOT NULL,
    entity_id INT NOT NULL,
    user_id INT NOT NULL,
    permission ENUM('view', 'edit', 'admin') DEFAULT 'view',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- 11. Notifications Table
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    type ENUM('reminder', 'mention', 'share', 'comment', 'system') NOT NULL,
    entity_type ENUM('task', 'list', 'comment', 'system') NOT NULL,
    entity_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);


-- Tabel untuk menyimpan permintaan kolaborasi
CREATE TABLE collaboration_requests (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    list_id INT NOT NULL,
    sender_id INT NOT NULL,
    target_user_id INT NOT NULL,
    permission VARCHAR(20) NOT NULL, -- 'view', 'edit', atau 'admin'
    status VARCHAR(20) NOT NULL DEFAULT 'pending', -- 'pending', 'accepted', 'rejected'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (list_id) REFERENCES lists(list_id),
    FOREIGN KEY (sender_id) REFERENCES users(user_id),
    FOREIGN KEY (target_user_id) REFERENCES users(user_id)
);

-- Tabel untuk menyimpan kolaborator yang sudah disetujui
CREATE TABLE list_collaborators (
    collaborator_id INT PRIMARY KEY AUTO_INCREMENT,
    list_id INT NOT NULL,
    user_id INT NOT NULL,
    permission VARCHAR(20) NOT NULL, -- 'view', 'edit', atau 'admin'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (list_id) REFERENCES lists(list_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    UNIQUE KEY unique_collaboration (list_id, user_id)
);

-- Dummy Data untuk Todo List Aplikasi Universitas

-- 1. Users (Pejabat Universitas)
INSERT INTO users (username, email, password, full_name, role) VALUES
('rektor', 'rektor@univ.ac.id', '$2y$10$NfuJ34DB1UtgWbo2WZxgyu.qYJ7Ya3PYogrkT4RiwpKCYyaBlCNUK', 'Prof. Dr. Ahmad Sudirman, M.Sc.', 'admin'),
('wr1', 'wr1@univ.ac.id', '$2y$10$NfuJ34DB1UtgWbo2WZxgyu.qYJ7Ya3PYogrkT4RiwpKCYyaBlCNUK', 'Dr. Budi Santoso, M.Pd.', 'regular'),
('wr2', 'wr2@univ.ac.id', '$2y$10$NfuJ34DB1UtgWbo2WZxgyu.qYJ7Ya3PYogrkT4RiwpKCYyaBlCNUK', 'Prof. Dr. Siti Aminah, M.M.', 'regular'),
('wr3', 'wr3@univ.ac.id', '$2y$10$NfuJ34DB1UtgWbo2WZxgyu.qYJ7Ya3PYogrkT4RiwpKCYyaBlCNUK', 'Dr. Hendra Wijaya, M.Kom.', 'regular'),
('dekanfti', 'dekanfti@univ.ac.id', '$2y$10$NfuJ34DB1UtgWbo2WZxgyu.qYJ7Ya3PYogrkT4RiwpKCYyaBlCNUK', 'Dr. Rudi Hartono, M.T.', 'regular'),
('dekanfeb', 'dekanfeb@univ.ac.id', '$2y$10$NfuJ34DB1UtgWbo2WZxgyu.qYJ7Ya3PYogrkT4RiwpKCYyaBlCNUK', 'Dr. Dewi Anggraini, M.Sc.', 'regular'),
('kaprodi_ti', 'kaprodi_ti@univ.ac.id', '$2y$10$NfuJ34DB1UtgWbo2WZxgyu.qYJ7Ya3PYogrkT4RiwpKCYyaBlCNUK', 'Dr. Joko Susilo, M.Kom.', 'regular'),
('kabag_aka', 'kabag_aka@univ.ac.id', '$2y$10$NfuJ34DB1UtgWbo2WZxgyu.qYJ7Ya3PYogrkT4RiwpKCYyaBlCNUK', 'Indra Maulana, M.M.', 'regular');
-- Note: Semua password adalah 'password'

-- 2. Lists untuk Rektor
INSERT INTO lists (user_id, title, description, color, icon) VALUES
(1, 'Rapat Pimpinan', 'Agenda rapat pimpinan universitas', '#e74c3c', 'university'),
(1, 'Kebijakan Strategis', 'Kebijakan dan arah pengembangan universitas', '#3498db', 'book'),
(1, 'Kerjasama', 'Kerja sama dengan pihak eksternal', '#2ecc71', 'handshake'),
(1, 'Akreditasi', 'Persiapan dan monitoring akreditasi institusi', '#9b59b6', 'certificate');

-- 3. Lists untuk Wakil Rektor 1 (Akademik)
INSERT INTO lists (user_id, title, description, color, icon) VALUES
(2, 'Kurikulum', 'Pengembangan kurikulum semua prodi', '#f39c12', 'book-open'),
(2, 'Kalender Akademik', 'Penetapan dan monitoring kalender akademik', '#1abc9c', 'calendar'),
(2, 'Penelitian Dosen', 'Monitoring dan evaluasi penelitian dosen', '#34495e', 'microscope');

-- 4. Lists untuk Wakil Rektor 2 (Keuangan)
INSERT INTO lists (user_id, title, description, color, icon) VALUES
(3, 'Anggaran', 'Perencanaan dan monitoring anggaran universitas', '#e74c3c', 'money-bill'),
(3, 'Aset', 'Pengelolaan aset universitas', '#3498db', 'building'),
(3, 'SDM', 'Pengembangan sumber daya manusia', '#2ecc71', 'users');

-- 5. Lists untuk Wakil Rektor 3 (Kemahasiswaan)
INSERT INTO lists (user_id, title, description, color, icon) VALUES
(4, 'Kegiatan Mahasiswa', 'Monitoring kegiatan kemahasiswaan', '#9b59b6', 'graduation-cap'),
(4, 'Beasiswa', 'Pengelolaan program beasiswa', '#f39c12', 'dollar-sign'),
(4, 'Alumni', 'Hubungan dengan alumni', '#1abc9c', 'users');

-- 6. Lists untuk Dekan FTI
INSERT INTO lists (user_id, title, description, color, icon) VALUES
(5, 'Rapat Fakultas', 'Agenda rapat fakultas', '#e74c3c', 'users'),
(5, 'Kegiatan Akademik', 'Monitoring kegiatan akademik fakultas', '#3498db', 'book'),
(5, 'Lab dan Fasilitas', 'Pengelolaan laboratorium dan fasilitas', '#2ecc71', 'flask');

-- 7. Tags untuk Pimpinan
INSERT INTO tags (user_id, name, color) VALUES
(1, 'Penting', '#e74c3c'),
(1, 'Mendesak', '#f39c12'),
(1, 'Strategis', '#3498db'),
(1, 'Evaluasi', '#2ecc71'),
(1, 'Pengembangan', '#9b59b6'),
(2, 'Akademik', '#e74c3c'),
(2, 'Kurikulum', '#3498db'),
(2, 'Penelitian', '#2ecc71'),
(3, 'Anggaran', '#e74c3c'),
(3, 'Aset', '#3498db'),
(3, 'SDM', '#2ecc71'),
(4, 'Mahasiswa', '#e74c3c'),
(4, 'Beasiswa', '#3498db'),
(4, 'Alumni', '#2ecc71');

-- 8. Tasks untuk Rektor - Rapat Pimpinan
INSERT INTO tasks (list_id, user_id, title, description, priority, status, due_date) VALUES
(1, 1, 'Rapat Koordinasi Pimpinan', 'Rapat koordinasi dengan semua wakil rektor dan dekan', 'high', 'pending', DATE_ADD(CURRENT_DATE, INTERVAL 3 DAY)),
(1, 1, 'Rapat Senat Universitas', 'Pembahasan kebijakan strategis universitas', 'high', 'pending', DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY)),
(1, 1, 'Review Capaian Semester', 'Evaluasi capaian kinerja universitas semester lalu', 'medium', 'completed', DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY)),
(1, 1, 'Rapat Kerja Tahunan', 'Persiapan rapat kerja tahunan universitas', 'high', 'in_progress', DATE_ADD(CURRENT_DATE, INTERVAL 14 DAY));

-- 9. Tasks untuk Rektor - Kebijakan Strategis
INSERT INTO tasks (list_id, user_id, title, description, priority, status, due_date) VALUES
(2, 1, 'Finalisasi Renstra', 'Finalisasi dokumen rencana strategis 5 tahun', 'high', 'in_progress', DATE_ADD(CURRENT_DATE, INTERVAL 10 DAY)),
(2, 1, 'Kebijakan Remunerasi', 'Review dan finalisasi kebijakan remunerasi baru', 'medium', 'pending', DATE_ADD(CURRENT_DATE, INTERVAL 20 DAY)),
(2, 1, 'Kebijakan Pengembangan Kampus', 'Finalisasi masterplan pengembangan kampus', 'high', 'pending', DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY));

-- 10. Tasks untuk Wakil Rektor 1 - Kurikulum
INSERT INTO tasks (list_id, user_id, title, description, priority, status, due_date) VALUES
(5, 2, 'Review Kurikulum Berbasis MBKM', 'Evaluasi implementasi kurikulum Merdeka Belajar Kampus Merdeka', 'high', 'in_progress', DATE_ADD(CURRENT_DATE, INTERVAL 5 DAY)),
(5, 2, 'Workshop OBE', 'Pelaksanaan workshop Outcome-Based Education untuk dosen', 'medium', 'pending', DATE_ADD(CURRENT_DATE, INTERVAL 15 DAY)),
(5, 2, 'Penyusunan Panduan Akademik', 'Finalisasi buku panduan akademik tahun ajaran baru', 'high', 'pending', DATE_ADD(CURRENT_DATE, INTERVAL 10 DAY));

-- 11. Tasks untuk Wakil Rektor 2 - Anggaran
INSERT INTO tasks (list_id, user_id, title, description, priority, status, due_date) VALUES
(8, 3, 'Rapat Anggaran Tahunan', 'Pembahasan RKAT dengan seluruh unit', 'high', 'pending', DATE_ADD(CURRENT_DATE, INTERVAL 2 DAY)),
(8, 3, 'Evaluasi Realisasi Anggaran', 'Evaluasi realisasi anggaran semester berjalan', 'medium', 'in_progress', DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY)),
(8, 3, 'Penyusunan Anggaran Tahun Berikutnya', 'Persiapan penyusunan anggaran tahun depan', 'medium', 'pending', DATE_ADD(CURRENT_DATE, INTERVAL 25 DAY));

-- 12. Tasks untuk Wakil Rektor 3 - Kemahasiswaan
INSERT INTO tasks (list_id, user_id, title, description, priority, status, due_date) VALUES
(11, 4, 'Persiapan PKKMB', 'Persiapan Pengenalan Kehidupan Kampus bagi Mahasiswa Baru', 'high', 'in_progress', DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY)),
(11, 4, 'Monitoring UKM', 'Evaluasi kegiatan Unit Kegiatan Mahasiswa semester lalu', 'medium', 'pending', DATE_ADD(CURRENT_DATE, INTERVAL 5 DAY)),
(11, 4, 'Seleksi Beasiswa', 'Pelaksanaan seleksi beasiswa unggulan', 'high', 'pending', DATE_ADD(CURRENT_DATE, INTERVAL 14 DAY));

-- 13. Tasks untuk Dekan FTI
INSERT INTO tasks (list_id, user_id, title, description, priority, status, due_date) VALUES
(14, 5, 'Rapat Pimpinan Fakultas', 'Koordinasi dengan kaprodi dan sekretaris fakultas', 'high', 'pending', DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY)),
(14, 5, 'Persiapan Akreditasi', 'Persiapan dokumen akreditasi prodi Teknik Informatika', 'high', 'in_progress', DATE_ADD(CURRENT_DATE, INTERVAL 10 DAY)),
(14, 5, 'Evaluasi Dosen', 'Review hasil evaluasi kinerja dosen', 'medium', 'pending', DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY));

-- 14. Subtasks untuk Rektor
INSERT INTO subtasks (task_id, title, status) VALUES
(1, 'Persiapan bahan rapat', 'completed'),
(1, 'Koordinasi dengan sekretariat', 'completed'),
(1, 'Undangan peserta rapat', 'pending'),
(2, 'Persiapan dokumen kebijakan', 'pending'),
(2, 'Koordinasi dengan sekretaris senat', 'pending'),
(4, 'Pengumpulan bahan dari wakil rektor', 'completed'),
(4, 'Draft agenda rapat kerja', 'in_progress');

-- 15. Comments
INSERT INTO comments (task_id, user_id, content) VALUES
(1, 1, 'Mohon dipersiapkan data capaian kinerja dari masing-masing bidang'),
(1, 2, 'Dokumen akademik sudah saya siapkan, Pak Rektor'),
(2, 1, 'Undangan sudah ditandatangani dan didistribusikan'),
(5, 2, 'Perlu masukan dari semua kaprodi terkait implementasi MBKM'),
(8, 3, 'Minta data realisasi anggaran dari masing-masing fakultas');

-- 16. Task Tags
INSERT INTO task_tags (task_id, tag_id) VALUES
(1, 1), -- Rapat Koordinasi - Penting
(1, 2), -- Rapat Koordinasi - Mendesak
(2, 1), -- Rapat Senat - Penting
(2, 3), -- Rapat Senat - Strategis
(4, 3), -- Rapat Kerja - Strategis
(5, 3), -- Finalisasi Renstra - Strategis
(5, 5), -- Finalisasi Renstra - Pengembangan
(8, 9), -- Rapat Anggaran - Anggaran
(11, 12), -- Persiapan PKKMB - Mahasiswa
(13, 13); -- Seleksi Beasiswa - Beasiswa

-- 17. Activity Logs
INSERT INTO activity_logs (user_id, action_type, entity_type, entity_id, details) VALUES
(1, 'create', 'list', 1, 'Membuat daftar Rapat Pimpinan'),
(1, 'create', 'task', 1, 'Membuat tugas: Rapat Koordinasi Pimpinan'),
(1, 'update', 'task', 3, 'Menyelesaikan tugas: Review Capaian Semester'),
(2, 'create', 'list', 5, 'Membuat daftar Kurikulum'),
(2, 'create', 'task', 10, 'Membuat tugas: Review Kurikulum Berbasis MBKM');

-- 18. Notifications
INSERT INTO notifications (user_id, title, message, type, entity_type, entity_id) VALUES
(1, 'Tugas Mendatang', 'Rapat Koordinasi Pimpinan akan berlangsung dalam 3 hari', 'reminder', 'task', 1),
(2, 'Tugas Mendatang', 'Review Kurikulum Berbasis MBKM dalam 5 hari', 'reminder', 'task', 10),
(3, 'Tugas Hari Ini', 'Rapat Anggaran Tahunan dijadwalkan hari ini', 'reminder', 'task', 11),
(5, 'Tugas Besok', 'Rapat Pimpinan Fakultas dijadwalkan besok', 'reminder', 'task', 14);


CREATE TABLE app_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT,
    setting_description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (updated_by) REFERENCES users(user_id)
);

-- Insert default settings
INSERT INTO app_settings (setting_key, setting_value, setting_description) VALUES
('app_name', 'UKK TODOLIST', 'Nama aplikasi'),
('app_version', '1.0.0', 'Versi aplikasi'),
('base_url', 'http://localhost:8002', 'URL dasar aplikasi'),
('timezone', 'Asia/Jakarta', 'Zona waktu aplikasi'),
('upload_max_size', '2', 'Ukuran maksimal upload dalam MB'),
('allowed_file_types', 'jpg,jpeg,png,gif,webp', 'Tipe file yang diizinkan untuk upload'),
('maintenance_mode', '0', 'Mode maintenance (0=off, 1=on)'),
('maintenance_message', 'Sistem sedang dalam pemeliharaan. Silakan coba beberapa saat lagi.', 'Pesan maintenance');