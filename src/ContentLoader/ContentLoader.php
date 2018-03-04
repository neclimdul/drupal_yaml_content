<?php

namespace Drupal\yaml_content\ContentLoader;

use Drupal\Core\Config\ConfigValueException;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Field\FieldException;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Parser;
use Drupal\yaml_content\Event\YamlContentEvents;
use Drupal\yaml_content\Event\ContentParsedEvent;
use Drupal\yaml_content\Event\EntityPreSaveEvent;
use Drupal\yaml_content\Event\EntityPostSaveEvent;
use Drupal\yaml_content\Event\FieldImportEvent;
use Drupal\yaml_content\Event\EntityImportEvent;
use Drupal\Component\Render\PlainTextOutput;

/**
 * ContentLoader class for parsing and importing YAML content.
 */
class ContentLoader implements ContentLoaderInterface {

  /**
   * Dependency injection container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface $container
   */
  protected $container;

  /**
   * The entity type manager interface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Helper service to load entities.
   *
   * @var \Drupal\yaml_content\Service\EntityLoadHelper
   */
  protected $entityLoadHelper;

  /**
   * The module handler interface for invoking any hooks.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Event dispatcher service to report events throughout the loading process.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected $dispatcher;

  /**
   * YAML parser.
   *
   * @var \Symfony\Component\Yaml\Parser
   */
  protected $parser;

  /**
   * The parsed content.
   *
   * @var mixed
   */
  protected $parsedContent;

  /**
   * Boolean value of whether other not to update existing content.
   *
   * @var bool
   */
  protected $existenceCheck = FALSE;

  /**
   * The directory path where content and assets may be found for import.
   *
   * @var string
   */
  protected $path;

  /**
   * The file path for the content file currently being loaded.
   *
   * @var string
   */
  protected $contentFile;

