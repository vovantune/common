<?php

namespace ArtSkills\Test\Controller\TestControllerTest;

use ArtSkills\TestSuite\AppControllerTestCase;

class TestControllerTest extends AppControllerTestCase
{
	/** Успешный JSON ответ */
	public function testJsonOk() {
		$this->get('/test/getJsonOk');
		$this->assertJsonOKEquals(['testProperty' => 123]);
	}

	/** Ответ об ошибке */
	public function testJsonError() {
		$this->get('/test/getJsonError');
		$this->assertJsonErrorEquals('Тестовая ошибка', 'Некорректное сообщение об ошибке', ['errorProperty' => 123]);

	}

	/** Пустой JSON ответ */
	public function testEmptyJson() {
		$this->get('/test/getEmptyJson');
		$this->assertJsonOKEquals([]);
	}

	/** ValueObject в качестве результата */
	public function testGetValueObject() {
		$this->get('/test/getValueObjectJson');
		$this->assertJsonOKEquals([
			'testProperty' => 'testData',
		]);
	}
}