<?php
/**
 * Importer NasaTheme
 * 
 * Since 4.0
 * 
 */
defined('ABSPATH') or die();

defined('ELESSI_IMPORT_TOTAL') or define('ELESSI_IMPORT_TOTAL', '25');

/**
 * Menu Importer Dashboard
 */
add_action('admin_menu', 'elessi_data_importer_menu', 99);
function elessi_data_importer_menu() {
    if (current_user_can('manage_options')) {
        $args = array(
            'parent_slug' => 'themes.php', // Parent Menu slug.
            'page_title' => esc_html__('Theme Setup', 'elessi-theme'),
            'menu_title' => esc_html__('Theme Setup', 'elessi-theme'),
            'capability' => 'edit_theme_options', // Capability.
            'menu_slug' => 'nasa-install-demo-data', // Menu slug.
            'function' => 'elessi_import_demo_data_output', // Callback.
        );

        add_theme_page(
            $args['page_title'],
            $args['menu_title'],
            $args['capability'],
            $args['menu_slug'],
            $args['function']
        );
    }
}

/**
 * Page Nasa Importer
 */
function elessi_import_demo_data_output() {
    wp_enqueue_script('nasa_back_end-script-demo-data', ELESSI_THEME_URI . '/admin/assets/js/nasa-core-demo-data.js');
    $nasa_core_js = 'var ajax_admin_demo_data="' . esc_url(admin_url('admin-ajax.php')) . '";';
    wp_add_inline_script('nasa_back_end-script-demo-data', $nasa_core_js, 'before');
    
    include ELESSI_ADMIN_PATH . 'importer/tpl-import-demo-data.php';
}

/**
 * Install Child Theme
 */
add_action('wp_ajax_nasa_install_child_theme', 'elessi_install_child_theme');
function elessi_install_child_theme() {
    global $wp_filesystem;
    
    // Initialize the WP filesystem
    if (empty($wp_filesystem)) {
        require_once ABSPATH . '/wp-admin/includes/file.php';
        WP_Filesystem();
    }
    
    $zip = ELESSI_ADMIN_PATH . 'importer/theme-child/theme-child.zip';
    if (!$wp_filesystem->is_file($zip)) {
        die('0');
    }
    
    try {
        // unzip child-theme
        $theme_root = ELESSI_THEME_PATH . '/../';
        $pathArrayString = str_replace(array('/', '\\'), '|', ELESSI_THEME_PATH);
        $themeNameArray = explode('|', $pathArrayString);
        $theme_name = end($themeNameArray);
        $theme_child = $theme_name . '-child';

        if (!$wp_filesystem->is_dir($theme_root . $theme_child)) {
            wp_mkdir_p($theme_root . $theme_child);
            unzip_file($zip, $theme_root . $theme_child);
        }

        // Active Child Theme
        if (is_dir($theme_root . $theme_child)) {
            switch_theme($theme_child);
        }
    } catch (Exception $exc) {
        die('0');
    }
    
    die('1');
}

/**
 * Install Plugin
 */
add_action('wp_ajax_nasa_install_plugin', 'elessi_install_plugin');
function elessi_install_plugin() {
    $plugin_slug = isset($_REQUEST['plg']) ? $_REQUEST['plg'] : null;
    $plugin_info = null;
    
    $res = array(
        'mess' => '',
        'status' => '1'
    );
    
    if (trim($plugin_slug) !== '') {
        $plugins = elessi_list_required_plugins();
        
        foreach ($plugins as $plugin) {
            if (isset($plugin['auto']) && $plugin['auto'] && $plugin['slug'] === $plugin_slug) {
                $plugin_info = $plugin;
                break;
            }
        }
        
        if (!class_exists('Elessi_Auto_Install_Plugins')) {
            require_once ELESSI_ADMIN_PATH . 'importer/auto-install-plugins.php';
        }
        
        $auto_install = new Elessi_Auto_Install_Plugins($plugin_info);
        
        $res['mess'] = $plugin_info['name'];
        $res['status'] = $auto_install->nasa_plugin_install() ? '1' : '0';
        
        die(json_encode($res));
    }
    
    die(json_encode($res));
}

