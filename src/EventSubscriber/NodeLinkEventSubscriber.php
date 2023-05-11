<?php

namespace Drupal\yaml_content\EventSubscriber;

use Drupal\node\Entity\Node;
use Drupal\yaml_content\Event\EntityPostSaveEvent;
use Drupal\yaml_content\Event\YamlContentEvents;
use Drupal\yaml_content\Plugin\ProcessingContext;
use Drupal\yaml_content\Plugin\YamlContentProcessManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber handling node menu link creation.
 *
 * Menu links for nodes being imported may be defined following this format:
 *
 * @code
 * - entity: node
 *   type: article
 *   menu:
 *     menu_name: main_menu
 *     parent:
 *      - uuid: 1234
 *      - title: 'Example Title'
 *     title: 'Example Title'
 *     description: 'Example Description'
 *     weight: 0
 * @code
 *
 * @code
 * - entity: node
 *   type: article
 *   menu:
 *   - "#process":
 *     callback: "menu_link_content"
 *     args:
 *     - menu_name: "main"
 *       title: "Basic Article"
 *       description: "Basic Article"
 *       weight: 0
 * @code
 *
 * @see \Drupal\yaml_content\Event\YamlContentEvents::ENTITY_POST_SAVE
 * @see \Drupal\yaml_content\Event\EntityPostSaveEvent
 *
 * @todo Determine a flexible method for looking up parent menu links.
 *
 * @package Drupal\yaml_content\EventSubscriber
 */
class NodeLinkEventSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\yaml_content\Plugin\YamlContentProcessManager
   */
  private $processManager;

  public function __construct(YamlContentProcessManager $processManager) {
    $this->processManager = $processManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[YamlContentEvents::ENTITY_POST_SAVE][] = ['createMenuLinks'];

    return $events;
  }

  /**
   * Create menu links if any are configured on the imported Node entity.
   *
   * This function utilizes the existing menu_ui module functions to load menu
   * link defaults and save a menu link entity using configured values if they
   * are available within the defined import content.
   *
   * @todo Should we care about updating an existing node?
   *
   * @param \Drupal\yaml_content\Event\EntityPostSaveEvent $event
   *   The entity post save event to be checked for menu links to create.
   */
  public function createMenuLinks(EntityPostSaveEvent $event) {
    // This is only applicable to Nodes.
    if ($event->getEntity()->getEntityTypeId() != 'node') {
      return;
    }

    // Handle any configured menu links.
    $import_content = $event->getContentData();
    if (isset($import_content['menu'])) {
      $context = new ProcessingContext();
      $context->setContentLoader($event->getContentLoader());
      /** @var \Drupal\node\Entity\Node $node */
      $node = $event->getEntity();
      \Drupal::logger('yaml_content')
        ->info('Creating menu node for %title.', ['%title' => $node->label()]);

      $this->processField($import_content['menu'], $context, $node);
    }
  }

  /**
   * Helper method to process field data into menu link.
   *
   * @param array $field_data
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  private function processField(array &$field_data, ProcessingContext $context, Node $node = NULL) {
    // If its a list, flatten it to a single value.
    if (isset($field_data[0])) {
      $field_data = $field_data[0];
    }

    // Push data into process structure so we can process it consistently.
    if (isset($field_data['#process'])) {
      $menu_data = $field_data['#process']['args'][0];
    }
    else {
      $menu_data = $field_data;
    }

    // Populate node link and langcode.
    if (isset($node)) {
      $menu_data = [
          'link' => [['uri' => 'entity:node/' . $node->id()]],
          'langcode' => $node->language()->getId(),
        ] + $menu_data;
    }

    // If there is a parent, process it first so we have the value.
    if (isset($menu_data['parent'])) {
      $this->processField($menu_data['parent'], $context);
    }

    $field_data = [
      '#process' => [
        'callback' => 'menu_link_content',
        'args' => [
          $menu_data,
        ]
      ]
    ];

    $this->processManager->preprocessFieldData($context, $field_data);
  }

}
