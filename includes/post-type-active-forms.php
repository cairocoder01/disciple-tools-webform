<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly
/**
 * DT_Webform_Active_Form_Post_Type
 */

/**
 * Initialize class
 */
DT_Webform_Active_Form_Post_Type::instance();

/**
 * Class DT_Webform_Active_Form_Post_Type
 */
class DT_Webform_Active_Form_Post_Type
{
    public $post_type;
    public $form_type;
    public $post_id;
    public $contact_fields;

    /**
     * DT_Webform_Active_Form_Post_Type The single instance of DT_Webform_Active_Form_Post_Type.
     *
     * @var    object
     * @access private
     * @since  0.1.0
     */
    private static $_instance = null;

    /**
     * Main DT_Webform_Active_Form_Post_Type Instance
     * Ensures only one instance of DT_Webform_Active_Form_Post_Type is loaded or can be loaded.
     *
     * @since  0.1.0
     * @static
     * @return DT_Webform_Active_Form_Post_Type instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    } // End instance()

    public function __construct() {
        $this->post_type = 'dt_webform_forms';

        add_action( 'init', [ $this, 'register_post_type' ] );

        if ( is_admin() ) {
            if ( isset( $_GET['post'] ) ) {
                $this->post_id = sanitize_text_field( wp_unslash( $_GET['post'] ) );
            }
            $this->form_type = get_post_meta( $this->post_id, 'form_type', true );

            $this->contact_fields = DT_Webform_Utilities::get_contact_defaults();
            if ( is_wp_error( $this->contact_fields ) ) {
                $this->contact_fields = [ 'sources' => [], 'fields' => [], 'channels' => [], 'address_types' => [], 'connection_types' => [] ];
            }

            add_action( 'admin_menu', [ $this, 'meta_box_setup' ], 20 );
            add_action( 'save_post', [ $this, 'meta_box_save' ] );
            add_action( 'save_post', [ $this, 'save_core_fields' ] );
            add_action( 'save_post', [ $this, 'save_extra_fields' ] );
            add_action( 'admin_head', [ $this, 'scripts' ], 20 );
        }
    }


    // Register Custom Post Type
    public function register_post_type() {

        $labels = array(
        'name'                  => _x( 'Forms', 'Post Type General Name', 'dt_webform' ),
        'singular_name'         => _x( 'Form', 'Post Type Singular Name', 'dt_webform' ),
        'menu_name'             => __( 'Forms', 'dt_webform' ),
        'name_admin_bar'        => __( 'Form', 'dt_webform' ),
        'archives'              => __( 'Form Archives', 'dt_webform' ),
        'attributes'            => __( 'Form Attributes', 'dt_webform' ),
        'parent_item_colon'     => __( 'Parent Form:', 'dt_webform' ),
        'all_items'             => __( 'All Forms', 'dt_webform' ),
        'add_new_item'          => __( 'Add New Form', 'dt_webform' ),
        'add_new'               => __( 'Add New', 'dt_webform' ),
        'new_item'              => __( 'New Form', 'dt_webform' ),
        'edit_item'             => __( 'Edit Form', 'dt_webform' ),
        'update_item'           => __( 'Update Form', 'dt_webform' ),
        'view_item'             => __( 'View Form', 'dt_webform' ),
        'view_items'            => __( 'View Forms', 'dt_webform' ),
        'search_items'          => __( 'Search Form', 'dt_webform' ),
        'not_found'             => __( 'Not found', 'dt_webform' ),
        'not_found_in_trash'    => __( 'Not found in Trash', 'dt_webform' ),
        'featured_image'        => __( 'Featured Image', 'dt_webform' ),
        'set_featured_image'    => __( 'Set featured image', 'dt_webform' ),
        'remove_featured_image' => __( 'Remove featured image', 'dt_webform' ),
        'use_featured_image'    => __( 'Use as featured image', 'dt_webform' ),
        'insert_into_item'      => __( 'Add item', 'dt_webform' ),
        'uploaded_to_this_item' => __( 'Uploaded to this form', 'dt_webform' ),
        'items_list'            => __( 'Forms list', 'dt_webform' ),
        'items_list_navigation' => __( 'Forms list navigation', 'dt_webform' ),
        'filter_items_list'     => __( 'Filter forms list', 'dt_webform' ),
        );
        $args = array(
        'label'                 => __( 'Form', 'dt_webform' ),
        'description'           => __( 'DT Webform Forms', 'dt_webform' ),
        'labels'                => $labels,
        'supports'              => array( 'title' ),
        'hierarchical'          => false,
        'public'                => false,
        'show_ui'               => true,
        'show_in_menu'          => false,
        'menu_position'         => 5,
        'show_in_admin_bar'     => false,
        'show_in_nav_menus'     => false,
        'can_export'            => false,
        'has_archive'           => false,
        'exclude_from_search'   => true,
        'publicly_queryable'    => false,
        'capability_type'       => 'page',
        'show_in_rest'          => false,
        );
        register_post_type( $this->post_type, $args );

    }

    /**
     * Setup the meta box.
     *
     * @access public
     * @since  0.1.0
     * @return void
     */
    public function meta_box_setup() {
        add_meta_box( $this->post_type . '_info_box', __( 'Form Details', 'dt_webform' ), [ $this, 'load_info_meta_box' ], $this->post_type, 'normal', 'high' );
        add_meta_box( $this->post_type . '_core_fields', __( 'Core Fields', 'dt_webform' ), [ $this, 'load_core_fields_metabox' ], $this->post_type, 'normal', 'high' );
        add_meta_box( $this->post_type . '_extra_fields', __( 'Extra Fields', 'dt_webform' ), [ $this, 'load_extra_fields_meta_box' ], $this->post_type, 'normal', 'high' );
        add_meta_box( $this->post_type . '_appearance_box', __( 'Form Appearance', 'dt_webform' ), [ $this, 'load_appearance_meta_box' ], $this->post_type, 'normal', 'high' );
        add_meta_box( $this->post_type . '_demo', __( 'Demo', 'dt_webform' ), [ $this, 'load_demo_meta_box' ], $this->post_type, 'normal', 'low' );
        add_meta_box( $this->post_type . '_localize', __( 'Localize', 'dt_webform' ), [ $this, 'load_localize_meta_box' ], $this->post_type, 'normal', 'low' );
        add_meta_box( $this->post_type . '_embed', __( 'Embed Code', 'dt_webform' ), [ $this, 'load_embed_meta_box' ], $this->post_type, 'side', 'high' );
        add_meta_box( $this->post_type . '_css', __( 'Form Styles', 'dt_webform' ), [ $this, 'load_form_styles_meta_box' ], $this->post_type, 'side', 'low' );
        add_meta_box( $this->post_type . '_dt_fields_list', __( 'DT Fields List', 'dt_webform' ), [ $this, 'load_dt_fields_list_meta_box' ], $this->post_type, 'side', 'low' );

    }

    /**
     * Load type metabox
     */
    public function load_info_meta_box( $post ) {
        $this->meta_box_content( 'info' ); // prints
    }

    public function load_form_styles_meta_box( $post ) {

        $css = DT_Webform_Utilities::get_theme( 'get-default-css', get_post_meta( $post->ID, 'token', true ) );
        echo nl2br( esc_html( $css ) );

    }

