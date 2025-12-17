<?php
/**
 * Background::style_declaration()
 *
 * @package Builder\FrontEnd
 * @since ??
 */

namespace ET\Builder\Packages\StyleLibrary\Declarations\Background\Traits;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Packages\StyleLibrary\Utils\StyleDeclarations;
use ET\Builder\Packages\StyleLibrary\Declarations\Background\Background;
use ET\Builder\Packages\StyleLibrary\Declarations\Background\Utils\BackgroundStyleUtils;
use ET\Builder\Packages\ModuleUtils\ModuleUtils;

trait StyleDeclarationTrait {

	/**
	 * Generate background CSS declaration, dynamically comparing with parent breakpoint.
	 * Only outputs properties that differ from parent to prevent duplicate CSS.
	 *
	 * This function is equivalent of JS function:
	 * {@link /docs/builder-api/js/style-library/background-style-declaration backgroundStyleDeclaration} in:
	 * `@divi/style-library` package.
	 *
	 * @since ??
	 *
	 * @param array $args {
	 *     An array of arguments.
	 *
	 *     @type array      $attr       Original attribute structure for dynamic breakpoint comparison.
	 *     @type array      $attrValue  Processed background attribute value.
	 *     @type string     $breakpoint Current breakpoint being processed.
	 *     @type string     $state      Current state (value, hover, sticky).
	 *     @type bool|array $important  Optional. Whether declarations should be marked important.
	 *     @type string     $returnType Optional. Return type ('string' or 'key_value_pair').
	 *     @type string     $keyFormat  Optional. Key format for declarations.
	 *     @type bool       $hasBackgroundPresets Optional. Whether presets are actively applied. Default `false`.
	 * }
	 *
	 * @return array|string
	 */
	public static function style_declaration( array $args ) {
		$args = wp_parse_args(
			$args,
			[
				'important'            => false,
				'returnType'           => 'string',
				'keyFormat'            => 'param-case',
				'hasBackgroundPresets' => false,
			]
		);

		$important              = $args['important'];
		$return_type            = $args['returnType'];
		$key_format             = $args['keyFormat'];
		$has_background_presets = $args['hasBackgroundPresets'];
		$breakpoint             = $args['breakpoint'] ?? null;
		$state                  = $args['state'] ?? 'value';
		$attr                   = $args['attr'] ?? null;
		$attr_value             = $args['attrValue'] ?? null;
		$preview                = $attr_value['preview'] ?? false;
		$color                  = $attr_value['color'] ?? null;
		$gradient               = $attr_value['gradient'] ?? null;
		$image                  = $attr_value['image'] ?? [];
		$style_declarations     = new StyleDeclarations(
			[
				'important'  => $important,
				'returnType' => $return_type,
				'keyFormat'  => $key_format,
			]
		);

		$is_image_not_enabled    = is_array( $image ) && array_key_exists( 'enabled', $image ) && 'off' === $image['enabled'];
		$is_gradient_not_enabled = is_array( $gradient ) && array_key_exists( 'enabled', $gradient ) && 'off' === $gradient['enabled'];

		$background_default_attr = Background::$background_default_attr;
		$default_attr            = $attr_value['defaultAttr'] ?? $background_default_attr;
		$default_attr            = array_merge( $background_default_attr, $default_attr );
		$background_images       = [];

		// Extract responsive width and height values for current breakpoint.
		// Use full responsive attr structure if available, otherwise fallback to attrValue.
		$responsive_width  = null;
		$responsive_height = null;

		if ( $attr && $breakpoint ) {
			// Extract responsive width using D5's inheritance system.
			$responsive_width = ModuleUtils::get_attr_subname_value(
				[
					'attr'         => $attr,
					'subname'      => 'image.width',
					'breakpoint'   => $breakpoint,
					'state'        => $state,
					'mode'         => 'getOrInheritClosest',
					'defaultValue' => $default_attr['image']['width'],
				]
			);

			// Extract responsive height using D5's inheritance system.
			$responsive_height = ModuleUtils::get_attr_subname_value(
				[
					'attr'         => $attr,
					'subname'      => 'image.height',
					'breakpoint'   => $breakpoint,
					'state'        => $state,
					'mode'         => 'getOrInheritClosest',
					'defaultValue' => $default_attr['image']['height'],
				]
			);
		}

		// Load default so if the attribute lacks required value, it'll be rendered using default.
		$image_values = array_merge( $default_attr['image'], $image );
		$parallax     = $image_values['parallax'];

		// Override width and height with responsive values if available.
		if ( null !== $responsive_width ) {
			$image_values['width'] = $responsive_width;
		}
		if ( null !== $responsive_height ) {
			$image_values['height'] = $responsive_height;
		}

		if ( $image && ! $is_image_not_enabled ) {
			$url               = $image_values['url'];
			$size              = $image_values['size'];
			$width             = $image_values['width'];
			$height            = $image_values['height'];
			$position          = $image_values['position'];
			$horizontal_offset = $image_values['horizontalOffset'];
			$vertical_offset   = $image_values['verticalOffset'];
			$repeat            = $image_values['repeat'];
			$blend             = $image_values['blend'];
			$is_img_var        = strpos( $url, 'var(' ) !== false;

			$should_output_property = function( $prop, $value, $attr, $breakpoint, $state ) use ( $image ) {
				if ( 'desktop' === $breakpoint || null === $attr ) {
					return ! empty( $value );
				}

				$breakpoints   = array_keys( $attr );
				$current_index = array_search( $breakpoint, $breakpoints, true );

				if ( false === $current_index ) {
					return isset( $attr[ $breakpoint ][ $state ]['image'][ $prop ] );
				}

				$current_raw_value = $attr[ $breakpoint ][ $state ]['image'][ $prop ] ?? null;

				if ( null === $current_raw_value ) {
					return false;
				}

				for ( $i = $current_index - 1; $i >= 0; $i-- ) {
					$parent_breakpoint = $breakpoints[ $i ];
					if ( isset( $attr[ $parent_breakpoint ][ $state ]['image'][ $prop ] ) ) {
						$parent_raw_value = $attr[ $parent_breakpoint ][ $state ]['image'][ $prop ];

						// For custom size, check if underlying width/height values changed.
						if ( 'size' === $prop && 'custom' === $current_raw_value && 'custom' === $parent_raw_value ) {
							$has_changed =
								( $attr[ $breakpoint ][ $state ]['image']['width'] ?? null ) !== ( $attr[ $parent_breakpoint ][ $state ]['image']['width'] ?? null ) ||
								( $attr[ $breakpoint ][ $state ]['image']['height'] ?? null ) !== ( $attr[ $parent_breakpoint ][ $state ]['image']['height'] ?? null );
						} else {
							$has_changed = $current_raw_value !== $parent_raw_value;
						}

						return $has_changed;
					}
				}
				return true;
			};

			if ( isset( $image['url'] ) && isset( $parallax['enabled'] ) && 'on' !== $parallax['enabled'] ) {
				if ( $should_output_property( 'url', $url, $attr, $breakpoint, $state ) ) {
					$background_url = $is_img_var ? "{$url}" : "url({$url})";

					if ( ! in_array( $background_url, $background_images, true ) ) {
						$background_images[] = $background_url;
					}
				}

				$properties = array(
					'size'     => array(
						'css'       => 'background-size',
						'value'     => $size,
						'generator' => function () use ( $size, $width, $height ) {
							return BackgroundStyleUtils::get_background_size_css( $size, $width, $height, 'image' );
						},
					),
					'position' => array(
						'css'       => 'background-position',
						'value'     => $position,
						'generator' => function () use ( $position, $horizontal_offset, $vertical_offset ) {
							return BackgroundStyleUtils::get_background_position_css( $position, $horizontal_offset, $vertical_offset );
						},
					),
					'repeat'   => array(
						'css'       => 'background-repeat',
						'value'     => $repeat,
						'generator' => function () use ( $repeat ) {
							return $repeat;
						},
					),
					'blend'    => array(
						'css'       => 'background-blend-mode',
						'value'     => $blend,
						'generator' => function () use ( $blend ) {
							return $blend;
						},
					),
				);

				foreach ( $properties as $prop => $config ) {
					$has_explicit_value = isset( $image[ $prop ] );

					if ( 'repeat' === $prop ) {
						// - No presets active: Always generate background-repeat for Divi 4 compatibility
						// Issue reference https://github.com/elegantthemes/Divi/issues/32583
						// - Presets active: Only generate background-repeat when explicitly set by user
						// This prevents Option Group Presets with "repeat" from being overridden by "no-repeat" defaults.
						if ( $has_background_presets ? isset( $image['repeat'] ) : isset( $repeat ) ) {
							$should_output_default = $should_output_property( $prop, $repeat, $attr, $breakpoint, $state );
							if ( $should_output_default ) {
								$style_declarations->add( 'background-repeat', $repeat );
							}
						}
					} elseif ( $has_explicit_value ) {
						$should_output = $should_output_property( $prop, $config['value'], $attr, $breakpoint, $state );

						if ( $should_output ) {
							$style_declarations->add( $config['css'], $config['generator']() );
						}
					}
				}
			}

			if ( $preview && $image['url'] && isset( $parallax['enabled'] ) && 'on' === $parallax['enabled'] ) {
				if ( $should_output_property( 'url', $url, $attr, $breakpoint, $state ) ) {
					$background_url = $is_img_var ? "{$url}" : "url({$url})";

					if ( ! in_array( $background_url, $background_images, true ) ) {
						$background_images[] = $background_url;
					}
				}

				// Background styles for preview area when parallax is on.
				$style_declarations->add( 'background-size', 'cover' );
				$style_declarations->add( 'background-position', 'center' );
				$style_declarations->add( 'background-repeat', 'no-repeat' );
				$style_declarations->add( 'background-blend-mode', $blend );
			}
		}

		if ( $gradient ) {
			// Render gradient when enabled.
			if ( isset( $gradient['enabled'] ) && 'on' === $gradient['enabled'] ) {
				// D4 compatibility: When parallax AND gradient overlays image are both enabled in non-preview mode,
				// don't add gradient to parent element. The parallax container will have the gradient background.
				$gradient_overlays_image_check = $gradient['overlaysImage'] ?? 'off';
				$should_skip_gradient          = isset( $parallax['enabled'] ) && 'on' === $parallax['enabled'] && 'on' === $gradient_overlays_image_check && ! $preview;

				if ( ! $should_skip_gradient ) {
					// Load default so if the attribute lacks required value, it'll be rendered using default.
					$gradient_background = array_merge( $default_attr['gradient'], $gradient );

					$background_images[] = Background::gradient_style_declaration( $gradient_background );
				}
			}

			// Render 'none' when disabled and breakpoint isn't desktop.
			if ( 'desktop' !== $breakpoint && $is_gradient_not_enabled ) {
				$background_images[] = 'none';
			}
		}

		// CRITICAL FIX: Ensure inherited images are included when gradient is rendered.
		// When gradient is added to $background_images but image wasn't (because URL didn't change from parent),
		// we must add the inherited image so both appear in the CSS output together.
		// This prevents gradient-only output that would override and remove the inherited image.
		// Note: Image is prepended (added to beginning) to maintain default order [image, gradient].
		// The overlaysImage logic below will reverse this if needed to put gradient on top.
		if ( ! empty( $background_images ) && $gradient && isset( $gradient['enabled'] ) && 'on' === $gradient['enabled'] && ! empty( $gradient['stops'] ) ) {
			if ( $image && ! $is_image_not_enabled && isset( $image['url'] ) && isset( $parallax['enabled'] ) && 'on' !== $parallax['enabled'] ) {
				$image_url      = $image_values['url'];
				$is_image_var   = strpos( $image_url, 'var(' ) !== false;
				$background_url = $is_image_var ? "{$image_url}" : "url({$image_url})";

				// Only add if it's not already in the array (it may have been added earlier if URL changed).
				if ( ! in_array( $background_url, $background_images, true ) ) {
					// Prepend image to beginning of array to maintain default order [image, gradient].
					// This ensures overlaysImage logic works correctly.
					array_unshift( $background_images, $background_url );
				}
			}
		}

		// D4 compatibility: When parallax and gradient overlays image are both enabled, set background-image to initial.
		// This prevents the parent element from having the background, which is handled by the parallax container.
		$gradient_overlays_image = $gradient['overlaysImage'] ?? $default_attr['gradient']['overlaysImage'] ?? 'off';
		$parallax_enabled        = $parallax['enabled'] ?? 'off';

		if ( ! $preview && 'on' === $parallax_enabled && 'on' === $gradient_overlays_image ) {
			$background_images = [ 'initial' ];
		} elseif ( ! empty( $background_images ) ) {
			// Swap background gradient on top of background image when gradient has stops and overlayImage option is on.
			$gradient_overlays_value = $gradient['overlaysImage'] ?? $default_attr['gradient']['overlaysImage'] ?? 'off';
			if ( $gradient && ! empty( $gradient['stops'] ) && 'on' === $gradient_overlays_value ) {
				$background_images = array_reverse( $background_images );
			}
		} elseif ( $is_image_not_enabled || $is_gradient_not_enabled ) {
			// If both image and gradient are disabled, empty the array.
			$background_images = [ 'initial' ];
		}

		if ( ! empty( $background_images ) ) {
			$style_declarations->add( 'background-image', implode( ', ', $background_images ) );
		}

		// Determine if we should force initial based on gradient + image + blend (D4 logic).
		$should_force_initial = count( $background_images ) >= 2 && 'normal' !== $image_values['blend'];

		if ( $color ) {
			// When color IS set: use 'initial' if shouldForceInitial, otherwise use the color.
			$background_color = $should_force_initial ? 'initial' : $color;

			$style_declarations->add( 'background-color', $background_color );
		} elseif ( $should_force_initial ) {
			// When color is NOT set: still add 'initial' for gradient + image + blend.
			// This fixes issue #41171 and #44645.
			$style_declarations->add( 'background-color', 'initial' );
		}

		return $style_declarations->value();

	}

}
