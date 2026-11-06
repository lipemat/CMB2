<?php
/**
 * CMB2_Field tests
 *
 * @package   Tests_CMB2
 * @author    CMB2 team
 * @license   GPL-2.0+
 * @link      https://cmb2.io
 */

require_once( 'cmb-tests-base.php' );

/**
 * Test the oEmbed functionality
 */
class Test_CMB2_Options extends CMB2TestCase {

	// Options-specific test properties
	protected $option_metabox_array;
	protected $options_cmb;
	protected $opt_set;

	/**
	 * Set up the test fixture
	 */
	public function set_up() {
		parent::set_up();

		$this->option_metabox_array = array(
			'id'            => 'options_page',
			'title'         => 'Theme Options Metabox',
			'show_on'    => array(
				'options-page' => array( 'theme_options' ),
			),
			'fields'        => array(
				'bg_color' => array(
					'name'    => 'Site Background Color',
					'desc'    => 'field description (optional)',
					'id'      => 'bg_color',
					'type'    => 'colorpicker',
					'default' => '#ffffff',
				),
			),
		);

		$this->options_cmb = new CMB2( $this->option_metabox_array );

		$this->opt_set = array(
			'bg_color' => '#ffffff',
			'my_name' => 'Justin',
		);
		add_option( $this->options_cmb->cmb_id, $this->opt_set );
	}

	public function test_cmb2_options_function() {
		$opts = cmb2_options( $this->options_cmb->cmb_id );
		$this->assertSame( $opts->get_options(), $this->opt_set );
	}

	public function test_cmb2_get_option() {
		$get = get_option( $this->options_cmb->cmb_id );
		$val = cmb2_get_option( $this->options_cmb->cmb_id, 'my_name' );

		$this->assertSame( $this->opt_set['my_name'], $get['my_name'] );
		$this->assertSame( $val, $get['my_name'] );
		$this->assertSame( $val, $this->opt_set['my_name'] );
	}

	public function test_cmb2_get_option_bad_value() {
		$opts = cmb2_options( $this->options_cmb->cmb_id );

		$opts->set( '1' );

		$get = get_option( $this->options_cmb->cmb_id );
		$val = $opts->get_options();

		$this->assertSame( '1', $get );
		$this->assertSame( array( '1' ), $val );

		$opts->delete_option();
		$get = get_option( $this->options_cmb->cmb_id );
		$val = $opts->get_options();

		$this->assertSame( false, $get );
		$this->assertSame( array(), $val );

		// Reset the option for future tests.
		$opts->set( $this->opt_set );
	}

	public function test_cmb2_remove_option_bad_value() {
		$opts = cmb2_options( $this->options_cmb->cmb_id );
		$opts->delete_option();

		$val = $opts->remove( 'my_name' );

		$this->assertSame( array(), $val );

		$opts->set( $this->opt_set );
	}

	public function test_cmb2_update_option() {
		$new_value = 'James';

		cmb2_update_option( $this->options_cmb->cmb_id, 'my_name', $new_value );

		$get = get_option( $this->options_cmb->cmb_id );
		$val = cmb2_get_option( $this->options_cmb->cmb_id, 'my_name' );

		$this->assertSame( $new_value, $get['my_name'] );
		$this->assertSame( $val, $get['my_name'] );
		$this->assertSame( $val, $new_value );
	}

	public function test_cmb2_with_empty_options() {
		$opts = cmb2_options( 'cmb_empty_option' );
		$this->assertIsArray( $opts->get_options() );
		$this->assertSame( array(), $opts->get_options() );
	}

	public function test_cmb2_get_option_with_empty_options() {
		$opts = cmb2_options( 'cmb_empty_option' );
		$this->assertFalse( $opts->get( 'nothing' ) );
	}

	public function test_cmb2_update_option_with_empty_options() {
		$new_value = 'Van Anh';

		cmb2_update_option( 'cmb_empty_option', 'my_name', $new_value );

		$get = get_option( 'cmb_empty_option' );
		$val = cmb2_get_option( 'cmb_empty_option', 'my_name' );

		$this->assertSame( $new_value, $get['my_name'] );
		$this->assertSame( $val, $get['my_name'] );
		$this->assertSame( $val, $new_value );
	}

	public function test_add_option_from_outside_after_an_empty_read() {
		$opts = cmb2_options( 'cmb_outside_add_option' );
		$this->assertSame( array(), $opts->get_options() );

		add_option( 'cmb_outside_add_option', array( 'my_name' => 'Van Anh' ) );

		$this->assertSame( array( 'my_name' => 'Van Anh' ), $opts->get_options() );
	}

