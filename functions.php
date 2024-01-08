<?php

// Include search-route.php & like-route.php file from the 'inc' directory
require get_theme_file_path('/inc/search-route.php');
require get_theme_file_path('/inc/like-route.php');

// Register a custom REST field 'authorName' for the 'post' type
function university_custom_rest()
{
    register_rest_field('post', 'authorName', array(
        'get_callback' => function () {
            return get_the_author();
        }
    ));

    register_rest_field('note', 'userNoteCount', array(
        'get_callback' => function () {
            return count_user_posts(get_current_user_id(), 'note');
        }
    ));
}

// Hook the custom REST registration function to the REST API initialization
add_action('rest_api_init', 'university_custom_rest');

// Function to display a page banner with optional parameters
function pageBanner($args = NULL)
{
    // Check if $args is an array, initialize as an empty array if not
    if (!is_array($args)) {
        $args = array();
    }

    // Set default values for title and subtitle
    $args['title'] = isset($args['title']) ? $args['title'] : get_the_title();
    $args['subtitle'] = isset($args['subtitle']) ? $args['subtitle'] : get_field('page_banner_subtitle');

    // Set default photo if not provided
    if (!isset($args['photo'])) {
        $args['photo'] = get_field('page_banner_background_image') ?
            get_field('page_banner_background_image')['sizes']['pageBanner'] :
            get_theme_file_uri('/images/ocean.jpg');
    }
?>
    <!-- Display page banner with dynamic content -->
    <div class="page-banner">
        <div class="page-banner__bg-image" style="background-image: url(<?php echo esc_url($args['photo']); ?>);"></div>
        <div class="page-banner__content container container--narrow">
            <h1 class="page-banner__title"><?php echo wp_kses_post($args['title']); ?></h1>
            <div class="page-banner__intro">
                <p><?php echo wp_kses_post($args['subtitle']); ?></p>
            </div>
        </div>
    </div>
<?php
}

// Enqueue scripts and styles for the theme
function university_files()
{
    // Enqueue Google Maps script, main JavaScript file, and styles
    // Localize script with site URL and nonce for REST API requests
    wp_enqueue_script('googleMap', '//maps.googleapis.com/maps/api/js?key=yourGoogleMapsAPIKey', NULL, '1.0', true);
    wp_enqueue_script('main-university-js', get_theme_file_uri('/build/index.js'), array('jquery'), '1.0', true);
    wp_enqueue_style('custom-google-fonts', '//fonts.googleapis.com/css?family=Roboto+Condensed:300,300i,400,400i,700,700i|Roboto:100,300,400,400i,700,700i');
    wp_enqueue_style('font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
    wp_enqueue_style('university_main_styles', get_theme_file_uri('/build/style-index.css'));
    wp_enqueue_style('university_extra_styles', get_theme_file_uri('/build/index.css'));

    wp_localize_script('main-university-js', 'universityData', array(
        'root_url' => get_site_url(),
        'nonce' => wp_create_nonce('wp_rest')
    ));
}

// Hook the theme files function to the script and styles enqueue
add_action('wp_enqueue_scripts', 'university_files');

// Add theme support for title, post thumbnails, and custom image sizes
function university_features()
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_image_size('professorLandscape', 400, 260, true);
    add_image_size('professorPortrait', 480, 650, true);
    add_image_size('pageBanner', 1500, 350, true);
}

// Hook the theme features function to the theme setup
add_action('after_setup_theme', 'university_features');

// Modify queries for specific post types (campus, program, event)
function university_adjust_queries($query)
{
    if (!is_admin() && is_post_type_archive('campus') && $query->is_main_query()) {
        $query->set('posts_per_page', -1);
    }

    if (!is_admin() && is_post_type_archive('program') && $query->is_main_query()) {
        $query->set('orderby', 'title');
        $query->set('order', 'ASC');
        $query->set('posts_per_page', -1);
    }

    if (!is_admin() && is_post_type_archive('event') && $query->is_main_query()) {
        $today = date('Ymd');
        $query->set('meta_key', 'event_date');
        $query->set('orderby', 'meta_value_num');
        $query->set('order', 'ASC');
        $query->set('meta_query', array(
            array(
                'key' => 'event_date',
                'compare' => '>=',
                'value' => $today,
                'type' => 'numeric'
            )
        ));
    }
}

// Hook the query adjustments to the pre_get_posts action
add_action('pre_get_posts', 'university_adjust_queries');

// Set Google Maps API key for Advanced Custom Fields (ACF)
function universityMapKey($api)
{
    $api['key'] = 'yourKeyGoesHere';
    return $api;
}

// Hook the ACF Google Map API key function to the ACF Google Map API filter
add_filter('acf/fields/google_map/api', 'universityMapKey');

// Redirect subscriber accounts out of admin and onto the homepage
add_action('admin_init', 'redirectSubsToFrontend');

// Function to redirect subscribers to the homepage on admin login
function redirectSubsToFrontend()
{
    $ourCurrentUser = wp_get_current_user();

    if (count($ourCurrentUser->roles) == 1 && $ourCurrentUser->roles[0] == 'subscriber') {
        wp_redirect(site_url('/'));
        exit;
    }
}

// Function to hide the admin bar for subscribers
add_action('wp_loaded', 'noSubsAdminBar');

// Function to hide the admin bar for subscribers
function noSubsAdminBar()
{
    $ourCurrentUser = wp_get_current_user();

    if (count($ourCurrentUser->roles) == 1 && $ourCurrentUser->roles[0] == 'subscriber') {
        show_admin_bar(false);
    }
}

// Customize Login Screen
add_filter('login_headerurl', 'ourHeaderUrl');

// Function to return the custom header URL for login screen
function ourHeaderUrl()
{
    return esc_url(site_url('/'));
}

// Enqueue styles for the login screen
add_action('login_enqueue_scripts', 'ourLoginCSS');

// Function to enqueue custom styles for the login screen
function ourLoginCSS()
{
    wp_enqueue_style('custom-google-fonts', '//fonts.googleapis.com/css?family=Roboto+Condensed:300,300i,400,400i,700,700i');
    wp_enqueue_style('font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
    wp_enqueue_style('university_main_styles', get_theme_file_uri('/build/style-index.css'));
    wp_enqueue_style('university_extra_styles', get_theme_file_uri('/build/index.css'));
}

// Customize Login Screen Title
add_filter('login_headertitle', 'ourLoginTitle');

// Function to return the custom login screen title
function ourLoginTitle()
{
    return get_bloginfo('name');
}

// Force note posts to be private and limit the number of notes per user
add_filter('wp_insert_post_data', 'makeNotePrivate', 10, 2);

// Function to make note posts private and enforce a limit
function makeNotePrivate($data, $postarr)
{
    if ($data['post_type'] == 'note') {
        if (count_user_posts(get_current_user_id(), 'note') > 4 && !$postarr['ID']) {
            die("You have reached your note limit.");
        }

        $data['post_content'] = sanitize_textarea_field($data['post_content']);
        $data['post_title'] = sanitize_text_field($data['post_title']);
    }

    if ($data['post_type'] == 'note' && $data['post_status'] != 'trash') {
        $data['post_status'] = "private";
    }

    return $data;
}


add_filter('ai1wm_exclude_content_from_export', 'ignoreCertainFiles');

function ignoreCertainFiles()
{
    $exclude_filters[] = 'themes/trooper-university-theme/node_modules';
    return $exclude_filters;
}
