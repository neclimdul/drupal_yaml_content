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

  public function testBuildEntity() {
    $this->markTestIncomplete();
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
