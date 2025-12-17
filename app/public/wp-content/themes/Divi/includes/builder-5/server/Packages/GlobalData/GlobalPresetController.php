<?php
/**
 * REST: GlobalPresetController class.
 *
 * @package Divi
 * @since ??
 */

namespace ET\Builder\Packages\GlobalData;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Framework\Controllers\RESTController;
use ET\Builder\Framework\UserRole\UserRole;
use ET\Builder\Packages\GlobalData\GlobalPreset;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * GlobalPreset REST Controller class.
 *
 * @since ??
 */
class GlobalPresetController extends RESTController {

	/**
	 * Sync global preset data with the server.
	 *
	 * @since ??
	 *
	 * @param WP_REST_Request $request REST request object.
	 *
	 * @return WP_REST_Response|WP_Error Returns the REST response object or WP_Error if validation fails.
	 */
	public static function sync( WP_REST_Request $request ) {
		$prepared_data = GlobalPreset::prepare_data( $request->get_param( 'presets' ) );
		$action_type   = $request->get_param( 'actionType' ) ?? '';

		// CRITICAL SAFETY CHECK: Validate that we're not accidentally losing presets during sync.
		// Only DELETE actions should reduce the preset count. All other operations (save, add, update)
		// should preserve or increase the preset count.
		// This prevents bugs where incomplete restore points or data corruption causes preset loss.
		// We compare against the current database state (source of truth), not client-side state.
		$current_presets  = GlobalPreset::get_data();
		$validation_error = GlobalPreset::validate_preset_count( $current_presets, $prepared_data, $action_type );

		if ( is_wp_error( $validation_error ) ) {
			return $validation_error;
		}

		$saved_data = GlobalPreset::save_data( $prepared_data );

		return RESTController::response_success( (object) $saved_data );
	}

	/**
	 * Generates the properties for a preset type.
	 *
	 * @param string $preset_type The type of the preset.
	 * @param array  $extra_items_properties Additional properties to merge with the default item properties.
	 *
	 * @return array The array structure defining the properties of the preset type.
	 */
	public static function preset_type_properties( string $preset_type, array $extra_items_properties = [] ): array {
		$items_properties = array_merge(
			[
				'type'         => [
					'required' => true,
					'type'     => 'string',
					'enum'     => [ $preset_type ],
				],
				'id'           => [
					'required'  => true,
					'type'      => 'string',
					'format'    => 'text-field', // Set format to 'text-field' to get the value sanitized using sanitize_text_field.
					'minLength' => 1, // Prevent empty string.
				],
				'name'         => [
					'required'  => true,
					'type'      => 'string',
					'format'    => 'text-field', // Set format to 'text-field' to get the value sanitized using sanitize_text_field.
					'minLength' => 1, // Prevent empty string.
				],
				'priority'     => [
					'required' => false,
					'type'     => 'integer',
					'default'  => 10, // Default priority value.
				],
				'created'      => [
					'required' => true,
					'type'     => 'integer',
				],
				'updated'      => [
					'required' => true,
					'type'     => 'integer',
				],
				'version'      => [
					'required'  => true,
					'type'      => 'string',
					'format'    => 'text-field', // Set format to 'text-field' to get the value sanitized using sanitize_text_field.
					'minLength' => 1, // Prevent empty string.
				],
				'attrs'        => [
					'required' => false,
					'type'     => 'object', // Will be sanitized using GlobalPreset::prepare_data().
				],
				'renderAttrs'  => [
					'required' => false,
					'type'     => 'object', // Will be sanitized using GlobalPreset::prepare_data().
				],
				'styleAttrs'   => [
					'required' => false,
					'type'     => 'object', // Will be sanitized using GlobalPreset::prepare_data().
				],
				'groupPresets' => [
					'required'             => false,
					'type'                 => 'object', // Object containing group preset references keyed by group ID.
					'properties'           => [],
					'additionalProperties' => [
						'type'                 => 'object',
						'properties'           => [
							'presetId'  => [
								'required' => true,
								'type'     => 'array',
								'items'    => [
									'type'      => 'string',
									'format'    => 'text-field',
									'minLength' => 1,
								],
								'minItems' => 1, // At least one preset ID required.
							],
							'groupName' => [
								'required'  => true,
								'type'      => 'string',
								'format'    => 'text-field',
								'minLength' => 1,
							],
						],
						'additionalProperties' => false,
					],
				],
			],
			$extra_items_properties
		);

		return [
			'required' => false,
			'type'     => 'array',
			'items'    => [
				'type'                 => 'object',
				'properties'           => [
					'default' => [
						'required' => true,
						'type'     => 'string',
						'format'   => 'text-field', // Set format to 'text-field' to get the value sanitized using sanitize_text_field.
					],
					'items'   => [
						'required' => true,
						'type'     => 'array',
						'items'    => [
							'type'                 => 'object',
							'properties'           => $items_properties,
							'additionalProperties' => false,
						],
					],
				],
				'additionalProperties' => false,
			],
		];
	}

	/**
	 * Get the arguments for the sync action.
	 *
	 * This function returns an array that defines the arguments for the sync action,
	 * which is used in the `register_rest_route()` function.
	 *
	 * @since ??
	 *
	 * @return array An array of arguments for the sync action. The array should aligns with the GlobalData.Presets.RestSchemaItems TS interface.
	 */
	public static function sync_args(): array {
		return [
			'presets'    => [
				'required'             => true,
				'type'                 => 'object',
				'properties'           => [
					'module' => self::preset_type_properties(
						'module',
						[
							'moduleName' => [
								'required'  => true,
								'type'      => 'string',
								'format'    => 'text-field', // Set format to 'text-field' to get the value sanitized using sanitize_text_field.
								'minLength' => 1, // Prevent empty string.
							],
						]
					),
					'group'  => self::preset_type_properties(
						'group',
						[
							'groupId'         => [
								'required'  => true,
								'type'      => 'string',
								'format'    => 'text-field', // Set format to 'text-field' to get the value sanitized using sanitize_text_field.
								'minLength' => 1, // Prevent empty string.
							],
							'groupName'       => [
								'required'  => true,
								'type'      => 'string',
								'format'    => 'text-field', // Set format to 'text-field' to get the value sanitized using sanitize_text_field.
								'minLength' => 1, // Prevent empty string.
							],
							'moduleName'      => [
								'required'  => true,
								'type'      => 'string',
								'format'    => 'text-field', // Set format to 'text-field' to get the value sanitized using sanitize_text_field.
								'minLength' => 1, // Prevent empty string.
							],
							'primaryAttrName' => [
								'type'   => 'string',
								'format' => 'text-field', // Set format to 'text-field' to get the value sanitized using sanitize_text_field.
							],
						]
					),
				],
				'additionalProperties' => false,
			],
			'actionType' => [
				'required' => false,
				'type'     => 'string',
				'format'   => 'text-field', // Set format to 'text-field' to get the value sanitized using sanitize_text_field.
			],
		];
	}

	/**
	 * Provides the permission status for the sync action.
	 *
	 * @since ??
	 *
	 * @return bool Returns `true` if the current user has the permission to use the Visual Builder, `false` otherwise.
	 */
	public static function sync_permission(): bool {
		return UserRole::can_current_user_use_visual_builder();
	}

}
