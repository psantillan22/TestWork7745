<?php
function child_enqueue_styles(){
    $parent_style = 'storefront-style';
    wp_enqueue_style( $parent_style, get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array( $parent_style ),
        wp_get_theme()->get('Version')
    );

    wp_enqueue_script( 'custom-ajax-script', get_stylesheet_directory_uri() . '/js/custom.js', array('jquery'), null, true );
    wp_localize_script( 'custom-ajax-script', 'ajaxurl', admin_url( 'admin-ajax.php' ) );

}

add_action( 'wp_enqueue_scripts', 'child_enqueue_styles' );


// CUSTOM POST TYPE
function create_city_post_type() {
    $labels = array(
        'name'               => _x( 'Cities', 'post type general name', '' ),
        'singular_name'      => _x( 'City', 'post type singular name', '' ),
        'menu_name'          => _x( 'Cities', 'admin menu', '' ),
        'name_admin_bar'     => _x( 'City', 'add new on admin bar', '' ),
        'add_new'            => _x( 'Add New', 'city', '' ),
        'add_new_item'       => __( 'Add New City', '' ),
        'new_item'           => __( 'New City', '' ),
        'edit_item'          => __( 'Edit City', '' ),
        'view_item'          => __( 'View City', '' ),
        'all_items'          => __( 'All Cities', '' ),
        'search_items'       => __( 'Search Cities', '' ),
        'parent_item_colon'  => __( 'Parent Cities:', '' ),
        'not_found'          => __( 'No cities found.', '' ),
        'not_found_in_trash' => __( 'No cities found in Trash.', '' ),
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'cities' ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' ),
        'menu_icon'          => 'dashicons-location-alt',
    );

    register_post_type( 'cities', $args );
}

add_action( 'init', 'create_city_post_type' );

// META BOX
function city_location_meta_box() {
    add_meta_box(
        'city_location_box',
        'City Location',
        'display_city_location_meta_box',
        'cities', 
        'normal',
        'high'
    );
}

add_action( 'add_meta_boxes', 'city_location_meta_box' );

function display_city_location_meta_box( $post ) {
    
    $latitude = get_post_meta( $post->ID, '_city_latitude', true );
    $longitude = get_post_meta( $post->ID, '_city_longitude', true );
    ?>

    <label for="city_latitude">Latitude:</label>
    <input type="text" name="city_latitude" id="city_latitude" value="<?php echo esc_attr( $latitude ); ?>" size="25" />

    <br/><br/>

    <label for="city_longitude">Longitude:</label>
    <input type="text" name="city_longitude" id="city_longitude" value="<?php echo esc_attr( $longitude ); ?>" size="25" />

    <?php
}

function save_city_location_meta_box( $post_id ) {

    if ( isset( $_POST['post_type'] ) && 'cities' == $_POST['post_type'] ) {
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
    }
 
    if ( isset( $_POST['city_latitude'] ) ) {
        update_post_meta( $post_id, '_city_latitude', sanitize_text_field( $_POST['city_latitude'] ) );
    }

    if ( isset( $_POST['city_longitude'] ) ) {
        update_post_meta( $post_id, '_city_longitude', sanitize_text_field( $_POST['city_longitude'] ) );
    }
}

add_action( 'save_post', 'save_city_location_meta_box' );

// COUNTY TAXONOMY
function create_country_taxonomy() {
    $labels = array(
        'name'              => _x( 'Countries', 'taxonomy general name', '' ),
        'singular_name'     => _x( 'Country', 'taxonomy singular name', '' ),
        'search_items'      => __( 'Search Countries', '' ),
        'all_items'         => __( 'All Countries', '' ),
        'parent_item'       => __( 'Parent Country', '' ),
        'parent_item_colon' => __( 'Parent Country:', '' ),
        'edit_item'         => __( 'Edit Country', '' ),
        'update_item'       => __( 'Update Country', '' ),
        'add_new_item'      => __( 'Add New Country', '' ),
        'new_item_name'     => __( 'New Country Name', '' ),
        'menu_name'         => __( 'Countries', '' ),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'country' ),
    );

    register_taxonomy( 'country', array( 'cities' ), $args );
}

add_action( 'init', 'create_country_taxonomy', 0 );


// CUSTOM WIDGET
class City_Widget extends WP_Widget {

    function __construct() {
        parent::__construct(
            'City_Widget',
            __('City', ''), 
            array( 'description' => __( 'A widget that displays a city from the Cities post type.', '' ) )
        );
    }