	public function test_update_option_from_outside_after_an_empty_read() {
		$opts = cmb2_options( 'cmb_outside_update_option' );
		$this->assertSame( array(), $opts->get_options() );

		update_option( 'cmb_outside_update_option', array( 'my_name' => 'Justin' ) );

		$this->assertSame( array( 'my_name' => 'Justin' ), $opts->get_options() );
	}

	public function test_update_option_from_outside_after_a_populated_read() {
		$this->assertSame( 'Justin', cmb2_get_option( $this->options_cmb->cmb_id, 'my_name' ) );

		update_option( $this->options_cmb->cmb_id, array( 'my_name' => 'James' ) );

		$this->assertSame( 'James', cmb2_get_option( $this->options_cmb->cmb_id, 'my_name' ) );
	}

	public function test_delete_option_from_outside_after_a_populated_read() {
		$opts = cmb2_options( $this->options_cmb->cmb_id );
		$this->assertSame( $this->opt_set, $opts->get_options() );

		delete_option( $this->options_cmb->cmb_id );

		$this->assertSame( array(), $opts->get_options() );
	}

	public function test_write_to_another_option_leaves_the_values_alone() {
		$opts = cmb2_options( $this->options_cmb->cmb_id );
		$this->assertSame( $this->opt_set, $opts->get_options() );

		update_option( 'cmb_unrelated_option', 'anything' );

		$this->assertSame( $this->opt_set, $opts->get_options() );
	}

	/**
	 * A `cmb2_override_option_save_*` handler does the storing itself, so the
	 * write it makes must not be mistaken for an outside change.
	 */
	public function test_set_wins_over_a_write_made_while_saving() {
		$opts = cmb2_options( 'cmb_override_save_option' );

		add_filter( 'cmb2_override_option_save_cmb_override_save_option', function() {
			update_option( 'cmb_override_save_option', array( 'my_name' => 'Stale' ) );
			return true;
		} );

		$opts->update( 'my_name', 'Justin' );
		$opts->set();

		$this->assertSame( array( 'my_name' => 'Justin' ), $opts->get_options() );
	}

	/**
	 * A `pre_option_*` filter changes the value without touching the row,
	 * so no option write action fires.
	 */
	public function test_pre_option_filter_added_after_a_read() {
		$opts = cmb2_options( $this->options_cmb->cmb_id );
		$this->assertSame( $this->opt_set, $opts->get_options() );

		add_filter( "pre_option_{$this->options_cmb->cmb_id}", function() {
			return array( 'my_name' => 'Van Anh' );
		} );

		$this->assertSame( array( 'my_name' => 'Van Anh' ), $opts->get_options() );
	}

	public function test_staged_values_are_held_until_set() {
		$opts = cmb2_options( 'cmb_staged_option' );

		$opts->update( 'my_name', 'Justin' );

		$this->assertSame( array( 'my_name' => 'Justin' ), $opts->get_options() );
		$this->assertFalse( get_option( 'cmb_staged_option' ) );

		$opts->set();

		$this->assertSame( array( 'my_name' => 'Justin' ), get_option( 'cmb_staged_option' ) );
	}

	public function test_an_outside_write_does_not_discard_staged_values() {
		$opts = cmb2_options( $this->options_cmb->cmb_id );
		$opts->update( 'my_name', 'Van Anh' );

		update_option( $this->options_cmb->cmb_id, array( 'my_name' => 'Stale' ) );

		$this->assertSame( 'Van Anh', $opts->get( 'my_name' ) );
	}

	/**
	 * A `cmb2_override_option_get_*` handler is the only place the value
	 * may come from, so the database must not be read behind it.
	 */
	public function test_override_get_handler_answers_every_read() {
		add_filter( 'cmb2_override_option_get_cmb_override_get_option', function() {
			return array( 'my_name' => 'Justin' );
		} );

		$opts = cmb2_options( 'cmb_override_get_option' );
		$this->assertSame( array( 'my_name' => 'Justin' ), $opts->get_options() );

		update_option( 'cmb_override_get_option', array( 'my_name' => 'Van Anh' ) );

		$this->assertSame( array( 'my_name' => 'Justin' ), $opts->get_options() );
	}

	public function test_options_are_read_per_site() {
		$this->skipWithoutMultisite();

		update_option( 'cmb_per_site_option', array( 'my_name' => 'Justin' ) );
		$opts = cmb2_options( 'cmb_per_site_option' );
		$this->assertSame( array( 'my_name' => 'Justin' ), $opts->get_options() );

		switch_to_blog( self::factory()->blog->create() );
		update_option( 'cmb_per_site_option', array( 'my_name' => 'Van Anh' ) );
		$this->assertSame( array( 'my_name' => 'Van Anh' ), $opts->get_options() );
		restore_current_blog();

		$this->assertSame( array( 'my_name' => 'Justin' ), $opts->get_options() );
	}

}
