<?php

/**
 * CMB text_time field type
 *
 * @author    CMB2 team
 * @since     2.2.2
 *
 * @link      https://cmb2.io
 * @license   GPL-2.0+
 * @package   CMB2
 * @category  WordPress_Plugin
 */
class CMB2_Type_Text_Time extends CMB2_Type_Text_Date {

	/**
	 * Return a formatted timestamp for a field
	 *
	 * @since  2.0.0
	 *
	 * @param string     $format     Either date_format or time_format.
	 * @param string|int $meta_value Optional meta value to check.
	 *
	 * @return string             Formatted date
	 */
	public function get_timestamp_format( $format = 'date_format', $meta_value = 0 ) {
		$meta_value = $meta_value ? $meta_value : $this->field->escaped_value();
		if ( empty( $meta_value ) ) {
			$meta_value = $this->field->get_default();
		}

		if ( ! CMB2_Utils::is_valid_time_stamp( $meta_value ) ) {
			$meta_value = CMB2_Utils::make_valid_time_stamp( $meta_value );
		}
		if ( empty( $meta_value ) ) {
			return '';
		}

		$dt = new DateTime();
		$dt->setTimestamp( $meta_value );
		return $dt->format( stripslashes( $this->field->args( $format ) ) );
	}


	public function render( $args = [] ) {
		$this->args = $this->parse_picker_options( 'time', wp_parse_args( $this->args, [
			'class'           => 'cmb2-timepicker text-time',
			'value'           => $this->get_timestamp_format( 'time_format' ),
			'js_dependencies' => [ 'jquery-ui-core', 'jquery-ui-datepicker', 'jquery-ui-datetimepicker' ],
		] ) );

		return parent::render();
	}

}
