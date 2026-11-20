<?php
/**
 * Plugin Name: EC Connect WordPress
 * Description: Průvodce připojením webu k EC Hubu. Založí uživatele „ec-hub“ a heslo aplikace WordPressu. EC Hub pak používá jen standardní REST API WordPressu – plugin nemá vlastní API ani přihlašování a práva hlídá WordPress.
 * Version: 0.6.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Euroclean
 * License: GPLv2 or later
 * Update URI: https://github.com/Euroclean/ec-connect-wordpress
 */

if (!defined('ABSPATH')) {
    exit;
}

const ECHC_VERSION = '0.6.0';
const ECHC_REPO = 'https://github.com/Euroclean/ec-connect-wordpress';
const ECHC_UPDATE_JSON = 'https://raw.githubusercontent.com/Euroclean/ec-connect-wordpress/main/update.json';
const ECHC_UPDATE_CACHE = 'ec_hub_connect_update';
const ECHC_OPT_UNFILTERED = 'ec_hub_connect_unfiltered_html';
const ECHC_OPT_STEC = 'ec_hub_connect_stec';
const ECHC_LOGIN = 'ec-hub';
const ECHC_APP_NAME = 'EC Hub';
const ECHC_APP_ID = '7c1e4f52-2b9d-4a63-8f0e-5d3a9b6c1e27';
const ECHC_MARK = 'ec_hub_connect_managed';
const ECHC_OPT_TYPES = 'ec_hub_connect_rest_types';
const ECHC_OPT_TAXONOMIES = 'ec_hub_connect_rest_taxonomies';
const ECHC_ROLES = array('contributor', 'author', 'editor');

/* ---------- Zpřístupnění vybraných typů obsahu a taxonomií v REST API (oficiální volba show_in_rest) ---------- */

function echc_selected($option)
{
    $value = get_option($option, array());
    return is_array($value) ? array_values(array_filter($value, 'is_string')) : array();
}

/** Jen veřejné typy a taxonomie: show_in_rest zpřístupní zveřejněné položky každému čtenáři API. */
function echc_is_public($args)
{
    return !empty($args['public']) || !empty($args['publicly_queryable']);
}

add_filter('register_post_type_args', static function ($args, $name) {
    if (in_array($name, echc_selected(ECHC_OPT_TYPES), true) && empty($args['show_in_rest']) && echc_is_public($args)) {
        $args['show_in_rest'] = true;
    }
    return $args;
}, 20, 2);

add_filter('register_taxonomy_args', static function ($args, $name) {
    if (in_array($name, echc_selected(ECHC_OPT_TAXONOMIES), true) && empty($args['show_in_rest']) && echc_is_public($args)) {
        $args['show_in_rest'] = true;
    }
    return $args;
}, 20, 2);

// Typ zpřístupněný pluginem si v administraci ponechá dosavadní editor (např. WPBakery), nepřepne se na bloky.
add_filter('use_block_editor_for_post_type', static function ($use, $post_type) {
    return in_array($post_type, echc_selected(ECHC_OPT_TYPES), true) ? false : $use;
}, 20, 2);

/* ---------- Yoast SEO: titulek, popis a klíčové slovo v REST API (0.3.0) ---------- */

/** Pole Yoast SEO, která EC Hub zapisuje. Yoast je sám v REST API nevystavuje k zápisu. */
function echc_seo_fields()
{
    return array(
        '_yoast_wpseo_title'    => 'sanitize_text_field',
        '_yoast_wpseo_metadesc' => 'sanitize_textarea_field',
        '_yoast_wpseo_focuskw'  => 'sanitize_text_field',
    );
}

function echc_yoast_active()
{
    return defined('WPSEO_VERSION');
}

