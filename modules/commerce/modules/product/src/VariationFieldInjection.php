<?php

namespace Drupal\commerce_product;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Default implementation of VariationFieldInjectionInterface.
 */
class VariationFieldInjection implements VariationFieldInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Local cache for injectable field definitions.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface[]
   */
  protected $fieldDefinitions = [];


  /**
   * Constructs a new VariationFieldInjector object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinitions($variation_type_id) {
    if (!isset($this->fieldDefinitions[$variation_type_id])) {
      $definitions = $this->entityFieldManager->getFieldDefinitions('commerce_product_variation', $variation_type_id);
      $definitions = array_filter($definitions, function ($definition) {
        /** @var \Drupal\Core\Field\FieldDefinitionInterface $definition */
        $name = $definition->getName();
        if ($definition instanceof BaseFieldDefinition && !in_array($name, $this->allowedBaseFields())) {
          return FALSE;
        }
        return (strpos($name, 'attribute_') === FALSE);
      });

      $this->fieldDefinitions[$variation_type_id] = $definitions;
    }

    return $this->fieldDefinitions[$variation_type_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getRenderableFields(ProductVariationInterface $variation, $view_mode = 'default') {
    $injectable_fields = [];

    $field_definitions = $this->getFieldDefinitions($variation->bundle());

    $view_display = $this->entityTypeManager
      ->getStorage('entity_view_display')
      ->load("commerce_product_variation.{$variation->bundle()}.$view_mode");

    if (!$view_display) {
      // Use default if one was not available.
      /** @var \Drupal\Core\Entity\Entity\EntityViewDisplay $view_display */
      $view_display = commerce_get_entity_display('commerce_product_variation', $variation->bundle(), 'view');
    }

    $view_builder = $this->entityTypeManager->getViewBuilder('commerce_product_variation');

    foreach ($field_definitions as $field_name => $field_definition) {
      if ($view_display->getComponent($field_name)) {
        $content = $view_builder->viewField($variation->$field_name, $view_mode);
        $content['#attributes']['class'][] = $this->baseCssClass();

        $content['#attributes']['class'][] = $this->variationFieldCssClass('variation_' . $field_name, $variation);
        $content['#ajax_injection_class'] = $this->variationFieldCssClass('variation_' . $field_name, $variation);
        $injectable_fields['variation_' . $field_name] = $content;
      }
    }

    return $injectable_fields;
  }

  /**
   * {@inheritdoc}
   */
  public function addAjaxCommands(AjaxResponse $response, ProductVariationInterface $variation, $view_mode = 'default') {
    foreach ($this->getRenderableFields($variation, $view_mode) as $field_name => $injection_field) {
      $response->addCommand(new ReplaceCommand('.' . $injection_field['#ajax_injection_class'], $injection_field));
    }

    $this->moduleHandler->invokeAll('commerce_product_variation_field_injection', [
      $response,
      $variation,
      $view_mode,
    ]);
  }

  /**
   * The allowed base field definitions for injection.
   *
   * @return array
   *   An array of base field names.
   */
  protected function allowedBaseFields() {
    return ['title', 'sku', 'price'];
  }

  /**
   * The base CSS class all injected fields will have.
   *
   * This is also the base block class for injectable field classes that can be
   * accessed via AJAX, follow BEM concepts.
   *
   * @return string
   *   The CSS class.
   */
  protected function baseCssClass() {
    return 'product--variation-field';
  }

  /**
   * The CSS class used to identify the field with AJAX callbacks.
   *
   * @param string $field_name
   *   The field name.
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The product variation.
   *
   * @return string
   *   The CSS class.
   */
  protected function variationFieldCssClass($field_name, ProductVariationInterface $variation) {
    return $this->baseCssClass() . '--' . $field_name . '__' . $variation->getProductId();
  }

}
