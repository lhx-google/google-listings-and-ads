#!/usr/bin/env php
<?php
declare( strict_types=1 );

// phpcs:ignoreFile

$replacements  = [
	'League\\Container' => 'league/container',
	'League\\ISO3166'   => 'league/iso3166',
	'Google\\Auth'      => 'google/auth',
	'GuzzleHttp'        => 'guzzlehttp',
];
$vendor_dir    = dirname( __DIR__ ) . '/vendor';
$new_namespace = 'Automattic\\WooCommerce\\GoogleListingsAndAds\\Vendor';

// Vendor libraries which are dependent on a library we are prefixing.
$dependencies = [
	'google/auth' => [
		'google/apiclient',
		'google/gax',
	],
	'guzzlehttp'  => [
		'google/apiclient',
		'google/auth',
		'google/gax',
	],
];

// Namespaces which are used directly within the code.
$direct_replacements = [
	'guzzlehttp' => [
		'GuzzleHttp\Client(',
		'GuzzleHttp\ClientInterface::MAJOR_VERSION',
		'GuzzleHttp\ClientInterface::VERSION',
		'GuzzleHttp\describe_type',
		'GuzzleHttp\HandlerStack::create',
		'GuzzleHttp\Message\ResponseInterface)',
		'GuzzleHttp\Promise\promise_for',
		'GuzzleHttp\Psr7\Message::bodySummary',
		'GuzzleHttp\Psr7\str',
		'GuzzleHttp\Psr7\Utils::streamFor',
		'GuzzleHttp\Psr7\Utils::tryFopen',
	],
];

foreach ( $replacements as $namespace => $path ) {
	$files = find_files( $path );

	$quoted = preg_quote( $namespace, '#' );
	foreach ( $files as $file ) {
		$contents = file_get_contents( $file );

		// Check to see whether a replacement has already run for this namespace. Just in case.
		if ( false !== strpos( $contents, "{$new_namespace}\\{$namespace}" ) ) {
			continue 2;
		}

		$contents =	preg_replace(
			"#^(\s*)(namespace)\s*({$quoted}[\\\\|;])#m",
			"\$1\$2 {$new_namespace}\\\\\$3",
			$contents
		);

		$contents =	preg_replace(
			"#^(\s*)(use)\s*({$quoted}\\\\)#m",
			"\$1\$2 {$new_namespace}\\\\\$3",
			$contents
		);

		if ( ! empty( $direct_replacements[ $path ] ) ) {
			foreach( $direct_replacements[ $path ] as $replace ) {
				$replace = preg_quote( $replace, '#' );
				$contents =	preg_replace(
					"#({$replace})#m",
					"{$new_namespace}\\\\\$1",
					$contents
				);
			}
		}

		file_put_contents( $file, $contents );
	}

	// Update the namespace in the composer.json files.
	$composer_files = array_filter(
		explode(
			"\n",
			`find {$vendor_dir}/{$path} -iname 'composer.json'`
		)
	);

	array_map(
		function( $file ) use ( $namespace, $new_namespace ) {
			return replace_in_json_file( $file, $namespace, $new_namespace );
		},
		$composer_files
	);

	// Update the namespace in vendor/composer/installed.json
	// This file is used to generate the classmaps.
	replace_in_json_file( "{$vendor_dir}/composer/installed.json", $namespace, $new_namespace );
}

function find_files( string $path ): array {
	global $vendor_dir, $dependencies;

	$files = array_filter(
		explode(
			"\n",
			`find {$vendor_dir}/{$path} -iname '*.php'`
		)
	);

	if ( ! empty( $dependencies[ $path ] ) ) {
		foreach( $dependencies[ $path ] as $dependency ) {
			$dependent_files = array_filter(
				explode(
					"\n",
					`find {$vendor_dir}/{$dependency} -iname '*.php'`
				)
			);
			$files = array_merge( $files, $dependent_files );
		}
	}

	return $files;
}

function replace_in_json_file( string $file, string $namespace, string $new_namespace ) {
	if ( ! file_exists( $file ) ) {
		return;
	}

	$contents = file_get_contents( $file );
	file_put_contents(
		$file,
		str_replace(
			addslashes( "{$namespace}\\" ),
			addslashes( "{$new_namespace}\\{$namespace}\\" ),
			$contents
		)
	);
}