// Oficiální registrace meta polí: zapisovat smí jen ten, kdo smí upravit daný příspěvek (edit_post). Platí pro všechny typy obsahu.
add_action('init', static function () {
    if (!echc_yoast_active()) {
        return;
    }
    foreach (echc_seo_fields() as $key => $sanitize) {
        if (registered_meta_key_exists('post', $key, '')) {
            continue;
        }
        register_post_meta('', $key, array(
            'type'              => 'string',
            'single'            => true,
            // Jen v kontextu úprav: anonymní čtenář API pole neuvidí (klíčové slovo není veřejné).
            'show_in_rest'      => array('schema' => array('type' => 'string', 'context' => array('edit'))),
            'sanitize_callback' => $sanitize,
            'auth_callback'     => static function ($allowed, $meta_key, $post_id, $user_id) {
                return user_can($user_id, 'edit_post', $post_id);
            },
        ));
    }
}, 30);

/* ---------- Adaptér: kalendář Stachethemes (0.4.0) ----------
 * Stachethemes má vlastní práva: kdo smí spravovat akce (výchozí jen administrátor) a u každé akce „kdo smí upravovat“.
 * Když to správce webu zapne, plugin uživateli ec-hub udělí práva přes veřejné filtry Stachethemes
 * (manage_stec_* a stec_user_permissions_list). Na akcích ani v nastavení kalendáře se nic nepřepisuje.
 * Ověřeno se Stachethemes Event Calendar 5.x; jiná hlavní verze se nenapojí a stránka nastavení ukáže varování. */

function echc_stec_version()
{
    return (defined('STEC_PLUGIN_VERSION') && class_exists('\\Stachethemes\\Stec\\Permissions')) ? (string) STEC_PLUGIN_VERSION : '';
}

function echc_stec_supported()
{
    $v = echc_stec_version();
    return $v !== '' && version_compare($v, '5.0', '>=') && version_compare($v, '6.0', '<');
}

function echc_stec_enabled_for($user_id)
{
    return $user_id > 0 && get_option(ECHC_OPT_STEC) === '1' && echc_stec_supported() && echc_is_managed($user_id);
}

foreach (array('manage_stec_event', 'manage_stec_cal', 'manage_stec_cat', 'manage_stec_loc', 'manage_stec_org', 'manage_stec_gst') as $echc_filter) {
    add_filter($echc_filter, static function ($allowed, $user_id = 0) {
        return echc_stec_enabled_for((int) $user_id) ? 1 : $allowed;
    }, 20, 2);
}
unset($echc_filter);

/**
 * Oprávnění, která se v kalendáři vyskytují (role, „přihlášení“, autoři akcí a položek kalendáře).
 * „Soukromé“ znamená u Stachethemes autora (stec_user_ID), proto se přidávají i autoři.
 */
function echc_stec_tokens()
{
    static $tokens = null;
    if ($tokens !== null) {
        return $tokens;
    }
    global $wpdb;
    $taxonomies = "'stec_cal','stec_cat','stec_loc','stec_org','stec_gst'";
    $values = array_merge(
        (array) $wpdb->get_col("SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.post_type = 'stec_event' AND pm.meta_key IN ('edit_permission','read_permission') LIMIT 500"),
        (array) $wpdb->get_col("SELECT DISTINCT tm.meta_value FROM {$wpdb->termmeta} tm JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = tm.term_id WHERE tt.taxonomy IN ($taxonomies) AND tm.meta_key IN ('edit_permission','use_permission','read_permission') LIMIT 500")
    );
    $authors = array_merge(
        (array) $wpdb->get_col("SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.post_type = 'stec_event' AND pm.meta_key = 'author' LIMIT 500"),
        (array) $wpdb->get_col("SELECT DISTINCT tm.meta_value FROM {$wpdb->termmeta} tm JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = tm.term_id WHERE tt.taxonomy IN ($taxonomies) AND tm.meta_key = 'author' LIMIT 500")
    );
    $tokens = array();
    foreach ($values as $value) {
        if (is_string($value) && $value !== 'stec_private' && preg_match('/^[a-z0-9_-]{1,60}$/', $value)) {
            $tokens[] = $value;
        }
    }
    foreach ($authors as $author) {
        if (is_numeric($author) && (int) $author > 0) {
            $tokens[] = 'stec_user_' . (int) $author;
        }
    }
    $tokens = array_values(array_unique($tokens));
    return $tokens;
}

