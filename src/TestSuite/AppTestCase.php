<?php
declare(strict_types=1);

namespace ArtSkills\TestSuite;

use Cake\TestSuite\TestCase;

/**
 * @SuppressWarnings(PHPMD.MethodMix)
 */
abstract class AppTestCase extends TestCase
{
    use TestCaseTrait;

    /**
     * @inheritdoc
     * @return void
     */
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
