<?php
/**
 * Settings class file.
 *
 * @package Voyado_Subscribe/Settings
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings class.
 */
class Voyado_Subscribe_Settings {

	/**
	 * The single instance of Voyado_Subscribe_Settings.
	 *
	 * @var     object
	 * @access  private
	 * @since   1.0.0
	 */
	private static $_instance = null; //phpcs:ignore

	/**
	 * The main plugin object.
	 *
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $parent = null;

	public $_token = 'voyado_subscribe';

	public $helper = null;

	/**
	 * Prefix for plugin settings.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $base = '';

	/**
	 * Available settings for plugin.
	 *
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = array();

	/**
	 * Checks for a valid connection
	 *
	 * @var boolean
	 */
	private $valid_connection = false;

	/**
	 * Constructor function.
	 *
	 * @param object $parent Parent object.
	 */
	public function __construct( $parent ) {
		$this->parent = $parent;
		$this->base = $this->_token;
		$this->helper = new Voyado_Subscribe_Helper();
		$this->valid_connection = $this->helper->check_connection();

		// Initialise settings.
		add_action( 'init', array( $this, 'init_settings' ), 11 );

		// Register plugin settings.
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Add settings page to menu.
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );

		// Add settings link to plugins page.
		add_filter(
			'plugin_action_links_' . plugin_basename( $this->parent->file ),
			array(
				$this,
				'add_settings_link',
			)
		);

