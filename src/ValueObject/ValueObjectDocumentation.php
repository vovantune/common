<?php

namespace ArtSkills\ValueObject;


use ArtSkills\Error\InternalException;
use ArtSkills\Filesystem\File;
use ArtSkills\Lib\Arrays;
use ArtSkills\Lib\Strings;
use ArtSkills\Traits\Library;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\Array_;
use phpDocumentor\Reflection\Types\Compound;
use phpDocumentor\Reflection\Types\Context;
use ArtSkills\Error\Assert;
use phpDocumentor\Reflection\Types\Mixed;
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

	const EXTENSION_JSDOC = '.js';
	const EXTENSION_JSON = '.json';

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
		'int' => 'integer',
		'int[]' => 'integer[]',
		'float' => 'number',
		'float[]' => 'number[]',
		'double' => 'number',
		'double[]' => 'number[]',
		'decimal' => 'number',
		'decimal[]' => 'number[]',
		'mixed' => '*',
		'array' => 'object',
	];

	/** Стандартные типы данных JSON schema  */
	const JSON_SCHEMA_INTERNAL_DATA_TYPES = [
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

		$jsDocFilePath = $dstJsDocFolder . DS . static::_convertPsr4ToPsr0($fullClassName) . static::EXTENSION_JSDOC;
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
	 * @param string $schemaLocationUri часть ссылки до папки со схемами
	 */
	public static function buildJsonSchema($absFilePath, $dstSchemaFolder, $schemaLocationUri) {
		Assert::file($absFilePath);
		Assert::fileExists($dstSchemaFolder);
		Assert::notEmpty($schemaLocationUri);

		$fullClassName = static::_getFullNamespace($absFilePath) . '\\' . static::_getClassName($absFilePath);
		$propertyList = static::_getPropertyList((new \ReflectionClass($fullClassName)), static::_getUsesList($absFilePath));

		$schemaFilePath = $dstSchemaFolder . DS . static::_convertPsr4ToPsr0($fullClassName) . static::EXTENSION_JSON;
		$schemaFile = new File($schemaFilePath);
		if ($schemaFile->exists()) {
			$schemaFile->delete();
		}

		if (!empty($propertyList)) {
			static::_createJsonSchemaFile($schemaFile, $fullClassName, $propertyList, $schemaLocationUri);
		}
	}

	/**
	 * namespace в файле
	 *
	 * @param string $absFilePath
	 * @return string
	 */
	protected static function _getFullNamespace($absFilePath) {
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
	protected static function _getClassName($absFilePath) {
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
	protected static function _getUsesList($absFilePath) {
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
	 * @return array ['имя метода' => ['type' => null|Var_, 'description' => null|string, 'default' => 'дефотовое значение'], ...]
	 * @throws InternalException
	 */
	protected static function _getPropertyList(\ReflectionClass $reflectionClass, array $usesList) {
		$propertyList = [];

		$excludeProperties = $reflectionClass->getConstant('EXCLUDE_EXPORT_PROPS');
		Assert::isArray($excludeProperties, 'Список не экспортируемых свойств должен быть массивом');

		$defaultValues = $reflectionClass->getDefaultProperties();
		if ($reflectionClass->isSubclassOf(ValueObject::class)) {
			foreach ($reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
				$propertyName = $property->getName();
				if (in_array($propertyName, $excludeProperties)) {
					continue;
				}

				$propertyInfo = [
					'type' => null,
					'default' => $defaultValues[$propertyName],
					'description' => null,
				];

				$rawDocBlock = $property->getDocComment();
				if (empty($rawDocBlock)) {
					throw new InternalException($reflectionClass->getName() . '::' . $propertyName . ': Нет описания типа данных');
				}

				$docBlock = DocBlockFactory::createInstance()
					->create($rawDocBlock, (new Context($property->getDeclaringClass()
						->getNamespaceName(), $usesList)));
				/** @var Var_[] $vars */
				$vars = $docBlock->getTagsByName('var');
				if (empty($vars)) {
					throw new InternalException($reflectionClass->getName() . '::' . $propertyName . ': Нет описания типа данных');
				}
				$propertyInfo['type'] = $vars[0];

				$propertyInfo['description'] = $docBlock->getSummary();

				$propertyList[$propertyName] = $propertyInfo;
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
	protected static function _convertPsr4ToPsr0($fullName) {
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
	protected static function _createJsDocFile(File $jsDocFile, $fullClassName, array $propertyList) {
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
				$jsName = static::_getJsVariableName($fullClassName, $propertyName, $propertyInfo['type'], static::JS_TYPE_ALIAS);
				$jsDocArr[] = ' * @property {' . (is_array($jsName) ? implode('|', $jsName)
						: $jsName) . '} ' . $propertyName . ' = ' . var_export($propertyInfo['default'], true) . $propertyDescription;
			} else {
				$jsDocArr[] = ' * @property ' . $propertyName . ' = ' . var_export($propertyInfo['default'], true) . $propertyDescription;
			}
		}
		$jsDocArr[] = ' */';
		$jsDocFile->write(implode("\n", $jsDocArr) . "\n");
		$jsDocFile->close();
	}

	/**
	 * Создаём файл с JSON схемой
	 *
	 * @param File $schemaFile
	 * @param string $fullClassName
	 * @param array $propertyList
	 * @param string $schemaLocationUri
	 */
	protected static function _createJsonSchemaFile(
		File $schemaFile, $fullClassName, array $propertyList, $schemaLocationUri
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
			$jsName = static::_getJsVariableName($fullClassName, $propertyName, $propertyInfo['type'], static::JSON_SCHEMA_TYPE_ALIAS);
			if (Strings::endsWith($jsName, '[]')) {
				$propertyType = [
					'type' => 'array',
					'items' => static::_getJsonSchemaTypeStructure(Strings::replacePostfix($jsName, '[]'), '', $schemaLocationUri),
					'minItems' => 0,
				];
				if (!empty($propertyDescription)) {
					$propertyType['description'] = $propertyDescription;
				}
			} else {
				$propertyType = static::_getJsonSchemaTypeStructure($jsName, $propertyDescription, $schemaLocationUri);
			}

			$schemaObject['properties'][$propertyName] = $propertyType;
		}
		$schemaFile->write(Arrays::encode($schemaObject, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
		$schemaFile->close();
	}

	/**
	 * Формируем массив описания типа для JSON схемы
	 *
	 * @param string $typeName
	 * @param string $propertyDescription
	 * @param string $schemaLocationUrl URL адрес папки, в которой будут находится JSON схемы
	 * @return array
	 */
	protected static function _getJsonSchemaTypeStructure($typeName, $propertyDescription, $schemaLocationUrl) {
		if (in_array($typeName, static::JSON_SCHEMA_INTERNAL_DATA_TYPES)) {
			$result = [
				'type' => $typeName,
			];
			if (!empty($propertyDescription)) {
				$result['description'] = $propertyDescription;
			}
			return $result;
		} else {
			return [
				'$ref' => Strings::replaceIfEndsWith($schemaLocationUrl, '/') . '/' . $typeName . static::EXTENSION_JSON,
			];
		}
	}

	/**
	 * Получаем описание свойства с пробелом вначале, если оно не пустое
	 *
	 * @param string $description
	 * @return string
	 */
	protected static function _getPropertyDescription($description) {
		if (!empty($description)) {
			return trim(str_replace(["\r", "\n"], ' ', $description));
		} else {
			return '';
		}
	}

	/**
	 * Определяем JS тип исходня из PHP типа
	 *
	 * @param string $fullClassName
	 * @param string $propertyName
	 * @param Var_ $propertyVar
	 * @param array $typeAliases
	 * @return string
	 * @throws InternalException
	 */
	protected static function _getJsVariableName($fullClassName, $propertyName, Var_ $propertyVar, array $typeAliases) {
		$propertyType = $propertyVar->getType();
		if ((empty($propertyType) && $propertyVar->getVariableName() === 'this') || $propertyType instanceof Self_ || $propertyType instanceof Static_ || $propertyType instanceof This) {
			throw new InternalException($fullClassName . '::' . $propertyName . ': ValueObject не может ссылаться сам на себя!');
		}

		if ($propertyType instanceof Compound) {
			throw new InternalException($fullClassName . '::' . $propertyName . ': Свойство ValueObject должно быть только одного типа!');
		}

		if ($propertyType instanceof Mixed) {
			throw new InternalException($fullClassName . '::' . $propertyName . ': Свойство ValueObject должно быть только одного типа!');
		}

		if ($propertyType instanceof Array_) {
			if ($propertyType->getValueType() instanceof Mixed) {
				throw new InternalException($fullClassName . '::' . $propertyName . ': Свойство ValueObject должно быть простым типом, либо объектом!');
			}
		}

		$propertyTypeString = static::_convertPsr4ToPsr0((string)$propertyType);
		if (array_key_exists($propertyTypeString, $typeAliases)) {
			return $typeAliases[$propertyTypeString];
		} else {
			return $propertyTypeString;
		}
	}
}