add_filter('stec_user_permissions_list', static function ($list, $user_id = 0) {
    if (!echc_stec_enabled_for((int) $user_id) || !is_array($list)) {
        return $list;
    }
    return array_values(array_unique(array_merge($list, echc_stec_tokens())));
}, 20, 2);

/* ---------- Pojistka oprávnění uživatele ec-hub (0.5.0) ----------
 * Oficiální filtr user_has_cap: ec-hub nikdy nesmí spravovat web (pluginy, šablony, uživatelé, nastavení, soubory,
 * aktualizace, import/export), ani kdyby mu někdo zvýšil roli. A ve výchozím stavu nesmí ukládat nefiltrované HTML
 * (skripty, vložené rámy) – WordPress pak jeho obsah čistí stejně jako u role Autor. */

function echc_denied_caps()
{
    return array(
        'manage_options', 'activate_plugins', 'install_plugins', 'update_plugins', 'delete_plugins', 'edit_plugins', 'upload_plugins', 'resume_plugins',
        'install_themes', 'update_themes', 'delete_themes', 'edit_themes', 'switch_themes', 'edit_theme_options', 'upload_themes', 'resume_themes', 'customize',
        'create_users', 'edit_users', 'delete_users', 'promote_users', 'list_users', 'remove_users', 'add_users',
        'update_core', 'update_languages', 'install_languages', 'edit_files', 'unfiltered_upload', 'export', 'import',
        'manage_network', 'manage_network_options', 'manage_network_plugins', 'manage_network_themes', 'manage_network_users', 'manage_sites',
        'create_sites', 'delete_sites', 'setup_network', 'view_site_health_checks', 'erase_others_personal_data', 'export_others_personal_data',
    );
}

function echc_unfiltered_allowed()
{
    return get_option(ECHC_OPT_UNFILTERED) === '1';
}

add_filter('user_has_cap', static function ($allcaps, $caps, $args, $user = null) {
    if (!is_object($user) || empty($user->ID) || !echc_is_managed($user->ID)) {
        return $allcaps;
    }
    foreach (echc_denied_caps() as $cap) {
        $allcaps[$cap] = false;
    }
    if (!echc_unfiltered_allowed()) {
        $allcaps['unfiltered_html'] = false;
    }
    return $allcaps;
}, 999, 4);

// Hub si u svého uživatele přečte verzi pluginu a nastavení (jen sám o sobě, jen v kontextu úprav).
add_action('rest_api_init', static function () {
    register_rest_field('user', 'ec_hub_connect', array(
        'get_callback' => static function ($user) {
            $id = is_array($user) && isset($user['id']) ? (int) $user['id'] : 0;
            if ($id < 1 || $id !== get_current_user_id() || !echc_is_managed($id)) {
                return null;
            }
            return array(
                'version'         => ECHC_VERSION,
                'unfiltered_html' => echc_unfiltered_allowed(),
                'calendar'        => get_option(ECHC_OPT_STEC) === '1' && echc_stec_supported(),
            );
        },
        'schema' => array('description' => 'EC Connect WordPress', 'type' => array('object', 'null'), 'context' => array('edit'), 'readonly' => true),
    ));
});

// 0.5.1: každá odpověď REST API pro ec-hub nese aktuální nastavení – Hub ho zná bez dotazu navíc.
add_filter('rest_post_dispatch', static function ($response) {
    $id = get_current_user_id();
    if ($id < 1 || !echc_is_managed($id) || !is_object($response) || !method_exists($response, 'header')) {
        return $response;
    }
    $user = get_userdata($id);
    $admin = $user && (in_array('administrator', (array) $user->roles, true) || !empty($user->allcaps['manage_options']));
    $response->header('X-EC-Hub-Connect', sprintf('v=%s;unfiltered=%d;calendar=%d;admin=%d', ECHC_VERSION,
        echc_unfiltered_allowed() ? 1 : 0, (get_option(ECHC_OPT_STEC) === '1' && echc_stec_supported()) ? 1 : 0, $admin ? 1 : 0));
    return $response;
}, 99);

/* ---------- Uživatel ec-hub: jen pro heslo aplikace, nikdy pro přihlášení formulářem ---------- */

function echc_is_managed($user_id)
{
    return get_user_meta((int) $user_id, ECHC_MARK, true) === '1';
}

