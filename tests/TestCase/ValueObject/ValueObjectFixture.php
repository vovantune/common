<?php
declare(strict_types=1);

namespace ArtSkills\Test\TestCase\ValueObject;

use ArtSkills\Lib\Strings;
use ArtSkills\ValueObject\ValueObject;
use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\Utility\String as CakeString;

/**
 * @method $this setField1(mixed $value)
 * @method $this setField2(mixed $value)
 * @method $this setField3(mixed $value)
 * @method $this setTimeField(mixed $value)
 * @method $this setDateField(mixed $value)
 */
class ValueObjectFixture extends ValueObject
{
    const EXCLUDE_EXPORT_PROPS = [
        'field2',
    ];

    const TIME_FIELDS = [
        'timeField',
    ];

    const DATE_FIELDS = [
        'dateField'
    ];

    /**
     * блаблабла
     * трололо
     *
     * @var string
     */
    public $field1 = 'asd';

    /** @var string */
    public $field2 = 'qwe';
    /** @var Strings */
    public $field3;

    /** @var CakeString */
    public $field4; // @phpstan-ignore-line

    /** @var Time */
    public $timeField;

    /** @var Date */
    public $dateField;
}
