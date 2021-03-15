<?php

class Test_Options_Handler extends \WP_Mock\Tools\TestCase {
	public function setUp() : void {
		\WP_Mock::setUp();
	}

	public function tearDown() : void {
		\WP_Mock::tearDown();
	}

	public function test_options_handler_register() {
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

		$options_handler = new \Landing_Page\Options_Handler();

		\WP_Mock::expectActionAdded( 'admin_menu', array( $options_handler, 'create_menu' ) );
		\WP_Mock::expectActionAdded( 'admin_enqueue_scripts', array( $options_handler, 'enqueue_scripts' ) );
		\WP_Mock::expectActionAdded( 'wp_ajax_search_posts', array( $options_handler, 'autocomplete' ) );

		\WP_Mock::expectFilterAdded( 'posts_where', array( $options_handler, 'posts_where' ), 10, 2 );

		$options_handler->register();

		$this->assertHooksAdded();
	}
}
