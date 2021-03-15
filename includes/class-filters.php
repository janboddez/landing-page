<?php
/**
 * Groups callbacks related to, e.g., canonical URLs and XML sitemaps.
 *
 * @package Landing_Page
 */

namespace Landing_Page;

/**
 * Filters class.
 */
class Filters {
	/**
	 * Plugin options.
	 *
	 * @var array $options Plugin options.
	 */
	private $options = array();

	/**
	 * Constructor.
	 *
	 * @param array $options Plugin options.
	 */
	public function __construct( $options = array() ) {
		$this->options = $options;
	}

	/**
	 * Sets up hook callbacks.
	 */
	public function register() {
		if ( is_admin() ) {
			// Filter permalinks.
			add_filter( 'page_link', array( $this, 'replace_url' ), 11 );
			add_filter( 'post_link', array( $this, 'replace_url' ), 11 );
			add_filter( 'post_type_link', array( $this, 'replace_url' ), 11 );

			// Exclude the preview buttons' URLs.
			add_filter( 'preview_post_link', array( $this, 'unreplace_url' ) );
		}

		// Update canonical URLs.
		add_filter( 'get_canonical_url', array( $this, 'replace_url' ), 11 );

		// If we're going to have canonical URLs that point to another domain,
		// these should probably not be included in any sitemap on the main
		// site. On a side note, our main code should already disable "mapped"
		// sitemaps, i.e., off the "main" site.
		add_action( 'jetpack_sitemap_skip_post', array( $this, 'jetpack_exclude_url' ), 10, 2 );
		add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', array( $this, 'wpseo_exclude_urls' ) );

		// Provide Page Themes with the correct WordPress URL.
		add_filter( 'page_themes_current_url', array( $this, 'unreplace_url' ) );
	}

	/**
	 * Replaces WordPress URLs with the domain mapped to them.
	 *
	 * @param string $original_url Original WordPress URL.
	 *
	 * @return string Filtered URL.
	 */
	public function replace_url( $original_url ) {
		if ( empty( $this->options['canonical'] ) ) {
			return $original_url;
		}

		$mapping = (array) apply_filters( 'landing_page_mapping', $this->options['mapping'] );

		if ( empty( $mapping ) || ! is_array( $mapping ) ) {
			// Nothing to do.
			return $original_url;
		}

		// We're going to compare paths only.
		$mapping = array_combine(
			array_keys( $mapping ),
			array_map(
				function( $value ) {
					return wp_parse_url( $value, PHP_URL_PATH );
				},
				$mapping
			)
		);

		$path = wp_parse_url( $original_url, PHP_URL_PATH );

		if ( ! in_array( $path, $mapping, true ) ) {
			// No match found.
			return $original_url;
		}

		// To do: replace `trailingslashit` with something smarter.
		$url = trailingslashit( wp_parse_url( home_url(), PHP_URL_SCHEME ) . '://' . array_search( $path, $mapping, true ) );

		/**
		 * Allows modifying replaced URLs.
		 *
		 * @param array Filtered URL.
		 * @param array Original WordPress URL.
		 */
		return apply_filters(
			'landing_page_replaced_url',
			$url,
			$original_url
		);
	}

	/**
	 * If applicable, replaces a mapped URL with the underlying WordPress URL.
	 *
	 * @param string $url (Possibly) mapped URL.
	 *
	 * @return string Filtered URL.
	 */
	public function unreplace_url( $url ) {
		if ( empty( $this->options['canonical'] ) ) {
			return $url;
		}

		$mapping = (array) apply_filters( 'landing_page_mapping', $this->options['mapping'] );

		if ( empty( $mapping ) || ! is_array( $mapping ) ) {
			// Nothing to do.
			return $url;
		}

		// Check for a corresponding WordPress URL.
		$host = wp_parse_url( $url, PHP_URL_HOST );

		if ( ! array_key_exists( $host, $mapping ) ) {
			// No match found.
			return $url;
		}

		/**
		 * Allows modifying unreplaced URLs.
		 *
		 * @param array Original WordPress URL.
		 * @param array Mapped URL.
		 */
		return apply_filters(
			'landing_page_unreplaced_url',
			$mapping[ $host ],
			$url
		);
	}

	/**
	 * Excludes mapped URLs from Jetpack-generated sitemaps.
	 *
	 * @link https://www.sitemaps.org/protocol.html#location
	 *
	 * @param bool   $skip If post should be excluded.
	 * @param object $post Database row object.
	 *
	 * @return bool If post should be excluded.
	 */
	public function jetpack_exclude_url( $skip, $post ) {
		if ( empty( $this->options['canonical'] ) ) {
			// If canonical URLs remain unfiltered, so should sitemap URLs.
			return $skip;
		}

		$mapping = (array) apply_filters( 'landing_page_mapping', $this->options['mapping'] );

		if ( empty( $mapping ) || ! is_array( $mapping ) ) {
			// Nothing to do.
			return $skip;
		}

		// We're going to compare paths only.
		$mapping = array_combine(
			array_keys( $mapping ),
			array_map(
				function( $value ) {
					return wp_parse_url( $value, PHP_URL_PATH );
				},
				$mapping
			)
		);

		$path = wp_parse_url( get_permalink( (int) $post->ID ), PHP_URL_PATH );

		if ( in_array( $path, $mapping, true ) ) {
			// Match found.
			return true;
		}

		return $skip;
	}

	/**
	 * Attempts to exclude mapped URLs from Yoast-generated sitemaps.
	 *
	 * @param array $excluded_post_ids Post IDs to exclude.
	 *
	 * @return array Updated list of post IDs to exclude.
	 */
	public function wpseo_exclude_urls( $excluded_post_ids ) {
		if ( empty( $this->options['canonical'] ) ) {
			// If canonical URLs remain unfiltered, so should sitemap URLs.
			return $excluded_post_ids;
		}

		$mapping = (array) apply_filters( 'landing_page_mapping', $this->options['mapping'] );

		if ( empty( $mapping ) || ! is_array( $mapping ) ) {
			// Nothing to do.
			return;
		}

		foreach ( $mapping as $target ) {
			$post_id = url_to_postid( $target );

			if ( 0 !== $post_id ) {
				$excluded_post_ids[] = $post_id;
			}
		}

		return $excluded_post_ids;
	}
}
