<?php
declare(strict_types=1);

namespace ArtSkills\Test\TestCase\Lib;

use ArtSkills\Lib\Strings;
use ArtSkills\TestSuite\AppTestCase;

class StringsTest extends AppTestCase
{

    /** проверка префикса */
    public function testStartsWith(): void
    {
        $prefix = 'prefix';
        $testStr = "{$prefix}asdfg";
        self::assertTrue(Strings::startsWith($testStr, $prefix));
        self::assertTrue(Strings::startsWith($testStr, [$prefix, 'shit']));
        self::assertTrue(Strings::startsWith($testStr, ['shit', $prefix]));
        self::assertFalse(Strings::startsWith($testStr, ['shit', 'crap']));

        $testStr = "asd{$prefix}fg";
        self::assertFalse(Strings::startsWith($testStr, $prefix));
    }

    /** проверка постфикса */
    public function testEndsWith(): void
    {
        $postfix = 'postfix';
        $testStr = "asdfg{$postfix}";
        self::assertTrue(Strings::endsWith($testStr, $postfix));
        self::assertTrue(Strings::endsWith($testStr, [$postfix, 'shit']));
        self::assertTrue(Strings::endsWith($testStr, ['shit', $postfix]));
        self::assertFalse(Strings::endsWith($testStr, ['shit', 'crap']));

        $testStr = "asd{$postfix}fg";
        self::assertFalse(Strings::endsWith($testStr, $postfix));
    }

    /** замена префикса */
    public function testReplacePrefix(): void
    {
        $prefix = 'prefix';
        $replacement = 'replacement';
        $testStr = "{$prefix}asdf{$prefix}";
        $expectedStr = "{$replacement}asdf{$prefix}";
        self::assertEquals($expectedStr, Strings::replacePrefix($testStr, $prefix, $replacement));
    }

    /** замена постфикса */
    public function testReplacePostfix(): void
    {
        $postfix = 'postfix';
        $replacement = 'replacement';
        $testStr = "{$postfix}asdf{$postfix}";
        $expectedStr = "{$postfix}asdf{$replacement}";
        self::assertEquals($expectedStr, Strings::replacePostfix($testStr, $postfix, $replacement));
    }

    /** замена по условию */
    public function testReplaceIfStarts(): void
    {
        $prefix = 'prefix';
        $restStr = 'asdfg';
        $testStr = "{$prefix}{$restStr}";

        $replacement = 'replacement';
        $expectedStr = "{$replacement}{$restStr}";
        self::assertEquals($expectedStr, Strings::replaceIfStartsWith($testStr, $prefix, $replacement));
        self::assertEquals($expectedStr, Strings::replaceIfStartsWith($testStr, [$prefix, 'shit'], $replacement));
        self::assertEquals($expectedStr, Strings::replaceIfStartsWith($testStr, ['shit', $prefix], $replacement));
        self::assertEquals($testStr, Strings::replaceIfStartsWith($testStr, ['shit', 'crap'], $replacement));
        self::assertEquals($replacement . $testStr, Strings::replaceIfStartsWith($testStr, [
            'shit',
            'crap',
        ], $replacement, true));

        $testStr = "asd{$prefix}fg";
        self::assertEquals($testStr, Strings::replaceIfStartsWith($testStr, $prefix, $replacement));
        self::assertEquals($replacement . $testStr, Strings::replaceIfStartsWith($testStr, $prefix, $replacement, true));
    }

    /** замена по условию */
    public function testReplaceIfEnds(): void
    {
        $postfix = 'postfix';
        $restStr = 'asdfg';
        $testStr = "{$restStr}{$postfix}";

        $replacement = 'replacement';
        $expectedStr = "{$restStr}{$replacement}";
        self::assertEquals($expectedStr, Strings::replaceIfEndsWith($testStr, $postfix, $replacement));
        self::assertEquals($expectedStr, Strings::replaceIfEndsWith($testStr, [$postfix, 'shit'], $replacement));
        self::assertEquals($expectedStr, Strings::replaceIfEndsWith($testStr, ['shit', $postfix], $replacement));
        self::assertEquals($testStr, Strings::replaceIfEndsWith($testStr, ['shit', 'crap'], $replacement));
        self::assertEquals($testStr . $replacement, Strings::replaceIfEndsWith($testStr, [
            'shit',
            'crap',
        ], $replacement, true));

        $testStr = "asd{$postfix}fg";
        self::assertEquals($testStr, Strings::replaceIfEndsWith($testStr, $postfix, $replacement));
        self::assertEquals($testStr . $replacement, Strings::replaceIfEndsWith($testStr, $postfix, $replacement, true));
    }
}