/**
 * Import demo data
 */
add_action('wp_ajax_nasa_import_contents', 'elessi_import_contents');
function elessi_import_contents() {
    $res = array('nofile' => 'false');
    
    if (current_user_can('manage_options')) {
        set_time_limit(0);
        header('X-XSS-Protection:0');
        $partial = $_POST['file'];
        $partial = $partial ? str_replace('data', '', $partial) : '';
    
        if (!defined('WP_LOAD_IMPORTERS')) {
            define('WP_LOAD_IMPORTERS', true); // we are loading importers
        }

        if (!class_exists('WP_Import')) { // if WP importer doesn't exist
            $wp_import = ELESSI_ADMIN_PATH . 'importer/wordpress-importer.php';
            require_once $wp_import;
        }

        if (class_exists('WP_Importer') && class_exists('WP_Import')) {
            if (!isset($_SESSION['nasa_import']) || $partial == 1) {
                $_SESSION['nasa_import'] = array();
            }
            
            /* Import Woocommerce if WooCommerce Exists */
            if (class_exists('WooCommerce')) {
                $partial = $partial < 10 ? '0' . $partial : $partial;
                
                $theme_xml = ELESSI_ADMIN_PATH . 'importer/data-import/datas/data_Part_0' . $partial . '_of_' . ELESSI_IMPORT_TOTAL . '.xml';
                if (is_file($theme_xml)) {
                    $importer = new WP_Import();

                    $importer->fetch_attachments = true;
                    ob_start();
                    $importer->import($theme_xml);
                    $res['mess'] = ob_get_clean();
                } else {
                    $res['mess'] = '<p class="nasa-error">';
                    $res['mess'] .= 'file: ' . ELESSI_ADMIN_PATH . 'importer/data-import/datas/data_Part_0' . $partial . '_of_' . ELESSI_IMPORT_TOTAL . '.xml is not exists';
                    $res['mess'] .= '</p>';
                    $res['nofile'] = 'true';
                }

                $res['end'] = 1;
                die(json_encode($res));
            }
        }
    }

    $res['mess'] = '';
    $res['end'] = 0;

    die(json_encode($res));
}

/**
 * Import Widgets Sidebar
 */
if (isset($_REQUEST['action']) && 'nasa_import_widgets_sidebar' == $_REQUEST['action']) {
    require_once ELESSI_ADMIN_PATH . 'importer/nasa-sidebars-widgets.php';
}
add_action('wp_ajax_nasa_import_widgets_sidebar', 'elessi_import_widgets_sidebar');
function elessi_import_widgets_sidebar() {
    try {
        $widget_data = elessi_sidebars_widgets_import();
    
        /**
         * Sidebars Widgets
         */
        update_option('sidebars_widgets', $widget_data['sidebars_widgets'], true);

        /**
         * Setting Widgets
         */
        foreach ($widget_data['widgets'] as $key => $value) {
            update_option($key, $value, true);
        }
    } catch (Exception $exc) {
        die('0');
    }
    
    die('1');
}

/**
 * Import Homes
 */