    public function load_dt_fields_list_meta_box( $post ) {
        // get contact defaults
        $contact_defaults = $this->contact_fields;
        if ( ! isset( $contact_defaults['fields'] ) ) {
            return;
        }
        foreach ($contact_defaults['fields'] as $key => $value) {
            echo '<strong>' . esc_attr( $key ) . '</strong> (' . esc_attr( $value['type'] ) . ')<br>';
            if ( ! empty( $value['default'] ) && is_array( $value['default'] ) ) {
                foreach ( $value['default'] as $k => $v) {
                    if ('connection_types' === $key) {
                        echo ' &nbsp;&nbsp; ' . esc_html( $v ) . '<br>';
                    } else {
                        echo ' &nbsp;&nbsp; ' . esc_html( $k ) . '<br>';
                    }
                }
            }
        }
    }

    public function load_localize_meta_box() {
        global $pagenow;
        if ( 'post-new.php' == $pagenow ) {

            echo esc_attr__( 'Leads list will display after you save the new form', 'dt_webform' );
            echo '<div style="display:none;">';
            $this->meta_box_content( 'appearance' ); // prints
            echo '</div>';

        } else {
            $this->meta_box_content( 'localize' ); // prints
        }
    }

    /**
     * Load type metabox
     */
    public function load_appearance_meta_box() {
        global $pagenow;

        if ( 'post-new.php' == $pagenow ) {

            echo esc_attr__( 'Leads list will display after you save the new form', 'dt_webform' );
            echo '<div style="display:none;">';
            $this->meta_box_content( 'appearance' ); // prints
            echo '</div>';

        } else {
            $this->meta_box_content( 'appearance' ); // prints
        }
    }

    /**
     * Load demo metabox
     */
    public function load_demo_meta_box( $post ) {
        global $pagenow;

        if ( 'post-new.php' == $pagenow ) {
            echo esc_attr__( 'Embed code will display after you save the new form', 'dt_webform' );
        }
        else {
            // WordPress.XSS.EscapeOutput.OutputNotEscaped
            // @phpcs:ignore
            echo $this->embed_code( $post->ID );
            ?>
            <hr>
            <?php
            $this->direct_link();
        }
    }


    /**
     * Load embed metabox
     */
    public function load_embed_meta_box( $post ) {
        global $pagenow;

        if ( 'post-new.php' == $pagenow ) {
            echo esc_attr__( 'Embed code will display after you save the new form', 'dt_webform' );
        }
        else {
            ?>
            <label for="embed-code">Copy and Paste this embed code</label><br>
            <textarea style="width:100%; height:200px;"><?php
                // WordPress.XSS.EscapeOutput.OutputNotEscaped
                // @phpcs:ignore
                echo $this->embed_code( $post->ID );
            ?></textarea><br>
            <?php
            $this->direct_link();
        }
    }

    public function direct_link() {
        global $post;
        $token = get_metadata( 'post', $post->ID, 'token', true );
        $site = dt_webform()->public_uri;
        ?>
        <div style="text-align:center;">
            <a href="<?php echo esc_url( $site ) ?>form.php?token=<?php echo esc_attr( $token ) ?>" target="_blank">Open form in its own window.</a>
        </div>
        <?php
    }

    public function embed_code( $post_id ) {
        $width = get_post_meta( $post_id, 'width', true );
        if ( ! ( substr( $width, -2, 2 ) === 'px' || substr( $width, -1, 1 ) === '%' ) ) {
            $width = '100%';
            update_post_meta( $post_id, 'width', $width );
        }
        $height = get_metadata( 'post', $post_id, 'height', true );
        if ( ! ( substr( $height, -2, 2 ) === 'px' || substr( $height, -1, 1 ) === '%' ) ) {
            $height = '550px';
            update_post_meta( $post_id, 'height', $height );
        }
        $token = get_metadata( 'post', $post_id, 'token', true );
        $site = dt_webform()->public_uri;

        return '<iframe src="'. esc_url( $site ) .'form.php?token='. esc_attr( $token )
            .'" style="width:'. esc_attr( $width ) .';height:'. esc_attr( $height ) .';" frameborder="0"></iframe>';
    }



    /**
     * The contents of our meta box.
     *
     * @param string $section
     */
    public function meta_box_content( $section = 'info' ) {
        global $post_id;
        $fields = get_post_custom( $post_id );
        $field_data = $this->get_custom_fields_settings();

        echo '<input type="hidden" name="' . esc_attr( $this->post_type ) . '_noonce" id="' . esc_attr( $this->post_type ) . '_noonce" value="' . esc_attr( wp_create_nonce( 'update_dt_webforms' ) ) . '" />';

        if ( 0 < count( $field_data ) ) {
            echo '<table class="form-table">' . "\n";
            echo '<tbody>' . "\n";

            foreach ( $field_data as $k => $v ) {

                if ( $v['section'] === $section || $section === 'all' ) {

                    $data = $v['default'];

                    if ( isset( $fields[ $k ] ) && isset( $fields[ $k ][0] ) ) {
                        $data = $fields[ $k ][0];
                    }

                    $type = $v['type'];

                    switch ( $type ) {

                        case 'text':
                            echo '<tr valign="top"><th scope="row"><label for="' . esc_attr( $k ) . '">' . esc_attr( $v['name'] ) . '</label></th><td><input name="' . esc_attr( $k ) . '" type="text" id="' . esc_attr( $k ) . '" class="regular-text" value="' . esc_attr( $data ) . '" />' . "\n";
                            echo '<p class="description">' . esc_html( $v['description'] ) . '</p>' . "\n";
                            echo '</td><tr/>' . "\n";
                            break;
//                        case 'sources':
//                            $contact_defaults = $this->contact_fields;
//                            if ( ! isset( $contact_defaults['sources'] ) ) {
//                                break;
//                            }
//                            echo '<tr class="' . esc_attr( $v['section'] ) . '" id="row_' . esc_attr( $k ) . '" valign="top"><th scope="row">
//                                <label for="' . esc_attr( $k ) . '">' . esc_attr( $v['name'] ) . '</label></th>
//                                <td>
//                                <select name="' . esc_attr( $k ) . '" id="' . esc_attr( $k ) . '" class="regular-text">';
//                            // Iterate the options
//                            foreach ( $contact_defaults['sources']  as $kk => $vv ) {
//                                echo '<option value="' . esc_attr( $kk ) . '" ';
//                                if ( $kk == $data ) {
//                                    echo 'selected';
//                                }
//                                echo '>' . esc_attr( $vv['label'] ) . '</option>';
//                            }
//                            echo '</select>' . "\n";
//                            echo '<p class="description">' . esc_attr( $v['description'] ) . '</p>' . "\n";
//                            echo '</td><tr/>' . "\n";
//                            break;
                        case 'number':
                            echo '<tr valign="top"><th scope="row"><label for="' . esc_attr( $k ) . '">' . esc_attr( $v['name'] ) . '</label></th><td><input name="' . esc_attr( $k ) . '" type="number" id="' . esc_attr( $k ) . '" class="regular-text" value="' . esc_attr( $data ) . '" />' . "\n";
                            echo '<p class="description">' . esc_html( $v['description'] ) . '</p>' . "\n";
                            echo '</td><tr/>' . "\n";
                            break;
                        case 'textarea':
                            echo '<tr valign="top"><th scope="row"><label for="' . esc_attr( $k ) . '">' . esc_attr( $v['name'] ) . '</label></th><td><textarea name="' . esc_attr( $k ) . '" style="width:100%;" type="text" id="' . esc_attr( $k ) . '" class="regular-text" rows="5" />' . esc_attr( $data ) . '</textarea>' . "\n";
                            echo '<p class="description">' . esc_html( $v['description'] ) . '</p>' . "\n";
                            echo '</td><tr/>' . "\n";
                            break;
                        case 'display_only':
                            echo '<tr valign="top"><th scope="row"><label for="' . esc_attr( $k ) . '">' . esc_attr( $v['name'] ) . '</label></th><td>' . esc_attr( $data )  . "\n";
                            echo '<input name="' . esc_attr( $k ) . '" type="hidden" id="' . esc_attr( $k ) . '" class="regular-text" value="' . esc_attr( $data ) . '" />';
                            echo '<p class="description">' . esc_html( $v['description'] ) . '</p>' . "\n";
                            echo '</td><tr/>' . "\n";
                            break;
                        case 'hidden':
//                            echo '<input name="' . esc_attr( $k ) . '" type="hidden" id="' . esc_attr( $k ) . '" class="regular-text" value="' . esc_attr( $data ) . '" />';
                            break;
                        case 'date':
                            echo '<tr valign="top"><th scope="row"><label for="' . esc_attr( $k ) . '">' . esc_attr( $v['name'] ) . '</label></th><td>
                                    <input name="' . esc_attr( $k ) . '" class="datepicker regular-text" type="text" id="' . esc_attr( $k ) . '"  value="' . esc_attr( $data ) . '" />' . "\n";
                            echo '<p class="description">' . esc_html( $v['description'] ) . '</p>' . "\n";
                            echo '</td><tr/>' . "\n";

                            break;
                        case 'key_select':
                            echo '<tr class="' . esc_attr( $v['section'] ) . '" id="row_' . esc_attr( $k ) . '" valign="top"><th scope="row">
                                <label for="' . esc_attr( $k ) . '">' . esc_attr( $v['name'] ) . '</label></th>
                                <td>
                                <select name="' . esc_attr( $k ) . '" id="' . esc_attr( $k ) . '" class="regular-text">';
                            // Iterate the options
                            foreach ( $v['default'] as $kk => $vv ) {
                                echo '<option value="' . esc_attr( $kk ) . '" ';
                                if ( $kk == $data ) {
                                    echo 'selected';
                                }
                                echo '>' . esc_attr( $vv ) . '</option>';
                            }
                            echo '</select>' . "\n";
                            echo '<p class="description">' . esc_attr( $v['description'] ) . '</p>' . "\n";
                            echo '</td><tr/>' . "\n";
                            break;

                        default:
                            break;
                    }
                }
            }

            echo '</tbody>' . "\n";
            echo '</table>' . "\n";
            ?>
            <div>
                <br clear="all">
                <span style="float:right;" id="update_fields_button"><button type="submit" class="button">Update</button> </span>
                <br clear="all">
            </div>
            <?php

        }
    } // End meta_box_content()