  /**
   * ContentLoader constructor.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The dependency injection container to laod dependent services.
   */
  public function __construct(ContainerInterface $container) {
    $this->container = $container;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container
    );
  }

  /**
   * Get the YAML parser service.
   *
   * @return \Symfony\Component\Yaml\Parser
   *   The YAML parser service.
   */
  protected function getParser() {
    if (!isset($this->parser)) {
      $this->parser = new Parser();
    }

    return $this->parser;
  }

  /**
   * Get the EntityTypeManager service.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The EntityTypeManager service.
   */
  public function getEntityTypeManager() {
    // Lazy load the entity type manager service.
    if (!isset($this->entityTypeManager)) {
      $this->entityTypeManager = $this->container
        ->get('entity_type.manager');
    }

    return $this->entityTypeManager;
  }

  /**
   * Get the EntityLoadHelper service.
   *
   * @return \Drupal\yaml_content\Service\EntityLoadHelper
   *   The EntityLoadHelper service.
   */
  protected function getEntityLoadHelper() {
    // Lazy load the entity load helper service.
    if (!isset($this->entityLoadHelper)) {
      $this->entityLoadHelper = $this->container
        ->get('yaml_content.entity_load_helper');
    }

    return $this->entityLoadHelper;
  }

  /**
   * Get the module handler service.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler service.
   */
  protected function getModuleHandler() {
    // Lazy load the module handler service.
    if (!isset($this->moduleHandler)) {
      $this->moduleHandler = $this->container
        ->get('module_handler');
    }

    return $this->moduleHandler;
  }

  /**
   * Get the event dispatcher service.
   *
   * @return \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   *   The event dispatcher service.
   */
  protected function getEventDispatcher() {
    // Lazy load the event dispatcher service.
    if (!isset($this->dispatcher)) {
      $this->dispatcher = $this->container
        ->get('event_dispatcher');
    }

    return $this->dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function setContentPath($path) {
    $this->path = $path;
  }

  /**
   * Returns whether or not the system should check for previous demo content.
   *
   * @return bool
   *   The true/false value of existence check.
   */
  public function existenceCheck() {
    return $this->existenceCheck;
  }

  /**
   * Set the whether or not the system should check for previous demo content.
   *
   * @param bool $existence_check
   *   The true/false value of existence check. Defaults to true if no value
   *   is provided.
   *
   * @return $this
   */
  public function setExistenceCheck($existence_check = TRUE) {
    $this->existenceCheck = $existence_check;

    return $this;
  }

  /**
   * Load an entity type definition.
   *
   * @param string $entity_type
   *   The entity type id of the definition to load.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface|null
   *   The entity type definition or NULL if the definition could not be loaded.
   */
  protected function getEntityTypeDefinition($entity_type) {
    return $this->getEntityTypeManager()->getDefinition($entity_type);
  }

  /**
   * Load an entity storage handler.
   *
   * @param string $entity_type
   *   The entity type id of the definition to load.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The storage handler service for the entity type.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getEntityStorage($entity_type) {
    return $this->getEntityTypeManager()->getStorage($entity_type);
  }

  /**
   * {@inheritdoc}
   */
  public function parseContent($content_file) {
    $file = $this->path . '/content/' . $content_file;
    // @todo Handle missing files gracefully.
    $this->parsedContent = $this->getParser()->parse(file_get_contents($file));

    // Never leave this as null, even on a failed parsing process.
    // @todo Output a warning for empty content files or failed parsing.
    $this->parsedContent = isset($this->parsedContent) ? $this->parsedContent : [];

    // Dispatch the event notification.
    $content_parsed_event = new ContentParsedEvent($this, $this->contentFile, $this->parsedContent);
    $this->getEventDispatcher()->dispatch(YamlContentEvents::CONTENT_PARSED, $content_parsed_event);

    return $this->parsedContent;
  }

  /**
   * {@inheritdoc}
   */
  public function loadContent($content_file, $skip_existence_check = TRUE) {
    $this->setExistenceCheck($skip_existence_check);
    $content_data = $this->parseContent($content_file);

    $loaded_content = [];

    // Create each entity defined in the yml content.
    foreach ($content_data as $content_item) {
      $entity = $this->buildEntity($content_item['entity'], $content_item);

      // Dispatch the pre-save event.
      $entity_pre_save_event = new EntityPreSaveEvent($this, $entity, $content_item);
      $this->getEventDispatcher()->dispatch(YamlContentEvents::ENTITY_PRE_SAVE, $entity_pre_save_event);

      $entity->save();

      // Dispatch the post-save event.
      $entity_post_save_event = new EntityPostSaveEvent($this, $entity, $content_item);
      $this->getEventDispatcher()->dispatch(YamlContentEvents::ENTITY_POST_SAVE, $entity_post_save_event);

      $loaded_content[] = $entity;
    }

    // Trigger a hook for post-import processing.
    $this->getModuleHandler()->invokeAll('yaml_content_post_import',
      [$content_file, &$loaded_content, $content_data]);

    return $loaded_content;
  }

  /**
   * Build an entity from the provided content data.
   *
   * @param string $entity_type
   *   The entity type.
   * @param array $content_data
   *   The array of content data to be parsed.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The created entity from the parsed content data.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function buildEntity($entity_type, array $content_data) {
    // Load entity type definition.
    $entity_definition = $this->getEntityTypeDefinition($entity_type);

    // Dispatch the entity import event.
    $entity_import_event = new EntityImportEvent($this, $entity_definition, $content_data);
    $this->getEventDispatcher()->dispatch(YamlContentEvents::IMPORT_ENTITY, $entity_import_event);

    // Parse properties for creation and fields for processing.
    $attributes = $this->getContentAttributes($entity_type, $content_data);

    // If it is a 'user' entity, append a timestamp to make the username unique.
    // @todo Move this into an entity-specific processor.
    if ($entity_type == 'user' && isset($attributes['property']['name'][0]['value'])) {
      $attributes['property']['name'][0]['value'] .= '_' . time();
    }

    // Create our entity with basic data.
    $entity = $this->createEntity($entity_type, $content_data);

    // Populate fields.
    if ($entity instanceof FieldableEntityInterface) {
      $this->populateEntityFields($entity, $attributes['field']);
    }

    return $entity;
  }

  /**
   * Get organized content attributes from import data.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param array $content_data
   *   The array of content data to be parsed.
   *
   * @return array
   *   Content data grouped by type.
   *
   * @see \Drupal\yaml_content\Service\EntityLoadHelper::categorizeAttributes()
   */
  protected function getContentAttributes($entity_type, array $content_data) {
    // Parse properties for creation and fields for processing.
    $attributes = $this->getEntityLoadHelper()
      ->categorizeAttributes($entity_type, $content_data);

    return $attributes;
  }

  /**
   * Create the entity based on basic properties.
   *
   * If existence checking is enabled, we'll attempt to load an existing
   * entity matched on the simple properties before creating a new one.
   *
   * @param string $entity_type
   *   The entity type.
   * @param array $content_data
   *   The array of content data to be parsed.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A loaded matching entity if existence checking is enabled and a matching
   *   entity was found, or a new one stubbed from simple properties otherwise.
   *
   * @see \Drupal\yaml_content\ContentLoader\ContentLoader::existenceCheck()
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function createEntity($entity_type, array $content_data) {
    // If existence checking is enabled, attempt to load the entity first.
    if ($this->existenceCheck()) {
      $entity = $this->entityExists($entity_type, $content_data);
    }

    // If the entity isn't loaded we'll stub it out.
    if (empty($entity)) {
      // Load entity type handler.
      $entity_handler = $this->getEntityStorage($entity_type);

      // Identify the content properties for the entity.
      $attributes = $this->getContentAttributes($entity_type, $content_data);

      $entity = $entity_handler->create($attributes['property']);
    }

    return $entity;
  }

  /**
   * Populate entity field data into an entity object.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity object being populated for import.
   * @param array $fields
   *   Content import data for entity fields keyed by field name.
   *
   * @throws \Drupal\Core\Field\FieldException
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @todo Add events for overall pre/post field import process.
   * @todo Throw more specific exceptions.
   */
  public function populateEntityFields(FieldableEntityInterface $entity, array $fields) {
    foreach ($fields as $field_name => $field_data) {
      try {
        if ($entity->hasField($field_name)) {
          $field_instance = $entity->get($field_name);

          // Dispatch field import event prior to populating fields.
          $field_import_event = new FieldImportEvent($this, $entity, $field_instance, $field_data);
          $this->getEventDispatcher()->dispatch(YamlContentEvents::IMPORT_FIELD, $field_import_event);

          $this->populateField($field_instance, $field_data);
        }
        else {
          throw new FieldException('Undefined field: ' . $field_name);
        }
      }
      catch (MissingDataException $exception) {
        watchdog_exception('yaml_content', $exception);
      }
      catch (ConfigValueException $exception) {
        watchdog_exception('yaml_content', $exception);
      }
    }
  }

  /**
   * Populate field content into the provided field.
   *
   * @param object $field
   *   The entity field object.
   * @param array $field_data
   *   The field data.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @todo Handle field data types more dynamically with typed data.
   */
  public function populateField($field, array &$field_data) {
    // Get the field cardinality to determine whether or not a value should be
    // 'set' or 'appended' to.
    $cardinality = $field->getFieldDefinition()
      ->getFieldStorageDefinition()
      ->getCardinality();

    // Gets the count of the field data array.
    $field_data_count = count($field_data);

    // If the cardinality is 0, throw an exception.
    if (!$cardinality) {
      throw new \InvalidArgumentException("'{$field->getName()}' cannot hold any values.");
    }

    // If the number of field content is greater than allowed, throw exception.
    if ($cardinality > 0 && $field_data_count > $cardinality) {
      throw new \InvalidArgumentException("'{$field->getname()}' cannot hold more than $cardinality values. $field_data_count values were parsed from the YAML file.");
    }

    // If we're updating content in-place, empty the field before population.
    if ($this->existenceCheck() && !$field->isEmpty()) {
      // Trigger delete callbacks on each field item.
      $field->delete();

      // Special handling for non-reusable entity reference values.
      if ($field instanceof EntityReferenceFieldItemList) {
        // Test if this is a paragraph field.
        $target_type = $field->getFieldDefinition()->getSetting('target_type');
        if ($target_type == 'paragraph') {
          /** @var \Drupal\Core\Entity\EntityInterface[] $entities */
          $entities = $field->referencedEntities();
          foreach ($entities as $entity) {
            $entity->delete();
          }
        }
      }

      // Empty out the field's list of items.
      $field->setValue([]);
    }

    // Iterate over each field data value and process it.
    foreach ($field_data as &$item_data) {
      // Preprocess the field data.
      $this->preprocessFieldData($field, $item_data);

      // Check if the field is a reference field. If so, build the entity ref.
      $is_reference = isset($item_data['entity']);
      if ($is_reference) {
        // Build the reference entity.
        $field_item = $this->buildEntity($item_data['entity'], $item_data);
      }
      else {
        $field_item = $item_data;
      }

      // If the cardinality is set to 1, set the field value directly.
      if ($cardinality == 1) {
        $field->setValue($field_item);

        // @todo Warn if additional item data is available for population.
        break;
      }
      else {
        // Otherwise, append the item to the multi-value field.
        $field->appendItem($field_item);
      }
    }
  }

  /**
   * Run any designated preprocessors on the provided field data.
   *
   * Preprocessors are expected to be provided in the following format:
   *
   * ```yaml
   *   '#process':
   *     callback: '<callback string>'
   *     args:
   *       - <callback argument 1>
   *       - <callback argument 2>
   *       - <...>
   * ```
   *
   * The callback function receives the following arguments:
   *
   *   - `$field`
   *   - `$field_data`
   *   - <callback argument 1>
   *   - <callback argument 2>
   *   - <...>
   *
   * The `$field_data` array is passed by reference and may be modified directly
   * by the callback implementation.
   *
   * @param object $field
   *   The entity field object.
   * @param array|string $field_data
   *   The field data.
   */
  protected function preprocessFieldData($field, &$field_data) {
    // Break here if the field data is not an array since there can be no
    // processing instructions included.
    if (!is_array($field_data)) {
      return;
    }

    // Check for a callback processor defined at the value level.
    if (isset($field_data['#process']) && isset($field_data['#process']['callback'])) {
      $callback_type = $field_data['#process']['callback'];
      $process_method = $callback_type . 'EntityLoad';
      if (isset($field_data['#process']['dependency'])) {
        $dependency = $field_data['#process']['dependency'];
        $this->processDependency($dependency);
      }
      // Check to see if this class has a method in the format of
      // '{fieldType}EntityLoad'.
      if (method_exists($this, $process_method)) {
        // Append callback arguments to field object and value data.
        $args = array_merge([$field, &$field_data], isset($field_data['#process']['args']) ? $field_data['#process']['args'] : []);
        // Pass in the arguments and call the method.
        call_user_func_array([$this, $process_method], $args);
      }
      // If the method does not exist, throw an exception.
      else {
        throw new ConfigValueException('Unknown type specified: ' . $callback_type);
      }
    }
  }

  /**
   * Process a file flagged as a dependency.
   *
   * @param string $dependency
   *   The dependent file that needs to be imported as well.
   */
  protected function processDependency($dependency) {
    $sub_loader = self::create($this->container);
    $sub_loader->setContentPath($this->path);
    $sub_loader->loadContent($dependency, $this->existenceCheck());
  }

  /**
   * Processor function for querying and loading a referenced entity.
   *
   * @param object $field
   *   The entity field object.
   * @param array $field_data
   *   The field data.
   * @param string $entity_type
   *   The entity type.
   * @param array $filter_params
   *   The filters for the query conditions.
   *
   * @return array|int
   *   The entity id.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *   Error for missing data.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *
   * @see ContentLoader::preprocessFieldData()
   */
  protected function referenceEntityLoad($field, array &$field_data, $entity_type, array $filter_params) {
    // Use query factory to create a query object for the node of entity_type.
    $query = \Drupal::entityQuery($entity_type);

    // Apply filter parameters.
    foreach ($filter_params as $property => $value) {
      $query->condition($property, $value);
    }

    $entity_ids = $query->execute();

    if (empty($entity_ids)) {
      $entity = $this->getEntityStorage($entity_type)->create($filter_params);
      $entity_ids = [$entity->id()];
    }

    if (empty($entity_ids)) {
      return $this->throwParamError('Unable to find referenced content', $entity_type, $filter_params);
    }

    // Use the first match for our value.
    $field_data['target_id'] = array_shift($entity_ids);

    // Remove process data to avoid issues when setting the value.
    unset($field_data['#process']);

    return $entity_ids;
  }

  /**
   * Processor function for processing and loading a file attachment.
   *
   * @param object $field
   *   The entity field object.
   * @param array $field_data
   *   The field data.
   * @param string $entity_type
   *   The entity type.
   * @param array $filter_params
   *   The filters for the query conditions.
   *
   * @return array|int
   *   The entity id.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   *   Error for missing data.
   *
   * @see ContentLoader::preprocessFieldData()
   */
  protected function fileEntityLoad($field, array &$field_data, $entity_type, array $filter_params) {
    $filename = $filter_params['filename'];
    $directory = '/data_files/';
    // If the entity type is an image, look in to the /images directory.
    if ($entity_type == 'image') {
      $directory = '/images/';
    }
    $output = file_get_contents($this->path . $directory . $filename);
    if ($output !== FALSE) {
      $destination = 'public://';
      // Look-up the field's directory configuation.
      if ($directory = $field->getSetting('file_directory')) {
        $directory = trim($directory, '/');
        $directory = PlainTextOutput::renderFromHtml(\Drupal::token()->replace($directory));
        if ($directory) {
          $destination .= $directory . '/';
        }
      }

      // Create the destination directory if it does not already exist.
      file_prepare_directory($destination, FILE_CREATE_DIRECTORY);

      // Save the file data or return an existing file.
      $file = file_save_data($output, $destination . $filename, FILE_EXISTS_REPLACE);

      // Use the newly created file id as the value.
      $field_data['target_id'] = $file->id();

      // Remove process data to avoid issues when setting the value.
      unset($field_data['#process']);

      return $file->id();
    }
    else {
      return $this->throwParamError('Unable to process file content', $entity_type, $filter_params);
    }
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
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *
   * @todo Potentially move this into a separate helper class.
   */
  public function entityExists($entity_type, array $content_data) {
    // Some entities require special handling to determine if it exists.
    switch ($entity_type) {
      // Always create new paragraphs since they're not reusable.
      // @todo Should new revisions be incorporated here?
      case 'paragraph':
        break;

      case 'media':
        // @todo Add special handling to check file name or path.
        break;

      default:
        // Load entity type handler.
        $entity_handler = $this->getEntityStorage($entity_type);

        // @todo Load this through dependency injection instead.
        $query = \Drupal::entityQuery($entity_type);
        foreach ($content_data as $key => $value) {
          if ($key != 'entity' && !is_array($value)) {
            $query->condition($key, $value);
          }
        }
        $entity_ids = $query->execute();

        if ($entity_ids) {
          $entity_id = array_shift($entity_ids);
          $entity = $entity_handler->load($entity_id);
        }
    }

    return isset($entity) ? $entity : FALSE;
  }

  /**
   * Prepare an error message and throw error.
   *
   * @param string $error_message
   *   The error message to display.
   * @param string $entity_type
   *   The entity type.
   * @param array $filter_params
   *   The filters for the query conditions.
   */
  protected function throwParamError($error_message, $entity_type, array $filter_params) {
    // Build parameter output description for error message.
    $error_params = [
      '[',
      '  "entity_type" => ' . $entity_type . ',',
    ];
    foreach ($filter_params as $key => $value) {
      $error_params[] = sprintf("  '%s' => '%s',", $key, $value);
    }
    $error_params[] = ']';
    $param_output = implode("\n", $error_params);

    throw new MissingDataException(__CLASS__ . ': ' . $error_message . ': ' . $param_output);
  }

}