add_action('wp_ajax_nasa_import_homes', 'elessi_import_homes');
function elessi_import_homes() {
    $elm_pages = isset($_POST['elm']) ? $_POST['elm'] : array();
    $wpb_pages = isset($_POST['wpb']) ? $_POST['wpb'] : array();
    
    if (!class_exists('Elessi_DF_Page_Importer')) {
        require_once ELESSI_ADMIN_PATH . 'importer/nasa-default-page.php';
    }
    
    try {
        /**
         * Push data Elementor pages
         */
        if (!empty($elm_pages)) {
            /**
             * Footer - Elementor Header & Footer Builder Plugin
             */
            if (function_exists('hfe_init')) {
                $elm_footers = array(
                    'footer-light',
                    'footer-light-2',
                    'footer-light-2-width-1300',
                    'footer-light-2-width-1400',
                    'footer-light-2-width-1600',
                    'footer-light-3',
                    'footer-dark',
                    'footer-dark-2',
                    'footer-mobile',
                );
                
                foreach ($elm_footers as $file) {
                    $file = trim($file);
                    Elessi_DF_Page_Importer::nasa_push_data_from_file('hfe', $file);
                }
            }
            
            /**
             * Pages Selected - Elementor
             */
            $elm_pages[] = 'contact-us';
            $elm_pages[] = 'about-us';
            
            foreach ($elm_pages as $file) {
                $file = trim($file);
                Elessi_DF_Page_Importer::nasa_push_data_from_file('elm', $file);
            }
        }

        /**
         * Push data WPBakery page
         */
        if (!empty($wpb_pages)) {
            foreach ($wpb_pages as $file) {
                $file = trim($file);
                Elessi_DF_Page_Importer::nasa_push_data_from_file('wpb', $file);
            }
        }
    } catch (Exception $exc) {
        echo $exc->getMessage();
        die('0');
    }

    die('1');
}

/**
 * Import Revslider
 */
add_action('wp_ajax_nasa_import_revsliders', 'elessi_import_revsliders');
function elessi_import_revsliders() {
    if (!class_exists('RevSliderSliderImport')) {
        die('0');
    }
    
    $zips = glob(ELESSI_ADMIN_PATH . 'importer/data-import/revsliders/*.zip');
    
    if (empty($zips)) {
        die('0');
    }
    
    try {
        foreach ($zips as $zip) {
            $import = new RevSliderSliderImport();
            $import->import_slider(true, $zip, false, false, true, true);
        }
    } catch (Exception $exc) {
        echo $exc->getMessage();
        die('0');
    }

    die('1');
}

/**
 * get Post by slug
 * 
 * @global type $wpdb
 * @param type $slug
 * @param type $post_type
 * @return type
 */
function elessi_import_get_post_by_slug($slug, $post_type) {
    global $wpdb;
    
    $sql = $wpdb->prepare(
        'SELECT ID FROM ' . $wpdb->posts . ' WHERE post_name = %s AND post_type = %s',
        $slug,
        $post_type
    );
    
    $page = $wpdb->get_var($sql);

    if ($page) {
        return get_post($page);
    }
    
    return null;
}

/**
 * Delete Default pages
 */
function elessi_import_delete_df_pages() {
    $pages = array(
        'sample-page',
        'shop-2',
        'my-account-2',
        'cart',
        'checkout-2'
    );
    
    foreach ($pages as $slug) {
        $page = elessi_import_get_post_by_slug($slug, 'page');
        
        if ($page) {
            wp_delete_post($page->ID, true);
        }
    }
}

/**
 * Delete Default posts
 */
function elessi_import_delete_df_posts() {
    $posts = array(
        'hello-world'
    );
    
    foreach ($posts as $slug) {
        $post = elessi_import_get_post_by_slug($slug, 'post');
        
        if ($post) {
            wp_delete_post($post->ID, true);
        }
    }
}

/**
 * Delete Default Contact Form
 */
function elessi_import_delete_df_contacts() {
    if (class_exists('WPCF7_ContactForm')) {
        global $wpdb;
        
        $contacts = array(
            'Contact form 1'
        );
        
        foreach ($contacts as $contact) {

            $sql = $wpdb->prepare(
                'SELECT ID FROM ' . $wpdb->posts . ' WHERE post_title = %s AND post_type = %s',
                $contact,
                WPCF7_ContactForm::post_type
            );

            $posts = $wpdb->get_results($sql);

            if ($posts) {
                foreach ($posts as $post) {
                    if (isset($post->ID)) {
                        wp_delete_post($post->ID, true);
                    }
                }
            }
        }
    }
}

/**
 * set Breadcrumb pages
 */