    public function widget( $args, $instance ) {
        echo $args['before_widget']; 

        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
        }

        $latitude = get_post_meta($instance['city_id'], '_city_latitude', true);
        $longitude = get_post_meta($instance['city_id'], '_city_longitude', true);
        $city = get_the_title($instance['city_id']);

        $temperature = get_city_temperature($latitude, $longitude);
      
        echo '<p>Temperature in ' . esc_html( $city ) . ': ' . esc_html( $temperature ) . '° </p>';

        echo $args['after_widget']; 
    }

    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'City Temperature', '' );
        $city_id = ! empty( $instance['city_id'] ) ? $instance['city_id'] : '';

        $cities = get_posts( array(
            'post_type'   => 'cities',
            'numberposts' => -1
        ) );

        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title:', '' ); ?></label> 
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>

        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'city_id' ) ); ?>"><?php _e( 'Select City:', '' ); ?></label>
            <select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'city_id' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'city_id' ) ); ?>">
                <option value=""><?php _e( 'Select a city', '' ); ?></option>
                <?php foreach ( $cities as $city ) : ?>
                    <option value="<?php echo esc_attr( $city->ID ); ?>" <?php selected( $city_id, $city->ID ); ?>>
                        <?php echo esc_html( $city->post_title ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php 
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
        $instance['city_id'] = ( ! empty( $new_instance['city_id'] ) ) ? absint( $new_instance['city_id'] ) : '';

        return $instance;
    }
}

function register_city_widget() {
    register_widget( 'City_Widget' );
}

add_action( 'widgets_init', 'register_city_widget' );

function search_cities_callback() {
    global $wpdb;

    $search_query = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

    $query = $wpdb->prepare("SELECT p.ID, p.post_title, pm1.meta_value AS latitude, pm2.meta_value AS longitude, t.name AS country
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->term_relationships} tr ON (p.ID = tr.object_id)
        INNER JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
        INNER JOIN {$wpdb->terms} t ON (tt.term_id = t.term_id)
        LEFT JOIN {$wpdb->postmeta} pm1 ON (p.ID = pm1.post_id AND pm1.meta_key = '_city_latitude')
        LEFT JOIN {$wpdb->postmeta} pm2 ON (p.ID = pm2.post_id AND pm2.meta_key = '_city_longitude')
        WHERE p.post_type = 'cities'
        AND p.post_status = 'publish'
        AND tt.taxonomy = 'country'
        AND p.post_title LIKE %s
    ", '%' . $wpdb->esc_like($search_query) . '%');

    $cities = $wpdb->get_results($query);

    if (!empty($cities)) {
        foreach ($cities as $city) {
            $temperature = get_city_temperature($city->latitude, $city->longitude);

            echo '<tr>';
            echo '<td>' . esc_html($city->country) . '</td>';
            echo '<td>' . esc_html($city->post_title) . '</td>';
            echo '<td>' . esc_html($city->latitude) . '</td>';
            echo '<td>' . esc_html($city->longitude) . '</td>';
            echo '<td>' . esc_html($temperature) . '° </td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="4">No cities found.</td></tr>';
    }

    wp_die();
}

add_action('wp_ajax_search_cities', 'search_cities_callback');
add_action('wp_ajax_nopriv_search_cities', 'search_cities_callback');

// API REFERENCE
function get_city_temperature($lat, $long) {
    $api_key = 'fc19d384fae1a6149915bba90d382e2d';
    $api_link = 'https://api.openweathermap.org/data/2.5/weather?';

    $data = array(
        'lat' => $lat,
        'lon' => $long,
        'appid' => $api_key
    );

    $api_url = $api_link.http_build_query($data);

    $response = wp_remote_get( $api_url );

    $temperature = '';

    if ( is_wp_error( $response ) ) {
        $temperature = '';
    } else {
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( isset( $data['main'] ) ) {
            $temperature = $data['main']['temp'];
        } else {
            $temperature = 'City not found or unable to retrieve temperature data.';
        }
    }

    return $temperature;
}

// CUSTOM HOOKS BEFORE AND AFTER TABLE
function add_before_table() {
    echo '<p>This is a custom content before the table.</p>';
}
add_action('before_cities_table', 'add_before_table');

function add_after_table() {
    echo '<p>This is custom content after the table</p>';
}
add_action('after_cities_table', 'add_after_table');