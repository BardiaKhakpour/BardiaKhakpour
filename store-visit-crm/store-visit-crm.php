<?php
/**
 * Plugin Name: Store Visit CRM
 * Description: CRM plugin for sellers to log store visits with tasks, photos (with geolocation), KPI ratings and additional store info. Includes admin dashboard, KPI overview and shortcode interface.
 * Version: 1.1.0
 * Author: Example Author
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Store_Visit_CRM {
    public function __construct() {
        add_action( 'init', array( $this, 'register_role_capabilities' ) );
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_post' ) );
        add_shortcode( 'store_visit_form', array( $this, 'visit_form_shortcode' ) );
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_ajax_svc_submit_visit', array( $this, 'handle_visit_submission' ) );
    }

    public static function activate() {
        $instance = new self();
        $instance->register_role_capabilities();
    }

    public static function deactivate() {
        if ( get_role( 'sales_rep' ) ) {
            remove_role( 'sales_rep' );
        }
    }

    public function register_post_type() {
        register_post_type( 'store_visit', array(
            'public' => false,
            'show_ui' => true,
            'label'  => 'Store Visits',
            'supports' => array( 'title' )
        ) );
    }

    public function register_role_capabilities() {
        $role = get_role( 'administrator' );
        if ( $role && ! $role->has_cap( 'manage_store_visits' ) ) {
            $role->add_cap( 'manage_store_visits' );
        }

        if ( ! get_role( 'sales_rep' ) ) {
            add_role( 'sales_rep', 'Sales Representative', array( 'read' => true, 'manage_store_visits' => true ) );
        }
    }

    public function enqueue_assets() {
        wp_enqueue_script( 'svc-form', plugins_url( 'js/visit-form.js', __FILE__ ), array('jquery'), '1.0', true );
        wp_localize_script( 'svc-form', 'svc_form', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
        wp_enqueue_style( 'svc-style', plugins_url( 'css/style.css', __FILE__ ) );
    }

    public function add_meta_boxes() {
        add_meta_box( 'svc_details', 'Visit Details', array( $this, 'render_meta_box' ), 'store_visit', 'normal', 'default' );
    }

    public function render_meta_box( $post ) {
        $tasks   = get_post_meta( $post->ID, '_svc_tasks', true );
        $rating  = get_post_meta( $post->ID, '_svc_rating', true );
        $lat     = get_post_meta( $post->ID, '_svc_lat', true );
        $lng     = get_post_meta( $post->ID, '_svc_lng', true );
        $photo   = get_post_meta( $post->ID, '_svc_photo', true );
        $store   = get_post_meta( $post->ID, '_svc_store', true );
        $contact = get_post_meta( $post->ID, '_svc_contact', true );
        $next    = get_post_meta( $post->ID, '_svc_next_visit', true );
        ?>
        <p><strong>Tasks</strong></p>
        <ul>
            <?php if ( ! empty( $tasks ) && is_array( $tasks ) ) : foreach ( $tasks as $task ) : ?>
                <li><?php echo esc_html( $task ); ?></li>
            <?php endforeach; endif; ?>
        </ul>
        <p><strong>Rating:</strong> <?php echo intval( $rating ); ?></p>
        <?php if ( $store ) : ?><p><strong>Store:</strong> <?php echo esc_html( $store ); ?></p><?php endif; ?>
        <?php if ( $contact ) : ?><p><strong>Contact:</strong> <?php echo esc_html( $contact ); ?></p><?php endif; ?>
        <?php if ( $next ) : ?><p><strong>Next Visit:</strong> <?php echo esc_html( $next ); ?></p><?php endif; ?>
        <p><strong>Location:</strong> <?php echo esc_html( $lat ); ?>, <?php echo esc_html( $lng ); ?></p>
        <?php if ( $photo ) : ?>
            <p><img src="<?php echo esc_url( wp_get_attachment_url( $photo ) ); ?>" style="max-width:200px;" /></p>
        <?php endif; ?>
        <?php
    }

    public function save_post( $post_id ) {
        if ( get_post_type( $post_id ) !== 'store_visit' ) {
            return;
        }
        // tasks and other fields are saved via front end form
    }

    public function visit_form_shortcode() {
        if ( ! current_user_can( 'manage_store_visits' ) ) {
            return 'You do not have permission to log visits.';
        }
        ob_start();
        include plugin_dir_path( __FILE__ ) . 'templates/visit-form.php';
        return ob_get_clean();
    }

    public function admin_menu() {
        add_menu_page( 'Store Visit CRM', 'Store Visit CRM', 'manage_store_visits', 'store-visit-crm', array( $this, 'admin_page' ), 'dashicons-location-alt' );
        add_submenu_page( 'store-visit-crm', 'KPI Overview', 'KPI Overview', 'manage_store_visits', 'store-visit-crm-kpi', array( $this, 'kpi_page' ) );
    }

    public function admin_page() {
        $visits = get_posts( array( 'post_type' => 'store_visit', 'numberposts' => -1 ) );
        ?>
        <div class="wrap">
        <h1>Store Visit Reports</h1>
        <table class="widefat">
            <thead>
                <tr>
                    <th>Seller</th>
                    <th>Tasks</th>
                    <th>Rating</th>
                    <th>Store</th>
                    <th>Contact</th>
                    <th>Next Visit</th>
                    <th>Location</th>
                    <th>Photo</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $visits as $visit ) :
                    $tasks  = get_post_meta( $visit->ID, '_svc_tasks', true );
                    $rating = get_post_meta( $visit->ID, '_svc_rating', true );
                    $lat    = get_post_meta( $visit->ID, '_svc_lat', true );
                    $lng    = get_post_meta( $visit->ID, '_svc_lng', true );
                    $photo  = get_post_meta( $visit->ID, '_svc_photo', true );
                    $store  = get_post_meta( $visit->ID, '_svc_store', true );
                    $contact = get_post_meta( $visit->ID, '_svc_contact', true );
                    $next   = get_post_meta( $visit->ID, '_svc_next_visit', true );
                    ?>
                    <tr>
                        <td><?php echo esc_html( get_the_author_meta( 'display_name', $visit->post_author ) ); ?></td>
                        <td>
                            <ul>
                            <?php if ( ! empty( $tasks ) && is_array( $tasks ) ) : foreach ( $tasks as $task ) : ?>
                                <li><?php echo esc_html( $task ); ?></li>
                            <?php endforeach; endif; ?>
                            </ul>
                        </td>
                        <td><?php echo intval( $rating ); ?></td>
                        <td><?php echo esc_html( $store ); ?></td>
                        <td><?php echo esc_html( $contact ); ?></td>
                        <td><?php echo esc_html( $next ); ?></td>
                        <td><?php echo esc_html( $lat ); ?>, <?php echo esc_html( $lng ); ?></td>
                        <td><?php if ( $photo ) : ?><img src="<?php echo esc_url( wp_get_attachment_url( $photo ) ); ?>" style="max-width:100px;" /><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php
    }

    public function kpi_page() {
        $visits = get_posts( array( 'post_type' => 'store_visit', 'numberposts' => -1 ) );
        $data = array();
        foreach ( $visits as $visit ) {
            $author = $visit->post_author;
            if ( ! isset( $data[ $author ] ) ) {
                $data[ $author ] = array( 'count' => 0, 'rating_total' => 0 );
            }
            $data[ $author ]['count']++;
            $data[ $author ]['rating_total'] += intval( get_post_meta( $visit->ID, '_svc_rating', true ) );
        }
        ?>
        <div class="wrap">
        <h1>KPI Overview</h1>
        <table class="widefat">
            <thead>
                <tr>
                    <th>Seller</th>
                    <th>Total Visits</th>
                    <th>Average Rating</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $data as $author_id => $info ) : ?>
                    <tr>
                        <td><?php echo esc_html( get_the_author_meta( 'display_name', $author_id ) ); ?></td>
                        <td><?php echo intval( $info['count'] ); ?></td>
                        <td><?php echo $info['count'] ? round( $info['rating_total'] / $info['count'], 2 ) : '0'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php
    }

    public function handle_visit_submission() {
        check_ajax_referer( 'svc_visit_form', 'svc_nonce' );

        $user_id = get_current_user_id();
        if ( ! $user_id || ! current_user_can( 'manage_store_visits' ) ) {
            wp_send_json_error( 'Authentication required' );
        }

        $post_id = wp_insert_post( array(
            'post_type'   => 'store_visit',
            'post_status' => 'publish',
            'post_title'  => 'Visit ' . current_time( 'Y-m-d H:i:s' ),
            'post_author' => $user_id,
        ) );

        $tasks = array_filter( array_map( 'sanitize_text_field', explode( "\n", wp_unslash( $_POST['svc_tasks'] ?? '' ) ) ) );
        update_post_meta( $post_id, '_svc_tasks', $tasks );

        $rating = intval( $_POST['svc_rating'] ?? 0 );
        update_post_meta( $post_id, '_svc_rating', $rating );

        $lat = sanitize_text_field( $_POST['svc_lat'] ?? '' );
        $lng = sanitize_text_field( $_POST['svc_lng'] ?? '' );
        update_post_meta( $post_id, '_svc_lat', $lat );
        update_post_meta( $post_id, '_svc_lng', $lng );

        $store   = sanitize_text_field( $_POST['svc_store'] ?? '' );
        $contact = sanitize_text_field( $_POST['svc_contact'] ?? '' );
        $next    = sanitize_text_field( $_POST['svc_next_visit'] ?? '' );
        update_post_meta( $post_id, '_svc_store', $store );
        update_post_meta( $post_id, '_svc_contact', $contact );
        update_post_meta( $post_id, '_svc_next_visit', $next );

        if ( ! empty( $_FILES['svc_photo']['name'] ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            $attachment_id = media_handle_upload( 'svc_photo', $post_id );
            if ( ! is_wp_error( $attachment_id ) ) {
                update_post_meta( $post_id, '_svc_photo', $attachment_id );
            }
        }

        wp_send_json_success();
    }
}

new Store_Visit_CRM();

register_activation_hook( __FILE__, array( 'Store_Visit_CRM', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Store_Visit_CRM', 'deactivate' ) );
