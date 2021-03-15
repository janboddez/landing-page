<?php
/**
 * Handles the WP Admin settings page.
 *
 * @package Landing_Page
 */

namespace Landing_Page;

/**
 * Handles the WP Admin settings page.
 */
class Options_Handler {
	/**
	 * Plugin settings.
	 *
	 * @var array $options Plugin settings.
	 */
	private $options = array(
		'mapping'   => array(),
		'canonical' => true,
		'redirect'  => false,
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->options = get_option( 'landing_page_settings', $this->options );
	}

	/**
	 * Registers hook callbacks.
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'create_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_search_posts', array( $this, 'autocomplete' ) );

		add_filter( 'posts_where', array( $this, 'posts_where' ), 10, 2 );
	}

	/**
	 * Registers settings page.
	 */
	public function create_menu() {
		add_options_page(
			__( 'Landing Page', 'landing-page' ),
			__( 'Landing Page', 'landing-page' ),
			'manage_options',
			'landing-page',
			array( $this, 'settings_page' )
		);
		add_action( 'admin_init', array( $this, 'add_settings' ) );
	}

	/**
	 * Registers the actual options.
	 */
	public function add_settings() {
		register_setting(
			'landing-page-settings-group',
			'landing_page_settings',
			array(
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);
	}

	/**
	 * Handles submitted options.
	 *
	 * @param array $settings Settings as submitted through WP Admin.
	 *
	 * @return array Options to be stored.
	 */
	public function sanitize_settings( $settings ) {
		// Reset these.
		$options = array(
			'mapping'   => array(),
			'canonical' => false,
			'redirect'  => false,
		);

		if ( isset( $settings['mapping']['domain'] ) && is_array( $settings['mapping']['domain'] ) &&
		     isset( $settings['mapping']['target'] ) && is_array( $settings['mapping']['target'] ) ) { // phpcs:ignore
			$count = count( $settings['mapping']['domain'] );

			// Loop through mapped domains.
			for ( $i = 0; $i < $count; $i++ ) {
				if ( empty( $settings['mapping']['domain'][ $i ] ) ) {
					// Empty row, skip.
					continue;
				}

				// Just in case, remove protocol, trailing slash.
				$domain = str_replace(
					array(
						'https://',
						'http://',
					),
					'',
					untrailingslashit( strtolower( $settings['mapping']['domain'][ $i ] ) )
				);

				$domain = trim( $domain );

				if ( ! empty( $settings['mapping']['target'][ $i ] ) && false !== wp_http_validate_url( $settings['mapping']['target'][ $i ] ) ) {
					// Target is a valid URL.
					$options['mapping'][ $domain ] = $settings['mapping']['target'][ $i ];
				}
			}
		}

		if ( isset( $settings['canonical'] ) && (bool) $settings['canonical'] ) {
			$options['canonical'] = true;
		}

		if ( isset( $settings['redirect'] ) && (bool) $settings['redirect'] ) {
			$options['redirect'] = true;
		}

		$this->options = $options;

		// Updated settings.
		return $this->options;
	}

	/**
	 * Echoes the plugin options form.
	 */
	public function settings_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Landing Page', 'landing-page' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				// Print nonces and such.
				settings_fields( 'landing-page-settings-group' );
				?>
				<h2><?php esc_html_e( 'Mapping', 'landing-page' ); ?></h2>
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Domain', 'landing-page' ); ?></th>
						<th colspan="2"><?php esc_html_e( 'Target', 'landing-page' ); ?></th>
					</tr>
					<?php
					foreach ( $this->options['mapping'] as $domain => $target ) :
						?>
						<tr>
							<td><input type="text" class="widefat" name="landing_page_settings[mapping][domain][]" value="<?php echo esc_attr( $domain ); ?>" /></td>
							<td><input type="text" class="widefat" name="landing_page_settings[mapping][target][]" value="<?php echo esc_attr( $target ); ?>" /></td>
							<td><button type="button" class="button remove-row" aria-label="<?php esc_attr_e( 'Remove this domain', 'landing-page' ); ?>" title="<?php esc_attr_e( 'Remove this domain', 'landing-page' ); ?>"><?php esc_html_e( '&#8722;', 'landing-page' ); ?></button></td>
						</tr>
						<?php
					endforeach;
					?>
					<tr class="new">
						<td><input type="text" class="widefat" name="landing_page_settings[mapping][domain][]" placeholder="(www.)example.org" /></td>
						<td><input type="text" class="widefat" name="landing_page_settings[mapping][target][]" placeholder="<?php echo esc_url( trailingslashit( home_url( 'sample-page' ) ) ); ?>" /></td>
						<td><button type="button" class="button remove-row" aria-label="<?php esc_attr_e( 'Remove this domain', 'landing-page' ); ?>" title="<?php esc_attr_e( 'Remove this domain', 'landing-page' ); ?>"><?php esc_html_e( '&#8722;', 'landing-page' ); ?></button></td>
					</tr>
					<tr>
						<td colspan="3"><p class="description"><?php esc_html_e( 'Map add-on domains to any post or page URL.', 'landing-page' ); ?></p></td>
					</tr>
					<tr>
						<td colspan="3"><button type="button" class="button add-row" aria-label="<?php esc_attr_e( 'Map more domains', 'landing-page' ); ?>" title="<?php esc_attr_e( 'Map more domains', 'landing-page' ); ?>"><?php esc_html_e( 'Add Row', 'landing-page' ); ?></button></td>
					</tr>
				</table>
				<h2><?php esc_html_e( 'Optional', 'landing-page' ); ?></h2>
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Canonical URLs', 'landing-page' ); ?></th>
						<td><label><input type="checkbox" name="landing_page_settings[canonical]" value="1" <?php checked( ( isset( $this->options['canonical'] ) ? $this->options['canonical'] : false ) ); ?>/> <?php esc_html_e( 'Make canonical URLs reflect mapped domains.', 'landing-page' ); ?></label>
						<p class="description"><?php esc_html_e( 'Avoid a so-called &ldquo;duplicate content&rdquo; penalty by search engines.', 'landing-page' ); ?></p></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( '301 Redirects', 'landing-page' ); ?></th>
						<td><label><input type="checkbox" name="landing_page_settings[redirect]" value="1" <?php checked( ( isset( $this->options['redirect'] ) ? $this->options['redirect'] : false ) ); ?>/> <?php esc_html_e( 'Redirect posts and pages to their mapped domains.', 'landing-page' ); ?></label></td>
					</tr>
				</table>
				<p class="submit"><?php submit_button( __( 'Save Changes', 'landing-page' ), 'primary', 'submit', false ); ?></p>
			</form>
		</div>
		<?php
	}