function echc_user()
{
    $user = get_user_by('login', ECHC_LOGIN);
    return ($user && echc_is_managed($user->ID)) ? $user : null;
}

// Heslo aplikace se ověřuje jinou cestou (wp_authenticate_application_password), tento filtr ho neovlivní.
add_filter('wp_authenticate_user', static function ($user) {
    if ($user instanceof WP_User && echc_is_managed($user->ID)) {
        return new WP_Error('ec_hub_no_login', 'Uživatel EC Hubu se do administrace nepřihlašuje. Slouží jen pro heslo aplikace.');
    }
    return $user;
});

add_filter('allow_password_reset', static function ($allow, $user_id) {
    return echc_is_managed($user_id) ? false : $allow;
}, 10, 2);

function echc_hub_passwords($user_id)
{
    $items = array();
    foreach (WP_Application_Passwords::get_user_application_passwords($user_id) as $item) {
        if (($item['app_id'] ?? '') === ECHC_APP_ID || ($item['name'] ?? '') === ECHC_APP_NAME) {
            $items[] = $item;
        }
    }
    return $items;
}

function echc_revoke($user_id)
{
    foreach (echc_hub_passwords($user_id) as $item) {
        WP_Application_Passwords::delete_application_password($user_id, $item['uuid']);
    }
}

/** Vrátí párovací kód (zobrazí se jen jednou, nikde se neukládá) nebo WP_Error. */
function echc_connect($role)
{
    if (!in_array($role, ECHC_ROLES, true)) {
        return new WP_Error('echc_role', 'Neplatný rozsah oprávnění.');
    }
    if (!wp_is_application_passwords_available()) {
        return new WP_Error('echc_unavailable', 'Hesla aplikací jsou na tomto webu vypnutá. Web musí běžet na HTTPS a hesla aplikací nesmí vypínat bezpečnostní plugin.');
    }
    $user = get_user_by('login', ECHC_LOGIN);
    if ($user && !echc_is_managed($user->ID)) {
        if (user_can($user, 'manage_options')) {
            return new WP_Error('echc_admin_exists', 'Uživatel „ec-hub“ už existuje a je správce webu. Plugin ho nepřevezme – přejmenujte nebo smažte ho.');
        }
        update_user_meta($user->ID, ECHC_MARK, '1');
    }
    if (!$user) {
        $host = (string) wp_parse_url(home_url(), PHP_URL_HOST);
        $id = wp_insert_user(array(
            'user_login'   => ECHC_LOGIN,
            'user_pass'    => wp_generate_password(64, true, true),
            'user_email'   => 'ec-hub@' . $host,
            'display_name' => 'EC Hub',
            'nickname'     => 'EC Hub',
            'role'         => $role,
            'description'  => 'Technický uživatel pro EC Hub (plugin EC Connect WordPress). Do administrace se nepřihlašuje.',
        ));
        if (is_wp_error($id)) {
            return $id;
        }
        update_user_meta($id, ECHC_MARK, '1');
        $user = get_user_by('id', $id);
    }
    $user->set_role($role);
    if (!wp_is_application_passwords_available_for_user($user)) {
        return new WP_Error('echc_unavailable_user', 'Hesla aplikací jsou pro tohoto uživatele vypnutá (nastavení jiného pluginu).');
    }
    echc_revoke($user->ID);
    $created = WP_Application_Passwords::create_new_application_password($user->ID, array(
        'name'   => ECHC_APP_NAME,
        'app_id' => ECHC_APP_ID,
    ));
    if (is_wp_error($created)) {
        return $created;
    }
    $payload = array(
        'v'        => 1,
        'site'     => untrailingslashit(home_url()),
        'rest'     => get_rest_url(),
        'user'     => ECHC_LOGIN,
        'password' => $created[0],
    );
    return 'ECHUB-WP1.' . rtrim(strtr(base64_encode(wp_json_encode($payload)), '+/', '-_'), '=');
}

function echc_disconnect()
{
    $user = echc_user();
    if ($user) {
        echc_revoke($user->ID);
        $user->set_role(''); // bez role nesmí nic, autorství obsahu zůstává
    }
}

