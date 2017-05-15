<?php

namespace ArtSkills\ValueObject;


use ArtSkills\Filesystem\File;
use ArtSkills\Lib\Arrays;
use ArtSkills\Lib\Strings;
use ArtSkills\Lib\Url;
use ArtSkills\Traits\Library;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Compound;
use phpDocumentor\Reflection\Types\Context;
use ArtSkills\Error\Assert;
use phpDocumentor\Reflection\Types\Self_;
use phpDocumentor\Reflection\Types\Static_;
use phpDocumentor\Reflection\Types\This;

/**
 * Построитель документации по [ValueObject](https://github.com/ArtSkills/common/src/ValueObject/README.md)
 */
class ValueObjectDocumentation
{
	use Library;

	const DEFAULT_TYPE = null;

	/** Псевдонимы типов данных в JS  */
	const JS_TYPE_ALIAS = [
		'bool' => 'boolean',
		'bool[]' => 'boolean[]',
		'mixed' => '*',
		'integer' => 'int',
		'integer[]' => 'int[]',
		'array' => 'Object',
	];

	const JS_NOT_VARIANT_PROPERTY = '*';

	/** Псевдонимы типов для JSON схемы */
	const JSON_SCHEMA_TYPE_ALIAS = [
		'bool' => 'boolean',
		'bool[]' => 'boolean[]',
		'mixed' => '*',
		'int' => 'integer',
		'int[]' => 'integer[]',
		'array' => 'object',
	];

	const JSON_SCHEMA_DATA_TYPES = [
		'string',
		'integer',
		'number',
		'object',
		'array',
		'boolean',
		'null',
	];

	/**
	 * Формируем JSDoc документацию по ValueObject файлу
	 *
	 * @param string $absFilePath Абсолютный путь до класса
	 * @param string $dstJsDocFolder Папка, где хранить JSDoc описание
	 */
	public static function buildJsDoc($absFilePath, $dstJsDocFolder) {
		Assert::file($absFilePath);
		Assert::fileExists($dstJsDocFolder);

		$fullClassName = static::_getFullNamespace($absFilePath) . '\\' . static::_getClassName($absFilePath);
		$propertyList = static::_getPropertyList((new \ReflectionClass($fullClassName)), static::_getUsesList($absFilePath));

		$jsDocFilePath = $dstJsDocFolder . DS . static::_convertPsr4ToPsr0($fullClassName) . '.js';
		$jsDocFile = new File($jsDocFilePath);
		if ($jsDocFile->exists()) {
			$jsDocFile->delete();
		}

		if (!empty($propertyList)) {
			static::_createJsDocFile($jsDocFile, $fullClassName, $propertyList);
		}
	}

