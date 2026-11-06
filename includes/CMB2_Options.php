<?php
/**
 * CMB2 Utility classes for handling multi-dimensional array data for options
 *
 * @author    CMB2 team
 * @link      https://cmb2.io
 * @license   GPL-2.0+
 * @package   CMB2
 * @category  WordPress_Plugin
 */

/**
 * Retrieves an instance of CMB2_Option based on the option key
 *
 * @author    CMB2 team
 * @package   CMB2
 */
class CMB2_Options {
	/**
	 * Array of all CMB2_Option instances
	 *
	 * @since 1.0.0
	 * @var   CMB2_Option[]
	 */
	protected static $option_sets = [];


	public static function get( $option_key ) {
		if ( empty( self::$option_sets ) || empty( self::$option_sets[ $option_key ] ) ) {
			self::$option_sets[ $option_key ] = new CMB2_Option( $option_key );
		}

		return self::$option_sets[ $option_key ];
	}
}

/**
 * Handles getting/setting of values to an option array
 * for a specific option key
 *
 * @author    CMB2 team
 * @package   CMB2
 */
class CMB2_Option {

	/**
	 * Options array
	 *
	 * @var array
	 */
	protected $options = [];

	/**
	 * Current option key
	 *
	 * @var string
	 */
	protected $key = '';

	/**
	 * Do the options held here beat what is stored?
	 *
	 * True while changes are staged for the next `set`, or when an override
	 * filter answered the read in place of the database. Otherwise every read
	 * goes back to `get_option`, so writes made outside of CMB2 are picked up.
	 *
	 * @since 2.12.0.4
	 *
	 * @var bool
	 */
	protected $skip_database_reads = false;

	/**
	 * Id of the site the held options belong to.
	 *
	 * Options are per site, so a `switch_to_blog` gives up
	 * anything held here.
	 *
	 * @since 2.12.0.4
	 *
	 * @var int
	 */
	protected $site_id = 0;


	/**
	 * Initiate option object
	 *
	 * @since 2.0.0
	 *
	 * @param string $option_key Option key where data will be saved.
	 *                           Leave empty for temporary data store.
	 */
	public function __construct( $option_key = '' ) {
		$this->key = ! empty( $option_key ) ? $option_key : '';
	}


	/**
	 * Delete the option from the db
	 *
	 * @since  2.0.0
	 * @return mixed Delete success or failure
	 */
	public function delete_option() {
		$deleted = $this->key ? delete_option( $this->key ) : true;
		if ( $deleted ) {
			$this->options = [];
			$this->skip_database_reads = false;
		}
		return $this->options;
	}


	/**
	 * Removes an option from an option array
	 *
	 * @since  1.0.1
	 *
	 * @param string $field_id Option array field key.
	 * @param bool   $resave   Whether or not to resave.
	 *
	 * @return array             Modified options
	 */
	public function remove( $field_id, $resave = false ) {
		$this->get_options();

		if ( isset( $this->options[ $field_id ] ) ) {
			unset( $this->options[ $field_id ] );
			$this->skip_database_reads();
		}

		if ( $resave ) {
			$this->set();
		}

		return $this->options;
	}


	/**
	 * Retrieves an option from an option array
	 *
	 * @since  1.0.1
	 *
	 * @param string $field_id Option array field key.
	 * @param mixed  $default  Fallback value for the option.
	 *
	 * @return mixed            Requested field or default
	 */
	public function get( $field_id, $default = false ) {
		$opts = $this->get_options();

		if ( 'all' === $field_id ) {
			return $opts;
		}

		if ( array_key_exists( $field_id, $opts ) ) {
			$default = false !== $opts[ $field_id ] ? $opts[ $field_id ] : $default;
		}

		if ( empty( $opts[ $field_id ] ) ) {
			return apply_filters( "cmb2_default_option_{$this->key}_{$field_id}", $default, $this );
		}

		return $opts[ $field_id ];
	}


	/**
	 * Updates Option data
	 *
	 * @since  1.0.1
	 *
	 * @param string $field_id Option array field key.
	 * @param mixed  $value    Value to update data with.
	 * @param bool   $resave   Whether to re-save the data.
	 * @param bool   $single   Whether data should not be an array.
	 *
	 * @return boolean Return status of update.
	 */
	public function update( $field_id, $value = '', $resave = false, $single = true ) {
		$this->get_options();

		if ( true !== $field_id ) {
			if ( ! $single ) {
				// If multiple, add to array.
				$this->options[ $field_id ][] = $value;
			} else {
				$this->options[ $field_id ] = $value;
			}
			$this->skip_database_reads();
		}

		if ( $resave || true === $field_id ) {
			return $this->set();
		}

		return true;
	}


