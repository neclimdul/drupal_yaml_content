<?php

namespace Drupal\Tests\yaml_content\Unit;

use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use Drupal\yaml_content\ContentLoader\ContentLoader;
use org\bovigo\vfs\vfsStreamWrapper;

/**
 * @coversDefaultClass \Drupal\yaml_content\ContentLoader\ContentLoader
 * @group yaml_content
 */
class ContentLoaderTest extends UnitTestCase {

  /**
   * The mocked ConfigEntityStorage service.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  protected $configStorage;

  /**
   * The mocked EntityTypeManager service.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityTypeManager;

  /**
   * The mocked ModuleHandler service.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject
   */
  protected $moduleHandler;

  /**
   * The prepared root directory of the virtual file system.
   *
   * @var \org\bovigo\vfs\vfsStreamDirectory
   */
  protected $root;

  /**
   * A prepared ContentLoader object for testing.
   *
   * @var \Drupal\yaml_content\ContentLoader\ContentLoader
   */
  protected $contentLoader;

  /**
   * Prepare an abstract Entity mock object.
   *
   * @return \PHPUnit_Framework_MockObject_MockObject
   *   A mock entity object.
   *
   * @see \Drupal\Tests\views\Unit\Plugin\area\EntityTest::setUp()
   */
  protected function getEntityMock() {
    $mock_entity = $this->getMockForAbstractClass('Drupal\Core\Entity\Entity', [], '', FALSE, TRUE, TRUE, ['bundle']);
    $mock_entity->expects($this->any())
      ->method('bundle')
      ->will($this->returnValue('test_bundle'));

    return $mock_entity;
  }

  /**
   * Prepare a mock EntityTypeManager service object for testing.
   *
   * @return \PHPUnit_Framework_MockObject_MockObject
   *   A mock EntityTypeManager service.
   */
  protected function getEntityTypeManagerMock() {
    // Mock the entity storage service.
    $this->configStorage = $this->getMockBuilder('\Drupal\Core\Config\Entity\ConfigEntityStorage')
      ->disableOriginalConstructor()
      ->getMock();

    // Mock the entity type manager service.
    $this->entityTypeManager = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeManager')
      ->disableOriginalConstructor()
      ->getMock();

    // Stub the return for the entity storage handler.
    $this->entityTypeManager
      ->method('getStorage')
      ->willReturn($this->configStorage);

    return $this->entityTypeManager;
  }

  /**
   * Prepare a mock ModuleHandler service object for testing.
   *
   * @return \PHPUnit_Framework_MockObject_MockObject
   *   A mock ModuleHandler service.
   */
  protected function getModuleHandlerMock() {
    $this->moduleHandler = $this->getMockBuilder('Drupal\Core\Extension\ModuleHandlerInterface')
      ->disableOriginalConstructor()
      ->getMock();

    return $this->moduleHandler;
  }

  /**
   * Create a test file with specified contents for testing.
   *
   * @param string $filename
   *   The name of the test file to be created.
   * @param string $contents
   *   The contents to populate into the test file.
   *
   * @return $this
   */
  protected function createContentTestFile($filename, $contents) {
    vfsStream::newFile($filename)
      ->withContent($contents)
      ->at($this->root->getChild('content'));

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Prepare the directory structure.
    $this->root = vfsStream::setup('root');
    vfsStream::newDirectory('content')
      ->at($this->root);

    // Mock the EntityTypeManager.
    $this->entityTypeManager = $this->getEntityTypeManagerMock();
    // Mock the ModuleHandler.
    $this->moduleHandler = $this->getModuleHandlerMock();

    $this->contentLoader = new ContentLoader($this->entityTypeManager, $this->moduleHandler);
  }

  /**
   * Test the setContentPath() method.
   *
   * @see \Drupal\yaml_content\ContentLoader\ContentLoader::setContentPath()
   */
  public function testSetPath() {
    $this->contentLoader->setContentPath($this->root->url());
    $this->assertAttributeEquals($this->root->url(), 'path', $this->contentLoader);
  }

  /**
   * Test general behavior of the parseContent() method.
   *
   * @see \Drupal\yaml_content\ContentLoader\ContentLoader::parseContent()
   */
  public function testParseContent() {
    // @todo Test if $contentPath is not set
    // @todo Confirm `$path/content/$content_file` is loaded
    // @todo Confirm `/$content_file` is not loaded
    // @todo Handle parse failure
    // @todo Test no array at top level of content
    // @todo Confirm array structure loaded

    $this->markTestIncomplete();
  }

