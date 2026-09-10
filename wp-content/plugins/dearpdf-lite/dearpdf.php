<?php
/**
 * DearPDF Main CLass
 *
 * @since           1.0.0
 *
 * @package         dearpdf
 * @subpackage      dearpdf
 * @author          DearHive
 * @copyright   (c) Copyright by DearHive
 * @link            https://dearhive.com
 */

// Do not allow direct file access
if ( !defined( 'ABSPATH' ) ) {
  exit( 'Direct script access denied.' );
}


if ( !class_exists( 'DearPDF' ) ) {
  /**
   * Main DearPDF plugin class.
   *
   * @since   1.0.0
   *
   * @package dearpdf
   * @author  Deepak Ghimire
   */
  class DearPDF {
    
    #region Variables
    /**
     * Holds the singleton class object.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public static $instance;
    
    /**
     * Plugin version
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $version = '1.2.62';
    
    /**
     * The name of the plugin.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $plugin_name = 'DearPDF';
    
    /**
     * Unique plugin slug identifier.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $plugin_slug = 'dearpdf';
    /**
     * Plugin file.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $file = __FILE__;
    
    /**
     * Default values.
     *
     * @since 1.2.6
     *
     * @var string
     */
    public $defaults;
    public $post_tabs;
    public $post_shortcode_tabs;
    
    #endregion
    
    /**
     * Primary class constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {
      
      $this->settings_text = array();
      $this->external_translate = false;
      // Load the plugin.
      add_action( 'init', array( $this, 'action_init' ), 0 );
      
      //Filter to display viewer in PDF attachment page
      add_filter( 'the_content', array( $this, 'filter_pdf_attachment_content' ) );
    }
    
    /**
     * Loads the plugin into WordPress.
     *
     * @since 1.0.0
     */
    public function action_init() {
      
      $this->init_settings();
      
      // Load admin only components.
      if ( is_admin() && !( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
        $this->init_admin();
      } else { // Load frontend only components.
        $this->init_front();
      }
      
      // Load global components.
      $this->init_global();
      
    }
    
    public function init_settings() {
      $this->post_tabs = array(
          'source'   => __( 'Source', 'dearpdf' ),
          'general'  => __( 'General', 'dearpdf' ),
          'flipbook' => __( 'Flipbook', 'dearpdf' ),
          'advanced' => __( 'Advanced', 'dearpdf' )
      );
      
      $this->post_shortcode_tabs = array(
          'embed'    => array(
              'title'   => __( 'Embed', 'dearpdf' ),
              'content' => '[dearpdf id="{id}" ][/dearpdf]'
          ),
          'lightbox' => array(
              'title'   => __( 'LightBox(Popup)', 'dearpdf' ),
              'content' => 'Button:<br>[dearpdf id="{id}" type="button"][/dearpdf]'
          )
      );
      $this->defaults = array(
          'source'        => array(
              'std'            => "",
              'title'          => 'PDF File',
              'desc'           => 'Choose a PDF File to use as source for the book.',
              'placeholder'    => 'Select a PDF File',
              'type'           => 'upload',
              'button-tooltip' => 'Select a PDF File',
              'button-text'    => 'Select PDF',
          ),
          'viewerType'    => array(
              'std'     => 'reader',
              'choices' => array(
                  'global'   => 'Default Setting',
                  'reader'   => 'Vertical Reader',
                  'flipbook' => 'Flipbook'
              ),
              'title'   => 'Viewer Type',
              'desc'    => 'Choose the Viewer Type. Flipbook or normal viewer'
          ),
          'is3D'          => array(
              'std'       => 'true',
              'choices'   => array(
                  'global' => 'Default Setting',
                  'true'   => 'WebGL 3D',
                  'false'  => 'CSS 3D/2D'
              ),
              'title'     => 'Flipbook 3D or 2D',
              'desc'      => 'Choose the mode of display. WebGL for realistic 3d',
              'condition' => 'dearpdf_viewerType:is(flipbook)'
          ),
          'disableRange'  => array(
              'std'     => 'false',
              'choices' => array(
                  'global' => __( 'Default Setting', 'dearpdf' ),
                  'true'   => __( 'True', 'dearpdf' ),
                  'false'  => __( 'False', 'dearpdf' )
              ),
              'class'   => '',
              'title'   => 'Disable Partial Loading',
              'desc'    => 'Partial loading, Chunk issues are discovered in some servers and browsers. Disabling range can fix that issue.'
          ),
          'readDirection' => array(
              'std'       => 'ltr',
              'choices'   => array(
                  'global' => __( 'Default Setting', 'dearpdf' ),
                  'ltr'    => __( 'Left to Right', 'dearpdf' ),
                  'rtl'    => __( 'Right to Left', 'dearpdf' )
              ),
              'class'     => '',
              'title'     => 'Reading Direction',
              'desc'      => 'Choose if the PDF follows Left to Right or Right to Left Reading Direction.',
              'condition' => 'dearpdf_viewerType:is(flipbook)'
          )
      );
      $this->defaults['enableSound'] = array(
          'std'       => 'true',
          'choices'   => array(
              'global' => 'Default Setting',
              'true'   => 'True',
              'false'  => 'False',
          ),
          'title'     => 'Auto Enable Sound',
          'desc'      => 'Enable or disable Sound when flipbook starts',
          'condition' => 'dearpdf_viewerType:is(flipbook)'
      );
      $this->defaults['backgroundColor'] = array(
          'std'         => "transparent",
          'title'       => 'Background Color',
          'desc'        => 'Background color in hexadecimal format eg:<code>#FFF</code> or <code>#666666</code> or <code>transparent</code>',
          'placeholder' => 'Example: #ffffff',
          'type'        => 'text'
      );
      $this->defaults['backgroundImage'] = array(
          'std'            => "",
          'class'          => '',
          'title'          => 'Background Image',
          'desc'           => 'Background image JPEG or PNG format:',
          'placeholder'    => 'Select an image',
          'type'           => 'upload',
          'button-tooltip' => 'Select Background Image',
          'button-text'    => 'Select Image'
      );
      $this->defaults['height'] = array(
          'std'         => "auto",
          'title'       => 'Container Height',
          'desc'        => 'Height of the flipbook container when in normal mode.<br> <code>500</code>for 500px <br> <code>auto</code>for autofit height <br> <code>100%</code>for 100% height (of parent element, else it will be 320px).',
          'placeholder' => 'Example: 500',
          'type'        => 'text'
      );
      $this->defaults['showDownloadControl'] = array(
          'std'     => 'true',
          'choices' => array(
              'global' => 'Default Setting',
              'true'   => 'True',
              'false'  => 'False',
          ),
          'title'   => 'Enable Download',
          'desc'    => 'Enable PDF download'
      );
    }
    
    /**
     * Loads all admin related files into scope.
     *
     * @since 1.0.0
     */
    public function init_admin() {
      
      add_action( 'admin_menu', array( $this, 'action_admin_menu' ) );
      
      //include the scripts and styles for front end
      add_action( 'admin_enqueue_scripts', array( $this, 'action_init_admin_scripts' ) );
      
      // Load the metabox hooks and filters.
      add_action( 'add_meta_boxes', array( $this, 'action_add_meta_boxes' ), 100 );
      
      // Add action to save metabox config options. todo Only if the post screen
      add_action( 'save_post_dearpdf', array( $this, 'action_save_post' ), 10, 2 );
      
      /*Post Related filters*/
      // Remove quick editing from the dearpdf post type row actions.
      add_filter( 'post_row_actions', array( $this, 'filter_post_remove_quick_edit' ), 10, 1 );
      
      // Manage post type columns.
      add_filter( 'manage_dearpdf_posts_columns', array( $this, 'filter_post_columns' ) );
      add_action( 'manage_dearpdf_posts_custom_column', array( $this, 'action_post_columns_content' ), 10, 2 );
      
      add_filter( 'manage_edit-dearpdf_category_columns', array( $this, 'filter_post_category_columns' ) );
      add_filter( 'manage_dearpdf_category_custom_column', array( $this, 'filter_post_category_columns_content' ), 10, 3 );
      
    }
    
    public function action_admin_menu() {
    
//      $this->tools_menu_hook = add_submenu_page( 'edit.php?post_type=dearpdf', __( 'DearPDF Help and Tools', 'dearpdf' ), __( 'Help and Tools', 'dearpdf' ), 'manage_options',
//          $this->plugin_slug . '-help-tools', array( $this, 'help_tools_page' ) );
    
    }
    
    /**
     * Loads all frontend user related files
     *
     * @since 1.0.0
     */
    
    /**
     * Adds metaboxes for handling settings
     *
     * @since 1.0.0
     */
    public function action_add_meta_boxes() {
      
      add_meta_box( 'dearpdf_post_meta_box_support_us', __( 'More Features in &nbsp;<a href="https://dearpdf.com/go/wp-lite-full-version" target="_blank"> FULL VERSION!</a>', 'dearpdf' ),
          array( $this, 'create_meta_boxes_support_us' ), 'dearpdf', 'normal', 'high' );
      
      add_meta_box( 'dearpdf_post_meta_box', __( 'DEARPDF Settings', 'dearpdf' ), array( $this, 'create_meta_boxes' ), 'dearpdf', 'normal', 'high' );
      
      add_meta_box( 'dearpdf_post_meta_box_shortcode', __( 'Shortcode', 'dearpdf' ), array( $this, 'create_meta_boxes_shortcode' ), 'dearpdf', 'side', 'high' );
      
      //      add_meta_box('dearpdf_post_meta_box_video', __('Video Tutorial', 'dearpdf'), array($this, 'create_meta_boxes_video'), 'dearpdf', 'side', 'low');
      
    }
    
    
    /**
     * Creates metaboxes for upgrade display
     *
     * @param object $post The current post object.
     *
     * @since 1.2.4
     *
     */
    public function create_meta_boxes_support_us( $post ) {
      
      ?>
      <div class="dearpdf-notice lite-limits" style="padding:10px;">

        <div>
          With DearPDF Full version you will have further more possibility of handling flipbooks.
          <ol>
            <li> Ability to change settings for all flipbooks with Global Settings.</li>
            <li><strong>PDF LINKS</strong>, controls customization, etc.</li>
            <li><strong>Popup lightboxes for thumb, link and custom types</strong></li>
            <li> And more...</li>
          </ol>
          <strong style="text-transform: uppercase;"><a href="https://dearpdf.com/go/wp-lite-vs-premium"
                    target="_blank">See
              Full Comparision</a> | <a href="https://dearpdf.com/go/wp-lite-full-version" target="_blank">
              Get Full Version</a></strong>
        </div>
      </div>
      <?php
      
    }
    
    /**
     * Creates metaboxes for shortcode display
     *
     * @param object $post The current post object.
     *
     * @since 1.2.4
     *
     */
    public function create_meta_boxes_shortcode( $post ) {
      global $current_screen;
      
      $postId = $post->ID;
      $tabs = $this->post_shortcode_tabs;
      
      if ( $current_screen->post_type == 'dearpdf' ) {
        if ( $current_screen->action == 'add' ) {
          echo "Save Post to generate shortcode.";
        } else {
          ?>

          <div class="dearpdf-tabs normal-tabs">
            <ul class="dearpdf-tabs-list">
              <?php
              //create tabs
              $active_set = false;
              foreach ( (array) $tabs as $id => $tab ) {
                ?>
                <li class="dearpdf-tab <?php echo( $active_set == false ? 'dearpdf-active' : '' ) ?>">
                  <a href="#dearpdf-tab-content-<?php echo $id ?>"><?php echo $tab['title'] ?></a></li>
                <?php $active_set = true;
              }
              ?>
            </ul>
            <?php
            
            $active_set = false;
            foreach ( (array) $tabs as $id => $tab ) {
              ?>
              <div id="dearpdf-tab-content-<?php echo $id ?>"
                      class="dearpdf-tab-content <?php echo( $active_set == false ? "dearpdf-active" : "" ) ?>">
                <?php echo str_replace( "{id}", $postId, $tab['content'] ) ?>
                
                <?php if ( $id == 'embed' ) { ?>
                  <hr>
                  <a class="dp-notice" href="https://dearpdf.com/docs/multiple-viewers-in-a-page/" target="_blank">Not best for multiple viewers in a page.</a>
                <?php } ?>
                
                <?php $active_set = true; ?>
              </div>
            <?php } ?>
            <br>
            <a target="_blank" href="https://dearpdf.com/docs/shortcode-options/">More Shortcode Options</a>
          </div>
          <?php
        }
      }
      
    }
    
    /**
     * Creates metaboxes for video
     *
     * @param object $post The current post object.
     *
     * @since 1.2.4
     *
     */
    public function create_meta_boxes_video( $post ) {
      global $current_screen;
      //todo after publish
      if ( $current_screen->post_type == 'dearpdf' ) {
        ?>
        <!--        <ul>
                  <li>
                    <a class="video-tutorial" href="https://dearpdf.com/go/wp-lite-video-tutorial" target="_blank"><span
                          class="dashicons dashicons-video-alt3"></span>See Video Tutorial</a>
                  </li>
                  <li>
                    <a class="video-tutorial" href="
              https://dearpdf.com/go/wp-lite-docs" target="_blank"><span class="dashicons dashicons-book"></span>Live
                      Documentation</a>
                  </li>
                  <li>
                    <a class="video-tutorial" href="https://wordpress.org/support/plugin/dearpdf-lite/"
                       target="_blank"><span
                          class="dashicons dashicons-format-chat"></span>Any Issues? Share with us!</a>
                  </li>
                </ul>-->
        <?php
      }
      
    }
    
    /**
     * Creates metaboxes for handling settings
     *
     * @param object $post The current post object.
     *
     * @since 1.0.0
     *
     */
    public function create_meta_boxes( $post ) {
      
      // Keep security first.
      wp_nonce_field( $this->plugin_slug, $this->plugin_slug );
      
      $tabs = $this->post_tabs;
      if ( $error = get_transient( "my_save_post_errors_{$post->ID}" ) ) { ?>
        <div class="info hidden">
        <pre style="white-space: pre-wrap;"><?php echo $error; ?></pre>
        </div><?php
        
        delete_transient( "my_save_post_errors_{$post->ID}" );
      }
      
      //create tabs and content
      ?>
      <div class="dearpdf-tabs">
        <ul class="dearpdf-tabs-list">
          <?php
          //create tabs
          $active_set = false;
          foreach ( (array) $tabs as $id => $title ) {
            ?>
            <li class="dearpdf-update-hash dearpdf-tab <?php echo( $active_set == false ? 'dearpdf-active' : '' ) ?>">
              <a href="#dearpdf-tab-content-<?php echo $id ?>"><?php echo $title ?></a></li>
            <?php $active_set = true;
          }
          ?>
        </ul>
        <?php
        
        $active_set = false;
        foreach ( (array) $tabs as $id => $title ) {
          ?>
          <div id="dearpdf-tab-content-<?php esc_attr_e( $id ) ?>"
                  class="dearpdf-tab-content <?php esc_attr_e( $active_set == false ? "dearpdf-active" : "" ) ?>">
            
            <?php
            $active_set = true;
            
            //create content for tab
            $function = "tab_post_" . $id;
            call_user_func( array( $this, $function ), $post );
            
            ?>
          </div>
        <?php }
        $this->wordpress_rating_box();
        ?>
      </div>
      <?php
      
    }
    
    /**
     * Sanitizes an array value even if not existent
     *
     * @param object $arr     The array to lookup
     * @param mixed  $key     The key to look into array
     * @param mixed  $default Default value in-case value is not found in array
     *
     * @return mixed appropriate value if exists else default value
     * @since 1.0.0
     *
     */
    protected function val( $arr, $key, $default = '' ) {
      return isset( $arr[ $key ] ) ? $arr[ $key ] : $default;
    }
    
    protected function create_global_setting( $key, $post, $global_key ) {
      $this->create_setting( $key, null, $this->get_post_config( $key, $post, $global_key ), $global_key, $this->get_global_config( $key ) );
      
    }
    
    /**
     * Helper method for retrieving global check values.
     *
     * @param string $key  The config key to retrieve.
     * @param object $post The current post object.
     *
     * @return string Key value on success, empty string on failure.
     * @since 1.0.0
     *
     */
    public function global_config( $key ) {//todo name is not proper
      
      $global_value = $this->get_global_config( $key );
      $value = isset( $this->defaults[ $key ] ) ? is_array( $this->defaults[ $key ] ) ? isset( $this->defaults[ $key ]['choices'][ $global_value ] )
          ? $this->defaults[ $key ]['choices'][ $global_value ] : $global_value : $global_value : $global_value;
      
      return $value;
      
    }
    
    public function create_normal_setting( $key, $post ) {
      
      $this->create_setting( $key, null, $this->get_post_config( $key, $post ) );
      
    }
    
    /**
     * Creates the UI for Source tab
     *
     * @param object $post The current post object.
     *
     * @since 1.0.0
     *
     */
    public function tab_post_source( $post ) {
      
      $this->create_normal_setting( 'source', $post );
    }
    
    public function wordpress_rating_box() {
      ?>
      <div class="dearpdf-support-box">
        Thank you for using our little PDF viewer plugin :) We hope it has been useful for you and keeps helping you with
        your cause.
        <br>We love supporting and improving our plugin. <strong>You too can <a
                  href="https://wordpress.org/support/plugin/dearpdf-lite/reviews/?filter=5#new-post"
                  target="_blank">SHARE <span
                    style="color:#ffa000; font-size:1.2em;">&#9733;&#9733;&#9733;&#9733;&#9733;</span> REVIEW SUPPORT</a> on
          WordPress.org!</strong> It would mean a lot to us!
      </div>
      <?php
    }
    
    /**
     * Creates the UI for layout tab
     *
     * @param object $post The current post object.
     *
     * @since 1.0.0
     *
     */
    public function tab_post_general( $post ) {
      
      $this->create_global_setting( 'viewerType', $post, 'global' );
      
      $this->create_global_setting( 'height', $post, '' );
      $this->create_global_setting( 'backgroundColor', $post, '' );
      $this->create_global_setting( 'backgroundImage', $post, '' );
      $this->create_global_setting( 'showDownloadControl', $post, 'global' );
      
    }
    
    
    /**
     * Creates the UI for flipbook tab
     *
     * @param object $post The current post object.
     *
     * @since 1.0.0
     *
     */
    public function tab_post_flipbook( $post ) {
      $this->notice_flipbook_tab();
      
      $this->create_global_setting( 'is3D', $post, 'global' );
      $this->create_global_setting( 'enableSound', $post, 'global' );
      
      $this->create_global_setting( 'readDirection', $post, 'global' );
      
    }
    
    public function notice_flipbook_tab() {
      ?>

      <div class="dp-notice" data-condition='dearpdf_viewerType:not(flipbook)'>Make sure <strong>General->Viewer Type</strong> is set to flipbook to edit flipbook settings.</div>
      
      <?php
    }
    
    
    /**
     * Creates the UI for Advanced tab
     *
     * @param object $post The current post object.
     *
     * @since 1.0.0
     *
     */
    public function tab_post_advanced( $post ) {
      
      $this->create_global_setting( 'disableRange', $post, 'global' );
      
    }
    
    
    /**
     * Helper method for retrieving config values.
     *
     * @param string $key  The config key to retrieve.
     * @param object $post The current post object.
     *
     * @param null   $_default
     *
     * @return string Key value on success, empty string on failure.
     * @since 1.0.0
     *
     */
    public function get_post_config( $key, $post, $_default = null ) {
      
      $values = get_post_meta( $post->ID, '_dearpdf_data', true );
      $value = isset( $values[ $key ] ) ? $values[ $key ] : '';
      
      $default = $_default === null ? isset( $this->defaults[ $key ] ) ? is_array( $this->defaults[ $key ] ) ? isset( $this->defaults[ $key ]['std'] ) ? $this->defaults[ $key ]['std'] : ''
          : $this->defaults[ $key ] : '' : $_default;
      
      /* set standard value */
      if ( $default !== null ) {
        $value = $this->filter_std_value( $value, $default );
      }
      
      return $value;
      
    }
    
    /**
     * Saves values from dearpdf metaboxes.
     *
     * @param int    $post_id The current post ID.
     * @param object $post    The current post object.
     *
     * @since 1.0.0
     *
     */
    public function action_save_post( $post_id, $post ) {
      
      // Bail out if we fail a security check.
      if ( !isset( $_POST['dearpdf'] )
           || !wp_verify_nonce( $_POST['dearpdf'], 'dearpdf' )
           || !isset( $_POST['_dearpdf'] ) ) {
        set_transient( "my_save_post_errors_{$post_id}", "Security Check Failed", 10 );
        
        return;
      }
      
      // Bail out if running an autosave, ajax, cron or revision.
      if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        set_transient( "my_save_post_errors_{$post_id}", "Autosave", 10 );
        
        return;
      }
      if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        set_transient( "my_save_post_errors_{$post_id}", "Ajax", 10 );
        
        return;
      }
      if ( wp_is_post_revision( $post_id ) ) {
        set_transient( "my_save_post_errors_{$post_id}", "revision", 10 );
        
        return;
      }
      
      // Bail if this is not the correct post type.
      if ( isset( $post->post_type )
           && $this->plugin_slug !== $post->post_type ) {
        set_transient( "my_save_post_errors_{$post_id}", "Incorrect Post Type", 10 );
        
        return;
      }
      
      // Bail out if user is not authorized
      if ( !current_user_can( 'edit_post', $post_id ) ) {
        set_transient( "my_save_post_errors_{$post_id}", "UnAuthorized User", 10 );
        
        return;
      }
      
      $sanitized_data = $this->filter_save_post( $_POST['_dearpdf'], $post_id );
      
      $settings = get_post_meta( $post_id, '_dearpdf_data', true );
      if ( empty( $settings ) ) {
        $settings = array();
      }
      $settings = array_merge( $settings, $sanitized_data );
      
      // Update the post meta.
      update_post_meta( $post_id, '_dearpdf_data', $settings );
      
    }
    
    protected function filter_save_post( $raw_data, $post_id ) {
      
      //Source Tab
      $sanitized_data['source'] = esc_url_raw( $raw_data['source'] );
      
      //Layout tab
      $sanitized_data['viewerType'] = sanitize_text_field( $raw_data['viewerType'] );
      $sanitized_data['is3D'] = sanitize_text_field( $raw_data['is3D'] );
      $sanitized_data['height'] = sanitize_text_field( $raw_data['height'] );
      $sanitized_data['backgroundColor'] = sanitize_text_field( $raw_data['backgroundColor'] );
      $sanitized_data['backgroundImage'] = sanitize_text_field( $raw_data['backgroundImage'] );
      $sanitized_data['showDownloadControl'] = sanitize_text_field( $raw_data['showDownloadControl'] );
      $sanitized_data['enableSound'] = sanitize_text_field( $raw_data['enableSound'] );
      $sanitized_data['readDirection'] = sanitize_text_field( $raw_data['readDirection'] );
      $sanitized_data['disableRange'] = sanitize_text_field( $raw_data['disableRange'] );
      
      return $sanitized_data;
    }
    
    protected function process_thumb( $raw_data, $post_id ) {
      return '';
    }
    
    /**
     * Filter out unnecessary row actions dearpdf post table.
     *
     * @param array $actions Default row actions.
     *
     * @return array $actions Amended row actions.
     * @since 1.0.0
     *
     */
    public function filter_post_remove_quick_edit( $actions ) {
      if ( isset( get_current_screen()->post_type ) && 'dearpdf' == get_current_screen()->post_type ) {
        unset( $actions['inline hide-if-no-js'] );
      }
      
      return $actions;
    }
    
    /**
     * Customize the post columns for the dearpdf post type.
     *
     * @return array $columns New Updated columns.
     * @since 1.0.0
     *
     */
    public function filter_post_columns( $columns ) {
      
      $columns['shortcode'] = __( 'Shortcode', 'DFLIP' );
      $columns['modified'] = __( 'Last Modified', 'DFLIP' );
      
      return $columns;
      
    }
    
    /**
     * Add data to the custom columns added to the dearpdf post type.
     *
     * @param string $column_name Name of the custom column.
     * @param int    $post_id     Current post ID.
     *
     * @since 1.0.0
     *
     */
    public function action_post_columns_content( $column_name, $post_id ) {
      $post_id = absint( $post_id );
      
      switch ( $column_name ) {
        case 'shortcode':
          echo '<code>[dearpdf id="' . esc_attr( $post_id ) . '"][/dearpdf]</code>';
          break;
        
        case 'modified' :
          the_modified_date();
          break;
      }
    }
    
    /**
     * Customize the post columns for the dearpdf post type category page
     *
     * @param array $defaults columns.
     *
     * @return array $defaults default columns.
     * @since 1.2.9
     *
     */
    public function filter_post_category_columns( $defaults ) {
      $defaults['shortcode'] = 'Shortcode';
      
      return $defaults;
    }
    
    /**
     * Add data to the custom columns added to the dFldearpdfip post type category page.
     *
     * @param        $c
     * @param string $column_name Name of the custom column.
     * @param        $term_id
     *
     * @return string
     * @since        1.2.9
     *
     * @noinspection PhpUnusedParameterInspection
     */
    public function filter_post_category_columns_content( $c, $column_name, $term_id = "" ) {
      
      return '<code>[dearpdf posts="' . get_term( $term_id, 'dearpdf_category' )->slug . '"][/dearpdf]</code>';
      
    }
    
    /**
     * Display PDF Viewer in PDF attachment page.
     *
     * @param $content
     *
     * @return string Content
     */
    public function filter_pdf_attachment_content( $content ) {
      global $post;
      
      // Check if we're inside the main loop in a single post page.
      if ( is_single() && in_the_loop() && is_main_query() && $post->post_mime_type == "application/pdf" ) {
        
        $content = $this->is_attachment_lightbox( $post );
        
      }
      
      return $content;
    }
    
    public function is_attachment_lightbox( $post ) {
      return do_shortcode( '[dearpdf id="' . $post->ID . '"][/dearpdf]' );
    }
    
    public function init_front() {
      
      //include the shortcode parser
      add_shortcode( 'dearpdf', array( $this, 'shortcode_dearpdf_wrapper' ) );
      //include the scripts and styles for front end
      add_action( 'wp_enqueue_scripts', array( $this, 'action_init_front_scripts' ) );
      
      //some custom js that need to be passed. Registers dearPdfLocation and dearpdfWPGlobal
      add_action( 'wp_head', array( $this, 'action_hook_script' ) );
      
    }
    
    /**
     * Loads all global files into scope.
     *
     * @since 1.0.0
     */
    public function init_global() {
      //register post and taxonomy
      $this->register_post();
      
    }
    
    /**
     * Register Post and taxonomy
     */
    public function register_post() {
      $labels = array(
          'name'               => __( 'DearPDF Post', 'dearpdf' ),
          'singular_name'      => __( 'DearPDF Post', 'dearpdf' ),
          'menu_name'          => __( 'DearPDF', 'dearpdf' ),
          'name_admin_bar'     => __( 'DearPDF Post', 'dearpdf' ),
          'add_new'            => __( 'Add Post', 'dearpdf' ),
          'add_new_item'       => __( 'Add New DearPDF Post', 'dearpdf' ),
          'new_item'           => __( 'New DearPDF Post', 'dearpdf' ),
          'edit_item'          => __( 'Edit DearPDF Post', 'dearpdf' ),
          'view_item'          => __( 'View DearPDF Post', 'dearpdf' ),
          'all_items'          => __( 'All Post', 'dearpdf' ),
          'search_items'       => __( 'Search DearPDF Posts', 'dearpdf' ),
          'parent_item_colon'  => __( 'Parent DearPDF Posts:', 'dearpdf' ),
          'not_found'          => __( 'No DearPDF Posts found.', 'dearpdf' ),
          'not_found_in_trash' => __( 'No DearPDF Posts found in Trash.', 'dearpdf' )
      );
      
      $args = array(
          'labels'              => $labels,
          'description'         => __( 'Description.', 'dearpdf' ),
          'public'              => true,  //this removes the permalink option
          'publicly_queryable'  => false,
          'exclude_from_search' => true, // if not excluded, posts will be displayed in normal search. This will hide it from other archive and taxonomy listing, and needs to be fetched manually.
          'show_ui'             => true,
          'show_in_menu'        => true,
          'query_var'           => true,
          'rewrite'             => array( 'slug' => 'pdfs' ),
          'capability_type'     => 'post',
          'has_archive'         => true,
          'hierarchical'        => false,
          'menu_position'       => null,
          'menu_icon'           => 'dashicons-pdf',
          'supports'            => array( 'title' )
      );
      register_post_type( 'dearpdf', $this->filter_post_args( $args ) );
      
    }
    
    public function filter_post_args( $args ) {
      return $args;
    }
    
    /**
     * Loads all script and style sheets for frontend into scope.
     *
     * @since 1.0.0
     */
    public function action_init_front_scripts() {
      
      //register scripts
      wp_register_script( $this->plugin_slug . '-script', plugins_url( 'assets/js/dearpdf-lite.min.js', __FILE__ ), array( "jquery" ), $this->version, true );
      //    //register scripts
      wp_register_style( $this->plugin_slug . '-style', plugins_url( 'assets/css/dearpdf.min.css', __FILE__ ), array(), $this->version );
      
      
      //enqueue scripts
      wp_enqueue_script( $this->plugin_slug . '-script' );
      //enqueue styles
      wp_enqueue_style( $this->plugin_slug . '-style' );
      
    }
    
    public function action_init_admin_scripts() {
      
      global $id, $post;
      
      $is_dearpdf_screen = isset( get_current_screen()->post_type )
                           && $this->plugin_slug === get_current_screen()->post_type;
      $is_post_screen = $is_dearpdf_screen && isset( get_current_screen()->base ) && 'post' === get_current_screen()->base;
      
      
      if ( $is_dearpdf_screen ) {
        //register scripts
        wp_register_script( $this->plugin_slug . '-admin-script', plugins_url( 'assets/js/metaboxes.js', __FILE__ ), array( "jquery" ), $this->version );
        //register scripts
        wp_register_style( $this->plugin_slug . '-admin-style', plugins_url( 'assets/css/metaboxes.css', __FILE__ ), array(), $this->version );
        
        //enqueue scripts
        wp_enqueue_script( $this->plugin_slug . '-admin-script' );
        //enqueue styles
        wp_enqueue_style( $this->plugin_slug . '-admin-style' );
        wp_enqueue_media();
      }
      if ( $is_post_screen ) {
        // Set the post_id for localization.
        $post_id = isset( $post->ID ) ? $post->ID : (int) $id;
        wp_enqueue_media( array( 'post' => $post_id ) );
        
        wp_register_script( $this->plugin_slug . '-pdfjs', plugins_url( 'assets/js/libs/pdf.min.js', __FILE__ ), null, $this->version );
        wp_enqueue_script( $this->plugin_slug . '-pdfjs' );
        
        $this->action_init_front_scripts();
        $this->action_hook_script();
      }
      
    }
    
    /**
     * Registers a javascript variable into HTML DOM for url access
     *
     * @since 1.0.0
     */
    public function action_hook_script() {
      
      $data = array();
      
      $data = $this->filter_hook_data( $data );
      
      //registers a variable that stores the location of plugin
      echo '<script data-cfasync="false"> var dearPdfLocation = "' . plugins_url( 'assets/', __FILE__ ) . '"; var dearpdfWPGlobal = ' . json_encode( $data ) . ';</script>';
      
    }
    
    public function filter_hook_data( $data ) {
      $data['maxTextureSize'] = $this->get_global_config( 'maxTextureSize' );
      
      return $data;
    }
    
    public function shortcode_dearpdf_wrapper( $attr, $content = '' ) {
      
      if ( !( isset( $attr['posts'] ) && trim( $attr['posts'] ) !== '' ) ) {
        return $this->shortcode_dearpdf( $attr, $content );
      }
    }
    
    public function shortcode_dearpdf( $raw_attr, $content = '', $multi = false ) {
      $atts_default = array(
          'class' => '',
          'id'    => '',
          'type'  => null,
      );
      //atts or post defaults
      $atts = shortcode_atts( $atts_default, $raw_attr, 'dearpdf' );
      
      //in PHP7 if $attr is not an array it causes issue
      if ( is_array( $raw_attr ) == false ) {
        $raw_attr = array();
      }
      $html_attr = array();
      //default data
      $id = $atts['id'] === '' ? 'rand' . rand() : $atts['id'];
      $class = $atts['class'];
      $title = do_shortcode( $content );
      $thumb_url = '';
      
      //get Id
      $post_id = $atts['id'];
      
      $post = trim( $post_id ) == '' ? null : get_post( $post_id );
      $post_data = array();
      
      $post_data['source'] = isset( $raw_attr['source'] ) ? $raw_attr['source'] : "";
      
      if ( $post != null ) {
        
        $post_data = $this->get_post_data( $post, $post_data );
        $id = $post->ID;
        if ( $post->post_type == $this->plugin_slug ) {
          $thumb_url = isset( $post_data['pdfThumb'] ) ? $post_data['pdfThumb'] : "";
        }
        if ( empty( $title ) ) {
          $title = get_the_title( $post_id );
        }
        $post_data['slug'] = $post->post_name;
        $html_attr['data-slug'] = $post->post_name; //this can be overwritten
        $html_attr['data-_slug'] = $post->post_name; //this is for fallback
      }
      
      if ( isset( $raw_attr['isflipbook'] ) ) {
        if ( $raw_attr['isflipbook'] == 'true' ) {
          $post_data['viewerType'] = 'flipbook';
        }
      }
      $html_attr['data-title'] = sanitize_title( $title );
      
      if ( !$multi && isset( $raw_attr['slug'] ) && !empty( $raw_attr['slug'] ) ) {
        $html_attr['data-slug'] = sanitize_title( $raw_attr['slug'] );
      }
      
      if ( empty( $title ) ) {
        $title = "Open Book";
      }
      
      /*Attribute overrides*/
      
      $html_attr['id'] = 'dp_' . $id;
      $post_data['id'] = $id;
      $html_attr['data-option'] = 'dp_option_' . $id;
      $popup = isset( $raw_attr['type'] ) ? $raw_attr['type'] : "";
      
      if ( !isset( $raw_attr['data-thumb'] ) && $thumb_url !== '' ) {
        $html_attr['data-thumb'] = $thumb_url;
      }
      if ( isset( $raw_attr["data-page"] ) ) {
        $post_data["openPage"] = $raw_attr["data-page"];
      }
      
      $html = $this->render_shortcode_html( array(
          'popup'     => $popup,
          'class'     => $class,
          'title'     => $title,
          'raw_attr'  => $raw_attr,
          'html_attr' => $html_attr,
          'post_data' => $post_data
      ) );
      
      return $html;
    }
    
    public function get_post_data( $post, $post_data = array() ) {
      
      if ( $post->post_mime_type == "application/pdf" ) {
        $post_data['source'] = wp_get_attachment_url( $post->ID );
        
      } elseif ( $post->post_type == $this->plugin_slug ) {
        $post_meta = get_post_meta( $post->ID, '_dearpdf_data' );
        if ( is_array( $post_meta ) && count( $post_meta ) > 0 ) {
          $post_meta = $post_meta[0];
        }
        foreach ( $post_meta as $key => $value ) {
          
          //the key has to exist in defaults to be included. Avoids old and unwanted keys in output
          if ( isset( $this->defaults[ $key ] ) ) {
            
            if ( $value === "" || $value === null || $value == "global" ) {//newly added will be null in old post
              unset( $post_meta[ $key ] );
            } else {
              if ( $value === "true" ) {
                $post_data[ $key ] = true;
              } elseif ( $value === "false" ) {
                $post_data[ $key ] = false;
              } else {
                $post_data[ $key ] = $value;
              }
            }
            
          }
          
        }
      }
      
      return $post_data;
    }
    
    public function render_shortcode_html( $args ) {
      
      $popup = trim( $args['popup'] );
      $class = $args['class'] . " dp-lite";
      $title = $args['title'];
      $raw_attr = $args['raw_attr'];
      $html_attr = $args['html_attr'];
      $post_data = $args['post_data'];
      
      $attrHTML = ' ';
      foreach ( $html_attr as $key => $value ) {
        $attrHTML .= esc_attr( $key ) . '="' . esc_attr( $value ) . '" ';
      }
      
      //default
      if ( $popup == "" ) {
        $html = '<div class="dp-element' . esc_attr( $class ) . '" ' . $attrHTML . '></div>';
      } else {
        $html = '<div class="dp-element' . esc_attr( $class ) . '" ' . $attrHTML . ' data-lightbox="button">' . esc_attr( $title ) . '</div>';
      }
      
      $code = 'window.' . esc_attr( $html_attr['data-option'] ) . ' = ' . json_encode( $post_data ) . '; if(window.DEARPDF && window.DEARPDF.parseElements){window.DEARPDF.parseElements();}';
      
      $html .= '<script class="dp-shortcode-script" type="application/javascript">' . $code . '</script>';
      
      return $html;
    }
    
    /**
     * Helper method for retrieving default values.
     *
     * @param string $key The config key to retrieve.
     *
     * @return string Key value on success, empty string on failure.
     * @since 1.0.0
     *
     */
    public function get_default( $key ) {
      
      $default = isset( $this->defaults[ $key ] ) ? is_array( $this->defaults[ $key ] ) ? isset( $this->defaults[ $key ]['std'] ) ? $this->defaults[ $key ]['std'] : '' : $this->defaults[ $key ] : '';
      
      return $default;
      
    }
    
    /**
     * Helper function to filter standard option values.
     *
     * @param mixed $value Saved string or array value
     * @param mixed $std   Standard string or array value
     *
     * @return    mixed     String or array
     *
     * @access    public
     * @since     1.0.0
     */
    public function filter_std_value( $value = '', $std = '' ) {
      
      $std = maybe_unserialize( $std );
      
      if ( is_array( $value ) && is_array( $std ) ) {
        
        foreach ( $value as $k => $v ) {
          
          if ( '' === $v && isset( $std[ $k ] ) ) {
            
            $value[ $k ] = $std[ $k ];
            
          }
          
        }
        
      } else {
        if ( '' === $value && $std !== null ) {
          
          $value = $std;
          
        }
      }
      
      return $value;
      
    }
    
    /**
     * Helper method for retrieving config values.
     *
     * @param string $key The config key to retrieve.
     *
     * @return string Key value on success, empty string on failure.
     * @since 1.2.6
     *
     */
    public function get_global_config( $key ) {
      
      $value = '';
      
      $default = $this->get_default( $key );
      
      /* set standard value */
      if ( $default !== null ) {
        $value = $this->filter_std_value( $value, $default );
      }
      
      return $value;
      
    }
    
    /**
     * Helper function to create settings boxes
     *
     * @access    public
     *
     * @param        $key
     * @param null   $setting
     * @param null   $value
     * @param null   $global_key
     * @param string $global_value
     *
     * @since     1.2.6
     *
     */
    public function create_setting( $key, $setting = null, $value = null, $global_key = null, $global_value = '' ) {
      
      $setting = is_null( $setting ) ? $this->defaults[ $key ] : $setting;
      if ( is_null( $setting ) ) {
        echo "<!--    " . esc_html( $key ) . " Not found   -->";
        
        return;
      }
      $value = is_null( $value ) ? $this->get_global_config( $key ) : $value;
      $condition = isset( $setting['condition'] ) ? $setting['condition'] : '';
      $class = isset( $setting['class'] ) ? $setting['class'] : '';
      $placeholder = isset( $setting['placeholder'] ) ? $setting['placeholder'] : '';
      
      $global_attr = !is_null( $global_key ) ? $global_key : "";
      $global_face_value = $global_value;
      
      echo '<div id="dearpdf_' . esc_attr( $key ) . '_box" class="dearpdf-box ' . esc_attr( $class ) . '" data-condition="' . esc_attr( $condition ) . '">
      <div class="dearpdf-label"><label for="dearpdf_' . esc_attr( $key ) . '" >
				' . esc_attr( $setting['title'] ) . '
			</label><br><a class="dearpdf-help-link" target="_blank" href="https://dearpdf.com/docs/settings/#' . esc_attr( strtolower( $key ) ) . '">More Info >> </a></div>';
      echo '<div class="dearpdf-option">';
      if ( isset( $setting['choices'] ) && is_array( $setting['choices'] ) ) {
        
        echo '<div class="dearpdf-select">
				<select name="_dearpdf[' . esc_attr( $key ) . ']" id="dearpdf_' . esc_attr( $key ) . '" class="" data-global="' . esc_attr( $global_attr ) . '">';
        
        /** @noinspection PhpCastIsUnnecessaryInspection */
        foreach ( (array) $setting['choices'] as $val => $label ) {
          
          if ( is_null( $global_key ) && $val === "global" ) {
            continue;
          }
          
          echo '<option value="' . esc_attr( $val ) . '" ' . selected( $value, $val, false ) . '>' . esc_attr( $label ) . '</option>';
          
          //				}
        }
        echo '</select>';
        $global_face_value = $this->global_config( $key );
        
      } else if ( $setting['type'] == 'upload' ) {
        $tooltip = isset( $setting['button-tooltip'] ) ? $setting['button-tooltip'] : 'Select';
        $button_text = isset( $setting['button-text'] ) ? $setting['button-text'] : 'Select';
        echo '<div class="dearpdf-upload">
				<input placeholder="' . esc_attr( $placeholder ) . '" type="text" name="_dearpdf[' . esc_attr( $key ) . ']" id="dearpdf_' . esc_attr( $key ) . '"
				       value="' . esc_attr( $value ) . '"
				       class="widefat dearpdf-upload-input " data-global="' . esc_attr( $global_attr ) . '"/>
				<a href="javascript:void(0);" id="dearpdf_upload_' . esc_attr( $key ) . '"
				   class="dearpdf_upload_media dearpdf-button button button-primary light"
				   title="' . esc_attr( $tooltip ) . '">
					' . esc_attr( $button_text ) . '
				</a>';
      
      } else if ( $setting['type'] == 'textarea' ) {
        echo '<div class="">
				<textarea rows="3" cols="40" name="_dearpdf[' . esc_attr( $key ) . ']" id="dearpdf_' . esc_attr( $key ) . '"
				          class="" data-global="' . esc_attr( $global_attr ) . '">' . esc_attr( $value ) . '</textarea>';
      } else {
        $type = isset( $setting['type'] ) ? $setting['type'] : '';
        $attrHTML = ' ';
        
        if ( isset( $setting['attr'] ) ) {
          foreach ( $setting['attr'] as $attr_key => $attr_value ) {
            $attrHTML .= $attr_key . "=" . $attr_value . " ";
          }
        }
        
        echo '<div class="">
				<input  placeholder="' . esc_attr( $placeholder ) . '" value="' . esc_attr( $value ) . '" type="' . esc_attr( $type ) . '" ' . esc_attr( $attrHTML ) . ' name="_dearpdf[' . esc_attr( $key ) . ']" id="dearpdf_' . esc_attr( $key ) . '" class="" data-global="' . esc_attr( $global_attr ) . '"/>';
      }
      
      if ( !is_null( $global_key ) ) {
        echo '<div class="dearpdf-global-value" data-global-value="' . esc_attr( $global_value ) . '"><i>Default:</i>
					<code>' . esc_attr( $global_face_value ) . '</code></div>';
      }
      echo '</div>
			<div class="dearpdf-desc">
				' . $setting['desc'] . '
			</div></div>
		</div>';
    
    }
    
    
    //region Help and Tools
    
    /**
     * Callback to create the settings page
     *
     * @since 1.2
     */
    public function help_tools_page() {
      
      $tabs = array(
          'faqs'  => __( 'FAQs', 'dearpdf' ),
          'tools' => __( 'Tools & Diagnostic', 'dearpdf' )
      );
      
      //create tabs and content
      ?>

      <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
      <form id="dearpdf-settings" class="dearpdf-settings postbox">

        <div class="dearpdf-tabs">
          <ul class="dearpdf-tabs-list">
            <?php
            //create tabs
            $active_set = false;
            foreach ( $tabs as $id => $title ) {
              ?>
              <li class="dearpdf-update-hash dearpdf-tab <?php echo( $active_set == false ? 'dearpdf-active' : '' ) ?>">
                <a href="#dearpdf-tab-content-<?php echo $id ?>"><?php echo $title ?></a></li>
              <?php $active_set = true;
            }
            ?>
          </ul>
          <?php
          
          $active_set = false;
          foreach ( $tabs as $id => $title ) {
            ?>
            <div id="dearpdf-tab-content-<?php echo $id ?>"
                    class="dearpdf-tab-content <?php echo( $active_set == false ? "dearpdf-active" : "" ) ?>">
              
              <?php
              $active_set = true;
              
              //create content for tab
              $function = "tab_help_" . $id;
              call_user_func( array( $this, $function ) );
              
              ?>
            </div>
          <?php } ?>
        </div>
      </form>
      <?php
      
    }
    
    public function tab_help_faqs() {
    
    }
    
    public function tab_help_tools() {
      //fetch a PDF file
      $postslist = get_posts( array(
          'post_type'      => 'attachment',
          'post_mime_type' => 'application/pdf',
          'numberposts'    => 1
      ) );
      $testPDFURL = "";
      foreach ( $postslist as $post ) {
        $testPDFURL = wp_get_attachment_url( $post->ID );
        break;
      }
      if ( $testPDFURL != "" ) {
        echo "<div id='test_pdf-url'>Test PDF URL: <a  target='_blank' href='" . $testPDFURL . "'>" . $testPDFURL . "</a></div>";
        
      } else {
        echo "Please Upload some PDF in Media Library.";
      }
      
    }
    
    
    //endregion
    
    /**
     * Returns the singleton instance of the class.
     *
     * @param string $instance
     *
     * @return object DearPDF object.
     * @since 1.0.0
     */
    public static function get_instance( $instance = DearPDF::class ) {
      
      if ( !isset( self::$instance ) && !( self::$instance instanceof DearPDF ) ) {
        self::$instance = new $instance();
      }
      
      return self::$instance;
      
    }
    
  }
  
  
}

/*Avoid PHP closing tag to prevent "Headers already sent"*/