	/**
	 * Enqueues WP Admin scripts and styles.
	 *
	 * @param string $hook_suffix Current WP-Admin page.
	 */
	public function enqueue_scripts( $hook_suffix ) {
		if ( 'settings_page_landing-page' !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style( 'landing-page', plugins_url( '/assets/landing-page.css', dirname( __FILE__ ) ), array(), '0.1' );
		wp_enqueue_script( 'landing-page-js', plugins_url( '/assets/landing-page.js', dirname( __FILE__ ) ), array( 'jquery', 'jquery-ui-core', 'jquery-ui-autocomplete' ), '0.1', true );

		wp_localize_script(
			'landing-page-js',
			'landing_page_obj',
			array(
				'ajax_url'   => admin_url( 'admin-ajax.php' ),
				'ajax_nonce' => wp_create_nonce( basename( __FILE__ ) ),
			)
		);
	}

	/**
	 * Autocomplete callback.
	 */
	public function autocomplete() {
		check_ajax_referer( basename( __FILE__ ), 'wp_nonce' );

		$items = array();

		if ( isset( $_POST['search'] ) ) {
			$query = new \WP_Query(
				array(
					'order_by'       => 'post_date',
					'order'          => 'ASC',
					'nopaging'       => true,
					'posts_per_page' => 15,
					'post_type'      => (array) apply_filters( 'landing_page_post_types', array( 'post', 'page' ) ),
					'post_status'    => 'publish',
					's'              => sanitize_text_field( wp_unslash( $_POST['search'] ) ),
					'__post_name'    => basename( sanitize_text_field( wp_unslash( $_POST['search'] ) ) ),
				)
			);

			if ( ! empty( $query->posts ) && is_array( $query->posts ) ) {
				$filters = Landing_Page::get_instance()->get_filters();

				remove_filter( 'page_link', array( $filters, 'replace_url' ), 11 );
				remove_filter( 'post_link', array( $filters, 'replace_url' ), 11 );
				remove_filter( 'post_type_link', array( $filters, 'replace_url' ), 11 );

				foreach ( $query->posts as $post ) {
					$items[] = get_permalink( $post );
				}

				add_filter( 'page_link', array( $filters, 'replace_url' ), 11 );
				add_filter( 'post_link', array( $filters, 'replace_url' ), 11 );
				add_filter( 'post_type_link', array( $filters, 'replace_url' ), 11 );
			}
		}

		wp_send_json_success( $items );
	}

	/**
	 * Modifies search queries' `WHERE` clause.
	 *
	 * @param string    $search   `WHERE` clause.
	 * @param \WP_Query $wp_query The current WP_Query object.
	 *
	 * @return string Modified `WHERE` clause.
	 */
	public function posts_where( $search, $wp_query ) {
		$post_name = (string) $wp_query->get( '__post_name' );

		if ( ! empty( $post_name ) ) {
			global $wpdb;

			$post_types = (array) apply_filters( 'landing_page_post_types', array( 'post', 'page' ) );

			// Allow only registered post types.
			$post_types = array_intersect( $post_types, get_post_types() );

			// Escape for use in SQL queries.
			$post_types = array_map(
				function( $value ) {
					return "'" . esc_sql( $value ) . "'";
				},
				$post_types
			);

			$post_types = implode( ',', $post_types );

			$search .= $wpdb->prepare(
				" OR ({$wpdb->posts}.post_status = 'publish'" .
				" AND {$wpdb->posts}.post_type IN ($post_types)" . // phpcs:ignore
				" AND {$wpdb->posts}.post_name LIKE %s)",
				'%' . $wpdb->esc_like( $post_name ) . '%'
			);
		}

		return $search;
	}

	/**
	 * Returns this plugin's settings.
	 *
	 * @return array This plugin's settings.
	 */
	public function get_options() {
		return $this->options;
	}
}
