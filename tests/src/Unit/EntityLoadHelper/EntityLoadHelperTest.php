<?php

namespace Drupal\Tests\yaml_content\Unit\EntityLoadHelper;

use Drupal\Tests\UnitTestCase;
use Drupal\yaml_content\Service\EntityLoadHelper;

/**
 * Test functionality of the EntityLoadHelper class.
 *
 * @coversDefaultClass Drupal\yaml_content\Service\EntityLoadHelper
 * @group yaml_content
 */
class EntityLoadHelperTest extends UnitTestCase {

  /**
   * A prepared EntityLoadHelper object for testing.
   *
   * @var \Drupal\yaml_content\Service\EntityLoadHelper|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $loadHelper;

  /**
   * Mock the EntityLoadHelper class to support test inspections.
   *
   * Mock the EntityLoadHelper class with a configurable list of stubbed methods.
   *
   * @param array|null $stubbed_methods
   *   (Optional) An array of method names to leave active on the mock object.
   *   All other declared methods on the ContentLoader class will be stubbed. If
   *   this argument is omitted all methods are mocked and execute their
   *   original code.
   *
   * @return \PHPUnit_Framework_MockObject_MockObject
   *   The mocked ContentLoader object with
   */
  protected function getEntityLoadHelperMock($stubbed_methods = NULL) {
    // Partially mock the ContentLoader for testing specific methods.
    $this->contentLoader = $this->getMockBuilder(EntityLoadHelper::class)
      ->disableOriginalConstructor()
      ->setMethods($stubbed_methods)
      ->getMock();

    return $this->loadHelper;
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

  }

  /**
   * Test the entity type manager is lazy loaded upon request.
   *
   * @covers ::getEntityTypeManager
   */
  public function testEntityTypeManagerIsLazyLoaded() {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  /**
   * Test the entity field manager is lazy loaded upon request.
   *
   * @covers ::getEntityFieldManager
   */
  public function testEntityFieldManagerIsLazyLoaded() {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  /**
   * Test entityExists method returns true when an entity is loaded.
   *
   * @covers ::entityExists
   */
  public function testEntityExistsReturnsTrueWhenAnEntityIsLoaded() {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  /**
   * Test entityExists method returns false when an entity is not loaded.
   *
   * @covers ::entityExists
   */
  public function testEntityExistsReturnsFalseWhenAnEntityIsNotLoaded() {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  /**
   * Test that an entity is searched by UUID first if one is provided.
   *
   * @covers ::loadEntity
   */
  public function testLoadEntityLoadsUuidFirstIfAvailable() {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  /**
   * Test that an entity is searched by properties if no UUID is defined.
   *
   * @covers ::loadEntity
   */
  public function testLoadEntityLoadsByPropertiesIfUuidIsUnavailable() {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  /**
   * Test that UUID search only includes the UUID and entity type.
   *
   * @covers ::loadByUuid
   */
  public function testLoadByUuidSearchesByUuidOnly() {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  /**
   * Test loadByUuid returns only the first match.
   *
   * @covers ::loadByUuid
   */
  public function testLoadByUuidReturnsOnlyOneMatch() {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  /**
   * Test loadByUuid returns false if no match is found.
   *
   * @covers ::loadByUuid
   */
  public function testLoadByUuidReturnsFalseIfNoMatchIsFound() {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  /**
   * Test that property search only includes content property values.
   *
   * @covers ::loadByProperties
   */
  public function testLoadByPropertiesSearchesByPropertiesOnly() {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  /**
   * Test loadByProperties returns only the first match.
   *
   * @covers ::loadByProperties
   */
  public function testLoadByPropertiesReturnsOnlyOneMatch() {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  /**
   * Test loadByUuid returns false if no match is found.
   *
   * @covers ::loadByProperties
   */
  public function testLoadByPropertiesReturnsFalseIfNoMatchIsFound() {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  /**
   * Test extractContentProperties returns property attributes.
   *
   * @covers ::extractContentProperties
   */
  public function testExtractContentPropertiesOnlyReturnsProperties() {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  /**
   * Test categorizeAttributes always returns three attribute categories.
   *
   * @covers ::categorizeAttributes
   */
  public function testCategorizeAttributesAlwaysReturnsThreeKeys() {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  /**
   * Test categorizeAttributes sorts attributes as expected.
   *
   * @covers ::categorizeAttributes
   */
  public function testCategorizeAttributesSortsAsExpected() {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

}
