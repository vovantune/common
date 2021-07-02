<?php
declare(strict_types=1);

namespace ArtSkills\Excel;

use ArtSkills\Error\InternalException;
use ArtSkills\Lib\Arrays;
use XLSXWriter;

class ExcelWriter
{
    public const DEFAULT_WIDTH = 11.5;

    /** @var string[] Имена параметров стилей */
    public const STYLE_PARAM_NAMES = [
        'font',
        'font-size',
        'font-style',
        'color',
        'fill',
        'valign',
        'halign',
        'border',
        'border-style',
        'wrap_text',
    ];

    public const STYLE_URL = ['font-style' => 'underline', 'color' => '#2358C5'];

    public const STYLE_DEFAULT_HEADER = [
        'font-style' => 'bold',
        'wrap_text' => true,
        'fill' => '#F3F3F3',
        'valign' => 'bottom',
        'border' => 'left,right,top,bottom',
        'border-style' => 'thin',
    ];

    public const STYLE_DEFAULT_ROW = [
        'border' => 'left,right,top,bottom',
        'border-style' => 'thin',
    ];

    /**
     * @var string
     */
    private string $_filePath;

    /**
     * @var ?XLSXWriter
     */
    private ?XLSXWriter $_writer;

    /**
     * @var array<string, FieldMapElement[]>
     */
    private array $_fieldMap;

    /**
     * @var array<string, array<string, float>>
     */
    private array $_widths;

    /**
     * @var array<string, bool> Массив заполненных заголовков
     */
    private array $_writtenHeaders;

    /**
     * ExcelWriter constructor.
     *
     * @param string $filePath Абсолютный путь к файлу
     */
    public function __construct(string $filePath)
    {
        $this->_filePath = $filePath;
        $this->_writer = new XLSXWriter();
    }

    /**
     * Заполняем ассоциацию строковых индексов и числовых индексов
     *
     * @param string $pageName
     * @param FieldMapElement[] $fieldMap
     * @param array<string, float>|null $widths
     * @return $this
     */
    public function setSheetFieldMap(string $pageName, array $fieldMap, ?array $widths = null): self
    {
        if (empty($this->_fieldMap)) {
            $this->_fieldMap = [];
        }

        $maxIndex = 0;
        foreach ($fieldMap as $element) {
            if ($element->workIndex > $maxIndex) {
                $maxIndex = $element->workIndex;
            }
        }

        $sortedMap = [];
        for ($i = 0; $i <= $maxIndex; $i++) {
            $sortedMap[$i] = null;
        }

        foreach ($fieldMap as $element) {
            $sortedMap[$element->workIndex] = $element;
        }
        $this->_fieldMap[$pageName] = $sortedMap;
        $this->_widths[$pageName] = $widths;
        return $this;
    }

    /**
     * Шапка страницы
     *
     * @param string $pageName
     * @param array<string, string|int|float> $rowData
     * @param array|null $rowStyles
     * @param float|null $height
     * @return $this
     * @throws InternalException
     *
     * @SuppressWarnings(PHPMD.MethodArgs)
     * @phpstan-ignore-next-line
     */
    public function writeSheetHeader(string $pageName, array $rowData, ?array $rowStyles = null, ?float $height = null): self
    {
        $this->_writtenHeaders[$pageName] = true;

        $rowStyles = (!empty($rowStyles) ? $rowStyles : []) + self::STYLE_DEFAULT_HEADER;
        foreach ($this->_fieldMap[$pageName] as $element) {
            /** @var FieldMapElement $element */
            if (empty($element)) {
                continue;
            }
            if (in_array($element->type, [
                FieldMapElement::TYPE_INT,
                FieldMapElement::TYPE_FLOAT,
                FieldMapElement::TYPE_MONEY,
            ], true)) {
                Arrays::initPath($rowStyles, $element->name, []);
                $rowStyles[$element->name]['halign'] = 'right';
            }
        }

        [$headerTypes, $widths] = $this->_getSheetTypes($pageName);
        [$dataByIndex, $stylesByIndex] = $this->_buildSheetRow($pageName, $rowData, $rowStyles, $height);

        $this->_writer->writeSheetHeader($pageName, array_combine($dataByIndex, $headerTypes), ['widths' => $widths, 'freeze_rows' => 1] + $stylesByIndex);
        return $this;
    }

