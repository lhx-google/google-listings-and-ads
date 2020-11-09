<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Assets;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidAsset;
use Closure;

trait ScriptHelper {

	use SourceHelper;

	/**
	 * The asset handle.
	 *
	 * @var string
	 */
	protected $handle;

	/**
	 * The full URI to the script.
	 *
	 * @var string
	 */
	protected $uri;

	/**
	 * Array of dependencies for the script.
	 *
	 * @var array
	 */
	protected $dependencies = [];

	/**
	 * The version string for the script.
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * Whether the script should be printed in the footer.
	 *
	 * @var bool
	 */
	protected $in_footer = false;

	/**
	 * Array of localizations to add to the script.
	 *
	 * @var array
	 */
	protected $localizations = [];

	/**
	 * ScriptHelper constructor.
	 *
	 * @param string $handle       The script handle.
	 * @param string $uri          The URI for the script.
	 * @param array  $dependencies (Optional) Any dependencies the script has.
	 * @param string $version      (Optional) A version string for the script. Will default to the plugin version
	 *                             if not set.
	 * @param bool   $in_footer    (Optional) Whether the script should be printed in the footer. Defaults to false.
	 */
	public function __construct(
		string $handle,
		string $uri,
		array $dependencies = [],
		string $version = '',
		bool $in_footer = false
	) {
		$this->handle         = $handle;
		$this->uri            = $this->get_uri_from_path( $uri );
		$this->dependencies   = $dependencies;
		$this->version        = $version ?: $this->get_version();
		$this->in_footer      = $in_footer;
		$this->file_extension = 'js';
	}

	/**
	 * Add a localization to the script.
	 *
	 * @param string $object The object name.
	 * @param array  $data   Array of data for the object.
	 *
	 * @return $this
	 */
	public function add_localization( string $object, array $data ) {
		$this->localizations[ $object ] = $data;

		return $this;
	}

	/**
	 * Get the enqueue closure to use.
	 *
	 * @return Closure
	 */
	protected function get_register_closure(): Closure {
		return function() {
			if ( wp_script_is( $this->handle, 'registered' ) ) {
				return;
			}

			wp_register_script(
				$this->handle,
				$this->uri,
				$this->dependencies,
				$this->version,
				$this->in_footer
			);
		};
	}

	/**
	 * Get the enqueue closure to use.
	 *
	 * @return Closure
	 */
	protected function get_enqueue_closure(): Closure {
		return function() {
			if ( ! wp_script_is( $this->handle, 'registered' ) ) {
				throw InvalidAsset::asset_not_registered( $this->handle );
			}

			foreach ( $this->localizations as $object_name => $data_array ) {
				wp_localize_script( $this->handle, $object_name, $data_array );
			}

			wp_enqueue_script( $this->handle );
		};
	}

	/**
	 * Get the dequeue closure to use.
	 *
	 * @return Closure
	 */
	protected function get_dequeue_closure(): Closure {
		return function() {
			wp_dequeue_script( $this->handle );
		};
	}
}
