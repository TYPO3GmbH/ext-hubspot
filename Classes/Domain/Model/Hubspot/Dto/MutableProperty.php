<?php

declare(strict_types=1);


namespace T3G\Hubspot\Domain\Model\Hubspot\Dto;

use T3G\Hubspot\Domain\Model\Traits\PopulateFromArray;
use T3G\Hubspot\Domain\Model\Traits\Arrayable;

/**
 * A representation of the part of a Hubspot property containing all properties that can be modified.
 */
class MutableProperty
{
    use Arrayable;
    use PopulateFromArray;

    /**
     * @var string
     */
    protected $label = '';

    /**
     * @var string
     */
    protected $type = '';

    /**
     * @var string
     */
    protected $fieldType = '';

    /**
     * @var string
     */
    protected $groupName = '';

    /**
     * @var string
     */
    protected $description = '';

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var int
     */
    protected $displayOrder = -1;

    /**
     * @var bool
     */
    protected $hidden = false;

    /**
     * @var bool
     */
    protected $formField = false;

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel(string $label)
    {
        $this->label = $label;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getFieldType(): string
    {
        return $this->fieldType;
    }

    /**
     * @param string $fieldType
     */
    public function setFieldType(string $fieldType)
    {
        $this->fieldType = $fieldType;
    }

    /**
     * @return string
     */
    public function getGroupName(): string
    {
        return $this->groupName;
    }

    /**
     * @param string $groupName
     */
    public function setGroupName(string $groupName)
    {
        $this->groupName = $groupName;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * @return int
     */
    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    /**
     * @param int $displayOrder
     */
    public function setDisplayOrder(int $displayOrder)
    {
        $this->displayOrder = $displayOrder;
    }

    /**
     * @return bool
     */
    public function isHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * @param bool $hidden
     */
    public function setHidden(bool $hidden)
    {
        $this->hidden = $hidden;
    }

    /**
     * @return bool
     */
    public function isFormField(): bool
    {
        return $this->formField;
    }

    /**
     * @param bool $formField
     */
    public function setFormField(bool $formField)
    {
        $this->formField = $formField;
    }
}
