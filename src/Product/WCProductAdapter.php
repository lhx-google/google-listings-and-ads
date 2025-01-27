<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\GoogleListingsAndAds\Product;

use Automattic\WooCommerce\GoogleListingsAndAds\Exception\InvalidValue;
use Automattic\WooCommerce\GoogleListingsAndAds\PluginHelper;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\Condition;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\SizeSystem;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\SizeType;
use Automattic\WooCommerce\GoogleListingsAndAds\Product\Attributes\AgeGroup;
use Automattic\WooCommerce\GoogleListingsAndAds\Validator\GooglePriceConstraint;
use Automattic\WooCommerce\GoogleListingsAndAds\Validator\Validatable;
use DateInterval;
use Google\Service\ShoppingContent\Price as GooglePrice;
use Google\Service\ShoppingContent\Product as GoogleProduct;
use Google\Service\ShoppingContent\ProductShipping as GoogleProductShipping;
use Google\Service\ShoppingContent\ProductShippingDimension as GoogleProductShippingDimension;
use Google\Service\ShoppingContent\ProductShippingWeight as GoogleProductShippingWeight;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use WC_DateTime;
use WC_Product;
use WC_Product_Variable;
use WC_Product_Variation;

defined( 'ABSPATH' ) || exit;

/**
 * Class WCProductAdapter
 *
 * This class adapts the WooCommerce Product class to the Google's Product class by mapping their attributes.
 *
 * @package Automattic\WooCommerce\GoogleListingsAndAds\Product
 */
class WCProductAdapter extends GoogleProduct implements Validatable {
	use PluginHelper;

	public const AVAILABILITY_IN_STOCK     = 'in stock';
	public const AVAILABILITY_OUT_OF_STOCK = 'out of stock';

	public const IMAGE_SIZE_FULL = 'full';

	public const CHANNEL_ONLINE = 'online';

	/**
	 * @var WC_Product WooCommerce product object
	 */
	protected $wc_product;

	/**
	 * @var WC_Product WooCommerce parent product object if $wc_product is a variation
	 */
	protected $parent_wc_product;

	/**
	 * @var bool Whether tax is excluded from product price
	 */
	protected $tax_excluded;

	/**
	 * Initialize this object's properties from an array.
	 *
	 * @param array $array Used to seed this object's properties.
	 *
	 * @return void
	 *
	 * @throws InvalidValue When a WooCommerce product is not provided or it is invalid.
	 */
	public function mapTypes( $array ) {
		if ( empty( $array['wc_product'] ) || ! $array['wc_product'] instanceof WC_Product ) {
			throw InvalidValue::not_instance_of( WC_Product::class, 'wc_product' );
		}

		// throw an exception if the parent product isn't provided and this is a variation
		if ( $array['wc_product'] instanceof WC_Product_Variation &&
			 ( empty( $array['parent_wc_product'] ) || ! $array['parent_wc_product'] instanceof WC_Product_Variable )
		) {
			throw InvalidValue::not_instance_of( WC_Product_Variable::class, 'parent_wc_product' );
		}

		if ( empty( $array['targetCountry'] ) ) {
			throw InvalidValue::is_empty( 'targetCountry' );
		}

		$this->wc_product        = $array['wc_product'];
		$this->parent_wc_product = $array['parent_wc_product'] ?? null;

		$this->map_gla_attributes( $array['gla_attributes'] ?? [] );

		// Google doesn't expect extra fields, so it's best to remove them
		unset( $array['wc_product'] );
		unset( $array['parent_wc_product'] );
		unset( $array['gla_attributes'] );

		parent::mapTypes( $array );

		$this->map_woocommerce_product();
	}

