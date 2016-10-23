<?php

namespace Drupal\commerce_product;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_product\Entity\ProductVariationTypeInterface;
use Drupal\Core\Ajax\AjaxResponse;

/**
 * Interface VariationFieldInjectorInterface.
 *
 * @todo: Document
 */
interface VariationFieldInjectionInterface {

  /**
   * Gets the injectable field definitions for a product variation bundle.
   *
   * @param string $variation_type_id
   *   The product variation type ID.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   The array of injectable field definitions, keyed by field name.
   */
  public function getFieldDefinitions($variation_type_id);

  /**
   * Get the fields to inject.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The product variation entity.
   * @param string $view_mode
   *   The display mode.
   *
   * @return array
   *    Array of render arrays for injected fields, keyed by field name.
   */
  public function getRenderableFields(ProductVariationInterface $variation, $view_mode = 'default');

  /**
   * Adds commands to render variation fields to an AJAX response.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $response
   *   The AJAX response.
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The product variation entity.
   * @param string $view_mode
   *   The display mode.
   *
   * @return $this
   */
  public function addAjaxCommands(AjaxResponse $response, ProductVariationInterface $variation, $view_mode = 'default');
}
