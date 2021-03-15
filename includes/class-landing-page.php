<?php
/**
 * Main plugin class.
 *
 * @package Landing_Page
 */

namespace Landing_Page;

/**
 * Main plugin class.
 */
class Landing_Page {
	/**
	 * The single instance of this class.
	 *
	 * @var Landing_Page $instance Main plugin class instance.
	 */
	private static $instance;

	/**
	 * `Filters` instance.
	 *
	 * @var Filters $filters `Filters` instance.
	 */
	private $filters;

	/**
	 * `Options_Handler` instance.
	 *
	 * @var Options_Handler $options_handler `Options_Handler` instance.
	 */
	private $options_handler;

	/**
	 * The originally requested domain.
	 *
	 * @var string $domain Requested domain.
	 */
	private $domain = '';

	/**
	 * The originally requested path.
	 *
	 * @var string $request_uri Requested path.
	 */
	private $request_uri = '';

	/**
	 * The matched path.
	 *
	 * @var string $route Matched, and served, path.
	 */
	private $route = '';

	/**
	 * Plugin options.
	 *
	 * @var array $options Plugin options.
	 */
	private $options = array();

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Landing_Page Single class instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * (Private) constructor.
	 */
	private function __construct() {
		$this->options_handler = new Options_Handler();
		$this->options_handler->register();

		$this->options = $this->options_handler->get_options();
		$this->filters = new Filters( $this->options );
		$this->filters->register();
	}

	/**
	 * Registers hook callbacks.
	 */
	public function register() {
		// Allow i18n.
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'parse_request' ) );
		add_action( 'wp', array( $this, 'redirect' ) );
	}

	/**
	 * Forces WordPress to map certain add-on domains to specific posts or
	 * pages.
	 */
	public function parse_request() {
		/**
		 * Allows filtering the entire domain mapping table.
		 *
		 * @param array Domain mapping table, as saved in the database.
		 */
		$mapping = (array) apply_filters( 'landing_page_mapping', $this->options['mapping'] );

		if ( empty( $mapping ) || ! is_array( $mapping ) ) {
			// Nothing to do.
			return;
		}

		$host = apply_filters(
			'landing_page_host',
			( ! empty( $_SERVER['HTTP_HOST'] ) ? wp_unslash( $_SERVER['HTTP_HOST'] ) : '' ) // phpcs:ignore
		);

		// phpcs:ignore
		// $query    = wp_parse_url( $this->request_uri, PHP_URL_QUERY );
		// $fragment = wp_parse_url( $this->request_uri, PHP_URL_FRAGMENT );

		foreach ( $mapping as $domain => $route ) {
			// Strip away the protocol and host.
			$route = str_replace( home_url(), '', $route );

			if ( 0 === stripos( $host, $domain ) ) {
				// Match found. Store originally requested path.
				$this->request_uri = wp_unslash( $_SERVER['REQUEST_URI'] ); // phpcs:ignore

				$this->domain = $domain;

				// phpcs:ignore
				$this->route = $route; // . ( ! empty( $query ) ? '?' . $query : '' ) . ( ! empty( $fragment ) ? '#' . $fragment : '' );

				// Trick WordPress into serving the right page.
				$_SERVER['REQUEST_URI'] = $this->route;

				// Break out of `foreach` loop.
				break;
			}
		}
	}

	/**
	 * If applicable, initiates a 301 redirect to the mapped domain.
	 */
	public function redirect() {
		if ( empty( $this->options['redirect'] ) ) {
			// Nothing to do.
			return;
		}

		if ( is_customize_preview() ) {
			// Redirects don't work inside the Customizer.
			return;
		}

		if ( '' !== $this->domain ) {
			// Domain mapping active. Don't do anything, to prevent infinite
			// loops!
			return;
		}

		$mapping = (array) apply_filters( 'landing_page_mapping', $this->options['mapping'] );

		if ( empty( $mapping ) || ! is_array( $mapping ) ) {
			// Nothing to do.
			return;
		}

		// We're going to compare paths only, but trim slashes, too.
		$mapping = array_combine(
			array_keys( $mapping ),
			array_map(
				function( $value ) {
					return trim( wp_parse_url( $value, PHP_URL_PATH ), '/' );
				},
				$mapping
			)
		);

		global $wp;

		if ( in_array( $wp->request, $mapping, true ) ) {
			wp_redirect( // phpcs:ignore
				trailingslashit( wp_parse_url( home_url(), PHP_URL_SCHEME ) . '://' . array_search( $wp->request, $mapping, true ) ),
				301
			);
			exit;
		}
	}

	/**
	 * Allows i18n of this plugin.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'landing-page',
			false,
			basename( dirname( dirname( __FILE__ ) ) ) . '/languages'
		);
	}

	/**
	 * Returns `Filters` instance.
	 *
	 * @return Filters This plugin's `Filters` instance.
	 */
	public function get_filters() {
		return $this->filters;
	}

	/**
	 * Returns `Options_Handler` instance.
	 *
	 * @return Options_Handler This plugin's `Options_Handler` instance.
	 */
	public function get_options_handler() {
		return $this->options_handler;
	}

	/**
	 * Returns the originally requested domain.
	 *
	 * @return string The originally requested domain.
	 */
	public function get_domain() {
		return $this->domain;
	}

	/**
	 * Returns originally requested path.
	 *
	 * @return string The originally requested path.
	 */
	public function get_request_uri() {
		return $this->request_uri;
	}

	/**
	 * Returns the target path.
	 *
	 * @return string The target path.
	 */
	public function get_route() {
		return $this->route;
	}
}
