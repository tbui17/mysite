<?php
/**
 * ModuleElementsUtils Class
 *
 * @since ??
 *
 * @package Divi
 */

namespace ET\Builder\Packages\Module\Layout\Components\ModuleElements;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET_Builder_Post_Features;

/**
 * ModuleElementsUtils class.
 *
 * @since ??
 */
class ModuleElementsUtils {

	/**
	 * Interpolate a selector template with a value.
	 *
	 * This function is equivalent of JS function:
	 * {@link /docs/builder-api/js-beta/divi-module/functions/InterpolateSelector interpolateSelector} in
	 * `@divi/module` packages.
	 *
	 * @since ??
	 *
	 * @param array $args {
	 *     An array of arguments.
	 *
	 *     @type string $value                  The value to interpolate.
	 *     @type string|array $selectorTemplate The selector template to interpolate.
	 *     @type string $placeholder            Optional. The placeholder to replace. Default `{{selector}}`.
	 * }
	 *
	 * @return string|array The interpolated selector.
	 *                      If the selector template is a string, a string is returned.
	 *                      Otherwise an array is returned.
	 */
	public static function interpolate_selector( array $args ) {
		static $cached = null;

		$cache_key = md5( json_encode( $args ) );

		if ( isset( $cached[ $cache_key ] ) ) {
			return $cached[ $cache_key ];
		}

		$value             = $args['value'];
		$selector_template = $args['selectorTemplate'];
		$placeholder       = $args['placeholder'] ?? '{{selector}}';

		if ( is_string( $selector_template ) ) {
			$cached[ $cache_key ] = str_replace( $placeholder, $value, $selector_template );

			return $cached[ $cache_key ];
		}

		$stringify_selector_template = wp_json_encode( $selector_template );

		$updated_selector_template = str_replace( $placeholder, $value, $stringify_selector_template );

		$cached[ $cache_key ] = json_decode( $updated_selector_template, true );

		return $cached[ $cache_key ];
	}

	/**
	 * Extracts the attachment URL from the image source.
	 *
	 * @since ??
	 *
	 * @param string $image_src The URL of the image attachment.
	 * @return array {
	 *    An array containing the image path without the scaling suffix and the query string,
	 *    and the scaling suffix if found.
	 *
	 *    @type string $path   The image path without the scaling suffix and query string.
	 *    @type string $suffix The scaling suffix if found. Otherwise an empty string.
	 * }
	 */
	public static function extract_attachment_url( string $image_src ): array {
		// Remove the query string from the image URL.
		list( $image_src ) = explode( '?', $image_src );

		// If the image source contains a scaling suffix, extract it.
		// The scaling suffix is in the format of "-{width}x{height}.".
		// Regex pattern test: https://regex101.com/r/USnFl3/1.
		if ( strpos( $image_src, 'x' ) && preg_match( '/-\d+x\d+\./', $image_src, $match ) ) {
			return [
				'path'   => str_replace( $match[0], '.', $image_src ),
				'suffix' => $match[0],
			];
		}

		return [
			'path'   => $image_src,
			'suffix' => '',
		];
	}

