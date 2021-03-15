<?php

class Test_Filters extends \WP_Mock\Tools\TestCase {
	public function setUp() : void {
		\WP_Mock::setUp();
	}

	public function tearDown() : void {
		\WP_Mock::tearDown();
	}

	public function test_filters_register() {
		\WP_Mock::userFunction( 'is_admin', array(
			'times'  => 1,
			'return' => true,
		) );

		$filters = new \Landing_Page\Filters();

		\WP_Mock::expectFilterAdded( 'page_link', array( $filters, 'replace_url' ), 11 );
		\WP_Mock::expectFilterAdded( 'post_link', array( $filters, 'replace_url' ), 11 );
		\WP_Mock::expectFilterAdded( 'post_type_link', array( $filters, 'replace_url' ), 11 );
		\WP_Mock::expectFilterAdded( 'preview_post_link', array( $filters, 'unreplace_url' ) );
		\WP_Mock::expectFilterAdded( 'get_canonical_url', array( $filters, 'replace_url' ), 11 );

		\WP_Mock::expectActionAdded( 'jetpack_sitemap_skip_post', array( $filters, 'jetpack_exclude_url' ), 10, 2 );

		\WP_Mock::expectFilterAdded( 'wpseo_exclude_from_sitemap_by_post_ids', array( $filters, 'wpseo_exclude_urls' ) );

		$filters->register();

		$this->assertHooksAdded();
	}
}
