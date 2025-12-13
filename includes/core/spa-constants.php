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
 * - SPA_INCLUDES
 * 
 * FUNCTIONS DEFINED: žiadne
 * 
 * DATABASE TABLES: žiadne
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SPA_VERSION', '26.1.0');
define('SPA_PATH', get_stylesheet_directory());
define('SPA_URL', get_stylesheet_directory_uri());
define('SPA_INCLUDES', SPA_PATH . '/includes/');

// Artefactum support
if (defined('ARTEFACTUM_COMMON')) {
    define('ARTEFACTUM_ACTIVE', true);
} else {
    define('ARTEFACTUM_ACTIVE', false);
}