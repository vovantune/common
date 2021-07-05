<?php
declare(strict_types=1);

namespace ArtSkills\Test\TestCase\View\Helper;

use ArtSkills\TestSuite\AppTestCase;
use ArtSkills\View\Helper\FormHelper;
use Cake\Http\ServerRequest;
use Cake\View\View;

class FormTest extends AppTestCase
{

    /**
     * request
     *
     * @var ServerRequest
     */
    private $_request = null;

    /**
     * helper
     *
     * @var FormHelper
     */
    private $_helper = null;

    /** @inheritdoc */
    public function setUp()
    {
        $this->_request = new ServerRequest();
        $this->_helper = new FormHelper(new View($this->_request));
        parent::setUp();
    }

    /** Дописывание текста сразу после инпута */
    public function testAppend(): void
    {
        // по-умолчанию
        $result = $this->_helper->control('test');
        $expectedResult = '<div class="input text"><label for="test">Test</label><input type="text" name="test" id="test"/></div>';
        self::assertEquals($expectedResult, $result);

        // дописанный текст
        $result = $this->_helper->control('test', [
            'append' => 'append some text',
        ]);
        $expectedResult = '<div class="input text"><label for="test">Test</label><input type="text" name="test" id="test"/>append some text</div>';
        self::assertEquals($expectedResult, $result);

        // дописанный текст в <sub>
        $result = $this->_helper->control('test', [
            'sub' => 'append sub text',
        ]);
        $expectedResult = '<div class="input text"><label for="test">Test</label><input type="text" name="test" id="test"/> <sub>append sub text</sub></div>';
        self::assertEquals($expectedResult, $result);

        // дописанный текст при ошибке
        $errorConfig = [
            'schema' => [
                'errorField' => ['type' => 'string'],
            ],
            'errors' => [
                'errorField' => 'some error',
            ],
        ];
        $this->_helper->create($errorConfig);
        $result = $this->_helper->control('errorField', [
            'append' => 'append text when error',
        ]);
        $expectedResult = '<div class="input text error"><label for="errorfield">Error Field</label><input type="text" name="errorField" id="errorfield" class="form-error"/>append text when error<div class="error-message">some error</div></div>';
        self::assertEquals($expectedResult, $result);
    }

    /** Изменение шаблона контейнера и добавление ему атрибутов */
    public function testContainer(): void
    {
        // класс контейнера
        $result = $this->_helper->control('test', [
            'containerClass' => 'test-cont-class',
        ]);
        $expectedResult = '<div class="input text test-cont-class"><label for="test">Test</label><input type="text" name="test" id="test"/></div>';
        self::assertEquals($expectedResult, $result);

        // атрибуты контейнера
        $result = $this->_helper->control('test', [
            'container' => [
                'class' => 'some-class',
                'data-test' => 'test data',
            ],
        ]);
        $expectedResult = '<div class="input text some-class" data-test="test data"><label for="test">Test</label><input type="text" name="test" id="test"/></div>';
        self::assertEquals($expectedResult, $result);

        // другой контейнер
        $result = $this->_helper->control('test', [
            'container' => 'noDiv',
        ]);
        $expectedResult = '<label for="test">Test</label><input type="text" name="test" id="test"/>';
        self::assertEquals($expectedResult, $result);

        // другой контейнер с ошибкой
        $errorConfig = [
            'schema' => [
                'errorField' => ['type' => 'string'],
            ],
            'errors' => [
                'errorField' => 'some error',
            ],
        ];
        $this->_helper->create($errorConfig);
        $result = $this->_helper->control('errorField', [
            'append' => 'append text when error',
            'container' => 'noDiv',
        ]);
        $expectedResult = '<label for="errorfield">Error Field</label><input type="text" name="errorField" id="errorfield" class="form-error"/>append text when error<div class="error-message">some error</div>';
        self::assertEquals($expectedResult, $result);
    }

    /** изменение шаблона инпута */
    public function testInputChange(): void
    {
        // другой инпут
        $result = $this->_helper->control('test', [
            'inputTemplate' => 'inputDiv',
        ]);
        $expectedResult = '<div class="input text"><label for="test">Test</label><div><input type="text" name="test" id="test"/></div></div>';
        self::assertEquals($expectedResult, $result);

        // атрибуты дива другого инпута
        $result = $this->_helper->control('test', [
            'inputTemplate' => 'inputDiv',
            'divAttrs' => [
                'class' => 'col-sm-4',
            ],
        ]);
        $expectedResult = '<div class="input text"><label for="test">Test</label><div class="col-sm-4"><input type="text" name="test" id="test"/></div></div>';
        self::assertEquals($expectedResult, $result);

        // по-умолчанию, ничего не сбилось
        $result = $this->_helper->control('test');
        $expectedResult = '<div class="input text"><label for="test">Test</label><input type="text" name="test" id="test"/></div>';
        self::assertEquals($expectedResult, $result);
    }
}