function elessi_import_breadcrumb_pages() {
    $pages = array(
        'shop' => 'on',
        'my-account' => 'on',
        'blog' => 'on',
        'shopping-cart' => 'off',
        'checkout' => 'off'
    );
    
    foreach ($pages as $slug => $value) {
        $page = elessi_import_get_post_by_slug($slug, 'page');
        
        if ($page) {
            update_post_meta($page->ID, '_nasa_show_breadcrumb', $value);
        }
    }
}

/**
 * Global Options
 */
add_action('wp_ajax_nasa_global_options', 'elessi_global_options');
function elessi_global_options() {
    /**
     * Setting Main Menu
     */
    $locations = get_theme_mod('nav_menu_locations'); // registered menu locations in theme
    $menus = wp_get_nav_menus(); // registered menus

    if ($menus) {
        foreach ($menus as $menu) {
            switch ($menu->name) {
                /**
                 * Main Menu
                 */
                case 'Main Menu':
                    $locations['primary'] = $menu->term_id;
                    break;
                
                /**
                 * Vertical Menu
                 */
                case 'Vertical Menu':
                    $locations['vetical-menu'] = $menu->term_id;
                    break;

                default: break;
            }
        }
    }

    set_theme_mod('nav_menu_locations', $locations); // set menus to locations
    
    /**
     * Setting for WooCommerce
     */
    if (class_exists('WooCommerce')) {
        // Set pages
        $woopages = array(
            'woocommerce_shop_page_id' => 'shop', // Shop page
            'woocommerce_cart_page_id' => 'shopping-cart', // Cart page
            'woocommerce_checkout_page_id' => 'checkout', // Checkout page
            'woocommerce_myaccount_page_id' => 'my-account' // My Account page
        );
        
        foreach ($woopages as $woo_page_option => $woo_page_slug) {
            $woopage = elessi_import_get_post_by_slug($woo_page_slug, 'page');
            if ($woopage && isset($woopage->ID)) {
                update_option($woo_page_option, $woopage->ID);
            }
        }

        // Woo Image sizes
        $catalog = array(
            'width' => '450', // px
            'height' => '', // px
            'crop' => 0   // false
        );

        $single = array(
            'width' => '595', // px
            'height' => '', // px
            'crop' => 0   // false
        );

        $thumbnail = array(
            'width' => '120', // px
            'height' => '150', // px
            'crop' => 1   // false
        );

        update_option('shop_catalog_image_size', $catalog);   // Product category thumbs
        update_option('shop_single_image_size', $single);   // Single product image
        update_option('shop_thumbnail_image_size', $thumbnail);  // Image gallery thumbs

        // Wordpress Media Setting
        update_option('medium_size_w', 450);
        update_option('large_size_w', 595);

        // For Woo 3.3.x
        update_option('woocommerce_single_image_width', 595);       // Single product image
        update_option('woocommerce_thumbnail_image_width', 450);    // Product category thumbs
        update_option('woocommerce_thumbnail_cropping', 'uncropped');    // Option crop

        // default sorting
        update_option('woocommerce_default_catalog_orderby', 'menu_order');

        // Number decimals
        // update_option('woocommerce_price_num_decimals', '0');

        // We no longer need to install pages
        delete_option('_wc_needs_pages');
        delete_transient('_wc_activation_redirect');
        
        /**
         * Delete All transients product
         */
        $transients_to_clear = array(
            'wc_products_onsale',
            'wc_featured_products',
            'wc_outofstock_count',
            'wc_low_stock_count',
        );

        foreach ($transients_to_clear as $transient) {
            delete_transient($transient);
        }
        
        /**
         * Clear product transients
         */
        wc_delete_product_transients();
        
        /**
         * Clear Expired transients
         */
        wc_delete_expired_transients();
        
        /**
         * Update Lookup tables
         */
        wc_update_product_lookup_tables();
        
        /**
         * Update Recount all Terms
         */
        wc_recount_all_terms();
    }
    
    /**
     * Blog page
     */
    update_option('show_on_front', 'page');
    $blog = elessi_import_get_post_by_slug('blog', 'page');
    if ($blog) {
        update_option('page_for_posts', $blog->ID);
    }
    
    /**
     * Delete default pages
     */
    elessi_import_delete_df_pages();
    
    /**
     * Delete default posts
     */
    elessi_import_delete_df_posts();
    
    /**
     * Delete default contacts
     */
    elessi_import_delete_df_contacts();
    
    /**
     * Enable breadcrumb pages
     */
    elessi_import_breadcrumb_pages();
    
    /**
     * Update UX Attributes
     */
    elessi_update_ux_attrs();
    
    /**
     * Set Default Options
     */
    elessi_theme_set_options_default();
    
    die('1');
}

