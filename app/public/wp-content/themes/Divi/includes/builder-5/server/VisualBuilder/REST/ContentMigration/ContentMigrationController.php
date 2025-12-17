<?php
/**
 * REST: ContentMigrationController class.
 *
 * @package Builder\VisualBuilder\REST
 * @since ??
 */

namespace ET\Builder\VisualBuilder\REST\ContentMigration;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

use ET\Builder\Framework\Controllers\RESTController;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Content Migration REST API Controller class.
 *
 * Provides REST endpoint for applying D5-to-D5 migrations to content that is already
 * in D5 blocks format, without performing any D4-to-D5 conversion.
 *
 * @since ??
 */
class ContentMigrationController extends RESTController {

	/**
	 * Apply D5 migrations to content without performing D4-to-D5 conversion.
	 *
	 * This endpoint accepts content that is already in D5 blocks format and applies
	 * only D5-to-D5 migrations (AttributeMigration, FlexboxMigration, etc.) without
	 * performing any D4 shortcode conversion.
	 *
	 * Use this when you have D5 blocks that need migration but don't require conversion
	 * from D4 shortcodes.
	 *
	 * @since ??
	 *
	 * @param WP_REST_Request $request The REST request object containing the content to migrate.
	 *
	 * @return WP_REST_Response|WP_Error Returns migrated content or error response.
	 *
	 * @example:
	 * ```php
	 * $request = new WP_REST_Request( 'POST', '/divi/v1/content-migration' );
	 * $request->set_param( 'content', '<!-- wp:divi/section -->...<!-- /wp:divi/section -->' );
	 *
	 * $response = ContentMigrationController::migrate_content( $request );
	 * ```
	 */
	public static function migrate_content( WP_REST_Request $request ) {
		$content = $request->get_param( 'content' );

		if ( empty( $content ) ) {
			return self::response_error( 'empty_content', esc_html__( 'Content cannot be empty.', 'et_builder_5' ) );
		}

		try {
			// Apply only D5-to-D5 migrations (AttributeMigration, FlexboxMigration, etc.).
			// No D4-to-D5 conversion is performed.
			$migrated_content = apply_filters( 'divi_framework_portability_import_migrated_post_content', $content );

			$response_data = array(
				'original_content' => $content,
				'migrated_content' => $migrated_content,
			);

			return self::response_success( $response_data );

		} catch ( Exception $e ) {
			return self::response_error(
				'migration_failed',
				sprintf(
					esc_html__( 'Content migration failed: %s', 'et_builder_5' ),
					$e->getMessage()
				)
			);
		} catch ( Error $e ) {
			return self::response_error(
				'migration_fatal_error',
				esc_html__( 'Content migration encountered a fatal error.', 'et_builder_5' )
			);
		}
	}

	/**
	 * Get arguments for migrate_content action.
	 *
	 * Defines an array of arguments for the migrate_content action used in `register_rest_route()`.
	 *
	 * @since ??
	 *
	 * @return array An array of arguments for migrate_content action.
	 */
	public static function migrate_content_args(): array {
		return [
			'content' => [
				'required'          => true,
				'sanitize_callback' => [ __CLASS__, 'sanitize_content' ],
				'validate_callback' => [ __CLASS__, 'validate_content' ],
			],
		];
	}

	/**
	 * Sanitize content parameter.
	 *
	 * @since ??
	 *
	 * @param string $content The content to sanitize.
	 *
	 * @return string The sanitized content.
	 */
	public static function sanitize_content( string $content ): string {
		// Allow HTML and shortcodes in content.
		return wp_kses_post( $content );
	}

	/**
	 * Validate content parameter.
	 *
	 * @since ??
	 *
	 * @param string $content The content to validate.
	 *
	 * @return bool|WP_Error True if valid, WP_Error if invalid.
	 */
	public static function validate_content( string $content ) {
		if ( empty( trim( $content ) ) ) {
			return new WP_Error( 'empty_content', esc_html__( 'Content cannot be empty.', 'et_builder_5' ) );
		}

		return true;
	}

	/**
	 * Check if user has permission for migrate_content action.
	 *
	 * Checks if the current user has the permission to edit posts, used in `register_rest_route()`.
	 *
	 * @since ??
	 *
	 * @return bool|WP_Error Returns `true` if the user has permission, or `WP_Error` if the user does not have permission.
	 */
	public static function migrate_content_permission() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return self::response_error_permission();
		}

		return true;
	}

}
