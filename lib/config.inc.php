<?php

global $blacklist_addons, $blacklist_tables, $mod_map, $core;
$blacklist_addons = array(
    // ----- templates -----
    'blank'              => 'already included',
    // ----- modules -----
    'captcha_control'    => 'included in core',
    'jsadmin'            => 'no longer needed',
    'wrapper'            => 'newer version included',
    'wysiwyg'            => 'newer version included',
    'show_menu2'         => 'newer version included',
    'SecureFormSwitcher' => 'no longer needed',
    'droplets'           => 'newer version included',
    'menu_link'          => 'newer version included',
    // ----- languages -----
    'DE'                 => 'already included',
    'EN'                 => 'already included',
);

$blacklist_tables = array(
    'mod_droplets_extension'     => 'BC solution available',
    'mod_jsadmin'                => 'deprecated',
    'mod_output_filter'          => 'BC solution available',
    'mod_outputfilter_dashboard' => 'BC solution available',
);

$mod_map = array(
    'profiles'       => 'wbProfiles',
    'brax_highslide' => 'Brax_HighSlide_Gallery',
);

$core = array(
    'mod_captcha_control' => 1,
    'mod_droplets'        => 1,
    'mod_menu_link'       => 1,
    'mod_wrapper'         => 1,
    'mod_wysiwyg'         => 1,
);