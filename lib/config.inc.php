<?php

global $blacklist_addons, $blacklist_tables, $mod_map, $core;
$blacklist_addons = array(
    // ----- templates -----
    'blank'                      => 'already included',
    // ----- modules -----
    'addon_file_editor'          => 'not compatible, BC-Version available',
    'captcha_control'            => 'included in core',
    'ckeditor'                   => 'newer version available',
    'cwsoft-addon-file-editor'   => 'not compatible, BC-Version available',
    'droplets'                   => 'newer version included',
    'droplets_extension'         => 'newer version included',
    'dwoo'                       => 'newer version included',
    'edit_area'                  => 'not compatible, BC-Version available',
    'fckeditor'                  => 'newer version available',
    'foldergallery'              => 'not compatible',
    'initial_page'               => 'newer version included', // LEPTON only
    'jsadmin'                    => 'no longer needed',
    'lib_jquery'                 => 'newer version included',
    'menu_link'                  => 'newer version included',
    'phpmailer'                  => 'newer version available', // LEPTON only
    'SecureFormSwitcher'         => 'no longer needed',
    'output_interface'           => 'not compatible, BC-Version available',
    'pclzip'                     => 'already included', // LEPTON only
    'show_menu2'                 => 'newer version included',
    'wrapper'                    => 'newer version included',
    'wysiwyg'                    => 'newer version included',
    'wysiwyg_admin'              => 'newer version included', // LEPTON only
    // ----- languages -----
    'DE'                         => 'already included',
    'EN'                         => 'already included',
);

$blacklist_tables = array(
    'mod_droplets_extension'     => 'BC solution available',
    'mod_jsadmin'                => 'deprecated',
    'mod_output_filter'          => 'BC solution available',
    'mod_outputfilter_dashboard' => 'BC solution available',
    'mod_foldergallery_categories' => 'not compatible',
    'mod_foldergallery_files'      => 'not compatible',
    'mod_foldergallery_settings'   => 'not compatible',
);

$mod_map = array(
    'profiles'                   => 'wbProfiles',
    'brax_highslide'             => 'Brax_HighSlide_Gallery',
    'form'                       => 'form',
    'news_img'                   => 'news_img',
);

$core = array(
    'mod_captcha_control'        => 1,
    'mod_droplets'               => 1,
    'mod_menu_link'              => 1,
    'mod_wrapper'                => 1,
    'mod_wysiwyg'                => 1,
);