	/**
	 * Map the WooCommerce product attributes to the current class.
	 *
	 * @return void
	 */
	protected function map_woocommerce_product() {
		$this->setChannel( self::CHANNEL_ONLINE );

		$content_language = empty( get_locale() ) ? 'en' : strtolower( substr( get_locale(), 0, 2 ) ); // ISO 639-1.
		$this->setContentLanguage( $content_language );

		$this->map_wc_product_id()
			 ->map_wc_general_attributes()
			 ->map_wc_product_image( self::IMAGE_SIZE_FULL )
			 ->map_wc_availability()
			 ->map_wc_product_shipping()
			 ->map_wc_prices();

		$this->setIdentifierExists( ! empty( $this->getGtin() ) || ! empty( $this->getMpn() ) );
	}

	/**
	 * Map the general WooCommerce product attributes.
	 *
	 * @return $this
	 */
	protected function map_wc_general_attributes() {
		$this->setTitle( $this->wc_product->get_title() );
		$this->setDescription( $this->get_wc_product_description() );
		$this->setLink( $this->wc_product->get_permalink() );

		// set item group id for variations
		if ( $this->is_variation() ) {
			$this->setItemGroupId( $this->parent_wc_product->get_id() );
		}

		return $this;
	}

	/**
	 * Map the WooCommerce product ID.
	 *
	 * @return $this
	 */
	protected function map_wc_product_id(): WCProductAdapter {
		$offer_id = "{$this->get_slug()}_{$this->wc_product->get_id()}";
		$this->setOfferId( $offer_id );

		return $this;
	}

	/**
	 * Get the description for the WooCommerce product.
	 *
	 * @return string
	 */
	protected function get_wc_product_description(): string {
		$use_short_description = apply_filters( 'woocommerce_gla_use_short_description', false );

		$description = ! empty( $this->wc_product->get_description() ) && ! $use_short_description ?
			$this->wc_product->get_description() :
			$this->wc_product->get_short_description();

		// prepend the parent product description to the variation product
		if ( $this->is_variation() ) {
			$parent_description = ! empty( $this->parent_wc_product->get_description() ) && ! $use_short_description ?
				$this->parent_wc_product->get_description() :
				$this->parent_wc_product->get_short_description();
			$new_line           = ! empty( $description ) && ! empty( $parent_description ) ? PHP_EOL : '';
			$description        = $parent_description . $new_line . $description;
		}

		// Strip out invalid unicode.
		$description = mb_convert_encoding( $description, 'UTF-8', 'UTF-8' );
		$description = preg_replace(
			'/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F]/u',
			'',
			$description
		);

		// Strip out active shortcodes and HTML tags.
		$description = strip_shortcodes( wp_strip_all_tags( $description ) );

		return apply_filters( 'woocommerce_gla_product_attribute_value_description', $description, $this->wc_product );
	}

	/**
	 * Map the WooCommerce product images.
	 *
	 * @param string $image_size
	 *
	 * @return $this
	 */
	protected function map_wc_product_image( string $image_size ) {
		$image_id          = $this->wc_product->get_image_id();
		$gallery_image_ids = $this->wc_product->get_gallery_image_ids() ?: [];

		// check if we can use the parent product image if it's a variation
		if ( $this->is_variation() ) {
			$image_id              = $image_id ?? $this->parent_wc_product->get_image_id();
			$parent_gallery_images = $this->parent_wc_product->get_gallery_image_ids() ?: [];
			$gallery_image_ids     = ! empty( $gallery_image_ids ) ? $gallery_image_ids : $parent_gallery_images;
		}

		// use a gallery image as the main product image if no main image is available
		if ( empty( $image_id ) && ! empty( $gallery_image_ids[0] ) ) {
			$image_id = $gallery_image_ids[0];

			// remove the recently set main image from the list of gallery images
			unset( $gallery_image_ids[0] );
		}

		// set main image
		$image_link = wp_get_attachment_image_url( $image_id, $image_size, false );
		$this->setImageLink( $image_link );

		// set additional images
		$gallery_image_links = array_map(
			function ( $gallery_image_id ) use ( $image_size ) {
				return wp_get_attachment_image_url( $gallery_image_id, $image_size, false );
			},
			$gallery_image_ids
		);
		// Uniquify the set of additional images
		$gallery_image_links = array_unique( $gallery_image_links, SORT_REGULAR );
		$this->setAdditionalImageLinks( $gallery_image_links );

		return $this;
	}