    /**
     * Save meta box fields.
     *
     * @param int $post_id
     *
     * @return int
     * @throws \Exception 'Expected field to exist'.
     */
    public function meta_box_save( int $post_id ) {

        // Verify
        if ( get_post_type() !== $this->post_type ) {
            return $post_id;
        }
        $nonce_key = $this->post_type . '_noonce';
        if ( isset( $_POST[ $nonce_key ] ) && !wp_verify_nonce( sanitize_key( $_POST[ $nonce_key ] ), 'update_dt_webforms' ) ) {
            return $post_id;
        }

        if ( isset( $_POST['post_type'] ) && 'page' == esc_attr( sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) ) ) {
            if ( !current_user_can( 'manage_dt', $post_id ) ) {
                return $post_id;
            }
        } else {
            if ( !current_user_can( 'manage_dt', $post_id ) ) {
                return $post_id;
            }
        }

        if ( isset( $_GET['action'] ) ) {
            if ( $_GET['action'] == 'trash' || $_GET['action'] == 'untrash' || $_GET['action'] == 'delete' ) {
                return $post_id;
            }
        }
        if ( get_post_status( $post_id ) === "draft" ){
            wp_update_post( [
                'ID' => $post_id,
                'post_status' => 'publish'
            ] );
        }

        $field_data = $this->get_custom_fields_settings();
        $fields = array_keys( $field_data );

        foreach ( $fields as $f ) {

            if ( isset( $_POST[ $f ] ) ) {

                ${$f} = strip_tags( trim( sanitize_text_field( wp_unslash( $_POST[ $f ] ) ) ) );

                if ( get_post_meta( $post_id, $f ) == '' ) {
                    add_post_meta( $post_id, $f, ${$f}, true );
                } elseif ( ${$f} == '' ) {
                    delete_post_meta( $post_id, $f, get_post_meta( $post_id, $f, true ) );
                } elseif ( ${$f} != get_post_meta( $post_id, $f, true ) ) {
                    update_post_meta( $post_id, $f, ${$f} );
                }
            } else {
                dt_write_log( "Expected field $f to exist in " . __METHOD__ );
            }
        }

