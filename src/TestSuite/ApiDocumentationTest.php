<?php
declare(strict_types=1);

namespace ArtSkills\TestSuite;

use ArtSkills\Lib\Strings;
use PHPUnit\Framework\Assert;

/**
 * Класс для теста корректного описания JSON схемы для Swagger
 *
 * @see ApiDocumentationControllerTest
 */
class ApiDocumentationTest
{
    /**
     * Кеш существующих ссылок
     *
     * @var string[]
     */
    private array $_referenceCache = [];

    /**
     * Тест API документации в JSON и в HTML формате. Может работать относительно долго, ибо строит апи по всему коду.
     *
     * @param array $jsonSchema
     * @SuppressWarnings(PHPMD.MethodArgs)
     */
    public function testSchema(array $jsonSchema)
    {
        $this->_referenceCache = [];

        $this->_checkForRequiredProperties($jsonSchema, [
            'openapi',
            'info',
            'paths',
        ], 'Пустое свойство основного объекта ');
        $this->_testDefinitions($jsonSchema);
        $this->_testPaths($jsonSchema);

        if (!empty($jsonSchema['parameters'])) {
            foreach ($jsonSchema['parameters'] as $parameterName => $parameter) {
                $this->_checkParameter($parameter, '#/parameters/' . $parameterName, $jsonSchema);
            }
        }
    }

    /**
     * Тест структур
     *
     * @param array $jsonSchema
     * @SuppressWarnings(PHPMD.MethodArgs)
     */
    private function _testDefinitions(array $jsonSchema)
    {
        foreach ($jsonSchema['components']['schemas'] as $definitionName => $definition) {
            $this->_testDefinition($definition, '#/components/schemas/' . $definitionName, $jsonSchema);
        }
    }

    /**
     * Тесто свойства
     *
     * @param array $definition
     * @param string $definitionPath
     * @param array $jsonSchema
     * @SuppressWarnings(PHPMD.MethodArgs)
     */
    private function _testDefinition(array $definition, string $definitionPath, array $jsonSchema)
    {
        if (!empty($definition['$ref'])) {
            $this->_checkReference($definition['$ref'], $definitionPath, $jsonSchema);
        } elseif (!isset($definition['type'])) {
            if (!empty($definition['allOf'])) {
                $checkObject = $definition['allOf'];
            } else {
                $checkObject = null;
            }
            Assert::assertNotNull($checkObject, 'Не описаны свойства в ' . $definitionPath);
            foreach ($checkObject as $propertyIndex => $property) {
                $this->_testDefinition($property, $definitionPath . '::' . $propertyIndex, $jsonSchema);
            }
        } elseif ($definition['type'] === 'object') {
            Assert::assertNotEmpty($definition['properties'], 'Не описаны свойства в ' . $definitionPath);
            foreach ($definition['properties'] as $propertyName => $property) {
                $this->_testDefinitionElement($definitionPath . '::' . $propertyName, $property, $jsonSchema);
            }
        } elseif ($definition['type'] === 'array') {
            foreach ($definition['items']['properties'] as $propertyName => $property) {
                $this->_testDefinitionElement($definitionPath . '::' . $propertyName, $property, $jsonSchema);
            }
        } else {
            $this->_testDefinitionElement($definitionPath, $definition, $jsonSchema);
        }
    }

    /**
     * Проверка на заполнение обязательных параметров свойства
     *
     * @param string $propertyPath
     * @param array $property
     * @param array $jsonSchema
     * @SuppressWarnings(PHPMD.MethodArgs)
     */
    private function _testDefinitionElement(string $propertyPath, array $property, array $jsonSchema)
    {
        if (empty($property['$ref'])) {
            $this->_checkForRequiredProperties($property, [
                'type',
            ], 'Пустое свойство ' . $propertyPath);
        } else {
            $this->_checkReference($property['$ref'], $propertyPath, $jsonSchema);
        }
    }

