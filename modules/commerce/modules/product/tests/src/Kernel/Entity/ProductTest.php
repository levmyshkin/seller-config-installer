<?php

namespace Drupal\Tests\commerce_product\Kernel\Entity;


use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the attribute field manager.
 *
 * @coversDefaultClass \Drupal\commerce_product\Entity\Product
 *
 * @group commerce
 */
class ProductTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'field', 'options', 'user', 'path',
    'text', 'entity', 'filter', 'entity_test', 'commerce', 'commerce_price',
    'commerce_store', 'commerce_product', 'views', 'address', 'inline_entity_form',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product_variation_type');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_product_type');
    $this->installConfig(['commerce_product']);
  }

  /**
   * @covers ::getDefaultVariation
   */
  public function testGetDefaultVariation() {
    $variation1 = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'status' => 0,
    ]);
    $variation1->save();

    $variation2 = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'status' => 1,
    ]);
    $variation2->save();


    $product = Product::create([
      'type' => 'default',
      'variations' => [$variation1, $variation2],
    ]);
    $product->save();

    $this->assertEquals($product->getDefaultVariation(), $variation2);
    $this->assertNotEquals($product->getDefaultVariation(), $variation1);
  }

}
