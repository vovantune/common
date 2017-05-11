<?php

namespace ArtSkills\ValueObject;


use ArtSkills\Filesystem\File;
use ArtSkills\Lib\Strings;
use ArtSkills\Traits\Library;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\Context;
use Webmozart\Assert\Assert;

/**
 * Построитель документации по [ValueObject](https://github.com/ArtSkills/common/src/ValueObject/README.md)
 */
class ValueObjectDocumentation
{
	use Library;

	const DEFAULT_TYPE = 'mixed';

	/** Псевдонимы типов данных в JS  */
	const JS_TYPE_ALIAS = [
		'bool' => 'boolean',
		'bool[]' => 'boolean[]',
		'mixed' => '*',
		'integer' => 'int',
		'integer[]' => 'int[]',
		'array' => 'Object',
	];

	/**
	 * Формируем JSDoc документацию по ValueObject файлу
	 *
	 * @param string $absFilePath
	 * @param string $dstFolder где хранить JSDoc описание
	 */
	public static function build($absFilePath, $dstFolder) {
		Assert::file($absFilePath);
		Assert::fileExists($dstFolder);

		$fullNameSpace = static::_getFullNamespace($absFilePath);

		$fullClassName = $fullNameSpace . '\\' . static::_getClassName($absFilePath);
		$propertyList = self::_getPropertyList((new \ReflectionClass($fullClassName)), self::_getUsesList($absFilePath));

		$jsDocFilePath = $dstFolder . DS . static::_convertPsr4ToPsr0($fullClassName) . '.js';
		$jsDocFile = new File($jsDocFilePath);
		if ($jsDocFile->exists()) {
			$jsDocFile->delete();
		}

		if (!empty($propertyList)) {
			static::_createJsDocFile($jsDocFile, $fullClassName, $propertyList);
		}
	}

	/**
	 * namespace в файле
	 *
	 * @param string $absFilePath
	 * @return string
	 */
	private static function _getFullNamespace($absFilePath) {
		$lines = file($absFilePath);
		$result = preg_grep('/^namespace /', $lines);
		$namespaceLine = array_shift($result);
		$match = [];
		preg_match('/^namespace (.*);$/', $namespaceLine, $match);
		$fullNamespace = array_pop($match);

		return $fullNamespace;
	}

	/**
	 * Определяем имя класса исходя из имени файла
	 *
	 * @param string $absFilePath
	 * @return mixed
	 */
	private static function _getClassName($absFilePath) {
		$directoriesAndFilename = explode('/', $absFilePath);
		$absFilePath = array_pop($directoriesAndFilename);
		$nameAndExtension = explode('.', $absFilePath);
		$className = array_shift($nameAndExtension);

		return $className;
	}

	/**
	 * Список объектов из use PHP файла
	 *
	 * @param string $absFilePath
	 * @return array
	 */
	private static function _getUsesList($absFilePath) {
		$flContent = file_get_contents($absFilePath);
		$result = [];
		if (preg_match_all('/^use (.*);$/m', $flContent, $usesList)) {
			foreach ($usesList[1] as $use) {
				if (preg_match('/^([\S]+)\sas\s(.+)$/i', $use, $aliasMath)) { // use \ArtSkills\Strings as MyString
					$result[$aliasMath[2]] = $aliasMath[1];
				} else {
					$result[Strings::lastPart("\\", $use)] = $use;
				}
			}
		}

		return $result;
	}


	/**
	 * Определяем список методов и их типов
	 *
	 * @param \ReflectionClass $reflectionClass
	 * @param array $usesList
	 * @return array ['имя метода' => 'тип метода', ...]
	 */
	private static function _getPropertyList(\ReflectionClass $reflectionClass, array $usesList) {
		$propertyList = [];

		if ($reflectionClass->isSubclassOf(ValueObject::class)) {
			foreach ($reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
				$propertyType = self::_getPropertyType($property, $usesList);
				if (empty($propertyType)) {
					$propertyType = self::DEFAULT_TYPE;
				}

				$propertyList[$property->getName()] = !empty($propertyType) ? $propertyType : self::DEFAULT_TYPE;
			}
		}
		return $propertyList;
	}

	/**
	 * Определяем тип свойства исходя из PHPDoc комментария
	 *
	 * @param \ReflectionProperty $property
	 * @param array $usesList
	 * @return null|string
	 */
	private static function _getPropertyType(\ReflectionProperty $property, array $usesList) {
		$rawDocBlock = $property->getDocComment();
		if (!empty($rawDocBlock)) {
			$docBlock = DocBlockFactory::createInstance()
				->create($rawDocBlock, (new Context($property->getDeclaringClass()->getNamespaceName(), $usesList)));
			/** @var Var_[] $vars */
			$vars = $docBlock->getTagsByName('var');
			if (count($vars)) {
				return (string)$vars[0]->getType();
			}
		}
		return null;
	}

	/**
	 * Преобразовываем имя из namespace в PSR0
	 *
	 * @param string $fullName
	 * @return string
	 */
	private static function _convertPsr4ToPsr0($fullName) {
		$fullName = Strings::replaceIfStartsWith($fullName, "\\", '');
		$fullName = Strings::replaceIfStartsWith($fullName, "App\\", '');
		return str_replace("\\", '_', $fullName);
	}

	/**
	 * Создаём JSDoc описание объекта
	 *
	 * @param File $jsDocFile
	 * @param string $fullClassName
	 * @param array $propertyList
	 */
	private static function _createJsDocFile(File $jsDocFile, $fullClassName, array $propertyList) {
		$jsDocArr = [
			'// Auto generated file, to change structure edit ' . $fullClassName . ' php class',
			'/**',
			' * @typedef {Object} ' . self::_convertPsr4ToPsr0($fullClassName),
		];
		foreach ($propertyList as $propertyName => $propertyType) {
			if ($propertyType !== self::DEFAULT_TYPE) {
				$jsDocArr[] = ' * @property {' . static::_getJsVariableName($propertyType) . '} ' . $propertyName;
			} else {
				$jsDocArr[] = ' * @property ' . $propertyName;
			}
		}
		$jsDocArr[] = ' */';
		$jsDocFile->write(implode("\n", $jsDocArr) . "\n");
	}

	/**
	 * Определяем JS тип исходня из PHP типа
	 *
	 * @param string $propertyType
	 * @return string
	 */
	private static function _getJsVariableName($propertyType) {
		if (empty($propertyType)) {
			return '*';
		}

		$propertyType = self::_convertPsr4ToPsr0($propertyType);
		if (array_key_exists($propertyType, static::JS_TYPE_ALIAS)) {
			return static::JS_TYPE_ALIAS[$propertyType];
		} else {
			return $propertyType;
		}
	}
}