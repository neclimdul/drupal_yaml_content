<?php

namespace Drupal\yaml_content\Event;

use Drupal\yaml_content\ContentLoader\ContentLoaderInterface;

/**
 * Wraps a yaml content data import event for event listeners.
 */
class DataImportEvent extends EventBase {

  /**
   * The parsed content from the file used to create the entity.
   *
   * @var array
   */
  protected $contentData;

  /**
   * The original data parsed from the content file prior to alteration.
   *
   * @var array
   */
  protected $originalData;

  /**
   * A flag to track if content data has been altered.
   *
   * @var bool
   */
  protected $isAltered;

  /**
   * Constructs a yaml content entity pre-save event object.
   *
   * @param \Drupal\yaml_content\ContentLoader\ContentLoaderInterface $loader
   *   The active Content Loader that triggered the event.
   * @param array $content_data
   *   The parsed content loaded from the content file to be loaded into
   *   the entity field.
   */
  public function __construct(ContentLoaderInterface $loader, array &$content_data) {
    parent::__construct($loader);

    $this->contentData = &$content_data;
    $this->originalData = $content_data;

    $this->isAltered = FALSE;
  }

  /**
   * Gets the parsed content to populate into the field.
   *
   * @return array
   *   The parsed content loaded from the content file to be loaded into
   *   the entity field.
   */
  public function getContentData() {
    return $this->contentData;
  }

  /**
   * Update the content data being imported.
   *
   * @param array $altered_data
   *   The altered form of the content data that should be imported.
   *
   * @return $this
   */
  public function alterContentData(array $altered_data) {
    $this->contentData = $altered_data;

    // Ensure we flag that the data has been altered.
    $this->isAltered = TRUE;

    return $this;
  }

}