/* ---------- Aktualizace z GitHubu (0.6.0): standardní cesta WordPressu pro pluginy mimo katalog (Update URI, WP 5.8+) ---------- */

/** Informace o nejnovější verzi z update.json v repozitáři; jen ověřená pole, uloženo na 12 hodin. */
function echc_update_info($force = false)
{
    $cached = get_site_transient(ECHC_UPDATE_CACHE);
    if (!$force && is_array($cached)) {
        return ($cached['version'] ?? '') !== '' ? $cached : null;
    }
    $info = null;
    $response = wp_remote_get(ECHC_UPDATE_JSON, array('timeout' => 8, 'redirection' => 0, 'limit_response_size' => 16384, 'headers' => array('Accept' => 'application/json')));
    if (!is_wp_error($response) && (int) wp_remote_retrieve_response_code($response) === 200) {
        $data = json_decode((string) wp_remote_retrieve_body($response), true);
        if (is_array($data)) {
            $info = echc_valid_update($data);
        }
    }
    // Neúspěch se pamatuje jen hodinu, aby web GitHub zbytečně nezatěžoval.
    set_site_transient(ECHC_UPDATE_CACHE, $info ?: array('version' => ''), $info ? 12 * HOUR_IN_SECONDS : HOUR_IN_SECONDS);
    return $info;
}

/** Přijme jen přesný tvar: verze, balíček z vydání tohoto repozitáře se stejnou verzí a SHA-256. */
function echc_valid_update($data)
{
    $version = (string) ($data['version'] ?? '');
    $package = (string) ($data['package'] ?? '');
    $sha = strtolower((string) ($data['sha256'] ?? ''));
    if (!preg_match('/^\d+\.\d+\.\d+$/', $version) || !preg_match('/^[a-f0-9]{64}$/', $sha)) {
        return null;
    }
    if ($package !== ECHC_REPO . '/releases/download/v' . $version . '/ec-connect-wordpress-' . $version . '.zip') {
        return null;
    }
    $clean = static function ($v) {
        $v = (string) $v;
        return preg_match('/^\d+(\.\d+){0,2}$/', $v) ? $v : '';
    };
    return array('version' => $version, 'package' => $package, 'sha256' => $sha,
        'requires' => $clean($data['requires'] ?? ''), 'requires_php' => $clean($data['requires_php'] ?? ''), 'tested' => $clean($data['tested'] ?? ''));
}

add_filter('update_plugins_github.com', static function ($update, $plugin_data, $plugin_file) {
    if ($plugin_file !== plugin_basename(__FILE__)) {
        return $update;
    }
    $info = echc_update_info();
    if (!$info) {
        return $update;
    }
    return array('id' => ECHC_REPO, 'slug' => 'ec-connect-wordpress', 'version' => $info['version'], 'url' => ECHC_REPO,
        'package' => $info['package'], 'requires' => $info['requires'], 'requires_php' => $info['requires_php'], 'tested' => $info['tested']);
}, 10, 3);