/**
 * Attributes Color, Size;
 */
function elessi_update_ux_attrs() {
    global $wpdb;
    
    /**
     * Attribute Table
     */
    $attrs_table = $wpdb->prefix . 'woocommerce_attribute_taxonomies';
    
    /**
     * Update Attribute Label - Size
     */
    $wpdb->update(
        $attrs_table,
        array('attribute_type' => 'nasa_label', 'attribute_public' => 0),
        array('attribute_name' => 'size')
    );
    
    /**
     * Update Attribute Color
     */
    $wpdb->update(
        $attrs_table,
        array('attribute_type' => 'nasa_color', 'attribute_public' => 0),
        array('attribute_name' => 'color')
    );
    
    /**
     * Update Color for terms
     */
    $terms = get_terms(array(
        'taxonomy' => 'pa_color',
        'hide_empty' => false
    ));
    
    $codes = array(
        'black' => '#000000',
        'blue' => '#1e73be',
        'green' => '#81d742',
        'orange' => '#dd9933',
        'pink' => '#ffc0cb',
        'red' => '#dd3333',
        'yellow' => '#eeee22'
    );
    
    if (!empty($terms)) {
        foreach ($terms as $term) {
            $color_code = isset($codes[$term->slug]) ? $codes[$term->slug] : $term->slug;
            update_term_meta($term->term_id, 'nasa_color', $color_code);
        }
    }
    
    /**
     * Update Options wc_attribute_taxonomies
     */
    $attrs = $wpdb->get_results('SELECT * FROM ' . $attrs_table);
    if ($attrs) {
        update_option('_transient_wc_attribute_taxonomies', $attrs, true);
    }
}

/**
 * HFE Get Footer Id
 * 
 * @param type $slug
 * @return type
 */
function elessi_elm_fid_by_slug($slug) {
    $args = array(
        'name' => $slug,
        'post_type' => 'elementor-hf',
        'post_status' => 'publish',
        'posts_per_page' => 1
    );
    
    $footer_posts = get_posts($args);
    $footer_hfe = isset($footer_posts[0]) ? $footer_posts[0] : false;
    
    return isset($footer_hfe->ID) ? $footer_hfe->ID : '';
}

/**
 * Set Default Options
 */
