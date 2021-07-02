<?php
declare(strict_types=1);

namespace ArtSkills\Test\TestCase\TestSuite\Mock;

use ArtSkills\TestSuite\Mock\ConstantMocker;
use ArtSkills\Test\TestCase\TestSuite\Mock\Fixture\MockTestFixture;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ArtSkills\TestSuite\Mock\ConstantMocker
 */
class ConstantMockerTest extends TestCase
{
    const CLASS_CONST_NAME = MockTestFixture::CLASS_CONST_NAME;
    const GLOBAL_CONST_NAME = MockTestFixture::GLOBAL_CONST_NAME;

    /**
     * Мок константы в классе
     */
    public function testClassMock(): void
    {
        $originalValue = MockTestFixture::TEST_CONSTANT;
        $mockValue = 'qqq';
        ConstantMocker::mock(MockTestFixture::class, self::CLASS_CONST_NAME, $mockValue);
        self::assertEquals($mockValue, MockTestFixture::TEST_CONSTANT);

        ConstantMocker::restore();
        self::assertEquals($originalValue, MockTestFixture::TEST_CONSTANT);
    }

    /**
     * Мок константы в классе.
     * И вызов в том же файле через self
     */
    public function testClassMockSameFile(): void
    {
        $mockValue = 666;
        ConstantMocker::mock(MockTestFixture::class, self::CLASS_CONST_NAME, $mockValue);
        self::assertEquals($mockValue, MockTestFixture::getConst());
        ConstantMocker::restore();
    }

    /**
     * Мок константы вне класса
     */
    public function testSingleMock(): void
    {
        $originalValue = constant(self::GLOBAL_CONST_NAME);
        $mockValue = 'qqq';
        ConstantMocker::mock(null, self::GLOBAL_CONST_NAME, $mockValue);
        self::assertEquals($mockValue, constant(self::GLOBAL_CONST_NAME));

        ConstantMocker::restore();
        self::assertEquals($originalValue, constant(self::GLOBAL_CONST_NAME));
    }

    /**
     * Проверка на существование константы
     */
    public function testConstantExists(): void
    {
        $this->expectExceptionMessage("is not defined!");
        $this->expectException(AssertionFailedError::class);
        ConstantMocker::mock(null, 'BAD_CONST', 'bad');
    }

    /**
     * Дважды одно и то же мокнули
     */
    public function testConstantDoubleMock(): void
    {
        $this->expectExceptionMessage("is already mocked!");
        $this->expectException(AssertionFailedError::class);
        ConstantMocker::mock(null, self::GLOBAL_CONST_NAME, '1');
        ConstantMocker::mock(null, self::GLOBAL_CONST_NAME, '2');
    }
}