	/**
	 * Map the general WooCommerce product attributes.
	 *
	 * @return $this
	 */
	protected function map_wc_availability() {
		// todo: include 'preorder' status (maybe a new field for products / or using an extension?)
		$availability = $this->wc_product->is_in_stock() ? self::AVAILABILITY_IN_STOCK : self::AVAILABILITY_OUT_OF_STOCK;
		$this->setAvailability( $availability );

		return $this;
	}

	/**
	 * Map the shipping information for WooCommerce product.
	 *
	 * @return $this
	 */
	protected function map_wc_product_shipping(): WCProductAdapter {
		$this->add_shipping_country( $this->getTargetCountry() );

		if ( ! $this->is_virtual() ) {
			$dimension_unit = apply_filters( 'woocommerce_gla_dimension_unit', get_option( 'woocommerce_dimension_unit' ) );
			$weight_unit    = apply_filters( 'woocommerce_gla_weight_unit', get_option( 'woocommerce_weight_unit' ) );

			$this->map_wc_shipping_dimensions( $dimension_unit )
				 ->map_wc_shipping_weight( $weight_unit );
		}

		return $this;
	}

	/**
	 * Add a shipping country for the product.
	 *
	 * @param string $country
	 */
	public function add_shipping_country( string $country ): void {
		$product_shipping = [
			'country' => $country,
		];

		// Virtual products should override any country shipping cost.
		if ( $this->is_virtual() ) {
			$product_shipping['price'] = [
				'currency' => get_woocommerce_currency(),
				'value'    => 0,
			];
		}

		$new_shipping = [
			new GoogleProductShipping( $product_shipping ),
		];

		if ( ! $this->shipping_country_exists( $country ) ) {
			$current_shipping = $this->getShipping() ?? [];
			$this->setShipping( array_merge( $current_shipping, $new_shipping ) );
		}
	}

	/**
	 * Remove a shipping country from the product.
	 *
	 * @param string $country
	 *
	 * @since 1.2.0
	 */
	public function remove_shipping_country( string $country ): void {
		$product_shippings = $this->getShipping() ?? [];

		foreach ( $product_shippings as $index => $shipping ) {
			if ( $country === $shipping->getCountry() ) {
				unset( $product_shippings[ $index ] );
			}
		}

		$this->setShipping( $product_shippings );
	}