    /**
     * Проверка пулов запросов
     *
     * @param array $jsonSchema
     * @SuppressWarnings(PHPMD.MethodArgs)
     */
    private function _testPaths(array $jsonSchema)
    {
        $operationIds = [];
        foreach ($jsonSchema['paths'] as $pathUrl => $path) {
            foreach ($path as $requestMethod => $pathElement) {
                $this->_checkForRequiredProperties($pathElement, [
                    'tags',
                    'summary',
                    'operationId',
                    'responses',
                ], 'Пустое свойство ' . $pathUrl . '::' . $requestMethod);

                if (!empty($pathElement['parameters'])) {
                    foreach ($pathElement['parameters'] as $parameterId => $parameter) {
                        $parameterPath = $pathUrl . '::' . $requestMethod . '::parameters::' . $parameterId;
                        $this->_checkParameter($parameter, $parameterPath, $jsonSchema);
                    }
                }

                Assert::assertTrue(!empty($pathElement['responses'][200]) || !empty($pathElement['responses'][302]));
                foreach ($pathElement['responses'] as $responseId => $response) {
                    if ($responseId === 200) {
                        $values = ['description', 'content'];
                    } else {
                        $values = ['description'];
                    }
                    $this->_checkForRequiredProperties($response, $values, 'Пустое свойство ' . $pathUrl . '::' . $requestMethod . '::responses::' . $responseId);
                }

                Assert::assertFalse(in_array($pathElement['operationId'], $operationIds), 'Не уникальной operationId для ' . $pathUrl . '::' . $requestMethod);
            }
        }
    }

    /**
     * Проверяем параметр
     *
     * @param array $parameter
     * @param string $parameterPath
     * @param array $jsonSchema
     * @SuppressWarnings(PHPMD.MethodArgs)
     */
    private function _checkParameter(array $parameter, string $parameterPath, array $jsonSchema)
    {
        if (empty($parameter['$ref'])) {
            $this->_checkForRequiredProperties($parameter, [
                'name',
                'in',
                'description',
                'required',
            ], 'Пустое свойство ' . $parameterPath);

            if (!empty($parameter['schema'])) {
                $this->_testDefinition($parameter['schema'], $parameterPath . '::schema', $jsonSchema);
            } else {
                Assert::assertNotNull($parameter['type'], 'Пустое свойство ' . $parameterPath . '::type');
            }
        } else {
            $this->_checkReference($parameter['$ref'], $parameterPath, $jsonSchema);
        }
    }

    /**
     * Проверка на наличие обязательных свойств
     *
     * @param array $object
     * @param string[] $propertyList
     * @param string $errorMessage
     * @SuppressWarnings(PHPMD.MethodArgs)
     */
    private function _checkForRequiredProperties(array $object, array $propertyList, string $errorMessage)
    {
        foreach ($propertyList as $propertyName) {
            Assert::assertTrue(isset($object[$propertyName]), $errorMessage . '::' . $propertyName);
        }
    }

    /**
     * Проверка на существование ссылки
     *
     * @param string $reference
     * @param string $propertyPath
     * @param array $jsonSchema
     * @SuppressWarnings(PHPMD.MethodArgs)
     */
    private function _checkReference(string $reference, string $propertyPath, array $jsonSchema)
    {
        if (in_array($reference, $this->_referenceCache)) {
            return;
        }

        Assert::assertTrue(Strings::startsWith($reference, '#/'), 'Внешние ссылки запрещены');
        $reference = Strings::replacePrefix($reference, '#/');
        $referenceArr = explode('/', $reference);
        $errorMessage = $propertyPath . ' некорректная ссылка';
        $pathStep = $jsonSchema;
        foreach ($referenceArr as $pathElement) {
            Assert::assertTrue(isset($pathStep[$pathElement]) && !empty($pathStep[$pathElement]), $errorMessage);
            $pathStep = $pathStep[$pathElement];
        }

        $this->_referenceCache[] = $reference;
    }
}
