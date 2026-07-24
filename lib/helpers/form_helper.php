<?php
/**
 * Form field generators.
 *
 * Views never write raw `<input>` markup. Every field goes through this helper,
 * so the wrapper markup, label, id derivation and escaping stay consistent, and
 * a change to the field chrome is a one-file change.
 *
 * Field names use bracket notation (`book[title]`) so the controller receives a
 * ready-made array to hand to `MdModel::set_data()`.
 *
 * @package MiniDocs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MdFormHelper
 */
class MdFormHelper {

	/**
	 * Derive a DOM id from a bracketed field name: `book[title]` => `book-title`.
	 *
	 * @param string $name Field name.
	 * @param array  $atts Field attributes; an explicit `id` wins.
	 *
	 * @return string
	 */
	public static function name_to_id( string $name, array $atts = array() ): string {
		if ( ! empty( $atts['id'] ) ) {
			return $atts['id'];
		}

		$id = str_replace( array( '][', '[', ']' ), array( '-', '-', '' ), $name );

		return sanitize_html_class( $id );
	}

	/**
	 * Turn an attribute map into an escaped attribute string.
	 *
	 * @param array $atts Attributes.
	 *
	 * @return string
	 */
	public static function atts_string_from_array( array $atts = array() ): string {
		return MdUtilHelper::atts_string_from_array( $atts );
	}

	/**
	 * Open a form group wrapper.
	 *
	 * @param string $name         Field name.
	 * @param string $label        Label text; empty for no label.
	 * @param array  $wrapper_atts Wrapper attributes.
	 * @param string $extra_class  Extra wrapper class.
	 *
	 * @return string
	 */
	private static function open_group( string $name, string $label, array $wrapper_atts = array(), string $extra_class = '' ): string {
		$classes = trim( 'md-form-group ' . $extra_class . ' ' . ( $wrapper_atts['class'] ?? '' ) );
		unset( $wrapper_atts['class'] );

		$html = '<div class="' . esc_attr( $classes ) . '" ' . self::atts_string_from_array( $wrapper_atts ) . '>';

		if ( '' !== $label ) {
			$html .= '<label for="' . esc_attr( self::name_to_id( $name ) ) . '">' . esc_html( $label ) . '</label>';
		}

		return $html;
	}

	/**
	 * Close a form group wrapper.
	 *
	 * @param string $description Optional helper text under the field.
	 *
	 * @return string
	 */
	private static function close_group( string $description = '' ): string {
		$html = '';

		if ( '' !== $description ) {
			$html .= '<div class="md-form-description">' . esc_html( $description ) . '</div>';
		}

		return $html . '</div>';
	}

	/**
	 * Text input.
	 *
	 * @param string $name         Field name.
	 * @param string $label        Label.
	 * @param string $value        Current value.
	 * @param array  $atts         Input attributes.
	 * @param array  $wrapper_atts Wrapper attributes.
	 *
	 * @return string
	 */
	public static function text_field( string $name, string $label = '', $value = '', array $atts = array(), array $wrapper_atts = array() ): string {
		$description = $atts['description'] ?? '';
		unset( $atts['description'] );

		$atts = array_merge(
			array(
				'type'  => 'text',
				'id'    => self::name_to_id( $name, $atts ),
				'name'  => $name,
				'value' => (string) $value,
				'class' => trim( 'md-input ' . ( $atts['class'] ?? '' ) ),
			),
			array_diff_key( $atts, array_flip( array( 'class' ) ) )
		);

		return self::open_group( $name, $label, $wrapper_atts )
			. '<input ' . self::atts_string_from_array( $atts ) . ' />'
			. self::close_group( $description );
	}

	/**
	 * Number input.
	 *
	 * @param string     $name         Field name.
	 * @param string     $label        Label.
	 * @param mixed      $value        Current value.
	 * @param float|null $min          Minimum.
	 * @param float|null $max          Maximum.
	 * @param array      $atts         Input attributes.
	 * @param array      $wrapper_atts Wrapper attributes.
	 *
	 * @return string
	 */
	public static function number_field( string $name, string $label = '', $value = '', $min = null, $max = null, array $atts = array(), array $wrapper_atts = array() ): string {
		$atts['type'] = 'number';

		if ( ! is_null( $min ) ) {
			$atts['min'] = $min;
		}
		if ( ! is_null( $max ) ) {
			$atts['max'] = $max;
		}

		return self::text_field( $name, $label, $value, $atts, $wrapper_atts );
	}

	/**
	 * Date input.
	 *
	 * @param string $name         Field name.
	 * @param string $label        Label.
	 * @param string $value        Current value (Y-m-d).
	 * @param array  $atts         Input attributes.
	 * @param array  $wrapper_atts Wrapper attributes.
	 *
	 * @return string
	 */
	public static function date_field( string $name, string $label = '', $value = '', array $atts = array(), array $wrapper_atts = array() ): string {
		$atts['type'] = 'date';

		return self::text_field( $name, $label, $value, $atts, $wrapper_atts );
	}

