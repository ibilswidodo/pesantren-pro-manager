<?php
/**
 * Plugin Name: Pesantren Pro Manager
 * Version: 1.1
 *copyright Alifbata Digital
 */

if (!defined('ABSPATH')) exit;

define('PPM_DIR_PATH', plugin_dir_path(__FILE__));

// --- 1. REGISTRASI MENU INDUK ---
// Kita gunakan prioritas 10 agar menu utama tercipta dulu
add_action('admin_menu', 'ppm_register_main_menu', 10);

function ppm_register_main_menu() {
    add_menu_page(
        'Pesantren Pro',           // Browser Title
        'Pesantren Pro',           // Menu Title
        'manage_options',          // Capability
        'ppm-dashboard',           // Menu Slug (PENTING: Harus sama dengan yang di submenu)
        'ppm_dashboard_callback',  // Fungsi render
        'dashicons-mortarboard',   // Icon
        6                          // Posisi
    );
}

function ppm_dashboard_callback() {
    echo '<div class="wrap"><h1 class="text-2xl font-bold mt-5">Selamat Datang di Pesantren Pro Manager v1.1</h1><p class="mt-2 text-gray-600">Gunakan menu di samping untuk mengelola data pesantren.</p></div>';
}

// --- 2. LOAD MODUL (SUBMENU) ---
// File-file ini akan mendaftarkan submenu ke 'ppm-dashboard'
require_once PPM_DIR_PATH . 'includes/modul-santri.php';
require_once PPM_DIR_PATH . 'includes/modul-keuangan.php';
require_once PPM_DIR_PATH . 'includes/modul-ppdb.php';
require_once PPM_DIR_PATH . 'includes/modul-import.php';
require_once PPM_DIR_PATH . 'includes/modul-kartu.php';
require_once PPM_DIR_PATH . 'includes/modul-laporan.php';

// --- 3. LOAD ASSETS (TAILWIND) ---
add_action('admin_enqueue_scripts', function($hook) {
    if (strpos($hook, 'ppm-') !== false) {
        wp_enqueue_script('tailwind', 'https://cdn.tailwindcss.com');
        wp_enqueue_style('inter-font', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
    }
});
