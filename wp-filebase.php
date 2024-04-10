<?php

/*
  Plugin Name: WP-Filebase
  Plugin URI:  https://wpfilebase.com/
  Description: Adds a powerful downloads manager supporting file categories, download counter, widgets, sorted file lists and more to your WordPress blog.
  Version:     3.4.4
  Author:      Fabian Schlieper
  Author URI:  http://fabi.me/
  License:     GPL2
  License URI: https://www.gnu.org/licenses/gpl-2.0.html
  Domain Path: /languages
  Text Domain: wp-filebase
  GitHub Plugin URI: https://github.com/f4bsch/WP-Filebase
 */

if (!defined('WPFB')) {
    define('WPFB', 'wpfb');
    define('WPFB_VERSION', '3.4.4');
    define('WPFB_PLUGIN_ROOT', str_replace('\\', '/', dirname(__FILE__)) . '/');
    if (!defined('ABSPATH')) {
        define('ABSPATH', dirname(dirname(dirname(dirname(__FILE__)))));
    } else {
        //define('WPFB_PLUGIN_URI', is_multisite() ? str_replace(array('http://','https://'), '//', str_replace(str_replace('\\','/',ABSPATH),get_option('siteurl').'/',WPFB_PLUGIN_ROOT)) : plugin_dir_url(__FILE__));
        define('WPFB_PLUGIN_URI', is_multisite() ? get_site_url(null, substr(WPFB_PLUGIN_ROOT, strlen(ABSPATH))) : plugin_dir_url(__FILE__));
    }
    if (!defined('WPFB_PERM_FILE'))
        define('WPFB_PERM_FILE', 666);
    if (!defined('WPFB_PERM_DIR'))
        define('WPFB_PERM_DIR', 777); // default unix 755
    define('WPFB_OPT_NAME', 'wpfilebase');
    define('WPFB_PLUGIN_NAME', 'WP-Filebase');
    define('WPFB_TAG_VER', 2);

    function wpfb_loadclass($cl)
    {
        $cl = clean_string($cl);
        if (func_num_args() > 1) {
            $args = func_get_args(); // func_get_args can't be used as func param!
            return array_map(__FUNCTION__, $args);
        } else {
            $cln = 'WPFB_' . $cl;
            $cln = clean_string($cln);
            
            if (class_exists($cln))
                return true;

            $p = WPFB_PLUGIN_ROOT . "classes/".$cl.".php";
            $res = (include_once $p);
            if (!$res) {
                echo("<p>WP-Filebase Error: Could not include class file <b>'{$cl}'</b>!</p>");
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    print_r(debug_backtrace());
                }
            } else {
                if (!class_exists($cln)) {
                    echo("<p>WP-Filebase Error: Class <b>'{$cln}'</b> does not exists in loaded file!</p>");
                    return false;
                }

                if (method_exists($cln, 'InitClass'))
                    call_user_func(array($cln, 'InitClass'));
            }
        }
        return $res;
    }

    // calls static $fnc of class $cl with $params
    // $cl is loaded automatically if not existing
    function wpfb_call($cl, $fnc, $params = null, $is_args_array = false)
    {
        $cln = 'WPFB_' . $cl;
        
        $cln = clean_string($cln);
        $fnc = array($cln, $fnc);
        $fnc = clean_string($fnc);
        $cl = clean_string($cl);
        return (class_exists($cln) || wpfb_loadclass($cl)) ? ($is_args_array ? call_user_func_array($fnc, $params) : call_user_func($fnc, $params)) : null;
    }

    function wpfb_callback($cl, $fnc)
    {
        $fnc = clean_string($fnc);
        $cl = clean_string($cl);
        return create_function('', '$p=func_get_args();return wpfb_call("' . $cl . '","' . $fnc . '",$p,true);');

        //return custom_calback($cl, $fnc);
    }
    /**
 * This is a PHP 8 replacement function for the deprecated create_function function that allows developers to create anonymous functions at runtime using a string of PHP code as input.
 *
 * @param string $arg The argument list for the anonymous function.
 * @param string $body The body of the anonymous function.
 * @return callable Returns a new anonymous function.
 */