/** Balíček stáhne plugin sám a před instalací ověří SHA-256 z update.json; nesouhlasí-li, aktualizace se neprovede. */
add_filter('upgrader_pre_download', static function ($reply, $package) {
    if ($reply !== false || !is_string($package) || strpos($package, ECHC_REPO . '/releases/download/') !== 0) {
        return $reply;
    }
    $info = echc_update_info(true);
    if (!$info || $info['package'] !== $package) {
        return new WP_Error('echc_update_unknown', 'EC Connect: balíček neodpovídá zveřejněné verzi, aktualizace zastavena.');
    }
    if (!function_exists('download_url')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    $file = download_url($package, 120);
    if (is_wp_error($file)) {
        return $file;
    }
    if (!hash_equals($info['sha256'], strtolower((string) hash_file('sha256', $file)))) {
        wp_delete_file($file);
        return new WP_Error('echc_update_checksum', 'EC Connect: kontrolní součet balíčku nesouhlasí, aktualizace zastavena.');
    }
    return $file;
}, 10, 2);

/* ---------- Administrace: Nastavení → EC Hub ---------- */

add_action('admin_menu', static function () {
    add_options_page('EC Connect', 'EC Connect', 'manage_options', 'ec-connect-wordpress', 'echc_page');
});

add_filter('plugin_action_links_' . plugin_basename(__FILE__), static function ($links) {
    array_unshift($links, '<a href="' . esc_url(admin_url('options-general.php?page=ec-connect-wordpress')) . '">Nastavení</a>');
    return $links;
});

add_action('admin_init', static function () {
    if (get_option('echwp_readonly_key_hash', false) !== false) {
        delete_option('echwp_readonly_key_hash'); // úklid po pilotním pluginu EC Hub Connect 0.1.0
    }
});

function echc_hidden_objects($kind)
{
    $selected = echc_selected($kind === 'types' ? ECHC_OPT_TYPES : ECHC_OPT_TAXONOMIES);
    $objects = $kind === 'types' ? get_post_types(array('show_ui' => true), 'objects') : get_taxonomies(array('show_ui' => true), 'objects');
    $out = array();
    foreach ($objects as $name => $object) {
        if (!$object->_builtin && (!empty($object->public) || !empty($object->publicly_queryable))
            && (empty($object->show_in_rest) || in_array($name, $selected, true))) {
            $out[$name] = $object;
        }
    }
    return $out;
}

function echc_role_label($role)
{
    $roles = wp_roles()->roles;
    return isset($roles[$role]) ? translate_user_role($roles[$role]['name']) : 'bez role';
}

function echc_page()
{
    if (!current_user_can('manage_options')) {
        wp_die('Nemáte oprávnění.');
    }
    $code = null;
    $notice = null;
    $error = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['echc_action'])) {
        check_admin_referer('echc');
        $action = sanitize_key(wp_unslash($_POST['echc_action']));
        $role = isset($_POST['echc_role']) ? sanitize_key(wp_unslash($_POST['echc_role'])) : 'editor';
        if ($action === 'connect') {
            $result = echc_connect($role);
            if (is_wp_error($result)) {
                $error = $result->get_error_message();
            } else {
                $code = $result;
            }
        } elseif ($action === 'role' && echc_user() && in_array($role, ECHC_ROLES, true)) {
            echc_user()->set_role($role);
            $notice = 'Rozsah oprávnění uložen.';
        } elseif ($action === 'disconnect') {
            echc_disconnect();
            $notice = 'Web je odpojený: heslo aplikace EC Hub je zrušené a uživatel ec-hub nemá žádnou roli.';
        } elseif ($action === 'rest') {
            foreach (array('types' => ECHC_OPT_TYPES, 'taxonomies' => ECHC_OPT_TAXONOMIES) as $kind => $option) {
                $allowed = array_keys(echc_hidden_objects($kind));
                $posted = isset($_POST['echc_' . $kind]) ? array_map('sanitize_key', (array) wp_unslash($_POST['echc_' . $kind])) : array();
                update_option($option, array_values(array_intersect($allowed, $posted)), true);
            }
            $notice = 'Uloženo. EC Hub nově zpřístupněné typy najde sám, když s nimi poprvé pracuje.';
        } elseif ($action === 'stec') {
            $on = !empty($_POST['echc_stec']) && echc_stec_supported();
            update_option(ECHC_OPT_STEC, $on ? '1' : '0', true);
            $notice = $on ? 'EC Hub smí spravovat kalendář Stachethemes.' : 'Správa kalendáře pro EC Hub je vypnutá.';
        } elseif ($action === 'unfiltered') {
            $on = !empty($_POST['echc_unfiltered']);
            update_option(ECHC_OPT_UNFILTERED, $on ? '1' : '0', true);
            $notice = ($on ? 'EC Hub smí ukládat nefiltrované HTML.' : 'Obsah od EC Hubu WordPress čistí (bez skriptů a vložených rámů).')
                . ' EC Hub změnu pozná hned při další práci s webem.';
        }
    }

    $user = echc_user();
    $role = $user && $user->roles ? $user->roles[0] : '';
    $passwords = $user ? echc_hub_passwords($user->ID) : array();
    $connected = $user && $role !== '' && $passwords;

    echo '<div class="wrap"><h1>EC Connect WordPress</h1>';
    if ($error) {
        echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>';
    }
    if ($notice) {
        echo '<div class="notice notice-success"><p>' . esc_html($notice) . '</p></div>';
    }

    if ($code) {
        echo '<div class="notice notice-warning" style="padding:12px 16px"><p><strong>Párovací kód – zobrazí se jen teď.</strong> Zkopírujte ho a vložte v EC Hubu (Registrace připojení → WordPress). Do chatu ani e-mailu ho neposílejte.</p>';
        echo '<textarea id="echc-code" readonly rows="4" style="width:100%;font-family:monospace">' . esc_textarea($code) . '</textarea>';
        echo '<p><button type="button" class="button button-primary" onclick="var t=document.getElementById(\'echc-code\');t.select();navigator.clipboard&&navigator.clipboard.writeText(t.value);this.textContent=\'Zkopírováno\'">Kopírovat kód</button></p></div>';
    }

    echo '<h2>Připojení</h2>';
    $roleSelect = static function ($current) {
        $html = '<select name="echc_role">';
        foreach (array('contributor' => 'jen koncepty (Přispěvatel)', 'author' => 'publikace vlastních článků (Autor)', 'editor' => 'publikace a úpravy všeho, volba autora (Editor)') as $value => $label) {
            $html .= '<option value="' . esc_attr($value) . '"' . selected($current, $value, false) . '>' . esc_html($label) . '</option>';
        }
        return $html . '</select>';
    };
    $form = static function ($action, $inner, $button, $class = 'button', $confirm = '') {
        echo '<form method="post" style="margin:8px 0">';
        wp_nonce_field('echc');
        echo '<input type="hidden" name="echc_action" value="' . esc_attr($action) . '">' . $inner . ' ';
        echo '<button type="submit" class="' . esc_attr($class) . '"' . ($confirm ? ' onclick="return confirm(\'' . esc_js($confirm) . '\')"' : '') . '>' . esc_html($button) . '</button></form>';
    };

    if ($connected) {
        $item = $passwords[0];
        $used = esc_html($item['last_used'] ? wp_date('j. n. Y H:i', (int) $item['last_used']) . ' (IP ' . (string) $item['last_ip'] . ')' : 'zatím nepoužito');
        echo '<table class="form-table"><tr><th>Stav</th><td><strong style="color:#008a20">Připojeno</strong></td></tr>';
        echo '<tr><th>Uživatel</th><td>ec-hub – ' . esc_html(echc_role_label($role)) . '</td></tr>';
        echo '<tr><th>Heslo aplikace</th><td>EC Hub, vytvořeno ' . esc_html(wp_date('j. n. Y H:i', (int) $item['created'])) . '</td></tr>';
        echo '<tr><th>Poslední přístup Hubu</th><td>' . $used . '</td></tr></table>';
        $form('role', $roleSelect($role), 'Uložit rozsah oprávnění');
        $form('connect', '<input type="hidden" name="echc_role" value="' . esc_attr($role) . '">', 'Vytvořit nový párovací kód', 'button', 'Současné heslo EC Hubu přestane platit a web bude potřeba v Hubu znovu spárovat. Pokračovat?');
        $form('disconnect', '', 'Odpojit od EC Hubu', 'button button-link-delete', 'Heslo EC Hubu bude zrušeno a Hub ztratí přístup k webu. Pokračovat?');
    } else {
        echo '<p>Připojení založí technického uživatele <code>ec-hub</code> se zvoleným rozsahem oprávnění a jeho heslo aplikace. EC Hub pak pracuje jen přes standardní REST API WordPressu; k souborům, pluginům, nastavení ani uživatelům přístup nemá.</p>';
        $form('connect', 'Rozsah: ' . $roleSelect('editor'), 'Připojit k EC Hubu', 'button button-primary');
    }

    echo '<h2>Kalendář Stachethemes</h2>';
    $stec = echc_stec_version();
    if ($stec === '') {
        echo '<p>Kalendář Stachethemes není aktivní.</p>';
    } elseif (!echc_stec_supported()) {
        echo '<p style="color:#b32d2e">Kalendář Stachethemes ' . esc_html($stec) . ' – tato verze není s EC Connect ověřená. Správa kalendáře pro EC Hub je vypnutá, dokud nevyjde aktualizace EC Connect WordPress. Čtení akcí funguje dál.</p>';
    } else {
        $on = get_option(ECHC_OPT_STEC) === '1';
        echo '<p>Kalendář Stachethemes ' . esc_html($stec) . ' má vlastní práva (kdo smí spravovat akce, u každé akce kdo ji smí upravovat). '
            . 'Zapnutím dostane uživatel <code>ec-hub</code> právo spravovat všechny akce, kalendáře, kategorie, místa, organizátory a hosty. Na akcích ani v nastavení kalendáře se nic nemění; vypnutím se práva hned odeberou.</p>';
        $form('stec', '<label><input type="checkbox" name="echc_stec" value="1"' . checked($on, true, false) . '> Povolit EC Hubu správu kalendáře</label>', 'Uložit');
    }

    echo '<h2>Bezpečnost</h2><p>Uživatel <code>ec-hub</code> nikdy nesmí spravovat web (pluginy, šablony, uživatele, nastavení, soubory), ani kdyby mu někdo zvýšil roli.</p>';
    echo '<p>Ve výchozím stavu WordPress obsah od EC Hubu čistí stejně jako u role Autor: zůstane formátování, obrázky, odkazy, bloky a shortcody, '
        . 'odstraní se skripty, vložené rámy (iframe) a atributy <code>on…=</code>. Chrání to správce webu před škodlivým kódem, který by agent mohl převzít z cizích textů. '
        . 'Obsah, který takový kód už obsahuje, EC Hub neupravuje (WordPress by ho při uložení ořezal).</p>';
    $form('unfiltered', '<label><input type="checkbox" name="echc_unfiltered" value="1"' . checked(echc_unfiltered_allowed(), true, false) . '> Povolit EC Hubu ukládat nefiltrované HTML (skripty, vložené rámy) – nedoporučeno</label>', 'Uložit');

    echo '<h2>SEO</h2><p>' . (echc_yoast_active()
        ? 'Yoast SEO: titulek, popis a klíčové slovo jsou v API dostupné k zápisu (smí je měnit jen uživatel, který smí upravit daný obsah).'
        : 'Yoast SEO není aktivní – SEO pole se nezpřístupňují.') . '</p>';
    echo '<p class="description">Pole jsou v API jen pro úpravy; anonymní čtenář API je nevidí.</p>';

    echo '<h2>Zpřístupnění v API</h2><p>Veřejné typy obsahu a taxonomie šablony či pluginů, které nejsou v REST API WordPressu (neveřejné, např. formuláře, se nenabízejí – jejich obsah by byl v API čitelný pro kohokoli). Zaškrtnutím se u nich zapne oficiální volba <code>show_in_rest</code>; práva dál hlídá WordPress. Editor v administraci zůstane stejný.</p>';
    $types = echc_hidden_objects('types');
    $taxonomies = echc_hidden_objects('taxonomies');
    if (!$types && !$taxonomies) {
        echo '<p><em>Vše je už v API dostupné.</em></p>';
    } else {
        $boxes = '';
        foreach (array('types' => $types, 'taxonomies' => $taxonomies) as $kind => $objects) {
            $selected = echc_selected($kind === 'types' ? ECHC_OPT_TYPES : ECHC_OPT_TAXONOMIES);
            foreach ($objects as $name => $object) {
                $boxes .= '<label style="display:block;margin:4px 0"><input type="checkbox" name="echc_' . esc_attr($kind) . '[]" value="' . esc_attr($name) . '"' . checked(in_array($name, $selected, true), true, false) . '> '
                    . esc_html($object->labels->name) . ' <code>' . esc_html($name) . '</code>' . ($kind === 'taxonomies' ? ' (taxonomie)' : '') . '</label>';
            }
        }
        $form('rest', $boxes, 'Uložit zpřístupnění');
    }
    echo '<p style="color:#646970">EC Connect WordPress ' . esc_html(ECHC_VERSION) . '</p></div>';
}
