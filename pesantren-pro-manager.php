<?php
/**
 * Plugin Name: Pesantren Pro Manager (Modular)
 * Description: Manajemen Pesantren Pro
 * Version: 1.1
 * Copyright Alifbata Digital
 */

if (!defined('ABSPATH')) exit;

// Shortcut Path
define('PPM_DIR', plugin_dir_path(__FILE__));

// 1. Load Semua Modul Secara Otomatis
require_once PPM_DIR . 'includes/modul-santri.php';
require_once PPM_DIR . 'includes/modul-keuangan.php';
require_once PPM_DIR . 'includes/modul-ppdb.php';
require_once PPM_DIR . 'includes/modul-import.php';

// 2. Load CSS & Font untuk Dashboard Modern
add_action('admin_enqueue_scripts', function($hook) {
    // Kita load Tailwind hanya jika berada di menu Pesantren Pro
    if (strpos($hook, 'ppm-') !== false || strpos($hook, 'santri') !== false) {
        wp_enqueue_script('tailwind', 'https://cdn.tailwindcss.com');
        wp_enqueue_style('inter-font', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
    }
});