	/**
	 * Textarea.
	 *
	 * @param string $name         Field name.
	 * @param string $label        Label.
	 * @param string $value        Current value.
	 * @param array  $atts         Textarea attributes.
	 * @param array  $wrapper_atts Wrapper attributes.
	 *
	 * @return string
	 */
	public static function textarea_field( string $name, string $label = '', $value = '', array $atts = array(), array $wrapper_atts = array() ): string {
		$description = $atts['description'] ?? '';
		unset( $atts['description'] );

		$atts = array_merge(
			array(
				'id'    => self::name_to_id( $name, $atts ),
				'name'  => $name,
				'rows'  => 4,
				'class' => trim( 'md-input md-textarea ' . ( $atts['class'] ?? '' ) ),
			),
			array_diff_key( $atts, array_flip( array( 'class' ) ) )
		);

		return self::open_group( $name, $label, $wrapper_atts )
			. '<textarea ' . self::atts_string_from_array( $atts ) . '>' . esc_textarea( (string) $value ) . '</textarea>'
			. self::close_group( $description );
	}

	/**
	 * Select box.
	 *
	 * @param string $name           Field name.
	 * @param string $label          Label.
	 * @param array  $options        Value => label, or a list of `['value'=>,'label'=>]`.
	 * @param mixed  $selected_value Selected value.
	 * @param array  $atts           Select attributes.
	 * @param array  $wrapper_atts   Wrapper attributes.
	 *
	 * @return string
	 */
	public static function select_field( string $name, string $label = '', array $options = array(), $selected_value = '', array $atts = array(), array $wrapper_atts = array() ): string {
		$description = $atts['description'] ?? '';
		$placeholder = $atts['placeholder'] ?? '';
		unset( $atts['description'], $atts['placeholder'] );

		$atts = array_merge(
			array(
				'id'    => self::name_to_id( $name, $atts ),
				'name'  => $name,
				'class' => trim( 'md-input md-select ' . ( $atts['class'] ?? '' ) ),
			),
			array_diff_key( $atts, array_flip( array( 'class' ) ) )
		);

		$html = self::open_group( $name, $label, $wrapper_atts );
		$html .= '<select ' . self::atts_string_from_array( $atts ) . '>';

		if ( '' !== $placeholder ) {
			$html .= '<option value="">' . esc_html( $placeholder ) . '</option>';
		}

		foreach ( self::normalize_options( $options ) as $option ) {
			$html .= '<option value="' . esc_attr( $option['value'] ) . '" ' . selected( (string) $option['value'], (string) $selected_value, false ) . '>'
				. esc_html( $option['label'] )
				. '</option>';
		}

		$html .= '</select>';

		return $html . self::close_group( $description );
	}

	/**
	 * Multi-select box.
	 *
	 * @param string $name            Field name (a `[]` suffix is added).
	 * @param string $label           Label.
	 * @param array  $options         Options.
	 * @param array  $selected_values Selected values.
	 * @param array  $atts            Select attributes.
	 * @param array  $wrapper_atts    Wrapper attributes.
	 *
	 * @return string
	 */
	public static function multi_select_field( string $name, string $label = '', array $options = array(), array $selected_values = array(), array $atts = array(), array $wrapper_atts = array() ): string {
		$description = $atts['description'] ?? '';
		unset( $atts['description'] );

		$selected_values = array_map( 'strval', $selected_values );

		$atts = array_merge(
			array(
				'id'       => self::name_to_id( $name, $atts ),
				'name'     => $name . '[]',
				'multiple' => true,
				'class'    => trim( 'md-input md-select md-multi-select ' . ( $atts['class'] ?? '' ) ),
			),
			array_diff_key( $atts, array_flip( array( 'class' ) ) )
		);

		$html = self::open_group( $name, $label, $wrapper_atts );
		$html .= '<select ' . self::atts_string_from_array( $atts ) . '>';

		foreach ( self::normalize_options( $options ) as $option ) {
			$is_selected = in_array( (string) $option['value'], $selected_values, true );
			$html       .= '<option value="' . esc_attr( $option['value'] ) . '" ' . selected( $is_selected, true, false ) . '>'
				. esc_html( $option['label'] )
				. '</option>';
		}

		$html .= '</select>';

		return $html . self::close_group( $description );
	}

