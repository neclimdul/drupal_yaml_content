<?php

namespace Drupal\Tests\yaml_content\Functional;

use Drupal\Core\Entity\Entity;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests general Node creation functionality.
 *
 * @group yaml_content
 */
class MenuLinkTest extends BrowserTestBase {

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
    'menu_link_content',

    // This module.
    'yaml_content',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Prepare the content loader.
    $this->contentLoader = \Drupal::service('yaml_content.content_loader');
    // Look for content files in the tests directory.
    $this->contentLoader->setContentPath(drupal_get_path('module', 'yaml_content') . '/tests');
  }

  /**
   * Create a basic node.
   *
   * TODO: assert enabled status.
   */
  public function testCanCreateNode() {
    /** @var \Drupal\Core\Entity\Entity[] $entities */
    $entities = $this->contentLoader->loadContent('menu_link.content.yml');

    $this->assertTrue(is_array($entities), 'An array was not returned from loadContent().');
    $this->assertEquals(3, count($entities), 'No entity IDs were returned from loadContent().');

    $this->assertMenuLink($entities[0], 'Home', 'main', 'internal:/home');
    // Check that our extra class information came though.
    $this->assertEquals(
      'menu__link menu__link--home magic-button',
      $entities[0]->get('link')->get(0)->getValue()['options']['attributes']['class'],
      'Class attribute is populated');

    $this->assertMenuLink($entities[1], 'User', 'main','internal:/user');

    $this->assertMenuLink($entities[2], 'Register', 'main','internal:/user/register');

    // Check that our parent was set...
    $this->assertEquals(
      ['value' => 'menu_link_content:' . $entities[1]->uuid()],
      $entities[2]->get('parent')->get(0)->getValue(),
      'Parent attribute is populated');

  }

  protected function assertMenuLink(Entity $entity, $title, $menu_name, $link) {
    $this->assertEquals('menu_link_content', $entity->getEntityTypeId(), 'Entity type should be menu_link_content');
    $this->assertEquals($title, $entity->label(), 'Term name is populated.');
    $this->assertEquals(['value' => $menu_name], $entity->get('menu_name')->get(0)->getValue(), 'Menu name is populated');
    $this->assertEquals($link, $entity->get('link')->get(0)->getValue()['uri'], 'link uri is populated');
  }

}
