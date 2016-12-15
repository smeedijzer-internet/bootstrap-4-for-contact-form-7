<?php
/**
 * File module
 *
 * @package CF7BS
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 * @since 1.0.0
 */

add_action( 'wpcf7_init', 'cf7bs_add_shortcode_file', 11 );

function cf7bs_add_shortcode_file() {
	$add_func    = function_exists( 'wpcf7_add_form_tag' )    ? 'wpcf7_add_form_tag'    : 'wpcf7_add_shortcode';
	$remove_func = function_exists( 'wpcf7_remove_form_tag' ) ? 'wpcf7_remove_form_tag' : 'wpcf7_remove_shortcode';

	$tags = array(
		'file',
		'file*',
	);
	foreach ( $tags as $tag ) {
		call_user_func( $remove_func, $tag );
	}

	call_user_func( $add_func, $tags, 'cf7bs_file_shortcode_handler', true );
}

function cf7bs_file_shortcode_handler( $tag ) {
	$classname = class_exists( 'WPCF7_FormTag' ) ? 'WPCF7_FormTag' : 'WPCF7_Shortcode';

	$tag = new $classname( $tag );

	if ( empty( $tag->name ) ) {
		return '';
	}

	$mode = $status = 'default';

	$validation_error = wpcf7_get_validation_error( $tag->name );

	$class = wpcf7_form_controls_class( $tag->type );
	if ( $validation_error ) {
		$class .= ' wpcf7-not-valid';
		$status = 'error';
	}

	// size is not used since Bootstrap input fields always scale 100%
	//$atts['size'] = $tag->get_size_option( '40' );

	if ( $tag->is_required() ) {
		$mode = 'required';
	}

	$value = (string) reset( $tag->values );
	$placeholder = '';
	if ( $tag->has_option( 'placeholder' ) || $tag->has_option( 'watermark' ) ) {
		$placeholder = $value;
		$value = '';
	} elseif ( empty( $value ) ) {
		$value = $tag->get_default_option();
	}
	if ( wpcf7_is_posted() && isset( $_POST[$tag->name] ) ) {
		$value = stripslashes_deep( $_POST[$tag->name] );
	}

	$field = new CF7BS_Form_Field( cf7bs_apply_field_args_filter( array(
		'name'				=> $tag->name,
		'id'				=> $tag->get_option( 'id', 'id', true ),
		'class'				=> $tag->get_class_option( $class ),
		'type'				=> 'file',
		'value'				=> '1',
		'label'				=> $tag->content,
		'help_text'			=> $validation_error,
		'size'				=> cf7bs_get_form_property( 'size', 0, $tag ),
		'grid_columns'		=> cf7bs_get_form_property( 'grid_columns', 0, $tag ),
		'form_layout'		=> cf7bs_get_form_property( 'layout', 0, $tag ),
		'form_label_width'	=> cf7bs_get_form_property( 'label_width', 0, $tag ),
		'form_breakpoint'	=> cf7bs_get_form_property( 'breakpoint', 0, $tag ),
		'mode'				=> $mode,
		'status'			=> $status,
		'tabindex'			=> $tag->get_option( 'tabindex', 'int', true ),
		'wrapper_class'		=> $tag->name,
		'label_class'       => $tag->get_option( 'label_class', 'class', true ),
	), $tag->basetype, $tag->name ) );

	$html = $field->display( false );

	return $html;
}
