<?php
/**
 * Checkbox module
 *
 * @package CF7BS
 * @author Felix Arntz <felix-arntz@leaves-and-love.net>
 * @since 1.0.0
 */

add_action( 'wpcf7_init', 'cf7bs_add_shortcode_checkbox', 11 );

function cf7bs_add_shortcode_checkbox() {
	$add_func    = function_exists( 'wpcf7_add_form_tag' )    ? 'wpcf7_add_form_tag'    : 'wpcf7_add_shortcode';
	$remove_func = function_exists( 'wpcf7_remove_form_tag' ) ? 'wpcf7_remove_form_tag' : 'wpcf7_remove_shortcode';

	$tags = array(
		'checkbox',
		'checkbox*',
		'radio',
	);
	foreach ( $tags as $tag ) {
		call_user_func( $remove_func, $tag );
	}

	call_user_func( $add_func, $tags, 'cf7bs_checkbox_shortcode_handler', true );
}

function cf7bs_checkbox_shortcode_handler( $tag ) {
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

	$exclusive = $tag->has_option( 'exclusive' );
	$free_text = $tag->has_option( 'free_text' );

	$multiple = false;

	if ( 'checkbox' == $tag->basetype ) {
		$multiple = !$exclusive;
	} else {
		$exclusive = false;
	}

	if ( $exclusive ) {
		$class .= ' wpcf7-exclusive-checkbox';
	}

	if ( $tag->is_required() ) {
		$mode = 'required';
	}

	$values = (array) $tag->values;
	$labels = (array) $tag->labels;

	if ( $data = (array) $tag->get_data_option() ) {
		if ( $free_text ) {
			$values = array_merge( array_slice( $values, 0, -1 ), array_values( $data ), array_slice( $values, -1 ) );
			$labels = array_merge( array_slice( $labels, 0, -1 ), array_values( $data ), array_slice( $labels, -1 ) );
		} else {
			$values = array_merge( $values, array_values( $data ) );
			$labels = array_merge( $labels, array_values( $data ) );
		}
	}

	$defaults = array();

	$default_choice = $tag->get_default_option( null, 'multiple=1' );

	foreach ( $default_choice as $value ) {
		$key = array_search( $value, $values, true );

		if ( false !== $key ) {
			$defaults[] = (int) $key + 1;
		}
	}

	if ( $matches = $tag->get_first_match_option( '/^default:([0-9_]+)$/' ) ) {
		$defaults = array_merge( $defaults, explode( '_', $matches[1] ) );
	}

	$defaults = array_unique( $defaults );

	$options = array();
	$checked = '';
	if ( $multiple ) {
		$checked = array();
	}

	if ( isset( $_POST[ $tag->name ] ) ) {
		$post = $_POST[ $tag->name ];
	} else {
		if ( isset( $_GET[ $tag->name ] ) ) {
			if ( $multiple ) {
				$get = cf7bs_array_decode( rawurldecode( $_GET[ $tag->name ] ) );
			} else {
				$get = rawurldecode( $_GET[ $tag->name ] );
			}
		}
		$post = $multiple ? array() : '';
	}
	$posted = wpcf7_is_posted();

	$count = 0;
	$replace_index = count( (array) $tag->values ) - 1;

	foreach ( (array) $tag->values as $key => $value ) {
		$options[ $value ] = isset( $labels[ $key ] ) ? $labels[ $key ] : $value;
		if ( $free_text && $count == $replace_index ) {
			$options[ $value ] .= ' <input type="text" name="' . sprintf( '_wpcf7_%1$s_free_text_%2$s', $tag->basetype, $tag->name ) . '" class="wpcf7-free-text">';
		}

		if ( $posted && ! empty( $post ) ) {
			if ( $multiple && in_array( esc_sql( $value ), (array) $post ) ) {
				$checked[] = $value;
			}
			if ( ! $multiple && $post == esc_sql( $value ) ) {
				$checked = $value;
			}
		} elseif ( isset( $get ) && ! empty( $get ) ) {
			if ( $multiple && in_array( esc_sql( $value ), (array) $get ) ) {
				$checked[] = $value;
			}
			if ( ! $multiple && $get == esc_sql( $value ) ) {
				$checked = $value;
			}
		} elseif ( in_array( $key + 1, (array) $defaults ) ) {
			if ( $multiple ) {
				$checked[] = $value;
			} else {
				$checked = $value;
			}
		}
		$count++;
	}

	$label = $tag->content;

	if ( count( $options ) < 1 ) {
		if ( $free_text ) {
			$options = array( 'true' => '<input type="text" name="' . sprintf( '_wpcf7_%1$s_free_text_%2$s', $tag->basetype, $tag->name ) . '" class="wpcf7-free-text">' );
		} else {
			$options = array( 'true' => $label );
			$label = '';
		}
	}

	$field = new CF7BS_Form_Field( cf7bs_apply_field_args_filter( array(
		'name'				=> $tag->name,
		'id'				=> $tag->get_option( 'id', 'id', true ),
		'class'				=> '',
		'type'				=> $tag->basetype,
		'value'				=> $checked,
		'label'				=> $label,
		'options'			=> $options,
		'help_text'			=> $validation_error,
		'size'				=> cf7bs_get_form_property( 'size', 0, $tag ),
		'grid_columns'		=> cf7bs_get_form_property( 'grid_columns', 0, $tag ),
		'form_layout'		=> cf7bs_get_form_property( 'layout', 0, $tag ),
		'form_label_width'	=> cf7bs_get_form_property( 'label_width', 0, $tag ),
		'form_breakpoint'	=> cf7bs_get_form_property( 'breakpoint', 0, $tag ),
		'group_layout'		=> cf7bs_get_form_property( 'group_layout', 0, $tag ),
		'group_type'		=> cf7bs_get_form_property( 'group_type', 0, $tag ),
		'mode'				=> $mode,
		'status'			=> $status,
		'tabindex'			=> $tag->get_option( 'tabindex', 'int', true ),
		'wrapper_class'		=> $tag->get_class_option( $class . ' ' . $tag->name ),
		'label_class'       => $tag->get_option( 'label_class', 'class', true ),
	), $tag->basetype, $tag->name ) );

	$html = $field->display( false );

	return $html;
}