function elessi_theme_set_options_default() {
    defined('NASA_ELEMENTOR_ACTIVE') or define('NASA_ELEMENTOR_ACTIVE', defined('ELEMENTOR_PATH') && ELEMENTOR_PATH);
    
    set_theme_mod('transition_load', 'crazy');
    
    set_theme_mod('type_font_select', 'google');
    
    set_theme_mod('type_headings', 'Jost');
    set_theme_mod('type_texts', 'Jost');
    set_theme_mod('type_nav', 'Jost');
    set_theme_mod('type_alt', 'Jost');
    set_theme_mod('type_banner', 'Jost');
    set_theme_mod('type_price', 'Jost');
    
    set_theme_mod('type_headings_rtl', 'Jost');
    set_theme_mod('type_texts_rtl', 'Jost');
    set_theme_mod('type_nav_rtl', 'Jost');
    set_theme_mod('type_alt_rtl', 'Jost');
    set_theme_mod('type_banner_rtl', 'Jost');
    set_theme_mod('type_price_rtl', 'Jost');
    
    set_theme_mod('max_font_weight', '500');
    
    set_theme_mod('header-type', '1');
    set_theme_mod('topbar_content', 'topbar');
    set_theme_mod('f_buildin', '0');
    
    if (NASA_ELEMENTOR_ACTIVE) {
        update_option('elementor_load_fa4_shim', '');
        
        /**
         * Set Footer - HFE plugin
         */
        if (function_exists('hfe_init')) {
            set_theme_mod('footer_mode', 'builder-e');
            set_theme_mod('footer_elm', elessi_elm_fid_by_slug('hfe-footer-light-2'));
            set_theme_mod('footer_elm_mobile', elessi_elm_fid_by_slug('hfe-footer-mobile'));
        } else {
            set_theme_mod('f_buildin', '1');
            set_theme_mod('footer_mode', 'build-in');
            set_theme_mod('footer_build_in', '2');
            set_theme_mod('footer_build_in_mobile', 'm-1');
        }
        
        /**
         * Enable live search widget WP
         */
        update_option('elementor_experiment-e_hidden_wordpress_widgets', 'inactive');
        
        /**
         * Enable upload json file template
         */
        update_option('elementor_unfiltered_files_upload', '1');
        
        /**
         * Disable Elementor Light-box feature
         */
        $kit_id = get_option('elementor_active_kit');
        if ($kit_id) {
            $kit_settings = get_post_meta($kit_id, '_elementor_page_settings', true);
            if (!empty($kit_settings)) {
                $kit_settings['global_image_lightbox'] = '';
                update_post_meta($kit_id, '_elementor_page_settings', $kit_settings);
            } else {
                $kit_settings = array(
                    'system_colors' => array(
                        0 => array(
                            '_id' => 'primary',
                            'title' => 'Primary',
                            'color' => '#6EC1E4',
                        ),
                        1 => array(
                            '_id' => 'secondary',
                            'title' => 'Secondary',
                            'color' => '#54595F',
                        ),
                        2 => array(
                            '_id' => 'text',
                            'title' => 'Text',
                            'color' => '#555',
                        ),
                        3 => array(
                            '_id' => 'accent',
                            'title' => 'Accent',
                            'color' => '#61CE70',
                        ),
                    ),
                    'custom_colors' => array(),
                    'system_typography' => array(
                        0 => array(
                            '_id' => 'primary',
                            'title' => 'Primary',
                            'typography_typography' => 'custom',
                            'typography_font_family' => 'Jost',
                            'typography_font_weight' => '500',
                        ),
                        1 => array(
                            '_id' => 'secondary',
                            'title' => 'Secondary',
                            'typography_typography' => 'custom',
                            'typography_font_family' => 'Jost',
                            'typography_font_weight' => '400',
                        ),
                        2 => array(
                            '_id' => 'text',
                            'title' => 'Text',
                            'typography_typography' => 'custom',
                            'typography_font_family' => 'Jost',
                            'typography_font_weight' => '400',
                        ),
                        3 => array(
                            '_id' => 'accent',
                            'title' => 'Accent',
                            'typography_typography' => 'custom',
                            'typography_font_family' => 'Jost',
                            'typography_font_weight' => '500',
                        ),
                    ),
                    'custom_typography' => array(),
                    'default_generic_fonts' => 'Jost',
                    'site_name' => get_option('blogname'),
                    'site_description' => get_option('blogdescription'),
                    'page_title_selector' => 'h1.entry-title',
                    'global_image_lightbox' => '',
                    'viewport_md' => 768,
                    'viewport_lg' => 1025,
                );
                
                update_post_meta($kit_id, '_elementor_page_settings', $kit_settings);
            }
        }
    } else {
        set_theme_mod('footer_mode', 'builder');
        set_theme_mod('footer-type', 'footer-light-2');
        set_theme_mod('footer-mobile', 'footer-mobile');
    }
    
    set_theme_mod('style_quickview', 'sidebar');
    set_theme_mod('quick_view_item_thumb', '2-items');
    set_theme_mod('hotkeys_search', 'Sweater, Jacket, T-shirt ...');
    set_theme_mod('show_icon_cat_top', 'show-in-shop');
    set_theme_mod('checkout_layout', 'modern');
    set_theme_mod('search_layout', 'modern');
    
    /**
     * Color Badges
     */
    set_theme_mod('featured_badge', '1');
    set_theme_mod('color_hot_label', '#e42e2d');
    set_theme_mod('color_deal_label', '#dd9933');
    set_theme_mod('color_sale_label', '#83b738');
    set_theme_mod('color_variants_label', '#1e73be');
    set_theme_mod('color_bulk_label', '#00a32a');
    
    set_theme_mod('category_sidebar', 'top');
    set_theme_mod('limit_widgets_show_more', '5');
    set_theme_mod('products_per_row', '4-cols');
    set_theme_mod('products_per_row_tablet', '3-cols');
    set_theme_mod('products_per_row_small', '2-cols');
    
    set_theme_mod('product_image_style', 'scroll');
    set_theme_mod('label_attribute_single', '1');
    
    /**
     * WooCommerce Open
     */
    set_theme_mod('size_guide_product', 'size-guide');
    set_theme_mod('delivery_return_single_product', 'delivery-return');
    set_theme_mod('ask_a_question', '3282');
    set_theme_mod('request_a_callback', '3286');
    
    set_theme_mod('after_single_addtocart_form', 'trust-single-product');
    
    set_theme_mod('button_radius', '0');
    set_theme_mod('button_border', '1');
    set_theme_mod('input_radius', '0');
    
    set_theme_mod('facebook_url_follow', '#');
    set_theme_mod('twitter_url_follow', '#');
    set_theme_mod('pinterest_url_follow', '#');
    set_theme_mod('instagram_url', '#');
    
    set_theme_mod('enable_portfolio', '1');
    set_theme_mod('portfolio_columns', '5-cols');
    
    set_theme_mod('enable_nasa_mobile', '1');
    set_theme_mod('single_product_mobile', '1');
    
    set_theme_mod('effect_before_load', '0');
    
    set_theme_mod('nasa_cache_mode', 'file');
    set_theme_mod('nasa_cache_expire', '36000'); // Cache live 10 hours
    
    /**
     * Compare
     */
    $yith_woocompare_fields_attr = array(
        0 => 'image',
        1 => 'title',
        2 => 'price',
        3 => 'add-to-cart',
        4 => 'description',
        5 => 'sku',
        6 => 'stock',
        7 => 'weight',
        8 => 'dimensions',
        9 => 'pa_color',
        10 => 'pa_size'
    );
    update_option('yith_woocompare_fields_attrs', $yith_woocompare_fields_attr, true);
    
    $yith_woocompare_fields = array(
        'image' => 1,
        'title' => 1,
        'price' => 1,
        'add-to-cart' => 1,
        'description' => 1,
        'sku' => 1,
        'stock' => 1,
        'weight' => 1,
        'dimensions' => 1,
        'pa_color' => 1,
        'pa_size' => 1
    );
    update_option('yith_woocompare_fields', $yith_woocompare_fields, true);
    
    update_option('yith_woocompare_compare_button_in_products_list', 'yes');
    
    /**
     * Enable WooCommerce Register form
     */
    update_option('woocommerce_enable_myaccount_registration', 'yes');
    
    /**
     * Rebuild dynamic CSS
     */
    if (function_exists('nasa_theme_rebuilt_css_dynamic')) {
        nasa_theme_rebuilt_css_dynamic();
    }
    
    /**
     * Rewrite Rule URL
     */
    update_option('permalink_structure', '/%year%/%monthnum%/%day%/%postname%/', true);
    $wc_permalink = array(
        'product_base' => 'product',
        'category_base' => 'product-category',
        'tag_base' => 'product-tag',
        'use_verbose_page_rules' => false
    );
    update_option('woocommerce_permalinks', $wc_permalink, true);
    
    flush_rewrite_rules();
    
    /**
     * Clear transient on-sale and deal products
     */
    delete_transient('_wc_products_onsale');
    delete_transient('_nasa_products_deal');
    
    update_option('nasatheme_imported', 'imported');
}
