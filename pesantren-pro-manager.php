<?php
/**
 * Plugin Name: Pesantren Pro Manager
 * Description: Sistem Manajemen Pondok Pesantren Modern (Tahap 1: Data Santri & UI Foundation)
 * Version: 1.0
 * Author: Alifbata Digital
 */

if (!defined('ABSPATH')) exit;

// 1. Inisialisasi Plugin & Tailwind CSS untuk Admin
add_action('admin_enqueue_scripts', 'ppm_enqueue_admin_assets');
function ppm_enqueue_admin_assets($hook) {
    // Hanya load di halaman plugin kita agar tidak bentrok dengan plugin lain
    if (strpos($hook, 'santri') !== false || strpos($hook, 'pesantren') !== false) {
        wp_enqueue_script('tailwind-cdn', 'https://cdn.tailwindcss.com');
        wp_enqueue_style('google-fonts-inter', 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap');
    }
}

// 2. Registrasi Custom Post Type: Santri
add_action('init', 'ppm_register_santri_cpt');
function ppm_register_santri_cpt() {
    $labels = [
        'name' => 'Santri',
        'singular_name' => 'Santri',
        'menu_name' => 'Pesantren Pro',
        'add_new' => 'Tambah Santri Baru',
    ];

    $args = [
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-mortarboard', // Icon Topi Toga
        'supports' => ['title'], // Kita pakai meta box untuk field lengkapnya
        'show_in_rest' => true,
    ];
    register_post_type('santri', $args);
}

// 3. Menambahkan Meta Box (Form Input Detail Santri)
add_action('add_meta_boxes', 'ppm_santri_meta_boxes');
function ppm_santri_meta_boxes() {
    add_meta_box('ppm_detail_santri', 'Data Lengkap Santri', 'ppm_callback_santri_details', 'santri', 'normal', 'high');
}

function ppm_callback_santri_details($post) {
    $nis = get_post_meta($post->ID, '_ppm_nis', true);
    $wa_wali = get_post_meta($post->ID, '_ppm_wa_wali', true);
    $kamar = get_post_meta($post->ID, '_ppm_kamar', true);
    $kelas = get_post_meta($post->ID, '_ppm_kelas', true);
    
    // Nonce untuk keamanan
    wp_nonce_field('ppm_save_santri_meta', 'ppm_santri_nonce');

    // Tampilan Form dengan Tailwind
    echo '
    <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-6 font-["Inter"]">
        <div class="flex flex-col gap-2">
            <label class="font-semibold text-gray-700">Nomor Induk Santri (NIS)</label>
            <input type="text" name="ppm_nis" value="'.esc_attr($nis).'" class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-emerald-500" placeholder="Contoh: 20240001">
        </div>
        <div class="flex flex-col gap-2">
            <label class="font-semibold text-gray-700">WhatsApp Wali (Format: 628xxx)</label>
            <input type="text" name="ppm_wa_wali" value="'.esc_attr($wa_wali).'" class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-emerald-500" placeholder="628123456789">
        </div>
        <div class="flex flex-col gap-2">
            <label class="font-semibold text-gray-700">Nama Kamar / Asrama</label>
            <input type="text" name="ppm_kamar" value="'.esc_attr($kamar).'" class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-emerald-500">
        </div>
        <div class="flex flex-col gap-2">
            <label class="font-semibold text-gray-700">Jenjang Kelas</label>
            <select name="ppm_kelas" class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-emerald-500">
                <option value="Ula" '.selected($kelas, 'Ula', false).'>Ula (Dasar)</option>
                <option value="Wustho" '.selected($kelas, 'Wustho', false).'>Wustho (Menengah)</option>
                <option value="Ulya" '.selected($kelas, 'Ulya', false).'>Ulya (Atas)</option>
            </select>
        </div>
    </div>';
}

// 4. Menyimpan Data Meta Box
add_action('save_post', 'ppm_save_santri_details');
function ppm_save_santri_details($post_id) {
    if (!isset($_POST['ppm_santri_nonce']) || !wp_verify_nonce($_POST['ppm_santri_nonce'], 'ppm_save_santri_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    $fields = ['ppm_nis', 'ppm_wa_wali', 'ppm_kamar', 'ppm_kelas'];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
        }
    }
}