if ( ! function_exists( "create_function" ) ) {
    function create_function( $arg, $body ) {
        static $cache          = []; // A static array used to store previously created functions.
        static $max_cache_size = 64; // The maximum size of the cache.
        static $sorter; // A callback function used to sort the cache by hit count.
 
        if ( $sorter === null ) {
            // Define the sorter callback function.
            $sorter = function ( $a, $b ) {
                if ( $a->hits == $b->hits ) {
                    return 0;
                }
                return $a->hits < $b->hits ? 1 : -1;
            };
        }
 
        // Generate a unique key for the current function.
        $crc = crc32( $arg . "\\x00" . $body );
        if ( isset( $cache[$crc] ) ) {
            // If the function has already been created and cached, increment the hit count and return the cached function.
            ++$cache[$crc][1];
            return $cache[$crc][0];
        }
 
        if ( sizeof( $cache ) >= $max_cache_size ) {
            // If the cache size limit is reached, sort the cache by hit count and remove the least-used function.
            uasort( $cache, $sorter );
            array_pop( $cache );
        }
 
        // Create a new anonymous function using `eval` and store it in the cache along with a hit count of 0.
        $cache[$crc] = [
            ( $cb = eval( "return function(" . $arg . "){" . $body . "};" ) ),
            0,
        ];
        return $cb;
    }
}
    
    
    /*function custom_calback($cl, $fnc){
        $p=func_get_args();
        return wpfb_call("' . $cl . '","' . $fnc . '",$p,true);
    }*/
    
    function clean_string($string){
            $string = str_replace("'", "", $string);
            $string = str_replace(".", "", $string);
            $string = str_replace(" ", "", $string);
            return $string;
    }

    function wpfilebase_init()
    {
        wpfb_loadclass('Core');
    }

    function wpfilebase_widgets_init()
    {
        wpfb_loadclass('Widget');
        WPFB_Widget::register();
    }

    function wpfilebase_activate()
    {
        define('WPFB_NO_CORE_INIT', true);
        wpfb_loadclass('Core', 'Admin', 'Setup');
        WPFB_Setup::OnActivateOrVerChange(empty(WPFB_Core::$settings->version) ? null : WPFB_Core::$settings->version);
    }

    function wpfilebase_deactivate()
    {
        wpfb_loadclass('Core', 'Admin', 'Setup');
        wpfb_call('ExtensionLib', 'PluginDeactivated');
        WPFB_Setup::OnDeactivate();
    }

    // FIX: setup the OB to truncate any other output when downloading
    if (!empty($_GET['wpfb_dl'])) {
        @define('NGG_DISABLE_RESOURCE_MANAGER', true); // NexGen Gallery
        ob_start();
    }
}

/**
 * WPDB
 * @global wpdb $wpdb
 */
global $wpdb;

if (isset($wpdb)) {
    $wpdb->wpfilebase_cats = $wpdb->prefix . 'wpfb_cats';
    $wpdb->wpfilebase_files = $wpdb->prefix . 'wpfb_files';
    $wpdb->wpfilebase_files_id3 = $wpdb->prefix . 'wpfb_files_id3';
}

if (isset($_GET['wpfilebase_thumbnail'])) {
    require_once(WPFB_PLUGIN_ROOT . 'thumbnail.php');
}

if (function_exists('add_action')) {
    add_action('init', 'wpfilebase_init');
    add_action('widgets_init', 'wpfilebase_widgets_init');
    add_action('admin_menu', array('WPFB_Core', 'AdminMenu'));
    add_action('admin_init', array('WPFB_Core', 'AdminInit'), 10);
    register_activation_hook(__FILE__, 'wpfilebase_activate');
    register_deactivation_hook(__FILE__, 'wpfilebase_deactivate');
}