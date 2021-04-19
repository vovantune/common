<?php

namespace ArtSkills\TestSuite;

/**
 * Мок методов касса
 */
abstract class ClassMockEntity
{
    /**
     * Базовый метод, который и инциализирует все подмены
     */
    public static function init()
    {
    }

    /**
     * Вызов после каждого теста
     */
    public static function destroy()
    {
    }
}
