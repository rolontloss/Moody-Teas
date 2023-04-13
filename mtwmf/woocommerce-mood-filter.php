<?php
/*
Plugin Name: Moody Teas - WooCommerce Mood Filter
Description: A custom plugin to filter WooCommerce products based on mood, caffeine content, price, rating, and other product attributes.
Version: 1.4
Author: John Nelson - proud non-binary coder
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
function wmf_enqueue_scripts() {
    wp_enqueue_script('wmf-live-search', plugin_dir_url(__FILE__) . 'js/live-search.js', array('jquery'), '1.0.0', true);
    wp_localize_script('wmf-live-search', 'wmf_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'wmf_enqueue_scripts');

function wmf_register_mood_taxonomy() {
    $labels = array(
        'name' => __('Moods', 'woocommerce-mood-filter'),
        'singular_name' => __('Mood', 'woocommerce-mood-filter'),
    );
    $args = array(
        'labels' => $labels,
        'hierarchical' => true,
        'show_admin_column' => true,
    );
    register_taxonomy('mood', 'product', $args);
}
add_action('init', 'wmf_register_mood_taxonomy');

function wmf_register_caffeine_taxonomy() {
    $labels = array(
        'name' => __('Caffeine Content', 'woocommerce-mood-filter'),
        'singular_name' => __('Caffeine', 'woocommerce-mood-filter'),
    );
    $args = array(
        'labels' => $labels,
        'hierarchical' => true,
        'show_admin_column' => true,
    );
    register_taxonomy('caffeine_content', 'product', $args);
}
add_action('init', 'wmf_register_caffeine_taxonomy');

function wmf_register_tea_type_taxonomy() {
    $labels = array(
        'name' => __('Tea Types', 'woocommerce-mood-filter'),
        'singular_name' => __('Tea Type', 'woocommerce-mood-filter'),
    );
    $args = array(
        'labels' => $labels,
        'hierarchical' => true,
        'show_admin_column' => true,
    );
    register_taxonomy('tea_type', 'product', $args);
}
add_action('init', 'wmf_register_tea_type_taxonomy');

function wmf_custom_search_filter($search, $wp_query) {
    if (!is_admin() && $wp_query->is_search && isset($_GET['search_query']) && !empty($_GET['search_query'])) {
        global $wpdb;
        $search_query = sanitize_text_field($_GET['search_query']);
        $like = '%' . $wpdb->esc_like($search_query) . '%';
        $search = $wpdb->prepare(
            " AND ((({$wpdb->posts}.post_title LIKE %s) OR ({$wpdb->posts}.post_content LIKE %s)))",
            $like, $like
        );
    }

    return $search;
}
add_filter('posts_search', 'wmf_custom_search_filter', 10, 2);

function wmf_filter_form_shortcode() {
    // Fetch all terms for the 'mood' and 'caffeine_content' taxonomies
    $moods = get_terms(array('taxonomy' => 'mood', 'hide_empty' => false));
    $caffeine_contents = get_terms(array('taxonomy' => 'caffeine_content', 'hide_empty' => false));

    ob_start();
    ?>
    <form method="get" id="wmf_filter_form" action="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>">
		<input type="hidden" name="post_type" value="product" />
<div>
    <label for="search_query">Search:</label>
</div>
        <div>
            <label for="mood">Mood:</label>
            <select name="mood" id="mood">
                <option value="">Select a mood</option>
                <?php foreach ($moods as $mood) : ?>
                    <option value="<?php echo $mood->slug; ?>"><?php echo $mood->name; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="caffeine_content">Caffeine Content:</label>
            <select name="caffeine_content" id="caffeine_content">
                <option value="">Select caffeine content</option>
                <?php foreach ($caffeine_contents as $caffeine_content) : ?>
                    <option value="<?php echo $caffeine_content->slug; ?>"><?php echo $caffeine_content->name; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
<div>
    <label for="tea_type">Tea Type:</label>
    <select name="tea_type" id="tea_type">
        <option value="">Select tea type</option>
        <?php
            $tea_types = get_terms(array('taxonomy' => 'tea_type', 'hide_empty' => false));
            foreach ($tea_types as $tea_type) :
        ?>
            <option value="<?php echo $tea_type->slug; ?>"><?php echo $tea_type->name; ?></option>
        <?php endforeach; ?>
    </select>
</div>

        <div>
            <label for="price_min">Price Range:</label>
            <input type="number" step="1" min="0" name="price_min" id="price_min" placeholder="Min">
            <input type="number" step="1" min="0" name="price_max" id="price_max" placeholder="Max">
        </div>

        <div>
            <label for="rating">Rating:</label>
            <input type="number" step="1" min="0" max="5" name="rating" id="rating" placeholder="Minimum rating">
        </div>

        <div>
            <label>Attributes:</label>
            <label><input type="checkbox" name="attributes[]" value="new"> New</label>
        </div>

        <div>
            <button type="submit">Filter Products</button>
        </div>
    </form>
    <?php
    return ob_get_clean();
}
function wmf_live_search_callback() {
    $search_query = isset($_POST['search_query']) ? sanitize_text_field($_POST['search_query']) : '';

    if (!empty($search_query)) {
        $args = array(
            'post_type' => 'product',
            's' => $search_query
        );

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                // Modify the output as needed
                echo '<div class="search-result">';
                echo '<a href="' . get_permalink() . '">' . get_the_title() . '</a>';
                echo '</div>';
            }
        } else {
            echo '<p>No products found.</p>';
        }

        wp_reset_postdata();
    }

    wp_die();
}
add_action('wp_ajax_wmf_live_search', 'wmf_live_search_callback');
add_action('wp_ajax_nopriv_wmf_live_search', 'wmf_live_search_callback');


function wmf_product_search_form_shortcode() {
    $shop_url = esc_url(get_permalink(wc_get_page_id('shop')));
    $search_value = isset($_GET['s']) ? esc_attr($_GET['s']) : '';

    $form = <<<HTML
<form role="search" method="get" class="wmf-product-search-form" action="{$shop_url}">
    <label for="wmf-product-search">
        <span class="screen-reader-text">Search for:</span>
    </label>
    <input type="search" id="wmf-product-search" class="search-field" placeholder="Search products&hellip;" value="{$search_value}" name="s" title="Search for:" />
    <input type="hidden" name="post_type" value="product" />
    <button type="submit" class="search-submit">Search</button>
</form>
HTML;

    return $form;
}
add_shortcode('wmf_product_search_form', 'wmf_product_search_form_shortcode');


add_shortcode('wmf_filter_form', 'wmf_filter_form_shortcode');

function wmf_filter_products($query) {
    if (!is_admin() && $query->is_main_query() && is_woocommerce()) {
        $meta_query = array();
        $tax_query = array();
// Filter by search query
if (isset($_GET['s']) && !empty($_GET['s'])) {
    $args['s'] = sanitize_text_field($_GET['s']);
}

        // Add your custom filtering logic here, based on $_GET parameters from the filter form.
        // For example, filtering by mood:
        if (isset($_GET['mood']) && !empty($_GET['mood'])) {
            $tax_query[] = array(
                'taxonomy' => 'mood',
                'field' => 'slug',
                'terms' => $_GET['mood'],
            );
        }

        // Add other filtering conditions for caffeine content, price, rating, new, retro
        // Filtering by caffeine content
        if (isset($_GET['caffeine_content']) && !empty($_GET['caffeine_content'])) {
            $tax_query[] = array(
                'taxonomy' => 'caffeine_content',
                'field' => 'slug',
                'terms' => $_GET['caffeine_content'],
            );
        }
	 // Filter by Tea Type
       if (isset($_GET['tea_type']) && !empty($_GET['tea_type'])) {
            $tax_query[] = array(
               'taxonomy' => 'tea_type',
               'field' => 'slug',
               'terms' => sanitize_text_field($_GET['tea_type']),
    );
}


        // Filtering by price range
        if (isset($_GET['price_min']) && isset($_GET['price_max'])) {
            $meta_query[] = array(
                'key' => '_price',
                'value' => array(floatval($_GET['price_min']), floatval($_GET['price_max'])),
                'type' => 'numeric',
                'compare' => 'BETWEEN',
            );
        }

        // Filtering by rating
        if (isset($_GET['rating']) && !empty($_GET['rating'])) {
            $meta_query[] = array(
                'key' => '_wc_average_rating',
                'value' => floatval($_GET['rating']),
                'type' => 'numeric',
                'compare' => '>=',
            );
        }

        // Filtering by product attributes (new, retro, upcoming)
        if (isset($_GET['attributes']) && !empty($_GET['attributes'])) {
            foreach ($_GET['attributes'] as $attribute) {
                $tax_query[] = array(
                    'taxonomy' => 'pa_' . $attribute,
                    'field' => 'slug',
                    'terms' => $attribute,
                );
            }
        }


        // Apply the meta and tax queries to the main query
        if (!empty($meta_query)) {
            $query->set('meta_query', $meta_query);
        }
        if (!empty($tax_query)) {
            $query->set('tax_query', $tax_query);
        }
    }
}
if (isset($_GET['mood']) || isset($_GET['caffeine_content']) || isset($_GET['price']) || isset($_GET['rating']) || isset($_GET['new']) || isset($_GET['retro']) || isset($_GET['upcoming']) || isset($_GET['tea_type'])) {
    add_action('woocommerce_product_query', 'wmf_filter_products');
}


function wmf_enqueue_scripts() {
    wp_enqueue_script('wmf-ajax-filter', plugins_url('ajax-filter.js', __FILE__), array('jquery'), '1.0', true);
    wp_localize_script('wmf-ajax-filter', 'wmf_ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'wmf_enqueue_scripts');
