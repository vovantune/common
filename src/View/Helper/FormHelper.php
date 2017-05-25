<?php
namespace ArtSkills\View\Helper;

use ArtSkills\Lib\Arrays;
use Cake\View\View;

class FormHelper extends \Cake\View\Helper\FormHelper
{

	/**
	 * Переопределил несколько шаблонов:
	 * inputContainer - добавил возможность дописать произвольный текст к инпуту и настроить атрибуты;
	 * inputContainerError - то же самое;
	 * inputDiv - input обёрнутый в div для настраиваемости (например, чтобы вешать на него классы col из бутстрапа)
	 * noDivContainer - без контейнера, только содержимое
	 *
	 * @var array
	 */
	private $_resetTemplates = [
		'inputContainer' => '<div class="input {{type}}{{required}}{{containerClass}}"{{containerAttrs}}>{{content}}{{append}}</div>',
		'inputContainerError' => '<div class="input {{type}}{{required}} error{{containerClass}}"{{containerAttrs}}>{{content}}{{append}}{{error}}</div>',
		'noDivContainer' => '{{content}}{{append}}',
		'noDivContainerError' => '{{content}}{{append}}{{error}}',
		'inputDiv' => '<div{{divAttrs}}><input type="{{type}}" name="{{name}}"{{attrs}}/></div>',
	];

	/** @inheritdoc */
	public function __construct(View $View, array $config = []) {
		$this->_defaultConfig['templates'] = $this->_resetTemplates += $this->_defaultConfig['templates'];
		parent::__construct($View, $config);
	}

	/**
	 * @inheritdoc
	 * Добавил опции:
	 * 'append' - добавить произвольный текст к инпуту, строка;
	 * 'sub' - append, обёрнутый в тег sub, строка;
	 * 'container' - использовать нестандартный контейнер.
	 * Строка - название шаблона,
	 * Либо массив: 'template' - название шаблона, а все остальные элементы станут атрибутами;
	 * 'containerClass' - сокращение для ['container']['class'], строка;
	 * 'inputTemplate' - использовать нестандартный шаблон для инпута, строка;
	 * 'divAttrs' - атрибуты для тега div в шаблоне 'inputDiv', массив;
	 */
	protected function _parseOptions($fieldName, $options) {
		$options = parent::_parseOptions($fieldName, $options);
		if (!empty($options['inputTemplate'])) {
			Arrays::initPath($options, 'templates', []);
			$resetTemplate = $options['inputTemplate'];
			$options['templates']['input'] = $this->getConfig("templates.$resetTemplate");
			unset($options['inputTemplate']);
		}

		Arrays::initPath($options, 'templateVars', []);

		if (!empty($options['sub'])) {
			$options['append'] = " <sub>{$options['sub']}</sub>";
		}
		unset($options['sub']);

		$templateVars = ['append', 'container', 'containerClass', 'divAttrs'];
		foreach ($templateVars as $var) {
			if (array_key_exists($var, $options)) {
				$options['templateVars'][$var] = $options[$var];
			}
			unset($options[$var]);
		}

		if (!empty($options['templateVars']['divAttrs'])) {
			$options['templateVars']['divAttrs'] = $this->templater()->formatAttributes($options['templateVars']['divAttrs']);
		}

		if (!empty($options['templateVars']['container']) && is_array($options['templateVars']['container'])) {
			$containerConf = $options['templateVars']['container'];
			unset($options['templateVars']['container']);

			$copyFields = [
				'template' => 'container',
				'class' => 'containerClass',
			];
			foreach ($copyFields as $confKey => $varName) {
				if (!empty($containerConf[$confKey])) {
					$options['templateVars'][$varName] = $containerConf[$confKey];
				}
				unset($containerConf[$confKey]);
			}

			if (!empty($containerConf)) {
				$options['templateVars']['containerAttrs'] = $containerConf;
			}
		}

		return $options;
	}

	/**
	 * @inheritdoc
	 * Добавил возможность использовать кастомный контейнер $options['options']['templateVars']['container'] . 'Container';
	 * И там же добавил 'containerClass' 'containerAttrs' для добавления классов и атрибутов контейнеру
	 */
	protected function _inputContainerTemplate($options) {
		$templateVars = isset($options['options']['templateVars']) ? $options['options']['templateVars'] : [];
		$containerType = $options['options']['type'];
		if (!empty($templateVars['container'])) {
			$containerType = $templateVars['container'];
			unset($templateVars['container']);
		}

		$inputContainerTemplate = $containerType . 'Container' . $options['errorSuffix'];
		if (!$this->templater()->get($inputContainerTemplate)) {
			$inputContainerTemplate = 'inputContainer' . $options['errorSuffix'];
		}

		if (!empty($templateVars['containerAttrs'])) {
			$templateVars['containerAttrs'] = $this->templater()->formatAttributes($templateVars['containerAttrs']);
		}
		if (!empty($templateVars['containerClass'])) {
			$templateVars['containerClass'] = ' ' . $templateVars['containerClass'];
		}

		return $this->formatTemplate($inputContainerTemplate, [
			'content' => $options['content'],
			'error' => $options['error'],
			'required' => $options['options']['required'] ? ' required' : '',
			'type' => $options['options']['type'],
			'templateVars' => $templateVars,
		]);
	}

}