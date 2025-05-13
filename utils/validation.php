<?php
// utils/validation.php - Fungsi validasi input

// Validasi format email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validasi username (alfanumerik, 3-20 karakter)
function isValidUsername($username) {
    return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
}

// Validasi kekuatan password
function isValidPassword($password) {
    // Minimal 8 karakter, minimal 1 huruf besar, 1 huruf kecil, dan 1 angka
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{8,}$/', $password);
}

// Validasi format tanggal (YYYY-MM-DD)
function isValidDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

// Validasi format datetime (YYYY-MM-DD HH:MM:SS)
function isValidDateTime($datetime) {
    $d = DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
    return $d && $d->format('Y-m-d H:i:s') === $datetime;
}

// Menghasilkan array error validasi
function validateRegistration($data) {
    $errors = [];
    
    if (empty($data['username'])) {
        $errors['username'] = 'Username wajib diisi';
    } elseif (!isValidUsername($data['username'])) {
        $errors['username'] = 'Username harus 3-20 karakter dan hanya boleh mengandung huruf, angka, dan garis bawah';
    }
    
    if (empty($data['email'])) {
        $errors['email'] = 'Email wajib diisi';
    } elseif (!isValidEmail($data['email'])) {
        $errors['email'] = 'Masukkan alamat email yang valid';
    }
    
    if (empty($data['password'])) {
        $errors['password'] = 'Password wajib diisi';
    } elseif (!isValidPassword($data['password'])) {
        $errors['password'] = 'Password harus minimal 8 karakter dengan minimal satu huruf besar, satu huruf kecil, dan satu angka';
    }
    
    if (empty($data['confirm_password'])) {
        $errors['confirm_password'] = 'Konfirmasi password wajib diisi';
    } elseif ($data['password'] !== $data['confirm_password']) {
        $errors['confirm_password'] = 'Password tidak cocok';
    }
    
    if (empty($data['full_name'])) {
        $errors['full_name'] = 'Nama lengkap wajib diisi';
    }
    
    return $errors;
}

// Validasi data login
function validateLogin($data) {
    $errors = [];
    
    if (empty($data['username'])) {
        $errors['username'] = 'Username atau email wajib diisi';
    }
    
    if (empty($data['password'])) {
        $errors['password'] = 'Password wajib diisi';
    }
    
    return $errors;
}

// Validasi data tugas
function validateTask($data) {
    $errors = [];
    
    if (empty($data['title'])) {
        $errors['title'] = 'Judul tugas wajib diisi';
    }
    
    if (!empty($data['due_date']) && !isValidDate($data['due_date'])) {
        $errors['due_date'] = 'Format tanggal tidak valid. Gunakan format YYYY-MM-DD';
    }
    
    if (!empty($data['reminder']) && !isValidDateTime($data['reminder'])) {
        $errors['reminder'] = 'Format tanggal dan waktu tidak valid. Gunakan format YYYY-MM-DD HH:MM:SS';
    }
    
    return $errors;
}

// Validasi data daftar
function validateList($data) {
    $errors = [];
    
    if (empty($data['title'])) {
        $errors['title'] = 'Judul daftar wajib diisi';
    }
    
    return $errors;
}