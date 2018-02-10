<?php

namespace Drupal\Tests\yaml_content\Kernel;

use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests general Node creation functionality.
 *
 * @group yaml_content
 */
class NodeImportTest extends BrowserTestBase {

  /**
   * Directory where test files are to be created.
   *
   * @var \org\bovigo\vfs\vfsStreamContent $contentDirectory
   */
  protected $contentDirectory;

  /**
   * Prepared Content Loader service for testing.
   *
   * @var \Drupal\yaml_content\ContentLoader\ContentLoader $contentLoader
   */
  protected $contentLoader;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    // Core dependencies.
    'node',
    'field',
    'user',
    'filter',
    'text',

    // This module.
    'yaml_content',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create our article content type.
    $this->createContentType([
        'type' => 'article',
        'name' => 'Article',
      ]);

    // Prepare the content loader.
    $this->contentLoader = \Drupal::service('yaml_content.content_loader');
    // Look for content files in the tests directory.
    $this->contentLoader->setContentPath(drupal_get_path('module', 'yaml_content') . '/tests');
  }

  /**
   * Create a basic node.
   */
  public function testCanCreateNode() {
    $entities = $this->contentLoader->loadContent('basic_node.content.yml');

    $this->assertTrue(is_array($entities), 'An array was not returned from loadContent().');
    $this->assertEquals(1, count($entities), 'No entity IDs were returned from loadContent().');

    /** @var \Drupal\Core\Entity\Entity $entity */
    $entity = reset($entities);

    $this->assertEquals('node', $entity->getEntityTypeId(), 'The entity type created was not a Node.');
    $this->assertEquals('article', $entity->bundle(), 'An article Node was not correctly created.');
    $this->assertEquals('Basic Article', $entity->label(), 'An article\'s title was not correctly assigned.');

    return $entity;
  }

}
