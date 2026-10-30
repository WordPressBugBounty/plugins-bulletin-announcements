<?php

defined( 'ABSPATH' ) or exit;

class BULLETINWP_Review_Notice {
  const OPTION_KEY = 'bulletinwp_review_notice_after';
  const ACTION     = 'bulletinwp_review_notice';
  const REVIEW_URL = 'https://wordpress.org/support/plugin/bulletin-announcements/reviews/#new-post';

  public function __construct() {
    add_action( 'admin_notices', array( $this, 'render_notice' ) );
    add_action( 'admin_post_' . self::ACTION, array( $this, 'handle_choice' ) );
  }

  /**
   * Ask admins for a WordPress.org review on Bulletin's own pages, two weeks after first use
   * and only once they have created a bulletin
   *
   * @since 3.15.0
   *
   * @param void
   * @return void
   */
  public function render_notice() {
    if ( ! BULLETINWP::instance()->api->is_page_in_plugin() || ! current_user_can( 'manage_options' ) ) {
      return;
    }

    $show_after = get_option( self::OPTION_KEY );

    // First visit (or first visit since updating): start the clock
    if ( false === $show_after ) {
      update_option( self::OPTION_KEY, time() + 14 * DAY_IN_SECONDS, false );
      return;
    }

    // 0 means reviewed or dismissed for good
    if ( empty( $show_after ) || time() < (int) $show_after ) {
      return;
    }

    if ( ! BULLETINWP::instance()->sql->get_all_bulletins_count() ) {
      return;
    }

    ?>
    <div class="notice notice-info">
      <p>
        <strong><?php esc_html_e( 'Enjoying Bulletin?', 'bulletinwp' ) ?></strong>
        <?php esc_html_e( 'A quick review on WordPress.org helps other site owners find it, and it really makes our day. Thank you!', 'bulletinwp' ) ?>
      </p>
      <p>
        <a class="button button-primary" href="<?php echo esc_url( $this->get_choice_url( 'review' ) ) ?>" target="_blank" rel="noopener noreferrer" onclick="this.closest('.notice').remove()">
          <?php esc_html_e( 'Leave a review', 'bulletinwp' ) ?>
        </a>
        <a class="button" href="<?php echo esc_url( $this->get_choice_url( 'later' ) ) ?>">
          <?php esc_html_e( 'Maybe later', 'bulletinwp' ) ?>
        </a>
        <a class="button button-link" href="<?php echo esc_url( $this->get_choice_url( 'done' ) ) ?>">
          <?php esc_html_e( 'I already did', 'bulletinwp' ) ?>
        </a>
      </p>
    </div>
    <?php
  }

  /**
   * Store the choice: "later" snoozes for 30 days, anything else hides the notice for good
   *
   * @since 3.15.0
   *
   * @param void
   * @return void
   */
  public function handle_choice() {
    check_admin_referer( self::ACTION );

    if ( ! current_user_can( 'manage_options' ) ) {
      wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'bulletinwp' ), 403 );
    }

    $choice = isset( $_GET['choice'] ) ? sanitize_key( $_GET['choice'] ) : '';

    update_option( self::OPTION_KEY, ( 'later' === $choice ) ? time() + 30 * DAY_IN_SECONDS : 0, false );

    if ( 'review' === $choice ) {
      wp_redirect( self::REVIEW_URL );
      exit;
    }

    wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
    exit;
  }

  private function get_choice_url( $choice ) {
    return wp_nonce_url( admin_url( 'admin-post.php?action=' . self::ACTION . '&choice=' . $choice ), self::ACTION );
  }
}