		// Configure placement of plugin settings page. See readme for implementation.
		add_filter( $this->base . 'menu_settings', array( $this, 'configure_settings' ) );
	}

	/**
	 * Initialise settings
	 *
	 * @return void
	 */
	public function init_settings() {
		$this->settings = $this->settings_fields();
	}

	/**
	 * Add settings page to admin menu
	 *
	 * @return void
	 */
	public function add_menu_item() {

		$args = $this->menu_settings();

		// Do nothing if wrong location key is set.
		if ( is_array( $args ) && isset( $args['location'] ) && function_exists( 'add_' . $args['location'] . '_page' ) ) {
			switch ( $args['location'] ) {
				case 'options':
				case 'submenu':
					$page = add_submenu_page( $args['parent_slug'], $args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], $args['function'] );
					break;
				case 'menu':
					$page = add_menu_page( $args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], $args['function'], $args['icon_url'], $args['position'] );
					break;
				default:
					return;
			}
			add_action( 'admin_print_styles-' . $page, array( $this, 'settings_assets' ) );
		}
	}

	/**
	 * Prepare default settings page arguments
	 *
	 * @return mixed|void
	 */
	private function menu_settings() {
		return apply_filters(
			$this->base . 'menu_settings',
			array(
				'location' => 'options', // Possible settings: options, menu, submenu.
				'parent_slug' => 'options-general.php',
				'page_title' => __( 'Voyado Subscribe', 'voyado-subscribe' ),
				'menu_title' => __( 'Voyado Subscribe', 'voyado-subscribe' ),
				'capability' => 'manage_options',
				'menu_slug' => $this->parent->_token . '_settings',
				'function' => array( $this, 'settings_page' ),
				'icon_url' => '',
				'position' => null,
			)
		);
	}

	/**
	 * Container for settings page arguments
	 *
	 * @param array $settings Settings array.
	 *
	 * @return array
	 */
	public function configure_settings( $settings = array() ) {
		return $settings;
	}

	/**
	 * Load settings JS & CSS
	 *
	 * @return void
	 */
	public function settings_assets() {
		// Silence is golden
	}

	/**
	 * Add settings link to plugin list table
	 *
	 * @param  array $links Existing links.
	 * @return array        Modified links.
	 */
	public function add_settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=' . $this->parent->_token . '_settings">' . __( 'Settings', 'voyado-subscribe' ) . '</a>';
		array_push( $links, $settings_link );

		return $links;
	}

	/**
	 * Build settings fields
	 *
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields() {

		$settings['standard'] = array(
			'title' => __( 'Inställningar', 'voyado-subscribe' ),
			'description' => __( 'Konfigurera API Nyckel', 'voyado-subscribe' ),
			'fields' => array(
				array(
					'id' => '_api_key',
					'label' => __( 'API Nyckel', 'voyado-subscribe' ),
					'description' => __( '', 'voyado-subscribe' ),
					'type' => 'text',
					'default' => '',
					'class' => 'api-key',
					'placeholder' => __( 'XXXXXXXXXXX', 'voyado-subscribe' ),
				)
			),
		);

		$settings = apply_filters( $this->parent->_token . '_settings_fields', $settings );

		return $settings;
	}

	/**
	 * Register plugin settings
	 *
	 * @return void
	 */
	public function register_settings() {
		if ( is_array( $this->settings ) ) {
			foreach ( $this->settings as $section => $data ) {
				if ( $current_section && $current_section !== $section ) {
					continue;
				}

				// Add section to page.
				add_settings_section( $section, $data['title'], array( $this, 'settings_section' ), $this->parent->_token . '_settings' );

				foreach ( $data['fields'] as $field ) {
					// Validation callback for field.
					$validation = '';
					if ( isset( $field['callback'] ) ) {
						$validation = $field['callback'];
					}

					// Register field.
					$option_name = $this->base . $field['id'];

					register_setting( $this->parent->_token . '_settings', $option_name, $validation );

					// Add field to page.
					add_settings_field(
						$option_name,
						$field['label'],
						array( $this->parent->admin, 'display_field' ),
						$this->parent->_token . '_settings',
						$section,
						array(
							'field' => $field,
							'class' => $field['class'],
							'prefix' => $this->base,
						)
					);
				}

				if ( !$current_section ) {
					break;
				}
			}
		}
	}

	/**
	 * Settings section.
	 *
	 * @param array $section Array of section ids.
	 * @return void
	 */
	public function settings_section( $section ) {
		$html = '<p> ' . $this->settings[$section['id']]['description'] . '</p>' . "\n";
		echo $html; //phpcs:ignore
	}

	/**
	 * Load settings page content.
	 *
	 * @return void
	 */
	public function settings_page() {

		// Build page HTML.
		$html = '<div class="wrap" id="' . $this->parent->_token . '_settings">' . "\n";
		$html .= '<h2>' . __( 'Voyado Subscribe', 'voyado-subscribe' ) . '</h2>' . "\n";

		$html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

		// Get settings fields.
		ob_start();
		settings_fields( $this->parent->_token . '_settings' );
		do_settings_sections( $this->parent->_token . '_settings' );
		$html .= ob_get_clean();
	
		ob_start(); ?>


			<?php if ( !$this->valid_connection ) { ?>
				<div class="notice notice-error notice-alt"><p><?php echo __( 'Kunde inte ansluta till Voyado, var god uppdatera din API Nyckel.', 'voyado-subscribe'  ); ?></p></div>
			<?php } else { ?>
				<div class="notice notice-success notice-alt"><p><?php echo __( 'Aktiva prenumeranter: ', 'voyado-subscribe' ).$this->helper->get_subscribers(); ?></p></div>
			<?php } ?>

			<p>
				<?php echo __( 'Använd kortkoden nedan för att visa formuläret på valfri sida.', 'voyado-subscribe'  ); ?>	
			</p>
			<h4><?php _e( 'Kortkod exampel:', 'voyado-subscribe' ); ?></h4>
			<code>[voyado_form]</code>
			<div class="clearfix"></div>
			<br>

	<?php
		$html .= ob_get_clean();

		$html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Spara inställningar', 'voyado-subscribe' ) ) . '" />' . "\n";

		echo $html; //phpcs:ignore
	}

	/**
	 * Main Voyado_Subscribe_Settings Instance
	 *
	 * Ensures only one instance of Voyado_Subscribe_Settings is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see Voyado_Subscribe()
	 * @param object $parent Object instance.
	 * @return object Voyado_Subscribe_Settings instance
	 */
	public static function instance( $parent ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $parent );
		}

		return self::$_instance;
	}

	// End instance()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Cloning of Voyado_Subscribe_API is forbidden.' ) ), esc_attr( $this->parent->_version ) );
	}

	// End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Unserializing instances of Voyado_Subscribe_API is forbidden.' ) ), esc_attr( $this->parent->_version ) );
	}

	// End __wakeup()
}
