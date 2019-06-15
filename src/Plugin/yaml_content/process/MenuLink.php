<?php

namespace Drupal\yaml_content\Plugin\yaml_content\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;
use Drupal\yaml_content\Plugin\ProcessingContext;
use Drupal\yaml_content\Plugin\YamlContentProcessBase;
use Drupal\yaml_content\Plugin\YamlContentProcessInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin for querying and loading a menu content link entity.
 *
 * @YamlContentProcess(
 *   id = "menu_link_content",
 *   title = @Translation("Menu Link Content Processor"),
 *   description = @Translation("Processing and loading a file attachment.")
 * )
 */
class MenuLink extends YamlContentProcessBase implements YamlContentProcessInterface, ContainerFactoryPluginInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new EntityView.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process(ProcessingContext $context, array &$field_data) {
    $entity_type = 'menu_link_content';
    $filter_params = $this->configuration[0];

    $entity_storage = $this->entityTypeManager->getStorage($entity_type);

    // Use query factory to create a query object for the node of entity_type.
    $query = $entity_storage->getQuery('AND');

    // Apply filter parameters.
    foreach ($filter_params as $property => $value) {
      if (!is_array($value)) {
        $query->condition($property, $value);
      }
    }
    $entity_ids = $query->execute();

    if (empty($entity_ids)) {
      $entity = $entity_storage->create($filter_params);
      $entity->save();
      $entity_ids = [$entity->id()];
    }
    else {
      // Load the first match entity so we can get the uuid and bundle.
      $entity = $entity_storage->load(reset($entity_ids));
    }

    if (empty($entity_ids)) {
      return $this->throwParamError('Unable to find referenced content', $entity_type, $filter_params);
    }

    $field_data = $entity->bundle() . MenuLinkContent::DERIVATIVE_SEPARATOR . $entity->uuid();

    return $entity_ids;
  }

}
