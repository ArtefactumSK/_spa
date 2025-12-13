<?php
/**
 * SPA Constants - Základné konštanty a konfigúrácia
 * 
 * @package Samuel Piasecký ACADEMY
 * @subpackage Core
 * @version 1.0.0
 * 
 * PARENT MODULES: žiadne
 * CHILD MODULES: spa-core/spa-roles.php, všetky ostatné
 * 
 * GLOBAL VARIABLES DECLARED:
 * - SPA_VERSION
 * - SPA_PATH
 * - SPA_URL
 * - SPA_INCLUDES (ak ešte nie je definovaná)
 * 
 * FUNCTIONS DEFINED: žiadne
 * 
 * DATABASE TABLES: žiadne
 */

if (!defined('ABSPATH')) {
    exit;
}

// === KONŠTANTY ===
if (!defined('SPA_VERSION')) {
    define('SPA_VERSION', '26.1.0');
}

if (!defined('SPA_PATH')) {
    define('SPA_PATH', get_stylesheet_directory());
}

if (!defined('SPA_URL')) {
    define('SPA_URL', get_stylesheet_directory_uri());
}

if (!defined('SPA_INCLUDES')) {
    define('SPA_INCLUDES', SPA_PATH . '/includes/');
}

// === ARTEFACTUM SUPPORT ===
if (!defined('ARTEFACTUM_ACTIVE')) {
    if (defined('ARTEFACTUM_COMMON')) {
        define('ARTEFACTUM_ACTIVE', true);
    } else {
        define('ARTEFACTUM_ACTIVE', false);
    }
}