	/**
	 * Формируем JSON файл со схемой
	 *
	 * @param string $absFilePath Абсолютный путь до класса
	 * @param string $dstSchemaFolder Папка, где хранть схемы
	 * @param string $schemaLocationUrl URL адрес до папки со схемами
	 */
	public static function buildJsonSchema($absFilePath, $dstSchemaFolder, $schemaLocationUrl) {
		Assert::file($absFilePath);
		Assert::fileExists($dstSchemaFolder);
		Assert::notEmpty($schemaLocationUrl);

		$fullClassName = static::_getFullNamespace($absFilePath) . '\\' . static::_getClassName($absFilePath);
		$propertyList = static::_getPropertyList((new \ReflectionClass($fullClassName)), static::_getUsesList($absFilePath));

		$schemaFilePath = $dstSchemaFolder . DS . static::_convertPsr4ToPsr0($fullClassName) . '.json';
		$schemaFile = new File($schemaFilePath);
		if ($schemaFile->exists()) {
			$schemaFile->delete();
		}

		if (!empty($propertyList)) {
			static::_createJsonSchemaFile($schemaFile, $fullClassName, $propertyList, $schemaLocationUrl);
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
	 * @return string
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
	 * TODO: переделать на token_get_all, дабы избежать проблем с use трейтов и т.п.
	 * TODO: проверить с PHP7 грппировкой use
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
	 * Определяем список методов, их типов и комментариев
	 *
	 * @param \ReflectionClass $reflectionClass
	 * @param array $usesList
	 * @return array ['имя метода' => ['type' => null|Var_, 'description' => null|string], ...]
	 */
	private static function _getPropertyList(\ReflectionClass $reflectionClass, array $usesList) {
		$propertyList = [];

		if ($reflectionClass->isSubclassOf(ValueObject::class)) {
			foreach ($reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
				$propertyInfo = [
					'type' => null,
					'description' => null,
				];

				$rawDocBlock = $property->getDocComment();
				if (!empty($rawDocBlock)) {
					$docBlock = DocBlockFactory::createInstance()
						->create($rawDocBlock, (new Context($property->getDeclaringClass()
							->getNamespaceName(), $usesList)));
					/** @var Var_[] $vars */
					$vars = $docBlock->getTagsByName('var');
					if (count($vars)) {
						$propertyInfo['type'] = $vars[0];
					}

					$propertyInfo['description'] = $docBlock->getSummary();
				}
				$propertyList[$property->getName()] = $propertyInfo;
			}
		}
		return $propertyList;
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
			' * @typedef {Object} ' . static::_convertPsr4ToPsr0($fullClassName),
		];
		/** @var Var_ $propertyInfo */
		foreach ($propertyList as $propertyName => $propertyInfo) {
			$propertyDescription = static::_getPropertyDescription($propertyInfo['description']);
			if (!empty($propertyDescription)) {
				$propertyDescription = ' ' . $propertyDescription;
			}

			if (!empty($propertyInfo['type'])) {
				$jsName = static::_getJsVariableName($propertyInfo['type'], $fullClassName, static::JS_TYPE_ALIAS);
				$jsDocArr[] = ' * @property {' . (is_array($jsName) ? implode('|', $jsName)
						: $jsName) . '} ' . $propertyName . $propertyDescription;
			} else {
				$jsDocArr[] = ' * @property ' . $propertyName . $propertyDescription;
			}
		}
		$jsDocArr[] = ' */';
		$jsDocFile->write(implode("\n", $jsDocArr) . "\n");
		$jsDocFile->close();
	}

	private static function _createJsonSchemaFile(
		File $schemaFile, $fullClassName, array $propertyList, $schemaLocationUrl
	) {
		$schemaObject = [
			'$schema' => 'http://json-schema.org/draft-04/schema#',
			'title' => static::_convertPsr4ToPsr0($fullClassName),
			'description' => $fullClassName . ' php class',
			'type' => 'object',
			'properties' => [

			],
		];

		/** @var Var_ $propertyInfo */
		foreach ($propertyList as $propertyName => $propertyInfo) {
			$propertyDescription = static::_getPropertyDescription($propertyInfo['description']);
			$propertyTypes = [['type' => 'null']];

			if (!empty($propertyInfo['type']) || $propertyInfo['type'] === static::JS_NOT_VARIANT_PROPERTY) {
				$jsNames = (array)static::_getJsVariableName($propertyInfo['type'], $fullClassName, static::JSON_SCHEMA_TYPE_ALIAS);
				foreach ($jsNames as $jsName) {
					if (Strings::endsWith($jsName, '[]')) {
						$ins = [
							'type' => 'array',
							'items' => static::_getJsonSchemaTypeStructure(Strings::replacePostfix($jsName, '[]'), '', $schemaLocationUrl),
							'minItems' => 0,
						];
						if (!empty($propertyDescription)) {
							$ins['description'] = $propertyDescription;
						}
						$propertyTypes[] = $ins;
					} else {
						$propertyTypes[] = static::_getJsonSchemaTypeStructure($jsName, $propertyDescription, $schemaLocationUrl);
					}
				}
			} else {
				$propertyTypes[] = static::_getJsonSchemaTypeStructure(null, $propertyDescription . "\nТип данных у свойства \"" . $fullClassName . '::' . $propertyName . '" не описан.', $schemaLocationUrl);
			}
			$schemaObject['properties'][$propertyName] = ['oneOf' => $propertyTypes];
		}
		$schemaFile->write(Arrays::encode($schemaObject, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
		$schemaFile->close();
	}

	/**
	 * Формируем массив описания типа для JSON схемы
	 *
	 * @param string|null $typeName
	 * @param string $propertyDescription
	 * @param string $schemaLocationUrl URL адрес папки, в которой будут находится JSON схемы
	 * @return array
	 */
	private static function _getJsonSchemaTypeStructure($typeName, $propertyDescription, $schemaLocationUrl) {
		if (empty($typeName) || $typeName === static::JS_NOT_VARIANT_PROPERTY) {
			return [
				'description' => $propertyDescription, // оно обязательно
			];
		} elseif (in_array($typeName, static::JSON_SCHEMA_DATA_TYPES)) {
			$result = [
				'type' => $typeName,
			];
			if (!empty($propertyDescription)) {
				$result['description'] = $propertyDescription;
			}
			return $result;
		} else {
			return [
				'$ref' => Strings::replaceIfEndsWith($schemaLocationUrl, '/') . '/' . $typeName . '.json',
			];
		}
	}

	/**
	 * Получаем описание свойства с пробелом вначале, если оно не пустое
	 *
	 * @param string $description
	 * @return string
	 */
	private static function _getPropertyDescription($description) {
		if (!empty($description)) {
			return trim(str_replace(["\r", "\n"], ' ', $description));
		} else {
			return '';
		}
	}

	/**
	 * Определяем JS тип исходня из PHP типа
	 *
	 * @param Var_ $propertyVar
	 * @param string $fullClassName
	 * @param array $typeAliases
	 * @return string|string[]
	 */
	private static function _getJsVariableName(Var_ $propertyVar, $fullClassName, array $typeAliases) {
		$propertyType = $propertyVar->getType();
		if (empty($propertyType) && $propertyVar->getVariableName() === 'this') {
			$propertyType = new This();
		}

		if ($propertyType instanceof Compound) {
			$result = [];
			$index = 0;
			while ($propertyType->has($index)) {
				$result[] = static::_convertPhpTypeToJs($propertyType->get($index), $fullClassName, $typeAliases);
				$index++;
			}
			return $result;
		} else {
			return static::_convertPhpTypeToJs($propertyType, $fullClassName, $typeAliases);
		}
	}

	/**
	 * Определяем тип переменной в формате JS
	 *
	 * @param Type $propertyType
	 * @param string $fullClassName
	 * @param array $typeAliases
	 * @return string
	 */
	private static function _convertPhpTypeToJs(Type $propertyType, $fullClassName, array $typeAliases) {
		if ($propertyType instanceof Self_) {
			return static::JS_NOT_VARIANT_PROPERTY;
		} else if ($propertyType instanceof Static_ || $propertyType instanceof This) {
			return static::_convertPsr4ToPsr0($fullClassName);
		}

		$propertyTypeString = static::_convertPsr4ToPsr0((string)$propertyType);
		if (array_key_exists($propertyTypeString, $typeAliases)) {
			return $typeAliases[$propertyTypeString];
		} else {
			return $propertyTypeString;
		}
	}
}