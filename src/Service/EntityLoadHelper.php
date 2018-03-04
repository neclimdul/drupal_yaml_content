<?php

namespace Drupal\yaml_content\Service;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * A helper class to support identification and loading of existing entities.
 */
class EntityLoadHelper {

  use StringTranslationTrait;

  /**
   * The entity type manager interface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Event dispatcher service to report events throughout the loading process.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * The logging channel for recording import events.
   *
   * @var $logger
   */
  protected $logger;

  /**
   * @const array REQUIRES_SPECIAL_HANDLING
   *   An array of entity type machine names that require special handling.
   *
   * The entity types listed in this array cannot be loaded and treated the same
   * as other entity types and require special attention.
   *
   * @see https://www.drupal.org/project/yaml_content/issues/2893055
   */
  const REQUIRES_SPECIAL_HANDLING = [
    'paragraph',
    'media',
    'file',
  ];

  /**
   * Constructs the load helper service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity field manager service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   An event dispatcher service to publish events throughout the process.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logging channel for recording import events.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   String translation service for message logging.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, EventDispatcherInterface $dispatcher, LoggerChannelInterface $logger, TranslationInterface $translation) {

    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->dispatcher = $dispatcher;
    $this->logger = $logger;

    $this->setStringTranslation($translation);
  }

  /**
   * Query if a target entity already exists and should be updated.
   *
   * @param string $entity_type
   *   The type of entity being imported.
   * @param array $content_data
   *   The import content structure representing the entity being searched for.
   *
   * @return \Drupal\Core\Entity\EntityInterface|false
   *   Return a matching entity if one is found, or FALSE otherwise.
   */
  public function entityExists($entity_type, array $content_data) {
    return (boolean) $this->loadEntity($entity_type, $content_data);
  }

  /**
   * Load an entity matching content data if available.
   *
   * Loading by `uuid` will be prioritized if one is defined in order to support
   * updating of entity properties when a unique match is defined.
   *
   * If a `uuid` property is not defined, an entity matching defined properties
   * is searched for instead.
   *
   * @param string $entity_type
   *   The type of entity being imported.
   * @param array $content_data
   *   The import content structure representing the entity being searched for.
   *
   * @return \Drupal\Core\Entity\EntityInterface|false
   *   Return a matching entity if one is found, or FALSE otherwise.
   */
  public function loadEntity($entity_type, array $content_data) {
    // Prioritize loading by UUID to enable changing of properties if a
    // confident match is identified.
    if (isset($content_data['uuid'])) {
      $entity = $this->loadByUuid($entity_type, $content_data['uuid']);
    }
    // Fall back to loading by available properties otherwise.
    else {
      $entity = $this->loadByProperties($entity_type, $content_data);
    }

    return $entity;
  }

  /**
   * Load an existing entity by UUID.
   *
   * @param $entity_type
   *   The type of entity being imported.
   * @param string $uuid
   *   The UUID of the entity to be searched for.
   *
   * @return \Drupal\Core\Entity\EntityInterface|false
   */
  public function loadByUuid($entity_type, $uuid) {
    // Load the entity type handler.
    $entity_handler = $this->entityTypeManager->getStorage($entity_type);

    // Load by searching only for the `uuid` property.
    $entities = $entity_handler->loadByProperties(['uuid' => $uuid]);

    return reset($entities);
  }

  /**
   * Load an existing entity by property data.
   *
   * Some entity types require special handling and will be handled uniquely.
   * @see \Drupal\yaml_content\Service\EntityLoadHelper::REQUIRES_SPECIAL_HANDLING
   *
   * @param string $entity_type
   *   The type of entity being imported.
   * @param array $content_data
   *   The import content structure representing the entity being searched for.
   *
   * @return \Drupal\Core\Entity\EntityInterface|false
   *   Return a matching entity if one is found, or FALSE otherwise.
   */
  public function loadByProperties($entity_type, array $content_data) {

    // Address entities requiring special handling separately.
    // [#2893055] Until this is resolved, these entities are not loaded.
    if (in_array($entity_type, $this::REQUIRES_SPECIAL_HANDLING)) {
      // @todo Add system to process these entities specifically.
      return FALSE;
    }

    // Load the entity type handler.
    $entity_handler = $this->entityTypeManager->getStorage($entity_type);

    $properties = $this->extractContentProperties($entity_type, $content_data);

    $entity_ids = $entity_handler->loadByProperties($properties);
    return reset($entity_ids);
  }

  /**
   * Identify entity properties from content data.
   *
   * @param $entity_type
   *   The entity type ID.
   * @param array $content_data
   *   The import content structure being parsed for properties.
   *
   * @return array
   *   An array of entity properties compatible with loadByProperties().
   *
   * @see \Drupal\yaml_content\Service\EntityLoadHelper::categorizeAttributes()
   */
  public function extractContentProperties($entity_type, array $content_data) {
    $attributes = $this->categorizeAttributes($entity_type, $content_data);

    return $attributes['property'];
  }

  /**
   * Load an entity type definition.
   *
   * @param $entity_type
   *   The entity type ID.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface|null
   *   The entity type definition or NULL if it could not be loaded.
   */
  protected function getEntityDefinition($entity_type) {
    return $this->entityTypeManager->getDefinition($entity_type);
  }

  /**
   * Group content data attributes by type.
   *
   * @param $entity_type
   *   The entity type ID.
   * @param array $content_data
   *   The import content structure being parsed for properties.
   *
   * @return array
   *   An array of entity content keys grouped by attribute type:
   *     - 'property'
   *     - 'field'
   *     - 'other'
   */
  public function categorizeAttributes($entity_type, array $content_data) {
    // Parse properties for creation and fields for processing.
    $attributes = [
      'property' => [],
      'field' => [],
      'other' => [],
    ];
    foreach ($content_data as $key => $data) {
      $type = $this->identifyAttributeType($entity_type, $key);

      // Process simple values as properties for initial creation.
      if ($type == 'field' && !is_array($data)) {
        $type = 'property';
      }

      $attributes[$type][$key] = $data;
    }

    return $attributes;
  }

  /**
   * Identify data keys as properties, fields, or other.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string $key
   *   The data key being identified.
   *
   * @return string
   *   The type of attribute being identified. This value may be one of:
   *
   *   - property
   *     All values defined as entity keys will be indicated as properties.
   *   - field
   *     Defined fields for the entity type not indicated as entity keys will
   *     be indicated as fields.
   *   - other
   *     All other values will be categorized here.
   *
   * @todo Use entity type information to more accurately identify attributes.
   */
  protected function identifyAttributeType($entity_type, $key) {
    // Load the list of fields defined for the entity type.
    // @todo Add validation that the entity type is listed here.
    $field_list = $this->getEntityFields($entity_type);
    $entity_definition = $this->getEntityDefinition($entity_type);

    if ($entity_definition->hasKey($key)) {
      $attribute_type = 'property';
    }
    elseif (array_key_exists($key, $field_list)) {
      $attribute_type = 'field';
    }
    else {
      $attribute_type = 'other';
    }

    return $attribute_type;
  }

  /**
   * Get the list of fields available for an entity type.
   *
   * @param $entity_type
   *   The entity type ID.
   *
   * @return array
   *   The map of fields mapped for the given entity type.
   */
  protected function getEntityFields($entity_type) {
    $field_map = $this->entityFieldManager->getFieldMap();
    return $field_map[$entity_type];
  }

}
