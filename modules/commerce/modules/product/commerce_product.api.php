<?php

/**
 * @file
 * Documentation for Commerce Product module APIs.
 */

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\commerce_product\Entity\ProductVariationInterface;

/**
 * Modify the response when injecting variation fields via AJAX.
 *
 * @param \Drupal\Core\Ajax\AjaxResponse $response
 *   The AJAX response.
 * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
 *   The product variation.
 * @param string $display_mode
 *   The display mode.
 */
function hook_commerce_product_variation_field_injection(AjaxResponse $response, ProductVariationInterface $variation, $display_mode) {
  // Add a new AJAX command for a specific bundle.
  if ($variation->bundle() == 'my_custom_bundle') {
    $response->addCommand(new \Drupal\Core\Ajax\InsertCommand('.my-class', 'some content'));
  }
}
