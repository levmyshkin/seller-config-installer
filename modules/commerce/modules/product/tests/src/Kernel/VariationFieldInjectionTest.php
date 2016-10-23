<?php

namespace Drupal\Tests\commerce_product\Kernel;

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the attribute field manager.
 *
 * @coversDefaultClass \Drupal\commerce_product\VariationFieldInjection
 *
 * @group commerce
 */
class VariationFieldInjectionTest extends KernelTestBase {

  /**
   * The variation field injection.
   *
   * @var \Drupal\commerce_product\VariationFieldInjectionInterface
   */
  protected $variationFieldInjection;

  /**
   * The first variation type.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationType
   */
  protected $firstVariationType;

  /**
   * The second variation type.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationType
   */
  protected $secondVariationType;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'field', 'options', 'user', 'path', 'text',
    'entity', 'views', 'address', 'inline_entity_form', 'commerce',
    'commerce_price', 'commerce_store', 'commerce_product',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', 'router');
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product_variation_type');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_type');
    $this->installConfig(['commerce_product']);

    $this->variationFieldInjection = $this->container->get('commerce_product.variation_field_injection');

    $this->firstVariationType = ProductVariationType::create([
      'id' => 'shirt',
      'label' => 'Shirt',
    ]);
    $this->firstVariationType->save();
    $this->secondVariationType = ProductVariationType::create([
      'id' => 'mug',
      'label' => 'Mug',
    ]);
    $this->secondVariationType->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'injectable_field',
      'entity_type' => 'commerce_product_variation',
      'type' => 'text',
      'cardinality' => 1,
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $this->secondVariationType->id(),
      'label' => 'Injectable Field',
      'required' => TRUE,
      'translatable' => FALSE,
    ]);
    $field->save();
  }

  /**
   * @covers ::getFieldDefinitions
   */
  public function testGetInjectableFields() {
    $first_injectable_fields = $this->variationFieldInjection->getFieldDefinitions($this->firstVariationType->id());

    $this->assertFalse(isset($first_injectable_fields['product_id']), 'Variation product ID field is not injectable');
    $this->assertFalse(isset($first_injectable_fields['status']), 'Variation status is not injectable');
    $this->assertTrue(isset($first_injectable_fields['price']), 'Variation price is injectable');
    $this->assertTrue(isset($first_injectable_fields['title']), 'Variation title is injectable');
    $this->assertTrue(isset($first_injectable_fields['sku']), 'Variation SKU is injectable');

    $second_injectable_fields = $this->variationFieldInjection->getFieldDefinitions($this->secondVariationType->id());

    $this->assertTrue(isset($second_injectable_fields['injectable_field']), 'Custom field is injectable.');
    $this->assertInstanceOf(FieldDefinitionInterface::class, $second_injectable_fields['injectable_field']);
  }

  /**
   * @covers ::getRenderableFields
   */
  public function testGetRenderableFields() {
    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'status' => 1,
    ]);
    $variation->save();
    $product = Product::create([
      'type' => 'default',
      'variations' => [$variation],
    ]);
    $product->save();

    $renderable_fields = $this->variationFieldInjection->getRenderableFields($variation);

    $this->assertFalse(isset($renderable_fields['variation_product_id']), 'Variation product ID field was not injected');
    $this->assertTrue(isset($renderable_fields['variation_sku']), 'Variation product price field was injected');
    $this->assertEquals('product--variation-field--variation_sku__' . $variation->getProductId(), $renderable_fields['variation_sku']['#ajax_injection_class']);
  }

}
