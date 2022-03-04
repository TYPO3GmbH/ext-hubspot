<?php

declare(strict_types=1);


namespace T3G\Hubspot\Service\Event\Traits;


trait MappedTableRecordTrait
{
    /**
     * @var array
     */
    protected $mappedTableRecord;

    /**
     * @return array
     */
    public function getMappedTableRecord(): array
    {
        return $this->mappedTableRecord;
    }

    /**
     * @param array $mappedTableRecord
     */
    public function setMappedTableRecord(array $mappedTableRecord)
    {
        $this->mappedTableRecord = $mappedTableRecord;
    }
}