	/**
	 * Checkbox rendered as a pill toggle. A hidden field guarantees the key is
	 * always posted, so "unchecked" is distinguishable from "not submitted".
	 *
	 * @param string $name         Field name.
	 * @param string $label        Label.
	 * @param string $value        Value posted when checked.
	 * @param bool   $is_checked   Whether it starts checked.
	 * @param array  $atts         Input attributes.
	 * @param array  $wrapper_atts Wrapper attributes.
	 *
	 * @return string
	 */
	public static function toggle_field( string $name, string $label = '', string $value = 'on', bool $is_checked = false, array $atts = array(), array $wrapper_atts = array() ): string {
		$description = $atts['description'] ?? '';
		unset( $atts['description'] );

		$id = self::name_to_id( $name, $atts );

		$input_atts = array_merge(
			array(
				'type'  => 'checkbox',
				'id'    => $id,
				'name'  => $name,
				'value' => $value,
				'class' => 'md-toggle-input',
			),
			$atts
		);

		if ( $is_checked ) {
			$input_atts['checked'] = true;
		}

		$html = '<div class="md-form-group md-form-group-toggle ' . esc_attr( $wrapper_atts['class'] ?? '' ) . '">';
		$html .= '<input type="hidden" name="' . esc_attr( $name ) . '" value="off" />';
		$html .= '<label class="md-toggle" for="' . esc_attr( $id ) . '">';
		$html .= '<input ' . self::atts_string_from_array( $input_atts ) . ' />';
		$html .= '<span class="md-toggle-track"><span class="md-toggle-thumb"></span></span>';
		$html .= '<span class="md-toggle-label">' . esc_html( $label ) . '</span>';
		$html .= '</label>';

		return $html . self::close_group( $description );
	}

	/**
	 * A group of checkboxes posting into one array.
	 *
	 * @param string $name            Field name.
	 * @param string $label           Group label.
	 * @param array  $options         Options.
	 * @param array  $selected_values Checked values.
	 * @param array  $wrapper_atts    Wrapper attributes.
	 *
	 * @return string
	 */
	public static function multi_checkbox_field( string $name, string $label = '', array $options = array(), array $selected_values = array(), array $wrapper_atts = array() ): string {
		$selected_values = array_map( 'strval', $selected_values );

		$html = self::open_group( $name, $label, $wrapper_atts, 'md-form-group-checkboxes' );
		$html .= '<input type="hidden" name="' . esc_attr( $name ) . '[]" value="" />';
		$html .= '<div class="md-checkbox-grid">';

		foreach ( self::normalize_options( $options ) as $option ) {
			$id          = self::name_to_id( $name . '-' . $option['value'] );
			$is_selected = in_array( (string) $option['value'], $selected_values, true );

			$html .= '<label class="md-checkbox" for="' . esc_attr( $id ) . '">'
				. '<input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '[]" value="' . esc_attr( $option['value'] ) . '" ' . checked( $is_selected, true, false ) . ' />'
				. '<span>' . esc_html( $option['label'] ) . '</span>'
				. '</label>';
		}

		$html .= '</div>';

		return $html . self::close_group();
	}

	/**
	 * Hidden input.
	 *
	 * @param string $name  Field name.
	 * @param mixed  $value Value.
	 * @param array  $atts  Attributes.
	 *
	 * @return string
	 */
	public static function hidden_field( string $name, $value, array $atts = array() ): string {
		$atts = array_merge(
			array(
				'type'  => 'hidden',
				'id'    => self::name_to_id( $name, $atts ),
				'name'  => $name,
				'value' => (string) $value,
			),
			$atts
		);

		return '<input ' . self::atts_string_from_array( $atts ) . ' />';
	}

	/**
	 * Button.
	 *
	 * @param string $name  Button name.
	 * @param string $label Button label.
	 * @param string $type  Button type.
	 * @param array  $atts  Attributes.
	 *
	 * @return string
	 */
	public static function button( string $name, string $label, string $type = 'button', array $atts = array() ): string {
		$atts = array_merge(
			array(
				'type'  => $type,
				'name'  => $name,
				'id'    => self::name_to_id( $name, $atts ),
				'class' => trim( 'md-btn ' . ( $atts['class'] ?? '' ) ),
			),
			array_diff_key( $atts, array_flip( array( 'class' ) ) )
		);

		return '<button ' . self::atts_string_from_array( $atts ) . '>' . esc_html( $label ) . '</button>';
	}

	/**
	 * Accept both `value => label` maps and `[['value'=>,'label'=>], ...]` lists.
	 *
	 * @param array $options Raw options.
	 *
	 * @return array
	 */
	private static function normalize_options( array $options ): array {
		$normalized = array();

		foreach ( $options as $key => $option ) {
			if ( is_array( $option ) && array_key_exists( 'value', $option ) ) {
				$normalized[] = array(
					'value' => $option['value'],
					'label' => $option['label'] ?? $option['value'],
				);
				continue;
			}

			$normalized[] = array(
				'value' => $key,
				'label' => $option,
			);
		}

		return $normalized;
	}

	/**
	 * Options list built from any model, for select fields.
	 *
	 * @param string $model_class Model class name.
	 * @param string $label_field Property used as the label.
	 * @param array  $conditions  Optional where conditions.
	 *
	 * @return array
	 */
	public static function model_options_for_select( string $model_class, string $label_field = 'name', array $conditions = array() ): array {
		$options = array();

		if ( ! class_exists( $model_class ) ) {
			return $options;
		}

		$model   = new $model_class();
		$records = $model->where( $conditions )->order_by( $label_field . ' asc' )->get_results_as_models();

		foreach ( (array) $records as $record ) {
			$options[ $record->id ] = $record->$label_field;
		}

		return $options;
	}
}
