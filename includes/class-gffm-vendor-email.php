<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class GFFM_Vendor_Email {
    public static function init() {
        add_action( 'add_meta_boxes', [ __CLASS__, 'add_box' ] );
        add_action( 'save_post', [ __CLASS__, 'save' ] );
    }

    protected static function vendor_cpt() {
        return get_option( 'gffm_use_internal_vendors', 'no' ) === 'yes' ? 'gffm_vendor' : 'vendor';
    }

    public static function add_box() {
        add_meta_box( 'gffm_vendor_email', __( 'Vendor Email', 'gffm' ), [ __CLASS__, 'render' ], self::vendor_cpt(), 'side' );
    }

    public static function render( $post ) {
        $email = get_post_meta( $post->ID, '_email', true );
        wp_nonce_field( 'gffm_vendor_email', 'gffm_vendor_email_nonce' );
        echo '<p><label for="gffm_vendor_email_field">' . esc_html__( 'Email', 'gffm' ) . '</label>';
        echo '<input type="email" name="gffm_vendor_email_field" id="gffm_vendor_email_field" class="widefat" value="' . esc_attr( $email ) . '" />';
        echo '</p>';
    }

    public static function save( $post_id ) {
        if ( ! isset( $_POST['gffm_vendor_email_nonce'] ) || ! wp_verify_nonce( $_POST['gffm_vendor_email_nonce'], 'gffm_vendor_email' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        $email = isset( $_POST['gffm_vendor_email_field'] ) ? sanitize_email( wp_unslash( $_POST['gffm_vendor_email_field'] ) ) : '';
        if ( $email ) {
            update_post_meta( $post_id, '_email', $email );
        } else {
            delete_post_meta( $post_id, '_email' );
        }
    }
}
GFFM_Vendor_Email::init();
