<?php
declare(strict_types=1);

namespace ArtSkills\ORM;

use ArtSkills\Error\InternalException;

class Entity extends \Cake\ORM\Entity
{
    /**
     * Проверка, что значение поля изменилось
     * потому что dirty() и extractOriginalChanged() могут срабатывать даже когда не изменилось, а при любом присвоении
     *
     * @param string $fieldName
     * @return bool
     */
    public function changed(string $fieldName): bool
    {
        return $this->get($fieldName) != $this->getOriginal($fieldName);
    }

    /**
	 * Удалить дочернюю сущность и проставить dirty
	 *
	 * @param string $childEntity
	 * @param null|int $index
	 * @return void
	 * @throws InternalException
	 */
    public function deleteChild(string $childEntity, ?int $index = null)
    {
        if (!array_key_exists($childEntity, $this->_properties)) {
            throw new InternalException("Unknown property $childEntity");
        } elseif (is_array($this->{$childEntity})) {
            if ($index === null) {
                $this->set($childEntity, []);
            } else {
                unset($this->{$childEntity}[$index]);
            }
        } else {
            $this->set($childEntity, null);
        }
        $this->setDirty($childEntity, true);
    }

    /**
     * Ошибки без разделения по полям
     *
     * @return string[]
     */
    public function getAllErrors(): array
    {
        $errors = $this->getErrors();
        if (empty($errors)) {
            return [];
        }
        return array_merge(...array_values($errors));
    }
}