	/**
	 * Gets responsive image attributes for an image attachment.
	 *
	 * This function calculates and returns responsive image attributes such as width,
	 * height, srcset, and sizes for a given image. It uses caching to avoid repeated
	 * calculations and respects WordPress responsive images settings.
	 *
	 * @since ??
	 *
	 * @param array $image_attr_value {
	 *     An array of image attribute values.
	 *
	 *     @type string      $src Optional. The image source URL. Default empty string.
	 *     @type string      $url Optional. Alternative key for image source URL. Default empty string.
	 *     @type int|string  $id  Optional. The attachment ID. Default 0.
	 * }
	 *
	 * @return array {
	 *     An array of responsive image attributes. Returns empty array if cached data is invalid.
	 *
	 *     @type int         $id      The attachment ID.
	 *     @type array|false $meta    The attachment metadata from wp_get_attachment_metadata().
	 *     @type string      $width   Optional. The image width as a string.
	 *     @type string      $height  Optional. The image height as a string.
	 *     @type string      $srcset  Optional. The srcset attribute value for responsive images.
	 *     @type string      $sizes   Optional. The sizes attribute value for responsive images.
	 * }
	 */
	public static function get_responsive_image_attrs( array $image_attr_value ): array {
		static $is_responsive_images_enabled = null;

		if ( null === $is_responsive_images_enabled ) {
			$is_responsive_images_enabled = et_is_responsive_images_enabled();
		}

		$image_src     = $image_attr_value['src'] ?? $image_attr_value['url'] ?? '';
		$attachment_id = 0;

		// Only calculate attachment ID if the image source is a valid URL.
		if ( $image_src ) {
			// First try to get the attachment ID that is provided in the image attribute value.
			$attachment_id = absint( $image_attr_value['id'] ?? 0 );

			if ( ! $attachment_id ) {
				// If the attachment ID is not provided, try to get it from the image source URL.
				$attachment_id = self::attachment_url_to_id( $image_src );
			}
		}

		$cache_key = 'attachment_image_meta_' . $attachment_id;

		if ( $image_src ) {
			$cache_key .= '_' . md5( $image_src );
		}

		$cached_data = ET_Builder_Post_Features::instance()->get(
			// Cache key.
			$cache_key,
			// Callback function if the cache key is not found.
			function() use ( $image_src, $attachment_id, $is_responsive_images_enabled ) {
				$responsive_attrs = [
					'id' => $attachment_id,
				];

				if ( $image_src && $attachment_id ) {
					$image_meta = wp_get_attachment_metadata( $attachment_id );

					if ( $image_meta ) {
						$size_array = wp_image_src_get_dimensions( $image_src, $image_meta, $attachment_id );

						// Only proceed if the image size array is available.
						if ( $size_array ) {
							$responsive_attrs['width']  = strval( $size_array[0] );
							$responsive_attrs['height'] = strval( $size_array[1] );

							// Calculate srcset and sizes if responsive images are enabled.
							if ( $is_responsive_images_enabled ) {
								$image_srcset = wp_calculate_image_srcset( $size_array, $image_src, $image_meta, $attachment_id );

								if ( is_string( $image_srcset ) ) {
									$responsive_attrs['srcset'] = $image_srcset;
								}

								$image_sizes = wp_calculate_image_sizes( $size_array, $image_src, $image_meta, $attachment_id );

								if ( is_string( $image_sizes ) ) {
									$responsive_attrs['sizes'] = $image_sizes;
								}
							}
						} elseif ( isset( $image_meta['width'], $image_meta['height'] ) ) {
							$responsive_attrs['width']  = strval( $image_meta['width'] );
							$responsive_attrs['height'] = strval( $image_meta['height'] );

							$image_sizes = wp_calculate_image_sizes( 'full', $image_src, $image_meta, $attachment_id );

							if ( is_string( $image_sizes ) ) {
								$responsive_attrs['sizes'] = $image_sizes;
							}
						}
					}
				}

				return $responsive_attrs;
			},
			// Cache group.
			'attachment_image_meta',
			// Whether to forcefully update the cache,
			// in this case we are setting to true, because we want to update the cache,
			// even if the attachment ID is not found, so that we don't have to make the same
			// query again and again.
			true
		);

		if ( ! is_array( $cached_data ) ) {
			return [];
		}

		return $cached_data;
	}

	/**
	 * Converts an attachment URL to its corresponding ID.
	 *
	 * @since ??
	 *
	 * @param string $image_src The URL of the attachment image.
	 * @return int The ID of the attachment.
	 */
	public static function attachment_url_to_id( string $image_src ): int {
		// If the image source is a data URL, return 0.
		if ( 0 === strpos( $image_src, 'data:' ) ) {
			return 0;
		}

		// Get the instance of ET_Builder_Post_Features.
		$post_features = ET_Builder_Post_Features::instance();

		// Get the attachment ID from the cache.
		$attachment_id = $post_features->get(
			// Cache key.
			$image_src,
			// Callback function if the cache key is not found.
			function() use ( $image_src ) {
				$extracted_image_src = ModuleElementsUtils::extract_attachment_url( $image_src );

				// First attempt to get the attachment ID from the image source URL.
				$attachment_id = attachment_url_to_postid( $extracted_image_src['path'] );

				// If no attachment ID is found and the image source contains a scaling suffix, try to get the attachment ID from the image source with `-scaled.` suffix.
				// This could happens when the uploaded image larger than the threshold size (threshold being either width or height of 2560px), WordPress core system
				// will generate image file name with `-scaled.` suffix.
				//
				// @see https://make.wordpress.org/core/2019/10/09/introducing-handling-of-big-images-in-wordpress-5-3/
				// @see https://wordpress.org/support/topic/media-images-renamed-to-xyz-scaled-jpg/.
				if ( ! $attachment_id && $extracted_image_src['suffix'] ) {
					$attachment_id = attachment_url_to_postid( str_replace( $extracted_image_src['suffix'], '-scaled.', $image_src ) );
				}

				return $attachment_id;
			},
			// Cache group.
			'attachment_url_to_id',
			// Whether to forcefully update the cache,
			// in this case we are setting to true, because we want to update the cache,
			// even if the attachment ID is not found, so that we don't have to make the same
			// query again and again.
			true
		);

		return $attachment_id;
	}

	/**
	 * Populates the image element attributes with additional information.
	 *
	 * This function takes an array of attributes and populates it with additional information
	 * related to the image element, such as the attachment ID, width, height, srcset, and sizes.
	 *
	 * @since ??
	 *
	 * @param array $attrs The array of attributes to be populated.
	 * @return array The updated array of attributes.
	 */
	public static function populate_image_element_attrs( array $attrs ): array {
		foreach ( $attrs as $breakpoint => $states ) {
			foreach ( $states as $state => $state_value ) {
				if ( ! $state_value || ! is_array( $state_value ) ) {
					continue;
				}

				$responsive_image_attrs = self::get_responsive_image_attrs( $state_value );

				if ( $responsive_image_attrs ) {
					foreach ( $responsive_image_attrs as $responsive_image_attr_key => $responsive_image_attr_value ) {
						$attrs[ $breakpoint ][ $state ][ $responsive_image_attr_key ] = $responsive_image_attr_value;
					}
				}
			}
		}

		return $attrs;
	}
}