    /**
     * Инициализация типов на странице
     *
     * @param string $pageName
     * @return array [types, widths]
     *
     * @SuppressWarnings(PHPMD.MethodArgs)
     * @phpstan-ignore-next-line
     */
    private function _getSheetTypes(string $pageName): array
    {
        $width = $this->_widths[$pageName];

        $widthsByIndex = [];
        $typesByIndex = [];

        foreach ($this->_fieldMap[$pageName] as $index => $typeElement) {
            /** @var FieldMapElement|null $typeElement */
            if (!empty($typeElement)) {
                $typesByIndex[$index] = $typeElement->type;
                if (!empty($width)) {
                    if (!empty($width[$typeElement->name])) {
                        $widthsByIndex[$index] = (float)$width[$typeElement->name];
                    } else {
                        $widthsByIndex[$index] = self::DEFAULT_WIDTH;
                    }
                }
            } else {
                $typesByIndex[$index] = FieldMapElement::TYPE_FORMULA;
                if (!empty($width)) {
                    $widthsByIndex[$index] = self::DEFAULT_WIDTH;
                }
            }
        }

        return [
            $typesByIndex,
            $widthsByIndex,
        ];
    }

    /**
     * @param string $pageName
     * @param array<string, string|int|float> $rowData
     * @param array|null $rowStyles
     * @param float|null $height
     * @return self
     * @throws InternalException
     *
     * @SuppressWarnings(PHPMD.MethodArgs)
     * @phpstan-ignore-next-line
     */
    public function writeSheetRow(string $pageName, array $rowData, ?array $rowStyles = null, ?float $height = null): self
    {
        if (empty($this->_writtenHeaders[$pageName])) {
            [$headerTypes, $widths] = $this->_getSheetTypes($pageName);
            $this->_writer->writeSheetHeader($pageName, $headerTypes, ['widths' => $widths, 'suppress_row' => true]);
            $this->_writtenHeaders[$pageName] = true;
        }

        if (empty($this->_fieldMap[$pageName])) {
            throw new InternalException("Не инициализирована страница $pageName (setSheetFieldMap)");
        }

        $rowStyles = (!empty($rowStyles) ? $rowStyles : []) + self::STYLE_DEFAULT_ROW;

        [$dataByIndex, $stylesByIndex] = $this->_buildSheetRow($pageName, $rowData, $rowStyles, $height);
        $this->_writer->writeSheetRow($pageName, $dataByIndex, $stylesByIndex);
        return $this;
    }

    /**
     * Формируем массивы на запись
     *
     * @param string $pageName
     * @param array<string, string|int|float> $rowData
     * @param array|null $rowStyles
     * @param float|null $height
     * @return array
     *
     * @SuppressWarnings(PHPMD.MethodArgs)
     * @phpstan-ignore-next-line
     */
    private function _buildSheetRow(string $pageName, array $rowData, ?array $rowStyles = null, ?float $height = null): array
    {
        $dataByIndex = [];
        $stylesByIndex = [];
        if (!empty($height)) {
            $stylesByIndex['height'] = $height;
        }
        $defaultStyle = $this->_buildDefaultRowStyle($rowStyles);

        foreach ($this->_fieldMap[$pageName] as $index => $typeElement) {
            /** @var FieldMapElement|null $typeElement */
            if (!empty($typeElement)) {
                $elementName = $typeElement->name;
                if (array_key_exists($elementName, $rowData)) {
                    $dataByIndex[$index] = $rowData[$elementName];
                } else {
                    $dataByIndex[$index] = $typeElement->defaultValue ?? '';
                }

                if (!empty($rowStyles)) {
                    if (array_key_exists($elementName, $rowStyles)) {
                        $stylesByIndex[$index] = $rowStyles[$elementName] + $defaultStyle;
                    } else {
                        $stylesByIndex[$index] = $defaultStyle;
                    }
                }
            } else {
                $dataByIndex[$index] = '';
                if (!empty($rowStyles)) {
                    $stylesByIndex[$index] = $defaultStyle;
                }
            }
        }
        return [$dataByIndex, $stylesByIndex];
    }

    /**
     * Формируем дефолтовый стиль для строки
     *
     * @param array|null $rowStyle
     * @return array
     *
     * @SuppressWarnings(PHPMD.MethodArgs)
     * @phpstan-ignore-next-line
     */
    private function _buildDefaultRowStyle(?array $rowStyle): array
    {
        if (empty($rowStyle)) {
            return [];
        }

        $result = [];
        foreach (self::STYLE_PARAM_NAMES as $paramName) {
            if (array_key_exists($paramName, $rowStyle)) {
                $result[$paramName] = $rowStyle[$paramName];
            }
        }
        return $result;
    }

    /**
     * Закрывает файл
     *
     * @return void
     */
    public function close()
    {
        $this->_writer->writeToFile($this->_filePath);
        $this->_writer = null;
    }

    /**
     * Деструктор и в Африке деструктор
     */
    public function __destruct()
    {
        if (!empty($this->_writer)) {
            $this->close();
        }
    }
}