  /**
   * Tests behavior when a content file is unavailable.
   *
   * @expectedException \PHPUnit_Framework_Error_Warning
   */
  public function testMissingContentFile() {
    $test_file = 'missing.content.yml';

    // Confirm the file is not actually present.
    $this->assertFalse($this->root->hasChild('content/missing.content.yml'));

    // Prepare and parse the missing content file.
    $this->contentLoader->setContentPath($this->root->url());
    $parsed_content = $this->contentLoader->parseContent($test_file);
  }

  /**
   * Tests the correct return value when parsing an empty file.
   *
   * When parsing an empty file an empty array should be returned.
   */
  public function testEmptyContentFile() {
    // Prepare an empty content file for parsing.
    $test_file = 'emptyFile.content.yml';
    $this->createContentTestFile($test_file, '');

    // Prepare and parse the empty content file.
    $this->contentLoader->setContentPath($this->root->url());
    $parsed_content = $this->contentLoader->parseContent($test_file);

    // Confirm an empty array was returned.
    $this->assertArrayEquals([], $parsed_content, 'Empty content files return an empty array.');
  }

  public function testLoadContent() {
    $this->markTestIncomplete();
  }

  /**
   * Setup test fixtures for `buildEntity()` tests.
   */
  public function setupBuildEntityTests() {
    // Methods to be stubbed in the mock.
    // All methods except the ones excluded in the array below will be stubbed.
    $stub_methods = array_diff(get_class_methods(ContentLoader::class), [
      'buildEntity',
      'categorizeEntityFieldsAndProperties',
    ]);

    $this->contentLoader = $this->getMockBuilder(ContentLoader::class)
      ->setConstructorArgs([
        $this->getEntityTypeManagerMock(),
        $this->getModuleHandlerMock(),
      ])
      ->setMethods($stub_methods)
      ->getMock();
  }

  /**
   * Tests general functionality of the `buildEntity()` method.
   *
   * @dataProvider contentDataProvider
   *
   * @see \Drupal\Tests\yaml_content\Unit\ContentLoaderTest::setupBuildEntityTests()
   */
  public function testBuildEntity($entity_type, $test_content) {
    $this->setupBuildEntityTests();

    $this->markTestIncomplete();
  }

  /**
   * Tests that entityExists() is never called if the flag is disabled.
   *
   * @dataProvider contentDataProvider
   *
   * @see \Drupal\Tests\yaml_content\Unit\ContentLoaderTest::setupBuildEntityTests()
   */
  public function testBuildEntityExistenceCheckDoesntCallEntityExists($entity_type, $test_content) {
    $this->setupBuildEntityTests();

    // Confirm the scenario actually ran as expected.
    $this->contentLoader
      ->expects($this->once())
      ->method('existenceCheck')
      ->willReturn(FALSE);

    // Confirm `entityExists()` was never called.
    $this->contentLoader
      ->expects($this->never())
      ->method('entityExists');

    $this->contentLoader->buildEntity($entity_type, $test_content);
  }

  /**
   * Tests that entityExists() is correctly called if the flag is enabled.
   *
   * @dataProvider contentDataProvider
   *
   * @see \Drupal\Tests\yaml_content\Unit\ContentLoaderTest::setupBuildEntityTests()
   */
  public function testBuildEntityExistenceCheckCallsEntityExists($entity_type, $test_content) {
    $this->setupBuildEntityTests();

    $this->contentLoader
      ->setExistenceCheck(TRUE);

    $this->configStorage
      ->expects($this->once())
      ->method('entityExists');

    $this->contentLoader->buildEntity($entity_type, $test_content);
  }

  /**
   * Data provider function to test various content scenarios.
   *
   * @return array
   *   An array of content testing arguments:
   *   - string Entity Type
   *   - array Content data structure
   */
  public function contentDataProvider() {
    $test_content['basic_node'] = [
      'entity' => 'node',
      'status' => 1,
      'title' => 'Test Title',
      'field_rich_text' => [
        'value' => 'Lorem Ipsum',
        'format' => 'full_html',
      ],
      'field_simple_value' => [
        'value' => 'simple',
      ],
    ];

    return [
      ['node', $test_content['basic_node']],
    ];
  }

  public function testPopulateField() {
    $this->markTestIncomplete();
  }

  public function testPreprocessFieldData() {
    $this->markTestIncomplete();
  }

  public function testReferenceEntityLoad() {
    $this->markTestIncomplete();
  }

  public function testFileEntityLoad() {
    $this->markTestIncomplete();
  }

  public function testEntityExists() {
    $this->markTestIncomplete();
  }

}
