<?php

namespace ArtSkills\TestSuite;

/**
 * @SuppressWarnings(PHPMD.MethodMix)
 */
abstract class AppControllerTestCase extends IntegrationTestCase
{
    use TestCaseTrait;

    /** @inheritdoc */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::_setUpBeforeClass();
    }

    /** @inheritdoc */
    public function setUp()
    {
        parent::setUp();
        $this->_setUp();
    }

    /** @inheritdoc */
    public function tearDown()
    {
        parent::tearDown();
        $this->_tearDown();
    }
}
