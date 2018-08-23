<?php

namespace Drupal\Tests\yaml_content\Functional;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Tests\EntityReference\EntityReferenceTestTrait;
use Drupal\menu_link_content\Entity\MenuLinkContent as MenuLinkContentEntity;
use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests general Node creation functionality.
 *
 * @group yaml_content
 */
class NodeImportTest extends BrowserTestBase {

  use EntityReferenceTestTrait;

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
    'taxonomy',
    'node',
    'field',
    'user',
    'filter',
    'text',
    'menu_link_content',

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

    // Confirm body field content.
    $body_value = $entity->get('body')->get(0)->getValue();
    $this->assertEquals('full_html', $body_value['format'], 'Body field format was not correctly assigned to "full_html".');

    $expected_content = <<<END_OF_VALUE
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed vobis
voluptatum perceptarum recordatio vitam beatam facit, et quidem corpore
perceptarum. Tum Quintus: Est plane, Piso, ut dicis, inquit.</p>
<p>Primum cur ista res digna odio est, nisi quod est turpis? Duo Reges:
constructio interrete. Rhetorice igitur, inquam, nos mavis quam
dialectice disputare?</p>

END_OF_VALUE;

    $this->assertEquals($expected_content, $body_value['value'], 'Body field content was not correctly assigned.');
  }

  public function testFancyNode() {
    $this->setupTaxonomyField();

    /** @var \Drupal\Core\Entity\Entity[] $entities */
    $entities = $this->contentLoader->loadContent('fancy_node.content.yml');

    $this->assertTrue(is_array($entities), 'An array was not returned from loadContent().');
    $this->assertEquals(4, count($entities), 'No entity IDs were returned from loadContent().');

    $tag = $entities[0];
    $node = $entities[1];

    $tags = $node->get('field_tags');
    $this->assertEquals(['target_id' => $tag->id()], $tags->get(0)
      ->getValue(), 'Existing tag is connected.');
    $this->assertNotNull($tags->get(1), 'Missing tag in reference is created.');

    $this->assertEquals('1', $this->loadMenuId($node), 'Menu entry created.');
    $menu1 = MenuLinkContentEntity::load($this->loadMenuId($entities[2]));
    $menu2 = MenuLinkContentEntity::load($this->loadMenuId($entities[3]));

    // Assert node 3's menu entry has node 2's as a parent.
    list(, $uuid) = explode(MenuLinkContent::DERIVATIVE_SEPARATOR, $menu2->get('parent')
      ->get(0)
      ->getValue()['value']);
    $this->assertEquals($menu1->uuid(), $uuid, 'Menu parent correctly set on leaf.');
  }

  /**
   * Helper method to retrieve the menu ID for a node.
   *
   * @param \Drupal\node\Entity\Node $node
   *   A node with a menu entry.
   *
   * @return string
   *   The id of the node menu entry.
   */
  protected function loadMenuId($node) {
    $query = \Drupal::entityQuery('menu_link_content')
      ->condition('link.uri', 'entity:node/' . $node->id())
      ->condition('menu_name', 'main')
      ->sort('id', 'ASC')
      ->range(0, 1);
    $result = $query->execute();
    return reset($result);
  }

  /**
   * Helper to fill out the tags taxonomy field from the standard profile.
   */
  protected function setupTaxonomyField() {
    // Create tags reference field.
    $field_name = 'field_tags';
    $handler_settings = [
      'target_bundles' => [
        'tags' => 'tags',
      ],
      'auto_create' => TRUE,
    ];
    $this->createEntityReferenceField('node', 'article', $field_name, 'Tags', 'taxonomy_term', 'default', $handler_settings, FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    $entity_type_manager = $this->container->get('entity_type.manager');
    $entity_type_manager
      ->getStorage('entity_form_display')
      ->load('node.article.default')
      ->setComponent($field_name, [
        'type' => 'entity_reference_autocomplete_tags',
        'weight' => -4,
      ])
      ->save();
  }

}
