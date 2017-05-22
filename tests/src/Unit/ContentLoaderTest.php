<?php

namespace Drupal\Tests\yaml_content\Unit;

use Drupal\Tests\UnitTestCase;
use org\bovigo\vfs\vfsStream;
use Drupal\yaml_content\ContentLoader\ContentLoader;

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
   *
   * @todo Extend to accept configuration for available and missing fields.
   */
  protected function getEntityMock(array $defined_fields = []) {
    $mock_entity = $this->getMockForAbstractClass(
      'Drupal\Core\Entity\ContentEntityInterface',
      [],
      '',
      FALSE,
      TRUE,
      TRUE,
      ['bundle']);

    // Mock the bundle() method.
    $mock_entity->expects($this->any())
      ->method('bundle')
      ->will($this->returnValue('test_bundle'));

    // Mock the hasField() method.
    $mock_entity->expects($this->any())
      ->method('hasField')
      ->willReturn(TRUE);

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
   * Confirm the default value for existenceCheck().
   *
   * @return \Drupal\yaml_content\ContentLoader\ContentLoader
   *   The ContentLoader service being tested.
   */
  public function testExistenceCheckDefault() {
    $this->assertFalse($this->contentLoader->existenceCheck());

    return $this->contentLoader;
  }

  /**
   * Confirm the existence check value can be enabled.
   *
   * @param \Drupal\yaml_content\ContentLoader\ContentLoader $content_loader
   *   The ContentLoader service being tested.
   *
   * @return \Drupal\yaml_content\ContentLoader\ContentLoader
   *   The ContentLoader service being tested.
   *
   * @depends testExistenceCheckDefault
   */
  public function testEnableExistenceCheck(ContentLoader $content_loader) {
    $content_loader->setExistenceCheck();

    $this->assertTrue($content_loader->existenceCheck());

    return $content_loader;
  }

  /**
   * Confirm the existence check value can be disabled.
   *
   * @param \Drupal\yaml_content\ContentLoader\ContentLoader $content_loader
   *   The ContentLoader service being tested.
   *
   * @depends testEnableExistenceCheck
   */
  public function testDisableExistenceCheck(ContentLoader $content_loader) {
    $content_loader->setExistenceCheck(FALSE);

    $this->assertFalse($content_loader->existenceCheck());
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

    // Prepare the path for the missing content file.
    $this->contentLoader->setContentPath($this->root->url());

    // Parse the test file expecting an error for the missing file.
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
      'setExistenceCheck',
      'existenceCheck',
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

    // Disable existence checking.
    $this->contentLoader->setExistenceCheck(FALSE);

    // Prepare a mock entity to be created with stubbed methods.
    // @todo Expand this or add additional tests for missing field assignments.
    // @todo Expand this to confirm the correct field list is being passed to populateField().
    $entity_mock = $this->getEntityMock();
    $entity_mock->method('hasField')
      ->willReturn(TRUE);

    // Return the mocked entity when it is created.
    $this->configStorage
      ->method('create')
      ->willReturn($entity_mock);

    // Expect some fields to be populated.
    // @todo Expand logic here to more flexibly test multiple test data scenarios.
    // @todo Expand logic to confirm argument types submitted.
    $this->contentLoader->expects($this->atLeastOnce())
      ->method('populateField');

    $this->contentLoader->buildEntity($entity_type, $test_content);
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
    $this->contentLoader->setExistenceCheck(FALSE);

    // Confirm `entityExists()` was never called.
    $this->contentLoader
      ->expects($this->never())
      ->method('entityExists');

    // Confirm an entity was created.
    $this->configStorage
      ->expects($this->once())
      ->method('create')
      ->willReturn($this->getEntityMock());

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

    // Enable the existence checking.
    $this->contentLoader->setExistenceCheck();

    // Indicate the entity already exists.
    $this->contentLoader
      ->expects($this->once())
      ->method('entityExists')
      ->willReturn($this->getEntityMock());

    // Since the entity exists one should never be created.
    $this->configStorage
      ->expects($this->never())
      ->method('create');

    // Trigger the method for testing with the test data.
    $this->contentLoader->buildEntity($entity_type, $test_content);
  }

  /**
   * Tests that buildEntity() correctly handles unmatched entities.
   *
   * Confirm behavior when entity existence checking is enabled, but no
   * matching entities were found.
   *
   * @dataProvider contentDataProvider
   *
   * @see \Drupal\Tests\yaml_content\Unit\ContentLoaderTest::setupBuildEntityTests()
   */
  public function testBuildEntityExistenceCheckFindsNoMatches($entity_type, $test_content) {
    $this->setupBuildEntityTests();

    // Enable the existence checking.
    $this->contentLoader->setExistenceCheck(TRUE);

    // Indicate no matching entity was found.
    $this->contentLoader
      ->expects($this->once())
      ->method('entityExists')
      ->willReturn(FALSE);

    // Since the entity exists one should never be created.
    $this->configStorage
      ->expects($this->once())
      ->method('create')
      ->willReturn($this->getEntityMock());

    // Trigger the method for testing with the test data.
    $this->contentLoader->buildEntity($entity_type, $test_content);
  }

  /**
   * Data provider function to test various content scenarios.
   *
   * @return array
   *   An array of content testing arguments:
   *   - string Entity Type
   *   - array Content data structure
   *
   * @todo Refactor to provide entity definition and content data.
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

  /**
   * Test the preprocessFieldData method.
   *
   * @todo Test missing reference functions.
   */
  public function testPreprocessFieldData() {
    $this->markTestIncomplete();
  }

  /**
   * Test the referenceEntityLoad processor method.
   */
  public function testReferenceEntityLoad() {
    $this->markTestIncomplete();
  }

  /**
   * Test the fileEntityLoad processor method.
   *
   * @todo Test loading an image.
   * @todo Test loading a file.
   * @todo Test attempts to load missing files.
   * @todo Test attempts to load from missing subdirectories.
   */
  public function testFileEntityLoad() {
    $this->markTestIncomplete();
  }

  /**
   * Tests the entityExists method.
   *
   * @dataProvider entityExistsDataProvider
   *
   * @param bool $expected
   *   The expected result from the entityExists() method using these arguments.
   * @param array $content_data
   *   The content data being tested.
   * @param callable|null $setupCallack
   *   (Optional) A callback function to be used to prepare for this specific
   *   content test.
   *
   * @see \Drupal\yaml_content\ContentLoader\ContentLoader::entityExists()
   */
  public function testEntityExists($expected, array $content_data, $setupCallack = NULL) {
    // Execute the callback function for this test case if provided.
    if (is_callable($setupCallack)) {
      call_user_func($setupCallack);
    }

    $entity_type = $content_data['entity'];
    $actual = $this->contentLoader->entityExists($entity_type, $content_data);

    $this->assertEquals($expected, $actual);
//    $this->markTestIncomplete();
  }

  /**
   * Data provider to prepare entityExists method tests.
   */
  public function entityExistsDataProvider() {
    // Paragraphs should always be recreated since they can't reliably be
    // identified as duplicates without false positives.
    $paragraph_test = [
      // Expected result.
      FALSE,
      // Content data.
      [
        'entity' => 'paragraph',
        'type' => 'test_paragraph_bundle',
        'field_title' => [
          'value' => 'Test Title',
        ],
      ],
      // Callback setup.
      NULL,
    ];

    // Media and file entities require special handling to identify matches.
    $media_test = [];
    $file_test = [];

    // Nodes should match regularly based on availalbe property data.
    // Test an available match.
    $node_match_test = [];
    // Test no match found.
    $node_no_match_test = [];

    return [
      $paragraph_test,
//      $media_test,
//      $file_test,
//      $node_match_test,
//      $node_no_match_test,
    ];
  }

}
