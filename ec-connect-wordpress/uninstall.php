<?php
// Odinstalace: zruší heslo aplikace EC Hub, odebere roli uživateli ec-hub (autorství obsahu zůstává) a smaže nastavení.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$echc_user = get_user_by('login', 'ec-hub');
if ($echc_user && get_user_meta($echc_user->ID, 'ec_hub_connect_managed', true) === '1') {
    foreach (WP_Application_Passwords::get_user_application_passwords($echc_user->ID) as $echc_item) {
        if (($echc_item['app_id'] ?? '') === '7c1e4f52-2b9d-4a63-8f0e-5d3a9b6c1e27' || ($echc_item['name'] ?? '') === 'EC Hub') {
            WP_Application_Passwords::delete_application_password($echc_user->ID, $echc_item['uuid']);
        }
    }
    $echc_user->set_role('');
}
delete_option('ec_hub_connect_rest_types');
delete_option('ec_hub_connect_rest_taxonomies');
delete_option('echwp_readonly_key_hash');
delete_option('ec_hub_connect_stec');
delete_option('ec_hub_connect_unfiltered_html');
delete_site_transient('ec_hub_connect_update');
