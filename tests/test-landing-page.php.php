<?php

class Test_Landing_Page extends \WP_Mock\Tools\TestCase {
	public function setUp() : void {
		\WP_Mock::setUp();
	}

	public function tearDown() : void {
		\WP_Mock::tearDown();
	}

	public function test_landing_page_register() {
		$options = array(
			'mapping'   => array(),
			'canonical' => true,
			'redirect'  => false,
		);

		\WP_Mock::userFunction( 'get_option', array(
			'times'  => 1,
			'args'   => array(
				'landing_page_settings',
				$options,
			),
			'return' => $options,
		) );

		$landing_page = \Landing_Page\Landing_Page::get_instance();

		\WP_Mock::expectActionAdded( 'plugins_loaded', array( $landing_page, 'load_textdomain' ) );
		\WP_Mock::expectActionAdded( 'init', array( $landing_page, 'parse_request' ) );
		\WP_Mock::expectActionAdded( 'wp', array( $landing_page, 'redirect' ) );

		$landing_page->register();

		$this->assertHooksAdded();
	}
}