	/**
	 * @param string $country
	 *
	 * @return bool
	 */
	protected function shipping_country_exists( string $country ): bool {
		$current_shipping = $this->getShipping() ?? [];

		foreach ( $current_shipping as $shipping ) {
			if ( $country === $shipping->getCountry() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Map the measurements for the WooCommerce product.
	 *
	 * @param string $unit
	 *
	 * @return $this
	 */
	protected function map_wc_shipping_dimensions( string $unit = 'cm' ): WCProductAdapter {
		$length = $this->wc_product->get_length();
		$width  = $this->wc_product->get_width();
		$height = $this->wc_product->get_height();

		// Use cm if the unit isn't supported.
		if ( ! in_array( $unit, [ 'in', 'cm' ], true ) ) {
			$unit = 'cm';
		}
		$length = wc_get_dimension( (float) $length, $unit );
		$width  = wc_get_dimension( (float) $width, $unit );
		$height = wc_get_dimension( (float) $height, $unit );

		if ( $length > 0 && $width > 0 && $height > 0 ) {
			$this->setShippingLength(
				new GoogleProductShippingDimension(
					[
						'unit'  => $unit,
						'value' => $length,
					]
				)
			);
			$this->setShippingWidth(
				new GoogleProductShippingDimension(
					[
						'unit'  => $unit,
						'value' => $width,
					]
				)
			);
			$this->setShippingHeight(
				new GoogleProductShippingDimension(
					[
						'unit'  => $unit,
						'value' => $height,
					]
				)
			);
		}

		return $this;
	}

	/**
	 * Map the weight for the WooCommerce product.
	 *
	 * @param string $unit
	 *
	 * @return $this
	 */
	protected function map_wc_shipping_weight( string $unit = 'g' ): WCProductAdapter {
		// Use g if the unit isn't supported.
		if ( ! in_array( $unit, [ 'g', 'lbs', 'oz' ], true ) ) {
			$unit = 'g';
		}

		$weight = wc_get_weight( $this->wc_product->get_weight(), $unit );
		$this->setShippingWeight(
			new GoogleProductShippingWeight(
				[
					'unit'  => $unit,
					'value' => $weight,
				]
			)
		);

		return $this;
	}

	/**
	 * Sets whether tax is excluded from product price.
	 *
	 * @return $this
	 */
	protected function map_tax_excluded(): WCProductAdapter {
		// tax is excluded from price in US and CA
		$this->tax_excluded = in_array( $this->getTargetCountry(), [ 'US', 'CA' ], true );
		$this->tax_excluded = boolval( apply_filters( 'woocommerce_gla_tax_excluded', $this->tax_excluded ) );

		return $this;
	}

	/**
	 * Map the prices (base and sale price) for the product.
	 *
	 * @return $this
	 */
	protected function map_wc_prices(): WCProductAdapter {
		$this->map_tax_excluded();
		$this->map_wc_product_price( $this->wc_product );

		return $this;
	}

	/**
	 * Map the prices (base and sale price) for a given WooCommerce product.
	 *
	 * @param WC_Product $product
	 *
	 * @return $this
	 */
	protected function map_wc_product_price( WC_Product $product ): WCProductAdapter {
		// set regular price
		$regular_price = $product->get_regular_price();
		if ( '' !== $regular_price ) {
			$price = $this->tax_excluded ?
				wc_get_price_excluding_tax( $product, [ 'price' => $regular_price ] ) :
				wc_get_price_including_tax( $product, [ 'price' => $regular_price ] );

			/**
			 * Filters the calculated product price.
			 *
			 * @param float      $price        Calculated price of the product
			 * @param WC_Product $product      WooCommerce product
			 * @param bool       $tax_excluded Whether tax is excluded from product price
			 */
			$price = apply_filters( 'woocommerce_gla_product_attribute_value_price', $price, $product, $this->tax_excluded );

			$this->setPrice(
				new GooglePrice(
					[
						'currency' => get_woocommerce_currency(),
						'value'    => $price,
					]
				)
			);
		}
		// set sale price
		$this->map_wc_product_sale_price( $product );

		return $this;
	}

	/**
	 * Map the sale price and sale effective date for a given WooCommerce product.
	 *
	 * @param WC_Product $product
	 *
	 * @return $this
	 */
	protected function map_wc_product_sale_price( WC_Product $product ): WCProductAdapter {
		// Grab the sale price of the base product. Some plugins (Dynamic
		// pricing as an example) filter the active price, but not the sale
		// price. If the active price < the regular price treat it as a sale
		// price.
		$regular_price = $product->get_regular_price();
		$sale_price    = $product->get_sale_price();
		$active_price  = $product->get_price();
		if ( ( empty( $sale_price ) && $active_price < $regular_price ) ||
			 ( ! empty( $sale_price ) && $active_price < $sale_price ) ) {
			$sale_price = $active_price;
		}

		// set sale price and sale effective date if any
		if ( '' !== $sale_price ) {
			$sale_price = $this->tax_excluded ?
				wc_get_price_excluding_tax( $product, [ 'price' => $sale_price ] ) :
				wc_get_price_including_tax( $product, [ 'price' => $sale_price ] );

			/**
			 * Filters the calculated product sale price.
			 *
			 * @param float      $sale_price   Calculated sale price of the product
			 * @param WC_Product $product      WooCommerce product
			 * @param bool       $tax_excluded Whether tax is excluded from product price
			 */
			$sale_price = apply_filters( 'woocommerce_gla_product_attribute_value_sale_price', $sale_price, $product, $this->tax_excluded );

			// If the sale price dates no longer apply, make sure we don't include a sale price.
			$now                 = new WC_DateTime();
			$sale_price_end_date = $product->get_date_on_sale_to();
			if ( empty( $sale_price_end_date ) || $sale_price_end_date >= $now ) {
				$this->setSalePrice(
					new GooglePrice(
						[
							'currency' => get_woocommerce_currency(),
							'value'    => $sale_price,
						]
					)
				);

				$this->setSalePriceEffectiveDate( $this->get_wc_product_sale_price_effective_date( $product ) );
			}
		}

		return $this;
	}

	/**
	 * Return the sale effective dates for the WooCommerce product.
	 *
	 * @param WC_Product $product
	 *
	 * @return string|null
	 */
	protected function get_wc_product_sale_price_effective_date( WC_Product $product ): ?string {
		$start_date = $product->get_date_on_sale_from();
		$end_date   = $product->get_date_on_sale_to();

		$now = new WC_DateTime();
		// if we have a sale end date in the future, but no start date, set the start date to now()
		if ( ! empty( $end_date ) &&
			 $end_date > $now &&
			 empty( $start_date )
		) {
			$start_date = $now;
		}
		// if we have a sale start date in the past, but no end date, do not include the start date.
		if ( ! empty( $start_date ) &&
			 $start_date < $now &&
			 empty( $end_date )
		) {
			$start_date = null;
		}
		// if we have a start date in the future, but no end date, assume a one-day sale.
		if ( ! empty( $start_date ) &&
			 $start_date > $now &&
			 empty( $end_date )
		) {
			$end_date = clone $start_date;
			$end_date->add( new DateInterval( 'P1D' ) );
		}

		if ( empty( $start_date ) && empty( $end_date ) ) {
			return null;
		}

		return sprintf( '%s/%s', (string) $start_date, (string) $end_date );
	}

	/**
	 * Return whether the WooCommerce product is a variation.
	 *
	 * @return bool
	 */
	public function is_variation(): bool {
		return $this->wc_product instanceof WC_Product_Variation;
	}

	/**
	 * Return whether the WooCommerce product is virtual.
	 *
	 * @return bool
	 */
	public function is_virtual(): bool {
		return false !== $this->wc_product->is_virtual();
	}

	/**
	 * @param ClassMetadata $metadata
	 */
	public static function load_validator_metadata( ClassMetadata $metadata ) {
		$metadata->addPropertyConstraint( 'offerId', new Assert\NotBlank() );
		$metadata->addPropertyConstraint( 'title', new Assert\NotBlank() );
		$metadata->addPropertyConstraint( 'description', new Assert\NotBlank() );

		$metadata->addPropertyConstraint( 'link', new Assert\NotBlank() );
		$metadata->addPropertyConstraint( 'link', new Assert\Url() );

		$metadata->addPropertyConstraint( 'imageLink', new Assert\NotBlank() );
		$metadata->addPropertyConstraint( 'imageLink', new Assert\Url() );
		$metadata->addPropertyConstraint( 'additionalImageLinks', new Assert\All( [ 'constraints' => [ new Assert\Url() ] ] ) );

		$metadata->addGetterConstraint( 'price', new Assert\NotNull() );
		$metadata->addGetterConstraint( 'price', new GooglePriceConstraint() );
		$metadata->addGetterConstraint( 'salePrice', new GooglePriceConstraint() );

		$metadata->addConstraint( new Assert\Callback( 'validate_item_group_id' ) );

		$metadata->addPropertyConstraint( 'gtin', new Assert\Regex( '/^\d{8}(?:\d{4,6})?$/' ) );
		$metadata->addPropertyConstraint( 'mpn', new Assert\Type( 'alnum' ) ); // alphanumeric
		$metadata->addPropertyConstraint( 'mpn', new Assert\Length( null, 0, 70 ) ); // maximum 70 characters

		$metadata->addPropertyConstraint(
			'sizes',
			new Assert\All(
				[
					'constraints' => [
						new Assert\Type( 'string' ),
						new Assert\Length( null, 0, 100 ), // maximum 100 characters
					],
				]
			)
		);
		$metadata->addPropertyConstraint( 'sizeSystem', new Assert\Choice( array_keys( SizeSystem::get_value_options() ) ) );
		$metadata->addPropertyConstraint( 'sizeType', new Assert\Choice( array_keys( SizeType::get_value_options() ) ) );

		$metadata->addPropertyConstraint( 'color', new Assert\Length( null, 0, 100 ) ); // maximum 100 characters
		$metadata->addPropertyConstraint( 'material', new Assert\Length( null, 0, 200 ) ); // maximum 200 characters
		$metadata->addPropertyConstraint( 'pattern', new Assert\Length( null, 0, 100 ) ); // maximum 200 characters

		$metadata->addPropertyConstraint( 'ageGroup', new Assert\Choice( array_keys( AgeGroup::get_value_options() ) ) );
		$metadata->addPropertyConstraint( 'adult', new Assert\Type( 'boolean' ) );

		$metadata->addPropertyConstraint( 'condition', new Assert\Choice( array_keys( Condition::get_value_options() ) ) );

		$metadata->addPropertyConstraint( 'multipack', new Assert\Type( 'integer' ) );
		$metadata->addPropertyConstraint( 'multipack', new Assert\PositiveOrZero() );

		$metadata->addPropertyConstraint( 'isBundle', new Assert\Type( 'boolean' ) );
	}

	/**
	 * Used by the validator to check if the variation product has an itemGroupId
	 *
	 * @param ExecutionContextInterface $context
	 */
	public function validate_item_group_id( ExecutionContextInterface $context ) {
		if ( $this->is_variation() && empty( $this->getItemGroupId() ) ) {
			$context->buildViolation( 'ItemGroupId needs to be set for variable products.' )
					->atPath( 'itemGroupId' )
					->addViolation();
		}
	}

	/**
	 * @return WC_Product
	 */
	public function get_wc_product(): WC_Product {
		return $this->wc_product;
	}

	/**
	 * @param array $attributes Attribute values
	 *
	 * @return $this
	 */
	protected function map_gla_attributes( array $attributes ): WCProductAdapter {
		$gla_attributes = [];
		foreach ( $attributes as $attribute_id => $attribute_value ) {
			if ( property_exists( $this, $attribute_id ) ) {
				$gla_attributes[ $attribute_id ] = apply_filters( "woocommerce_gla_product_attribute_value_{$attribute_id}", $attribute_value, $this->get_wc_product() );
			}
		}

		parent::mapTypes( $gla_attributes );

		// Size
		if ( ! empty( $attributes['size'] ) ) {
			$this->setSizes( [ $attributes['size'] ] );
		}

		return $this;
	}

	/**
	 * @param string $targetCountry
	 *
	 * phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
	 */
	public function setTargetCountry( $targetCountry ) {
		// remove shipping for current target country
		$this->remove_shipping_country( $this->getTargetCountry() );

		// set the new target country
		parent::setTargetCountry( $targetCountry );

		// we need to reset the prices because tax is based on the country
		$this->map_wc_prices();

		// product shipping information is also country based
		$this->map_wc_product_shipping();
	}
}