	/**
	 * Saves the option array
	 * Needs to be run after finished using remove/update_option
	 *
	 * @since  1.0.1
	 * @uses   apply_filters() Calls 'cmb2_override_option_save_{$this->key}' hook
	 * to allow overwriting the option value to be stored.
	 *
	 * @param array $options Optional options to override.
	 *
	 * @return bool           Success/Failure
	 */
	public function set( $options = [] ): bool {
		if ( ! empty( $options ) || empty( $this->key ) ) {
			$this->options = $options;
		}

		$this->options = wp_unslash( \apply_filters( "cmb2_options_set_{$this->key}", $this->options, $this ) ); // get rid of those evil magic quotes.

		if ( empty( $this->key ) ) {
			return false;
		}

		$test_save = apply_filters( "cmb2_override_option_save_{$this->key}", 'cmb2_no_override_option_save', $this->options, $this );

		if ( 'cmb2_no_override_option_save' !== $test_save ) {
			// Stored somewhere we cannot read back, so what we hold is all we know.
			$this->skip_database_reads();
			return true;
		}

		/**
		 * Whether to auto-load the option when WordPress starts up.
		 *
		 * The dynamic portion of the hook name, $this->key, refers to the option key.
		 *
		 * @since 2.4.0
		 *
		 * @param bool        $autoload   Whether to load the option when WordPress starts up.
		 * @param CMB2_Option $cmb_option This object.
		 */
		$autoload = apply_filters( "cmb2_should_autoload_{$this->key}", true, $this );

		if ( update_option(
			$this->key,
			$this->options,
			! ( ! $autoload || 'no' === $autoload )
		) ) {
			$this->options = wp_unslash( get_option( $this->key ) );
		}
		// What is stored is the source of truth again.
		$this->skip_database_reads = false;

		return true;
	}


	/**
	 * Retrieve option value based on name of option.
	 *
	 * @since  1.0.1
	 * @uses   apply_filters() Calls 'cmb2_override_option_get_{$this->key}' hook to allow
	 * overwriting the option value to be retrieved.
	 *
	 * @param mixed $default Optional. Default value to return if the option does not exist.
	 *
	 * @return mixed          Value set for the option.
	 */
	public function get_options( $default = null ) {
		if ( ! empty( $this->key ) && ! $this->are_database_reads_skipped() ) {
			$test_get = apply_filters( "cmb2_override_option_get_{$this->key}", 'cmb2_no_override_option_get', $default, $this );

			if ( 'cmb2_no_override_option_get' !== $test_get ) {
				// An override is the only place this value may be read from.
				$this->options = $test_get;
				$this->skip_database_reads();
			} else {
				$this->options = get_option( $this->key, $default );
				$this->skip_database_reads = false;
				$this->site_id = get_current_blog_id();
			}
		}

		$this->options = (array) \apply_filters( "cmb2_options_get_{$this->key}", $this->options, $this );

		return $this->options;
	}


	/**
	 * Hold onto the current options in place of what is stored.
	 *
	 * @since 2.12.0.4
	 *
	 * @return void
	 */
	protected function skip_database_reads(): void {
		$this->skip_database_reads = true;
		$this->site_id = get_current_blog_id();
	}


	/**
	 * Do the options held here still beat the stored ones?
	 *
	 * Options belong to a single site, so switching sites gives up
	 * anything held no matter where it came from.
	 *
	 * @since 2.12.0.4
	 *
	 * @return bool
	 */
	protected function are_database_reads_skipped(): bool {
		return $this->skip_database_reads && get_current_blog_id() === $this->site_id;
	}


	/**
	 * Magic getter for our object.
	 *
	 * @since 2.6.0
	 *
	 * @param string $field Requested property.
	 *
	 * @throws Exception Throws an exception if the field is invalid.
	 * @return mixed
	 */
	public function __get( $field ) {
		switch ( $field ) {
			case 'options':
			case 'key':
				return $this->{$field};
			default:
				// translators: 1: class name, 2: property name.
				throw new Exception( sprintf( esc_html__( 'Invalid %1$s property: %2$s', 'cmb2' ), __CLASS__, $field ) );
		}
	}
}