        return $post_id;
    } // End meta_box_save()

    /**
     * Get the settings for the custom fields.
     *
     * @return mixed
     */
    public function get_custom_fields_settings() {

        $fields = [];

        $fields['description'] = [
        'name'        => __( 'Description', 'dt_webform' ),
        'description' => '',
        'type'        => 'textarea',
        'default'     => '',
        'section'     => 'info',
        ];
        $fields['token'] = [
        'name'        => __( 'Token', 'dt_webform' ),
        'description' => '',
        'type'        => 'display_only',
        'default'     => bin2hex( random_bytes( 16 ) ),
        'section'     => 'info',
        ];
        $fields['form_type'] = [
        'name'        => __( 'Form Type', 'dt_webform' ),
        'description' => '',
        'type'        => 'key_select',
        'default'     => $this->form_types(),
        'section'     => 'info',
        ];




        $fields['width'] = [
        'name'        => __( 'Width', 'dt_webform' ),
        'description' => __( 'ex. 400px or 100%', 'dt_webform' ),
        'type'        => 'text',
        'default'     => '100%',
        'section'     => 'appearance',
        ];
        $fields['height'] = [
        'name'        => __( 'Height', 'dt_webform' ),
        'description' => __( 'number of pixels', 'dt_webform' ),
        'type'        => 'text',
        'default'     => '550px',
        'section'     => 'appearance',
        ];
        $fields['theme'] = [
            'name'        => __( 'Theme', 'dt_webform' ),
            'description' => '',
            'type'        => 'key_select',
            'default'     => [
                'simple' => __( 'Simple', 'dt_webform' ),
                'heavy'   => __( 'Heavy', 'dt_webform' ),
                'wide-heavy'   => __( 'Wide Heavy', 'dt_webform' ),
                'none'   => __( 'None', 'dt_webform' ),
                'inherit'   => __( 'Inherit', 'dt_webform' ),
            ],
            'section'     => 'appearance',
        ];
        $fields['custom_css'] = [
            'name'        => __( 'Custom CSS', 'dt_webform' ),
            'description' => 'See "Form Styles" box for a list of ids and classes.',
            'type'        => 'textarea',
            'default'     => '',
            'section'     => 'appearance',
        ];


        $fields['js_string_required'] = [
            'name'        => __( 'Required', 'dt_webform' ),
            'description' => __( 'translate: "Required"', 'dt_webform' ),
            'type'        => 'text',
            'default'     => 'Required',
            'section'     => 'localize',
        ];
        $fields['js_string_char_required'] = [
            'name'        => __( 'Characters Required', 'dt_webform' ),
            'description' => __( 'translate: "At least {0} characters required! Note: {0} must be included to be replaced with the number of characters."', 'dt_webform' ),
            'type'        => 'text',
            'default'     => 'At least {0} characters required!',
            'section'     => 'localize',
        ];
        $fields['js_string_submit'] = [
            'name'        => __( 'Submit', 'dt_webform' ),
            'description' => __( 'translate: "Submit"', 'dt_webform' ),
            'type'        => 'text',
            'default'     => 'Submit',
            'section'     => 'localize',
        ];
        $fields['js_string_submit_in'] = [
            'name'        => __( 'Submit in', 'dt_webform' ),
            'description' => __( 'translate: "Submit in". Note: The final phrase will be a countdown. i.e. Submit in 5,4,3,2,1', 'dt_webform' ),
            'type'        => 'text',
            'default'     => 'Submit in',
            'section'     => 'localize',
        ];
        $fields['js_string_success'] = [
            'name'        => __( 'Success', 'dt_webform' ),
            'description' => __( 'translate: "Success"', 'dt_webform' ),
            'type'        => 'text',
            'default'     => 'Success',
            'section'     => 'localize',
        ];

        // core fields
        $fields['header_title_field'] = [
            'name'        => 'Title',
            'description' => '',
            'type'        => 'text',
            'default'     => '',
            'label'       => 'Contact Us',
            'required'    => 'yes',
            'hidden'      => 'no',
            'section'     => 'core',
        ];
        $fields['header_description_field'] = [
            'name'        => 'Title Description',
            'description' => '',
            'type'        => 'textarea',
            'default'     => '',
            'label'       => '',
            'required'    => 'no',
            'hidden'      => 'yes',
            'section'     => 'core',
        ];
        $fields['name_field'] = [
            'name'        => 'Name',
            'description' => '',
            'type'        => 'text',
            'default'     => '',
            'label'       => 'Name',
            'required'    => 'yes',
            'hidden'      => 'no',
            'section'     => 'core',
        ];
        $fields['phone_field'] = [
            'name'        => 'Phone',
            'description' => '',
            'type'        => 'text',
            'default'     => '',
            'label'       => 'Phone',
            'required'    => 'yes',
            'hidden'      => 'no',
            'section'     => 'core',
        ];
        $fields['email_field'] = [
            'name'        => 'Email',
            'description' => '',
            'type'        => 'text',
            'default'     => '',
            'label'       => 'Email',
            'required'    => 'no',
            'hidden'      => 'no',
            'section'     => 'core',
        ];
        $fields['assigned_to'] = [
            'name'        => 'Assign To User',
            'description' => '',
            'type'        => 'number',
            'default'     => '',
            'label'       => 'This field must be a number. This number is the user_id number from Disciple Tools. This can be found in the Admin > User section. See tutorial for more help.',
            'required'    => 'no',
            'hidden'      => 'yes',
            'section'     => 'hidden',
        ];
        $fields['source'] = [
            'name'        => 'Source',
            'description' => '',
            'type'        => 'source',
            'default'     => '',
            'label'       => 'Source',
            'required'    => 'no',
            'hidden'      => 'yes',
            'section'     => 'hidden',
        ];
        $fields['overall_status'] = [
            'name'        => 'Overall Status',
            'description' => '',
            'type'        => 'text',
            'default'     => '',
            'label'       => 'Source',
            'required'    => 'no',
            'hidden'      => 'yes',
            'section'     => 'hidden',
        ];

        return apply_filters( 'dt_custom_webform_forms', $fields, 'dt_webform_forms' );
    } // End get_custom_fields_settings()

    public function form_types() {
        $list = [
            'default_lead' => 'Lead Form',
//            'location_lead' => 'Lead Form with Location Field',
        ];

        return apply_filters( 'dt_webform_form_types', $list );
    }

    public function scripts() {
        global $pagenow, $post;
        if ( isset( $post->post_type ) && $post->post_type === $this->post_type ) {
            ?>
        <script type="text/javascript">
            jQuery(document).ready( function($) {
                $("#toplevel_page_dt_webform").addClass("current wp-has-current-submenu wp-menu-open");
                $("h1.wp-heading-inline").append(`
                    <a href="<?php echo esc_url( admin_url() ) ?>admin.php?page=dt_webform&tab=forms" class="page-title-action">Forms</a>
                    <a href="<?php echo esc_url( admin_url() ) ?>admin.php?page=dt_webform&tab=settings" class="page-title-action">Settings</a>
                    <a href="<?php echo esc_url( admin_url() ) ?>admin.php?page=dt_webform&tab=tutorial" class="page-title-action">Tutorial</a>
                `);
            });
        </script>
            <?php
        }
        // Catches post delete redirect to standard custom post type list, and redirects to the form list in the plugin.
        if ( $pagenow == 'edit.php' && isset( $_GET['post_type'] ) && esc_attr( sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) ) == $this->post_type ) {
            ?>
            <script type="text/javascript">
                window.location.href = "<?php echo esc_url( admin_url() ) ?>admin.php?page=dt_webform&tab=forms";
            </script>
            <?php
        }
    }

    public static function check_if_valid_token( $token ) {
        $form_object = new WP_Query( [
            'post_type' => 'dt_webform_forms',
            'meta_key' => 'token',
            'meta_value' => $token,
            'post_status' => 'draft, publish',
        ] );
        if ( is_wp_error( $form_object ) || $form_object->found_posts < 1 ) {
            return false;
        }
        return $form_object->post->ID;
    }

    public static function get_form_title_by_token( $token ) {
        $results = new WP_Query( [
            'post_type' => 'dt_webform_forms',
            'meta_value' => $token
        ] );
        if ( $results->post_count < 1) {
            return __( 'Unknown', 'dt_webform' );
        }
        return $results->post->post_title;
    }

    public static function get_form_id_by_token( $token ) {
        $results = new WP_Query( [
            'post_type' => 'dt_webform_forms',
            'meta_value' => $token
        ] );
        if ( $results->post_count < 1) {
            return __( 'Unknown', 'dt_webform' );
        }
        return $results->post->ID;
    }

    public static function get_extra_fields( $token ) {
        $post_id = self::get_form_id_by_token( $token );
        $meta = dt_get_simple_post_meta( $post_id );
        $fields = self::filter_for_custom_fields( $meta );
        $custom_fields = [];
        if ( ! empty( $fields ) ) {
            foreach ( $fields as $key => $value ) {
                $custom_fields[$key] = maybe_unserialize( $value );
            }
            $custom_fields = DT_Webform_Utilities::order_custom_field_array( $custom_fields );
        }
        return $custom_fields;
    }

    public static function get_core_fields_by_token( $token ) : array {
        $post_id = self::get_form_id_by_token( $token );
        return self::instance()->get_core_fields( (int) $post_id );
    }

    public function get_core_fields( int $post_id ) : array {

        $cache = wp_cache_get( __METHOD__, $post_id );
        if ( $cache ) {
            return $cache;
        }

        $core_fields = [];
        $custom_fields = $this->get_custom_fields_settings();
        $meta = dt_get_simple_post_meta( $post_id );

        foreach ( $custom_fields as $key => $field ) {
            if ( $field['section'] === 'core' ) {
                if ( isset( $meta[$key] ) ) {
                    $values = maybe_unserialize( $meta[$key] );
                } else {
                    $values = [
                        'name'  => $field['name'],
                        'label' => $field['label'],
                        'required' => $field['required'],
                        'hidden' => $field['hidden'],
                    ];
                }

                $core_fields[$key] = $values;
            }
        }

        wp_cache_set( __METHOD__, $core_fields, $post_id );

        return $core_fields;
    }

    public function get_hidden_fields( int $post_id ) : array {

        $cache = wp_cache_get( __METHOD__, $post_id );
        if ( $cache ) {
            return $cache;
        }

        $core_fields = [];
        $custom_fields = $this->get_custom_fields_settings();
        $meta = dt_get_simple_post_meta( $post_id );

        foreach ( $custom_fields as $key => $field ) {
            if ( $field['section'] === 'hidden' ) {
                if ( isset( $meta[$key] ) ) {
                    $values = maybe_unserialize( $meta[$key] );
                } else {
                    $values = [
                        'name'  => $field['name'],
                        'label' => $field['label'],
                        'required' => $field['required'],
                        'hidden' => $field['hidden'],
                    ];
                }

                $core_fields[$key] = $values;
            }
        }

        wp_cache_set( __METHOD__, $core_fields, $post_id );

        return $core_fields;
    }

    public function get_extra_fields_by_post_id( $post_id ) {
        $meta = dt_get_simple_post_meta( $post_id );
        $fields = self::filter_for_custom_fields( $meta );
        $custom_fields = [];
        if ( ! empty( $fields ) ) {
            foreach ( $fields as $key => $value ) {
                $custom_fields[$key] = maybe_unserialize( $value );
            }
            $custom_fields = DT_Webform_Utilities::order_custom_field_array( $custom_fields );
        }
        return $custom_fields;
    }

    public function load_core_fields_metabox( $post ) {
        global $pagenow;

        if ( 'post-new.php' == $pagenow ) {
            echo esc_attr__( 'Extra fields will display after you save the new form', 'dt_webform' );
            return;
        }

        $core_fields = $this->get_core_fields( $post->ID );
        ?>
        <p><strong>Basic Form Fields</strong></p>
        <table class="widefat striped">
            <thead>
            <tr>
                <th style="width:100px;">Name</th><th>Label</th><th>Required</th><th>Hidden</th>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach ( $core_fields as $key => $field ) {
                ?>
                    <tr>
                        <td><?php echo esc_html( $field['name'] ) ?><input type="hidden" style="width:100%;" name="<?php echo esc_attr( $key ) ?>[name]" value="<?php echo esc_html( $field['name'] ) ?>" /></td>
                        <td>
                            <?php if ( 'header_description_field' === $key ) : ?>
                                <textarea style="width:100%;" name="<?php echo esc_attr( $key ) ?>[label]"><?php echo esc_html( $field['label'] ) ?></textarea>
                            <?php else : ?>
                                <input style="width:100%;" type="text" name="<?php echo esc_attr( $key ) ?>[label]" placeholder="Enter a label" value="<?php echo esc_html( $field['label'] ) ?>" />
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ( 'header_description_field' === $key || 'header_title_field' === $key || 'sources' === $key || 'assigned_to' === $key ) : ?>
                                <input type="hidden" name="<?php echo esc_attr( $key ) ?>[required]" value="no" />
                            <?php else : ?>
                                <select name="<?php echo esc_attr( $key ) ?>[required]">
                                    <option value="no" <?php echo ( $field['required'] === 'no' ) ? 'selected' : '' ?>>No</option>
                                    <option value="yes" <?php echo ( $field['required'] === 'yes' ) ? 'selected' : '' ?>>Yes</option>
                                </select>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ( 'sources' === $key || 'assigned_to' === $key ) : ?>
                            <span style="text-align:center;">Yes</span>
                            <?php else: ?>
                            <select name="<?php echo esc_attr( $key ) ?>[hidden]">
                                <option value="no" <?php echo ( $field['hidden'] === 'no' ) ? 'selected' : '' ?>>No</option>
                                <option value="yes" <?php echo ( $field['hidden'] === 'yes' ) ? 'selected' : '' ?>>Yes</option>
                            </select>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php
            }
            ?>
            </tbody>
        </table>
        <?php

        ?>
        <p><strong>Protected Disciple Tools Linking Fields</strong></p>
        <table class="widefat striped">
            <thead>
            <tr>
                <th style="width:100px;">Name</th><th style="width:45%">Field</th><th>Description</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    Assign To User
                </td>
                <td>
                    <input name="assigned_to" type="number" value="<?php echo get_post_meta( $post->ID, 'assigned_to', true ) ?>" style="width:100%;" placeholder="user_id number from Disciple Tools system" />
                </td>
                <td>
                    Description
                </td>
            </tr>
            <tr>
                <td>
                    Sources
                </td>
                <td>
                    <select name="source" style="width:100%;">
                        <option value="web">Web</option>
                        <option disabled>-----</option>
                        <?php
                        $contact_defaults = $this->contact_fields;
                        $selected_value = get_post_meta( $post->ID, 'sources', true );
                        foreach ( $contact_defaults['sources']  as $kk => $vv ) {
                            echo '<option value="' . esc_attr( $kk ) . '" ';
                            if ( $kk === $selected_value ) {
                                echo 'selected';
                            }
                            echo '>' . esc_attr( $vv['label'] ) . '</option>';
                        }
                        ?>
                    </select>
                </td>
                <td>
                    Description
                </td>
            </tr>
            <tr>
                <td>
                    Overall Status
                </td>
                <td>
                    <select name="overall_status" style="width:100%;">
                        <?php
                        $contact_defaults = $this->contact_fields;
                        $selected_value = get_post_meta( $post->ID, 'overall_status', true );

                        echo '<option value="new">'.$contact_defaults['fields']['overall_status']['default']['new']['label'].'</option><option disabled>-----</option>';
                        foreach ( $contact_defaults['fields']['overall_status']['default']  as $kk => $vv ) {
                            echo '<option value="' . esc_attr( $kk ) . '" ';
                            if ( $kk === $selected_value ) {
                                echo 'selected';
                            }
                            echo '>' . esc_attr( $vv['label'] ) . '</option>';
                        }
                        ?>
                    </select>
                </td>
                <td>
                    Description
                </td>
            </tr>
            </tbody>
        </table>
        <div>
            <br>
            <span style="float:right;"><button type="submit" class="button">Update</button> </span>
        </div>
        <br clear="all" />

        <?php

    }

    public function filtered_contact_fields() : array {
        $contact_defaults = $this->contact_fields;

        $ignore = [
            'requires_update',
            'reason_unassignable',
            'reason_paused',
            'reason_closed',
            'accepted',
            'quick_button_no_answer',
            'quick_button_contact_established',
            'quick_button_meeting_scheduled',
            'quick_button_meeting_complete',
            'quick_button_no_show',
            'corresponds_to_user',
            'last_modified',
            'duplicate_data',
            'tags',
            'follow',
            'unfollow',
            'duplicate_of',
            'location_grid',
            'location_grid_meta',
            'location_lnglat',
            'tasks',
        ];

        // remove connections
        foreach( $contact_defaults['fields'] as $key => $field ) {
            if ( $field['type'] === 'connection' ) {
               unset( $contact_defaults['fields'][$key] );
            }
            else if ( array_search( $key, $ignore ) !== false ) {
                unset( $contact_defaults['fields'][$key] );
            }
        }
        $fields = $contact_defaults['fields'];
        ksort( $fields );
        return $fields;
    }

    /**
     * Load type metabox
     */
    public function load_extra_fields_meta_box( $post ) {
        global $pagenow;

        if ( 'post-new.php' == $pagenow ) {
            echo esc_attr__( 'Extra fields will display after you save the new form', 'dt_webform' );
            return;
        }

        $fields = dt_get_simple_post_meta( $post->ID );
        $custom_fields = self::filter_for_custom_fields( $fields );

        if ( ! empty( $custom_fields ) ) {
            ?>
            <form>

            <table class="widefat striped">
                <thead>
                <tr>
                    <th>Map To DT Field</th><th style="width:50px;">Order</th><th style="width:50px;">Required</th><th>Type</th><th>Map To DT Field</th><th>Label(s)</th><th>Value(s)</th><th>Actions</th>
                </tr>
                </thead>
                <tbody>
            <?php

            // reorder
            $ordered_fields = DT_Webform_Utilities::order_custom_field_array( $custom_fields );

            foreach ( $ordered_fields as $unique_key => $data ) {
                $data = maybe_unserialize( $data );
                ?>

                    <tr id="<?php echo esc_attr( $unique_key ) ?>">

                        <td>
                            <select name="<?php echo esc_attr( $unique_key ) ?>[dt_field]" >
                                <option value=""></option>
                                <option disabled>------</option>
                                <option disabled>COMMON</option>
                                <option value="sources" data-type="multi_select">Sources</option>
                                <option value="assigned_to" data-type="user_select">Assigned To</option>
                                <option value="overall_status" data-type="key_select">Overall Status</option>
                                <option disabled>------</option>
                                <option disabled>ALL</option>
                                <?php
                                $contact_fields = $this->filtered_contact_fields();
                                foreach( $contact_fields as $key => $field ) {
                                    echo '<option value="'.$key.'" data-type="'.$field['type'].'">' . $field['name'] . '</option>';
                                }
                                ?>
                            </select>
                        </td>

                        <td>
                            <input type="number" style="width:50px;" name="<?php echo esc_attr( $unique_key ) ?>[order]" placeholder="number" value="<?php echo esc_attr( $data['order'] ?? 1 ) ?>" />
                            <input type="hidden" name="<?php echo esc_attr( $unique_key ) ?>[key]" placeholder="key"
                                   value="<?php echo esc_attr( $data['key'] ) ?>" readonly/>&nbsp;
                        </td>
                        <td>
                            <select name="<?php echo esc_attr( $unique_key ) ?>[required]">
                                <option value="<?php echo esc_attr( $data['required'] ) ?>"><?php echo esc_attr( ucwords( $data['required'] ) ) ?></option>
                                <option disabled>---</option>
                                <option value="no"><?php echo esc_attr__( 'No', 'dt_webform' ) ?></option>
                                <option value="yes"><?php echo esc_attr__( 'Yes', 'dt_webform' ) ?></option>
                            </select>&nbsp;
                        </td>
                        <td>
                            <?php echo esc_attr( ucwords( str_replace( '_', ' ', $data['type'] ) ) ) ?>
                            <input type="hidden" name="<?php echo esc_attr( $unique_key ) ?>[type]"
                                   value="<?php echo esc_attr( $data['type'] ) ?>" />&nbsp;
                        </td>

                        <?php
                        if ( isset( $data['type'] ) ) {
                            switch ( $data['type'] ) {

                                // multi labels, multi values
                                case 'dropdown':
                                case 'multi_radio':
                                    ?>
                                    <td>
                                        <input type="text" style="width:100%;" name="<?php echo esc_attr( $unique_key ) ?>[dt_field]" placeholder="field key" value="<?php echo esc_attr( $data['dt_field'] ?? '' ) ?>" />
                                    </td>
                                    <td id="labels-cell-<?php echo esc_attr( $unique_key ) ?>">
                                        <input type="text" style="width:100%;" name="<?php echo esc_attr( $unique_key ) ?>[title]" placeholder="Give a title to the series" value="<?php echo esc_html( $data['title'] ?? '' ) ?>" /><br>
                                        <textarea type="text"
                                                  style="width:100%;"
                                                  rows="5"
                                                  name="<?php echo esc_attr( $unique_key ) ?>[labels]"
                                                  placeholder="One label per line. Same order as values." /><?php echo esc_html( $data['labels'] ?? '' ) ?></textarea>
                                    </td>
                                    <td id="values-cell-<?php echo esc_attr( $unique_key ) ?>">
                                        <select name="<?php echo esc_attr( $unique_key ) ?>[selected]" style="width:100%;">
                                            <option value="no" <?php echo ( $data['selected'] === 'no' ) ? 'checked' : ''; ?>>Not Pre-Selected</option>
                                            <option value="yes" <?php echo ( $data['selected'] === 'yes' ) ? 'checked' : ''; ?>>Selected First Line</option>
                                        </select><br>
                                        <textarea type="text"
                                                  style="width:100%;"
                                                  rows="5"
                                                  name="<?php echo esc_attr( $unique_key ) ?>[values]"
                                                  placeholder="One value per line. Underscores allowed. No spaces or special characters." /><?php echo esc_html( $data['values'] ?? '' ) ?></textarea>
                                    </td>
                                    <?php
                                    break;

                                // single labels, single values
                                case 'checkbox':
                                case 'tel':
                                case 'email':
                                case 'text':
                                    ?>
                                    <td>
                                        <input type="text" style="width:100%;" name="<?php echo esc_attr( $unique_key ) ?>[dt_field]" placeholder="field key" value="<?php echo esc_attr( $data['dt_field'] ?? '' ) ?>" />
                                    </td>
                                    <td id="labels-cell-<?php echo esc_attr( $unique_key ) ?>">
                                        <input type="text" style="width:100%;" id="label_<?php echo esc_attr( $unique_key ) ?>" name="<?php echo esc_attr( $unique_key ) ?>[labels]" placeholder="label" value="<?php echo esc_html( $data['labels'] ?? '' ) ?>"/>
                                    </td>
                                    <td id="values-cell-<?php echo esc_attr( $unique_key ) ?>">
                                        <input type="text" style="width:100%;" name="<?php echo esc_attr( $unique_key ) ?>[values]" placeholder="Value(s)" value="<?php echo esc_html( $data['values'] ?? '' ) ?>" />
                                    </td>
                                    <?php
                                    break;

                                case 'note':
                                    ?>
                                    <td>Saves to Comments</td>
                                    <td id="labels-cell-<?php echo esc_attr( $unique_key ) ?>">
                                        <input type="text" style="width:100%;" id="label_<?php echo esc_attr( $unique_key ) ?>" name="<?php echo esc_attr( $unique_key ) ?>[labels]" placeholder="label" value="<?php echo esc_html( $data['labels'] ?? '' ) ?>"/>
                                    </td>
                                    <td id="values-cell-<?php echo esc_attr( $unique_key ) ?>"></td>
                                    <?php
                                    break;
                                case 'custom_label':
                                case 'header':
                                    ?>
                                    <td></td>
                                    <td id="labels-cell-<?php echo esc_attr( $unique_key ) ?>">
                                        <input type="text" style="width:100%;" id="label_<?php echo esc_attr( $unique_key ) ?>" name="<?php echo esc_attr( $unique_key ) ?>[labels]" placeholder="label" value="<?php echo esc_html( $data['labels'] ?? '' ) ?>"/>
                                    </td>
                                    <td id="values-cell-<?php echo esc_attr( $unique_key ) ?>"></td>

                                    <?php
                                    break;
                                case 'description':
                                    ?>
                                    <td></td>
                                    <td id="labels-cell-<?php echo esc_attr( $unique_key ) ?>">
                                        <textarea type="text"
                                                  id="label_<?php echo esc_attr( $unique_key ) ?>"
                                                  style="width:100%;"
                                                  rows="5"
                                                  name="<?php echo esc_attr( $unique_key ) ?>[labels]"
                                                  placeholder="One label per line. Same order as values." /><?php echo esc_html( $data['labels'] ?? '' ) ?></textarea>
                                    </td>
                                    <td id="values-cell-<?php echo esc_attr( $unique_key ) ?>"></td>
                                    <?php
                                    break;
                                case 'map':
                                    ?>
                                    <td></td>
                                    <td id="labels-cell-<?php echo esc_attr( $unique_key ) ?>">
                                        <input type="text" style="width:100%;" id="label_<?php echo esc_attr( $unique_key ) ?>" name="<?php echo esc_attr( $unique_key ) ?>[labels]" placeholder="label" value="<?php echo esc_html( $data['labels'] ?? '' ) ?>"/>
                                    </td>
                                    <td id="values-cell-<?php echo esc_attr( $unique_key ) ?>">
                                        <select name="<?php echo esc_attr( $unique_key ) ?>[values]">
                                            <option value="click_map" selected>Click Map</option>
                                        </select>
                                    </td>
                                    <?php
                                    break;

                                // empty elements
                                case 'divider':
                                case 'spacer':
                                default:
                                    ?>
                                    <td></td>
                                    <td id="labels-cell-<?php echo esc_attr( $unique_key ) ?>"></td>
                                    <td id="values-cell-<?php echo esc_attr( $unique_key ) ?>"></td>
                                    <?php
                                    break;
                            }
                        }

                        ?>
                        <td>
                            <a href="javascript:void(0)" class="button" name="<?php echo esc_attr( $unique_key ) ?>" onclick="remove_add_custom_fields('<?php echo esc_attr( $unique_key ) ?>')"
                                    value=""><?php echo esc_attr__( 'X', 'dt_webform' ) ?>
                            </a>
                        </td>
                    </tr>
                <?php
            } // end foreach

            ?>
                </tbody></table>
            <?php
        } // end if

        $unique_key = bin2hex( random_bytes( 10 ) );
        ?>


        <div id="new-fields"></div>

        <div>
            <br>
            <a href="javascript:void(0)" class="button" onclick="add_dt_select_field()" id="add_field_button">Add</a>
            <span style="float:right;" id="update_fields_button"><button type="submit" class="button">Update</button> </span>
        </div>
        <br clear="all" />


        <script>
            function add_dt_select_field() {
                jQuery('#add_field_button').hide()
                jQuery('#new-fields').html(`
                <br><hr><br>
                <input type="hidden" name="field_<?php echo esc_attr( $unique_key ) ?>[key]" placeholder="key" value="field_<?php echo esc_attr( $unique_key ) ?>"/>
                <table class="widefat striped" id="new_<?php echo esc_attr( $unique_key ) ?>">
                <thead>
                    <tr>
                        <th>Map To DT Field</th><th>Type</th><th style="width:50px;">Order</th><th style="width:50px;">Required</th><th>Label(s)</th><th>Value(s)</th><th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td id="new-dt-field-<?php echo esc_attr( $unique_key ) ?>">
                                <select name="<?php echo esc_attr( $unique_key ) ?>[dt_field]" >
                                    <option value=""></option>
                                    <option disabled>------</option>
                                    <option disabled>COMMON</option>
                                    <option value="sources" data-type="multi_select">Sources</option>
                                    <option value="assigned_to" data-type="user_select">Assigned To</option>
                                    <option value="overall_status" data-type="key_select">Overall Status</option>
                                    <option disabled>------</option>
                                    <option disabled>ALL</option>
                                    <?php
                                        $contact_fields = $this->filtered_contact_fields();
                                        foreach( $contact_fields as $key => $field ) {
                                            echo '<option value="'.$key.'" data-type="'.$field['type'].'">' . $field['name'] . '</option>';
                                        }
                                        ?>
                                </select>
                            </td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td id="new-labels-<?php echo esc_attr( $unique_key ) ?>"></td>
                            <td id="new-values-<?php echo esc_attr( $unique_key ) ?>"></td>
                            <td>
                                <button class="button" type="submit"><?php echo esc_attr__( 'Save', 'dt_webform' ) ?></button>
                                <button class="button" onclick="remove_new_custom_fields()"><?php echo esc_attr__( 'Clear', 'dt_webform' ) ?></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                `)
            }
            function add_new_custom_fields() {
                jQuery('#add_field_button').hide()

                jQuery('#new-fields').html(`
                <br><hr><br>
                <input type="hidden" name="field_<?php echo esc_attr( $unique_key ) ?>[key]" placeholder="key" value="field_<?php echo esc_attr( $unique_key ) ?>"/>
                <table class="widefat striped" id="new_<?php echo esc_attr( $unique_key ) ?>">
                <thead>
                    <tr>
                        <th style="width:50px;">Order</th><th style="width:50px;">Required</th><th>Type</th><th>Map To DT Field</th><th>Label(s)</th><th>Value(s)</th><th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <input type="number" style="width:50px;" name="field_<?php echo esc_attr( $unique_key ) ?>[order]" placeholder="number" value="1" />
                            </td>
                            <td>
                                <select name="field_<?php echo esc_attr( $unique_key ) ?>[required]">
                                    <option value="no"><?php echo esc_attr__( 'No', 'dt_webform' ) ?></option>
                                    <option value="yes"><?php echo esc_attr__( 'Yes', 'dt_webform' ) ?></option>
                                </select>
                            </td>
                            <td>
                                <select id="type_<?php echo esc_attr( $unique_key ) ?>" name="field_<?php echo esc_attr( $unique_key ) ?>[type]">
                                    <option value="text"><?php echo esc_attr__( 'Text', 'dt_webform' ) ?></option>
                                    <option readonly>---</option>
                                    <option value="header"><?php echo esc_attr__( 'Section Header', 'dt_webform' ) ?></option>
                                    <option value="description"><?php echo esc_attr__( 'Section Description', 'dt_webform' ) ?></option>
                                    <option value="divider"><?php echo esc_attr__( 'Section Divider', 'dt_webform' ) ?></option>
                                    <option value="spacer"><?php echo esc_attr__( 'Section Spacer', 'dt_webform' ) ?></option>
                                    <option value="custom_label"><?php echo esc_attr__( 'Custom Label', 'dt_webform' ) ?></option>
                                    <option value="text"><?php echo esc_attr__( 'Text', 'dt_webform' ) ?></option>
                                    <option value="tel"><?php echo esc_attr__( 'Phone', 'dt_webform' ) ?></option>
                                    <option value="email"><?php echo esc_attr__( 'Email', 'dt_webform' ) ?></option>
                                    <option value="checkbox"><?php echo esc_attr__( 'Checkbox', 'dt_webform' ) ?></option>
                                    <option value="map"><?php echo esc_attr__( 'Map', 'dt_webform' ) ?></option>
                                    <option value="note"><?php echo esc_attr__( 'Note', 'dt_webform' ) ?></option>
                                    <option value="dropdown"><?php echo esc_attr__( 'Dropdown', 'dt_webform' ) ?></option>
                                    <option value="multi_radio"><?php echo esc_attr__( 'Multi-Select Radio', 'dt_webform' ) ?></option>
                               </select>
                            </td>
                            <td id="new-dt-field-<?php echo esc_attr( $unique_key ) ?>"></td>
                            <td id="new-labels-<?php echo esc_attr( $unique_key ) ?>"></td>
                            <td id="new-values-<?php echo esc_attr( $unique_key ) ?>"></td>
                            <td>
                                <button class="button" type="submit"><?php echo esc_attr__( 'Save', 'dt_webform' ) ?></button>
                                <button class="button" onclick="remove_new_custom_fields()"><?php echo esc_attr__( 'Clear', 'dt_webform' ) ?></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                `)

                let labels = jQuery('#new-labels-<?php echo esc_attr( $unique_key ) ?>')
                let values = jQuery('#new-values-<?php echo esc_attr( $unique_key ) ?>')
                let dt = jQuery('#new-dt-field-<?php echo esc_attr( $unique_key ) ?>')

                let dt_field = `<input type="text" style="width:100%;" name="field_<?php echo esc_attr( $unique_key ) ?>[dt_field]" placeholder="field key" />`
                let multi_title = `<input type="text" style="width:100%;" name="field_<?php echo esc_attr( $unique_key ) ?>[title]" placeholder="Give a title to the series" />`
                let first_line_default = `<select style="width:100%;" name="field_<?php echo esc_attr( $unique_key ) ?>[selected]"><option value="no" checked>Not Pre-Selected</option><option value="yes">Selected First Line</option></select>`
                let single_label = `<input type="text" style="width:100%;" name="field_<?php echo esc_attr( $unique_key ) ?>[labels]" placeholder="label" required/>`
                let multi_label = `<textarea type="text"
                                    style="width:100%;"
                                    rows="5"
                                    name="field_<?php echo esc_attr( $unique_key ) ?>[labels]"
                                    placeholder="One label per line. Same order as values." /></textarea>`

                let single_value = `<input type="text" style="width:100%;" name="field_<?php echo esc_attr( $unique_key ) ?>[values]" placeholder="Value(s)" />`
                let multi_value = `<textarea type="text"
                                    style="width:100%;"
                                    rows="5"
                                    name="field_<?php echo esc_attr( $unique_key ) ?>[values]"
                                    placeholder="One value per line. Underscores allowed. No spaces or special characters." /></textarea>`
                let map_select = `<select name="field_<?php echo esc_attr( $unique_key ) ?>[values]">
                                        <option value="click_map" selected>Click Map</option>
                                    </select>`

                labels.html(single_label)
                values.html(single_value)
                dt.html(dt_field)


                jQuery('#type_<?php echo esc_attr( $unique_key ) ?>').on('change', function(){
                    let type = jQuery('#type_<?php echo esc_attr( $unique_key ) ?>').val()

                    switch ( type ) {
                        case 'multi_radio':
                            labels.empty().append(multi_title).append('<br>').append(multi_label)
                            values.empty().append(first_line_default).append('<br>').append(multi_value)
                            dt.empty().html(dt_field)
                            break;
                        case 'dropdown':
                            labels.empty().append(multi_title).append('<br>').append(multi_label)
                            values.empty().append(first_line_default).append('<br>').append(multi_value)
                            dt.empty().html(dt_field)
                            break;
                        case 'checkbox':
                            labels.empty().html(single_label)
                            values.empty().html(single_value)
                            dt.empty().html(dt_field)
                            break;
                        case 'custom_label':
                        case 'note':
                        case 'header':
                            labels.empty().html(single_label)
                            values.empty()
                            dt.empty()
                            break;
                        case 'description':
                            labels.empty().html(multi_label)
                            values.empty()
                            dt.empty()
                            break;
                        case 'divider':
                            labels.empty()
                            values.empty()
                            dt.empty()
                            break;
                        case 'spacer':
                            labels.empty()
                            values.empty()
                            dt.empty()
                            break;
                        case 'map':
                            labels.empty().html(single_label)
                            values.empty().html(map_select)
                            dt.empty()
                            break;
                        default:
                            labels.empty().html(single_label)
                            values.empty().html(single_value)
                            dt.empty().html(dt_field)
                            break;
                    }
                })
            }

            function remove_new_custom_fields() {
                jQuery('#new-fields').empty()
                jQuery('#add_field_button').show()
                jQuery('#update_fields_button').show()
            }

            function remove_add_custom_fields(id) {
                jQuery('#' + id).empty().submit()
            }

        </script>

        </form>
        <?php
    }

    public function save_core_fields( $post_id ) {

        // fail process early
        if ( get_post_type() !== $this->post_type ) {
            return $post_id;
        }
        $nonce_key = $this->post_type . '_noonce';
        if ( isset( $_POST[ $nonce_key ] ) && !wp_verify_nonce( sanitize_key( $_POST[ $nonce_key ] ), 'update_dt_webforms' ) ) {
            return $post_id;
        }
        if ( !current_user_can( 'manage_dt', $post_id ) ) {
            return $post_id;
        }
        if ( isset( $_GET['action'] ) ) {
            if ( $_GET['action'] == 'trash' || $_GET['action'] == 'untrash' || $_GET['action'] == 'delete' ) {
                return $post_id;
            }
        }

        $array = $this->filter_for_core_fields( $_POST );

        foreach ( $array as $key => $value ) {
            if ( ! get_post_meta( $post_id, $key ) ) {
                add_post_meta( $post_id, $key, $value, true );
            } elseif ( $value == '' ) {
                delete_post_meta( $post_id, $key, get_post_meta( $post_id, $key, true ) );
            } elseif ( $value != get_post_meta( $post_id, $key, true ) ) {
                update_post_meta( $post_id, $key, $value );
            }
        }
        return $post_id;
    }

    public function save_extra_fields( $post_id ) {

        // fail process early
        if ( get_post_type() !== $this->post_type ) {
            return $post_id;
        }
        $nonce_key = $this->post_type . '_noonce';
        if ( isset( $_POST[ $nonce_key ] ) && !wp_verify_nonce( sanitize_key( $_POST[ $nonce_key ] ), 'update_dt_webforms' ) ) {
            return $post_id;
        }
        if ( !current_user_can( 'manage_dt', $post_id ) ) {
            return $post_id;
        }
        if ( isset( $_GET['action'] ) ) {
            if ( $_GET['action'] == 'trash' || $_GET['action'] == 'untrash' || $_GET['action'] == 'delete' ) {
                return $post_id;
            }
        }

        $current_fields_extra = $this->get_extra_fields_by_post_id( $post_id );


        $array = self::filter_for_custom_fields( $_POST );

        foreach ( $current_fields_extra as $key => $value ) {
            if ( ! isset( $array[$key] ) ) {
                delete_post_meta( $post_id, $key, get_post_meta( $post_id, $key, true ) );
            }
        }
        foreach ( $array as $key => $value ) {

            // @todo add filter to correct make keys and trim entries

            if ( ! get_post_meta( $post_id, $key ) ) {
                add_post_meta( $post_id, $key, $value, true );
            } elseif ( $value == '' ) {
                delete_post_meta( $post_id, $key, get_post_meta( $post_id, $key, true ) );
            } elseif ( $value != get_post_meta( $post_id, $key, true ) ) {
                update_post_meta( $post_id, $key, $value );
            }
        }
        return $post_id;
    }

    public static function filter_for_custom_fields( $array ) {
        return array_filter( $array, function( $key) {
            return strpos( $key, 'field_' ) === 0;
        }, ARRAY_FILTER_USE_KEY );
    }


    public function filter_for_core_fields( $array ) {

        return array_filter( $array, function( $key) {
            // @todo write dry
            if ( strpos( $key, 'header_title_field' ) === 0 ) {
                return true;
            }
            if ( strpos( $key, 'header_description_field' ) === 0 ) {
                return true;
            }
            if ( strpos( $key, 'name_field' ) === 0 ) {
                return true;
            }
            if ( strpos( $key, 'phone_field' ) === 0 ) {
                return true;
            }
            if ( strpos( $key, 'email_field' ) === 0 ) {
                return true;
            }
            return false;
        }, ARRAY_FILTER_USE_KEY );
    }

    public static function match_labels_with_values( string $labels, string $values ) : array {
        if ( empty( $labels ) || empty( $values ) ) {
            return [];
        }

        $labels = array_filter( explode( PHP_EOL, $labels ) );
        $values = array_filter( explode( PHP_EOL, $values ) );

        $list = [];

        foreach ( $values as $index => $item ) {
            $list[] = [
                'label' => trim( $labels[$index] ),
                'value' => trim( $values[$index] ),
            ];
        }

        return $list;
    }

}
