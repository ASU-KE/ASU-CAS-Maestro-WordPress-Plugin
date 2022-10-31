<?php
/*
Plugin Name: ASU CAS Maestro
Plugin URL: https://github.com/asu-ke-web-services/ASU-CAS-Maestro-WordPress-Plugin
Description: CAS authentication plugin customized for use by the ASU WordPress community
Version: 2.1.1
Author: NME - Núcleo de Multimédia e E-Learning.
Author URI: http://nme.ist.utl.pt
Text Domain: CAS_Maestro
GitHub Plugin URI: https://github.com/asu-ke-web-services/ASU-CAS-Maestro-WordPress-Plugin
*/

/*
|--------------------------------------------------------------------------
| CONSTANTS
|--------------------------------------------------------------------------
*/

// plugin folder url
if ( ! defined( 'CAS_MAESTRO_PLUGIN_URL' ) ) {
  define( 'CAS_MAESTRO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'CAS_MAESTRO_PLUGIN_PATH' ) ) {
  define( 'CAS_MAESTRO_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

// Mailing type
define( 'WPCAS_WELCOME_MAIL',0 );
define( 'WPCAS_WAITACCESS_MAIL',1 );

/*
|--------------------------------------------------------------------------
| Load ASU Directory utility class
|--------------------------------------------------------------------------
*/
use Asu_Research\AsuPublicDirectoryService\AsuDirectory;
require_once 'lib/AsuDirectory.php';

/*
|--------------------------------------------------------------------------
| MAIN CLASS
|--------------------------------------------------------------------------
*/
class CAS_Maestro {

  /*
  --------------------------------------------*
  * Atributes
  *--------------------------------------------
  */

  public $settings;
  public $network_settings;
  public $allowed_users;

  public $cas_configured = true;

  public $settings_hook = array( 'settings_page_wpcas_settings','toplevel_page_wpcas_settings' );
  public $current_page_hook;

  public $bypass_cas = false;

  public $change_users_capability;

  /*
  --------------------------------------------*
  * Constructor
  *--------------------------------------------
  */

  /**
   * Initializes the plugin by setting localization, filters, and administration functions.
   */
  function __construct() {
    // Initialize the settings
    $default_settings = array(
        'cas_menu_location' => 'settings',
        'new_user' => false,
          'cas_version' => '2.0',
          'server_hostname' => 'weblogin.asu.edu',
          'server_port' => '443',
          'server_path' => 'cas',
          'phpcas_path' => 'lib/phpCAS/',
          'debug_path' => '',
          'redirect_url' => get_option( 'siteurl' ),
          'email_registration' => 2,
          'email_suffix' => 'asu.edu',
          'global_sender' => get_bloginfo( 'admin_email' ),
          'full_name' => '',
          // CAS attributes
          'cas_first_name' => '',
          'cas_last_name' => '',
          'cas_user_email' => '',
          // Welcome email
          'welcome_mail' => array(
              'send_user' => false,
              'send_global' => false,
              'subject' => 'Welcome to %sitename%',
              'user_body' => 'Hello %realname%,

Welcome to %sitename%! Your account has been activated, and you are now able to access restricted features of %sitename%, depending on the access permissions granted to your account. You can sign in to %sitename% by going to the site and adding the following text to the end of the site\'s URL in your browser address bar: /wp-admin

Welcome aboard!',
              'global_body' => '',
            ),
          // Waiting for access email
          'wait_mail' => array(
              'send_user' => false,
              'send_global' => false,
              'subject' => '',
              'user_body' => '',
              'global_body' => '',
            ),
          // LDAP Settings
          'ldap_protocol' => '',
          'ldap_server' => '',
          'ldap_username_rdn' => '',
          'ldap_password' => '',
          'ldap_basedn' => '',
        );

    $this->network_settings = get_site_option( 'asuCAS_network_settings', $default_settings );

    // Get blog settings. If they doesn't exist, get the network settings.
    $this->settings = get_option( 'asuCAS_settings',$this->network_settings );
    $this->allowed_users = get_option( 'asuCAS_allowed_users',array() );
    $this->change_users_capability = 'manage_options';

    if ( ! isset( $_SESSION ) ) {
      session_start();
    }

    $this->bypass_cas = defined( 'WPCAS_BYPASS' ) || isset( $_GET['wp'] ) || isset( $_GET['checkemail'] ) || ( isset( $_SESSION['not_using_CAS'] ) && $_SESSION['not_using_CAS'] === true );
    $this->init( ! $this->bypass_cas );

  }


  /**
   * Plugin initialization, action & filters register, etc
   */
  function init( $run_cas = true ) {
    global $error;
    global $pagenow;

    if ( $run_cas ) {
      /**
       * Initalize phpCAS
       *
       * If setting hasn't been initialized (because original cas maestro plugin
       * had been installed, then set default value here
       */
      if ( empty( $this->settings['phpcas_path'] ) ) {
        $this->settings['phpcas_path'] = 'lib/phpCAS/';
      }
      include_once( $this->settings['phpcas_path'] . 'CAS.php' );

      if ( $this->settings['server_hostname'] == '' ||
          intval( $this->settings['server_port'] ) == 0 ) {
        $this->cas_configured = false;
      }

      if ( $this->cas_configured ) {
        // If everything is alright, let's initialize the phpCAS client
        phpCAS::client($this->settings['cas_version'],
            $this->settings['server_hostname'],
            intval( $this->settings['server_port'] ),
            $this->settings['server_path'],
        false);

        // function added in phpCAS v. 0.6.0
        // checking for static method existance is frustrating in php4
        $phpCas = new phpCas();
        if ( method_exists( $phpCas, 'setNoCasServerValidation' ) ) {
          phpCAS::setNoCasServerValidation();
        }
        unset( $phpCas );
        // if you want to set a cert, replace the above few lines

        if ( $this->settings['debug_path'] ) {
          phpCAS::setDebug( $this->settings['debug_path'] );
        }

        /**
         * Filters and actions registration
         */
        add_filter( 'authenticate', array( &$this, 'validate_login' ), 30, 3 );
        add_filter( 'login_url', array( &$this, 'bypass_reauth' ) );
        /*
        FIXME: re-enable once non-CAS user bug is fixed
        These actions are temporarily disabled until I can work out some minor routing issues for non-CAS users

        add_action('lost_password', array(&$this, 'disable_function'));
        add_action('retrieve_password', array(&$this, 'disable_function'));
        add_action('password_reset', array(&$this, 'disable_function'));
        */

        if ( $pagenow == 'profile.php' ) {
          add_filter( 'show_password_fields', array( &$this, 'show_password_fields' ), 999 );
        }
      } else {
        $error = __( 'wpCAS is not configured. Please, login, go to the settings and configure with your credentials.', 'CAS_Maestro' );
        // add_filter( 'login_head', array(&$this, 'display_login_notconfigured'));
      }
    }
    add_action( 'wp_logout', array( &$this, 'process_logout' ) );

    // Register the language initialization
    add_action( 'init' ,array( &$this, 'lang_init' ) );
    add_action( 'admin_init', array( &$this, 'add_meta_boxes' ) );
    add_action( 'profile_update', array( &$this, 'on_save_profile' ),10,2 );
    add_action( 'admin_notices', array( &$this, 'notify_email_update' ) );

    add_action( 'admin_menu', array( &$this, 'register_menus' ), 50 );
    add_action( 'admin_enqueue_scripts', array( &$this, 'register_javascript' ) );

    // Filter to rewrite the login form action to bypass cas
    if ( $this->bypass_cas ) {
      add_filter( 'site_url', array( &$this, 'bypass_cas_login_form' ), 20, 3 );
      add_filter( 'authenticate', array( &$this, 'validate_noncas_login' ), 30, 3 );
    }
  }

  /**
   * Initialize the language domain of the plugin
   */
  function lang_init() {
    if ( function_exists( 'load_plugin_textdomain' ) ) {
      load_plugin_textdomain( 'CAS_Maestro', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
    }
  }

  /**
   * Generates a random string with a length
   */
  function generate_random_string( $length = 10 ) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!"#$%&/()=?';
    $randomString = '';
    for ( $i = 0; $i < $length; $i++ ) {
      $randomString .= $characters[ rand( 0, strlen( $characters ) - 1 ) ];
    }
    return $randomString;
  }

  /*
  ----------------------------------------------*
  * Authentication managment
  *----------------------------------------------
  */

  function validate_noncas_login( $user, $username, $password ) {
    // Add session to flag that user logged in without CAS
    if ( ! is_wp_error( $user ) ) {
      if ( ! isset( $_SESSION ) ) {
        session_start();
      }
      $_SESSION['not_using_CAS'] = true;
    }
    return $user;
  }

  /**
   * Authenticate the user using CAS
   */
  function validate_login( $null, $username, $password ) {
    if ( ! $this->cas_configured ) {
      die( 'Error. CAS not configured and I was unable to redirect you to wp-login. Use define("WPCAS_BYPASS",true); in your wp-config.php to bypass wpCAS' );
    }

    phpCAS::forceAuthentication();

    // might as well be paranoid
    if ( ! phpCAS::isAuthenticated() ) {
      exit();
    }

    $username = phpCAS::getUser();
    $password = md5( $username . 'wpCASAuth!"#$"!$!"%$#"%#$' . rand() . $this->generate_random_string( 20 ) );
    $this->_default_value( $user_email, '' );
    $this->_default_value( $user_realname, '' );
    $this->_default_value( $firstname, '' );
    $this->_default_value( $lastname, '' );

    // lookup user in WordPress
    $user = get_user_by( 'login', $username );
    //$this->_write_log( $user );

    if ( $user ) {
      // we found user, so check if this a multisite install
      if ( is_multisite() ) {
        // this is multisite, so see if user is member of blog and is allowed to register
        if ( $this->can_user_register( $username ) && ! is_user_member_of_blog( $user->ID, get_current_blog_id() ) ) {
          do_action( 'casmaestro_multisite_before_register_user' );
          $nextrole = $this->can_user_register( $username );
          add_user_to_blog( get_current_blog_id(), $user->ID, $nextrole );
          do_action( 'casmaestro_multisite_after_register_user', $user );
        }
      }
      return $user;
    }

    /** Register a new user, if it is allowed */
    if ( $user_role = $this->can_user_register( $username ) ) {
      $user_email = '';
      $email_registration = $this->settings['email_registration'];

      // Is the site configured to acquire and use the user's email address?
      switch ( $email_registration ) {
        case 2: // Using suffix
          $user_email = $username . '@' . $this->settings['email_suffix'];
          break;

        case 3: // Using LDAP
          /*fetch user email from ldap*/
          $ds = ldap_connect( $this->settings['ldap_server'] );
          ldap_set_option( $ds, LDAP_OPT_PROTOCOL_VERSION, $this->settings['ldap_protocol'] );
          ldap_set_option( $ds, LDAP_OPT_RESTART, true );

          $r = ldap_bind( $ds,$this->settings['ldap_username_rdn'],$this->settings['ldap_password'] );

          $list = ldap_list($ds, $this->settings['ldap_basedn'],
          "uid=$username");

          if ( $list !== false ) {
            $result = ldap_get_entries( $ds, $list );

            if ( $result['count'] > 0 ) {
              $result = $result[0];
              if ( isset( $result['mail'] ) && is_array( $result['mail'] ) ) {
                $user_email = $result['mail'][0];
              }
              if ( isset( $result['displayname'] ) && is_array( $result['displayname'] ) ) {
                $user_realname = $result['displayname'][0];
                $exploded_name = explode( ' ', $user_realname );
                $firstname = $exploded_name[0];
                $lastname = end( $exploded_name );
              }
            }
          }
          break;

        case 4: // Using CAS attributes
          $cas_attributes = phpCAS::getAttributes();
          if ( isset( $cas_attributes['first_name'] ) ) {
            $firstname = $cas_attributes['first_name'];
          }
          if ( isset( $cas_attributes['last_name'] ) ) {
            $lastname = $cas_attributes['last_name'];
          }
          if ( isset( $cas_attributes['mail'] ) ) {
            $user_email = $cas_attributes['mail'];
          }
          $user_realname = $firstname . ' ' . $lastname;
          break;

        case 5: // Using ASURITE iSearch attributes
          $directory_info = AsuDirectory::getDirectoryInfoByAsurite( $username );
          if ( ! empty( AsuDirectory::getEmail( $directory_info ) ) ) {
            $user_email = AsuDirectory::getEmail( $directory_info );
          }
          if ( ! empty( AsuDirectory::getFirstName( $directory_info ) ) ) {
            $firstname = AsuDirectory::getFirstName( $directory_info );
          }
          if ( ! empty( AsuDirectory::getLastName( $directory_info ) ) ) {
            $lastname = AsuDirectory::getLastName( $directory_info );
          }
          if ( ! empty( AsuDirectory::getDisplayName( $directory_info ) ) ) {
            $user_realname = AsuDirectory::getDisplayName( $directory_info );
          }
          break;

        default: // No email predition
          break;

      }

      $user_info = array();
      $user_info['user_pass'] = $password;
      $user_info['user_email'] = $user_email;
      $user_info['user_login'] = $username;
      $user_info['display_name'] = $user_realname;
      $user_info['first_name'] = $firstname;
      $user_info['last_name'] = $lastname;

      // Verify if we need to add user to a specified role
      // user_role will be TRUE, if registrations are globally allowed
      // otherwise, it will contain the WP role assigned for this specific user
      if ( ! is_bool( $user_role ) ) {
        $user_info['role'] = $user_role;
      }

      do_action( 'casmaestro_before_register_user' );

      $registration_result = wp_insert_user( $user_info );
      //$this->_write_log( __CLASS__ . '.' . __FUNCTION__ . ' (' . __LINE__ . ') : New user result => ' );
      //$this->_write_log( $registration_result );

      if ( ! is_wp_error( $registration_result ) ) {
        $send_user = ! empty( $user_info['user_email'] ); // False, if user has no email
        if ( ! isset( $user_info['role'] ) && $this->settings['wait_mail']['send_user'] ) {
          // If user has no role and is allowed to send wait mail to user
          $this->process_mailing( WPCAS_WAITACCESS_MAIL,$user_info,$send_user );

        } elseif ( ! isset( $user_info['role'] ) && ! $this->settings['wait_mail']['send_user'] ) {
          // Otherwise, if has no role and we don't want a wait for access mail, send the welcome mail
          $this->process_mailing( WPCAS_WELCOME_MAIL,$user_info,$send_user );

        } else {
          // In any other case, send a Welcome Mail
          $this->process_mailing( WPCAS_WELCOME_MAIL,$user_info,$send_user );

        }

        $user = get_user_by( 'login',$username );
        if ( ! isset( $user_info['user_role'] ) ) {
          update_user_meta( $user->ID,'_wpcas_waiting',true );
        }

        do_action( 'casmaestro_after_register_user', $user );
        return $user;
      }
    } else {
      $caserror_file = get_template_directory() . '/cas_error.php';
      include( file_exists( $caserror_file ) ? $caserror_file : 'cas_error.php' );
      exit();

    }
  }

  /**
   * Update User Profile
   *  Hook to verify if user email was correctly filled, and send a welcome email when that is done.
   */
  function on_save_profile( $user_id, $old_user_data ) {
    $user = get_user_by( 'id',$user_id );
    $user_data['user_login'] = $user->user_login;
    $user_data['user_email'] = $user->user_email;
    $user_data['display_name'] = $user->display_name;

    if ( $old_user_data->user_email == '' && $user->user_email != '' ) {
      if ( ! isset( $this->allowed_users[ $user->user_login ] ) && $this->settings['wait_mail']['send_user'] ) {
        // If user is waiting for access (not in the allowed list) and is allowed to send wait mail to user
        $this->process_mailing( WPCAS_WAITACCESS_MAIL,$user_data,true,false );
      } else {
        $this->process_mailing( WPCAS_WELCOME_MAIL,$user_data, true,false ); // Send welcome mail only to the user, and not the admin
      }
    }

    // Verify if there was a role change
    $waiting = ! empty( get_user_meta( $user_id, '_wpcas_waiting', true ) );
    if ( $waiting && ! in_array( 'subscriber', $user->roles ) && $this->settings['wait_mail']['send_user'] ) {
      delete_user_meta( $user_id,'_wpcas_waiting' );
      // user permissions have been given, notify the user
      $this->process_mailing( WPCAS_WELCOME_MAIL, $user_data, true, false );
    }
  }

  function bypass_cas_login_form( $url, $path, $orig_scheme ) {
    if ( $this->bypass_cas ) {
      if ( $path == 'wp-login.php' || $path == 'wp-login.php?action=register' || $path == 'wp-login.php?action=lostpassword' || $path == 'wp-login.php?action=resetpass' ) {
        return add_query_arg( 'wp', '', $url );
      }
    }
    return $url;
  }

  function process_logout() {
    $not_using_cas = isset( $_SESSION['not_using_CAS'] ) && $_SESSION['not_using_CAS'] == true;
    session_destroy();

    do_action( 'casmaestro_before_logout_redirect' );

    if ( $not_using_cas ) {
      wp_redirect( home_url() );

    } elseif ( $this->settings['redirect_url'] ) {
      phpCAS::logoutWithRedirectService( $this->settings['redirect_url'] );

    } else {
      phpCAS::logout();

    }
    exit();
  }

  /**
   * Remove the reauth=1 parameter from the login URL, if applicable. This allows
   * us to transparently bypass the mucking about with cookies that happens in
   * wp-login.php immediately after wp_signon when a user e.g. navigates directly
   * to wp-admin.
   */
  function bypass_reauth( $login_url ) {
    $login_url = remove_query_arg( 'reauth', $login_url );
    return $login_url;
  }

  /**
   * If user was authenticated via CAS, don't show password fields on their profile page.
   * Conversely, let's not disable this filter for non-CAS users.
   */
  function show_password_fields( $show_password_fields ) {
    if ( ! $this->bypass_cas ) {
      return false;
    }
  }

  /**
   * Disable a function. To be hooked to an action
   *
   * We need to be able to bypass this when a user is not CAS-authenticated
   * (regular Wordpress users need to be able to perform these actions)
   * This is generally taken into account elsewhere, but just to be safe,
   * we will "disable this disable" if the user is not CAS-authenticated
   */
  function disable_function() {
    if ( ! $this->bypass_cas ) {
      die( 'Disabled' );
    }
  }

  /*
  ----------------------------------------------*
  * Administration Interface Functions
  *----------------------------------------------
  */

  function notify_email_update() {
    $user = wp_get_current_user();
    if ( empty( $user->user_email ) ) {
      echo '<div class="updated">
         <p>' . sprintf( __( 'You haven\'t filled out your profile yet. You need to at least enter your name and email address to begin using this site. <a href="%s"> Click here </a> to access your profile. ', 'CAS_Maestro' ), admin_url( 'profile.php' ) ) . '</p>
      </div>';
    }
  }

  function register_menus() {
    // If you wanna change the capability to edit authorized users, filter on this hook.
    $this->change_users_capability = apply_filters(
      'cas_maestro_change_users_capability',
      $this->change_users_capability
    );

    if ( current_user_can( 'manage_options' ) ) {
      switch ( $this->settings['cas_menu_location'] ) {
        case 'sidebar':
          $settings_page = add_menu_page(
              __( 'ASU CAS Maestro Settings', 'CAS_Maestro' ),
              __( 'ASU CAS Maestro', 'CAS_Maestro' ),
              'manage_options',
              'wpcas_settings',
              array( &$this,'admin_interface' ),
              '',
              214
          );
          break;
        case 'settings':
        default:
          $settings_page = add_options_page(
            __( 'ASU CAS Maestro', 'CAS_Maestro' ),
            __( 'ASU CAS Maestro', 'CAS_Maestro' ),
            'manage_options',
            'wpcas_settings',
            array( &$this,'admin_interface' )
          );
          break;
      }
    } elseif ( ! current_user_can( 'manage_options' ) && current_user_can( $this->change_users_capability ) ) {
      $settings_page = add_menu_page(
          __( 'ASU CAS Maestro Settings', 'CAS_Maestro' ),
          __( 'ASU CAS Maestro', 'CAS_Maestro' ),
          $this->change_users_capability,
          'wpcas_settings',
          array( &$this,'admin_interface' ),
          '',
          214
      );
    }
    // let's not try to add action if no settings page is available
    if ( ! empty( $settings_page ) ) {
      add_action( "load-{$settings_page}", array( &$this, 'on_load_settings_page' ) );
    }
  }

  function add_meta_boxes() {
    // Metabox General Settings
    foreach ( $this->settings_hook as $settings_hook ) {

      if ( current_user_can( 'manage_options' ) ) {
        add_meta_box(
            'wpcas_general_settings',
            __( 'General Settings', 'CAS_Maestro' ),
            array( &$this, 'meta_box_render' ),
            $settings_hook,
            'main',
            'high',
            array( 'metabox' => 'general' )
        );
      }

      // Metabox registration settings
      add_meta_box(
          'wpcas_registration',
          __( 'Registration', 'CAS_Maestro' ),
          array( &$this, 'meta_box_render' ),
          $settings_hook,
          'main',
          'high',
          array( 'metabox' => 'registration' )
      );

      // Metabox mailing settings
      if ( current_user_can( 'manage_options' ) ) {
        add_meta_box(
            'wpcas_mailing',
            __( 'Mailing', 'CAS_Maestro' ),
            array( &$this, 'meta_box_render' ),
            $settings_hook,
            'main',
            'high',
            array( 'metabox' => 'mail' )
        );
      }

      // SIDE META BOXES
      if ( current_user_can( 'manage_options' ) ) {
        add_meta_box(
            'wpcas_pdates',
            __( 'Important note', 'CAS_Maestro' ),
            array( &$this, 'meta_box_render' ),
            $settings_hook,
            'side',
            'high',
            array( 'metabox' => 'help_metabox' )
        );
      }

      add_meta_box(
          'wpcas_u1dates',
          __( 'Credits', 'CAS_Maestro' ),
          array( &$this, 'meta_box_render' ),
          $settings_hook,
          'side',
          'high',
          array( 'metabox' => 'soon_metabox' )
      );
    }
  }

  function meta_box_render( $module, $metabox = array() ) {
    if ( isset( $metabox['args']['metabox'] ) ) {
      include( CAS_MAESTRO_PLUGIN_PATH . '/views/metaboxes/' . $metabox['args']['metabox'] . '.php' );
    }
  }

  function register_javascript( $hook ) {
    $this->current_page_hook = $hook;
    if ( in_array( $hook, $this->settings_hook ) ) {

      wp_enqueue_script( 'select2-script', plugins_url( '/js/select2/select2.js', __FILE__ ) );
      wp_enqueue_style( 'select2', plugins_url( '/js/select2/select2.css', __FILE__ ) );

      wp_enqueue_script( 'autoinput', plugins_url( '/js/autoinput.js', __FILE__ ) );
      wp_enqueue_script( 'validations', plugins_url( '/js/validations.js', __FILE__ ) );

      wp_enqueue_script( 'admin', plugins_url( '/js/admin.js', __FILE__ ) );

      $js_vars = array(
        'url' => CAS_MAESTRO_PLUGIN_URL,
        'cas_respond' => __( 'CAS is responding', 'CAS_Maestro' ),
        'cas_not_respond' => __( 'CAS is not responding', 'CAS_Maestro' ),
        'ldap_respond' => __( 'LDAP is responding', 'CAS_Maestro' ),
        'ldap_not_respond' => __( 'LDAP is not responding', 'CAS_Maestro' ),
        'checking_html' => __( 'Checking...', 'CAS_Maestro' ),
        'choose_role' => __( 'Choose the User\'s role','CAS_Maestro' ),
        );
      wp_localize_script( 'validations', 'casmaestro', $js_vars );
      // Metabox related scripts
      wp_enqueue_script( 'common' );
      wp_enqueue_script( 'wp-lists' );
      wp_enqueue_script( 'postbox' );

      // Register CSS files
      wp_register_style( 'wpcas-backend', plugins_url( '/css/backend.css', __FILE__ ) );
      wp_enqueue_style( 'wpcas-backend' );
    }
  }

  function on_load_settings_page() {
    if ( isset( $_POST['submit'] ) && $_POST['submit'] ) {
      global $output_error;
      $this->save_settings();
      $url_parameters = '&success=true';
      if ( $output_error ) {
        $url_parameters .= '&error=true';
      }
      wp_redirect( menu_page_url( 'wpcas_settings',false ) . $url_parameters );
      exit;
    }
  }

  function admin_interface() {
    $user = wp_get_current_user();
    include( 'views/admin_interface.php' );
  }

  function save_settings() {
    if ( current_user_can( 'manage_options' ) ) {
      $optionarray_update = array(
        // CAS Settings
        'cas_version' => $_POST['cas_version'],
        'phpcas_path' => $_POST['phpcas_path'],
        'server_hostname' => $_POST['server_hostname'],
        'server_port' => $_POST['server_port'],
        'server_path' => $_POST['server_path'],
        'debug_path' => $_POST['debug_path'],
        'redirect_url' => $_POST['redirect_url'],
        // CAS Attributes
        'cas_first_name' => $_POST['cas_first_name'],
        'cas_last_name' => $_POST['cas_last_name'],
        'cas_user_email' => $_POST['cas_user_email'],
        // LDAP Settings
          'ldap_protocol' => $_POST['ldap_protocol'],
          'ldap_server' => $_POST['ldap_server'],
          'ldap_port' => $_POST['ldap_port'],
          'ldap_username_rdn' => $_POST['ldap_username_rdn'],
          'ldap_password' => $_POST['ldap_password'],
          'ldap_basedn' => $_POST['ldap_basedn'],
          'email_registration' => $_POST['email_registration'],
          'full_name' => $_POST['full_name'],
          // Mailing Settings
        'global_sender' => $_POST['global_sender'],
        'welcome_mail' => array(
              'send_user' => (bool) $_POST['welcome_send_user'],
              'send_global' => (bool) $_POST['welcome_send_global'],
              'subject' => $_POST['welcome_subject'],
              'user_body' => $_POST['welcome_user_body'],
              'global_body' => $_POST['welcome_global_body'],
            ),
        // Waiting for access email
          'wait_mail' => array(
              'send_user' => (bool) $_POST['wait_send_user'],
              'send_global' => (bool) $_POST['wait_send_global'],
              'subject' => $_POST['wait_subject'],
              'user_body' => $_POST['wait_user_body'],
              'global_body' => $_POST['wait_global_body'],
            ),
        'new_user' => $_POST['new_user'],
        'email_suffix' => $_POST['email_suffix'],
        // Global settings
        'cas_menu_location' => $_POST['admin_menu'],
      );

      $mandatory_fields = array(
        'server_hostname',
        'server_port',
        );
      if ( $optionarray_update['email_registration'] == 3 ) {
        // If LDAP is selected
        $new_mandatory = array(
          'ldap_server',
          'ldap_basedn',
          );
        $mandatory_fields = array_merge( $new_mandatory,$mandatory_fields );
      }

      // Create an update array without empty fields
      $updated_array = $optionarray_update;

      $this->settings = array_merge( $this->settings,$updated_array );
      update_option( 'asuCAS_settings',$this->settings );
    }

    // Allowed users to register processing
    $allowed_users = array();
    if ( isset( $_POST['username'] ) ) {
      foreach ( $_POST['username'] as $i => $username ) {
        if ( $username == '' ) {
          continue;
        }
        $allowed_users[ $username ] = $_POST['role'][ $i ];
      }
    }

    $this->allowed_users = $allowed_users;

    update_option( 'asuCAS_allowed_users',$this->allowed_users );

    // Check for empty fields to output error message
    global $output_error;
    $output_error = false;
    foreach ( $mandatory_fields as $field ) {
      if ( empty( $optionarray_update[ $field ] ) ) {
        $output_error = true;
        break;
      }
    }
  }

  /*
  ----------------------------------------------*
  * Auxiliary Functions
  *----------------------------------------------
  */

  /**
   * Return WordPress role if username is in the list of allowed usernames, or
   * return TRUE if user registration is globally enabled, or
   * return FALSE otherwise
   *
   * @param  [type] $username [description]
   * @return [type]           [description]
   */
  function can_user_register( $username ) {
    if ( isset( $this->allowed_users[ $username ] ) ) {
      return $this->allowed_users[ $username ];
    }

    if ( $this->settings['new_user'] ) {
      return true; // User global registration is enabled
    }

    return false;
  }

  /**
   * Process the mailing, sending a $type mail to the user and
   *   notifing the admin (if notification setting is true)
   */
  private function process_mailing( $type, array $user_info, $send_to_user = true, $send_to_admin = true ) {
    // Global sender is always the same.
    $from_mail = $this->settings['global_sender'];
    // Populate the variables, acording to the mail type
    switch ( $type ) {
      case WPCAS_WELCOME_MAIL:
        $user_body = $this->settings['welcome_mail']['user_body'];
        $global_body = $this->settings['welcome_mail']['global_body'];
        $user_subject = $this->settings['welcome_mail']['subject'];
        // Set the boolean variables
        $send_user = $this->settings['welcome_mail']['send_user'];
        $send_global = $this->settings['welcome_mail']['send_global'];

        break;
      case WPCAS_WAITACCESS_MAIL:
        $user_body = $this->settings['wait_mail']['user_body'];
        $global_body = $this->settings['wait_mail']['global_body'];
        $user_subject = $this->settings['wait_mail']['subject'];
        // Set the boolean variables
        $send_user = $this->settings['wait_mail']['send_user'];
        $send_global = $this->settings['wait_mail']['send_global'];

        break;
      default:
        return false;
    }

    $from = (empty( $this->settings['full_name'] ) ? get_bloginfo( 'name' ) : $this->settings['full_name']);
    $message_headers = "MIME-Version: 1.0\n" . 'From: ' . $from .
    " <{$from_mail}>\n" . 'Content-Type: text/plain; charset=' . get_option( 'blog_charset' ) . "\n";

    /**
     * Replace Variables with real content
     */
    $variables = array( '%sitename%','%username%','%realname%' );
    $variables_values = array( get_bloginfo( 'name' ),$user_info['user_login'],$user_info['display_name'] );

    if ( ! empty( $user_body ) ) {
      $subject = str_replace( $variables, $variables_values, $user_subject );
      $user_body = str_replace( $variables, $variables_values, $user_body );
    }

    if ( ! empty( $global_body ) ) {
      $subject = str_replace( $variables, $variables_values, $user_subject );
      $global_body = str_replace( $variables, $variables_values, $global_body );
    }

    /**
     * Finally, do the mailing
     */
    if ( ! $send_to_user ) {
      $send_user = false;
    }
    if ( ! $send_to_admin ) {
      $send_global = false;
    }

    if ( $send_user ) {
      wp_mail( $user_info['user_email'], $subject, $user_body, $message_headers );
    }
    if ( $send_global ) {
      wp_mail( $from_mail, $subject, $global_body, $message_headers );
    }

  }

  private function _write_log( $log ) {
    if ( true === WP_DEBUG ) {
      if ( is_array( $log ) || is_object( $log ) ) {
        error_log( print_r( $log, true ) . PHP_EOL, 3, 'cas_maestro_debug.log' );
      } else {
        error_log( $log . PHP_EOL, 3, 'cas_maestro_debug.log' );
      }
    }
  }

  private function _default_value( &$var, $default ) {
    if ( empty( $var ) ) {
      $var = $default;
    }
  }
}
// instantiate plugin's class
$GLOBALS['CAS_Maestro'] = new CAS_Maestro();

require_once( CAS_MAESTRO_PLUGIN_PATH . '/functions.php' );
