<?php

namespace Nottingham\ImportMapper\Repositories;

use Exception;
use ExternalModules\AbstractExternalModule;
use Nottingham\ImportMapper\Models\Mapping;

/**
 * Data access class for mappings stored in project settings
 */
final readonly class MappingRepository
{
    private const string SETTING_KEY = 'mappings';

    /**
     * @param AbstractExternalModule $module
     */
    public function __construct(private AbstractExternalModule $module)
    {
    }

    /**
     * Save a new mapping
     *
     * @param Mapping $mapping The mapping to save
     * @return string The generated mapping ID
     */
    public function save(Mapping $mapping): string
    {
        $mappings = $this->getAllAsArrays();
        $id = uniqid();

        $mappingData = $mapping->toArray();
        $mappingData['id'] = $id;

        $mappings[] = $mappingData;
        $this->module->setProjectSetting(self::SETTING_KEY, $mappings);

        return $id;
    }

    /**
     * Update an existing mapping
     *
     * @param string $id The mapping ID to update
     * @param Mapping $mapping The updated mapping data
     * @return void
     */
    public function update(string $id, Mapping $mapping): void
    {
        $mappings = $this->getAllAsArrays();

        foreach ($mappings as $index => $existingMapping) {
            if ($existingMapping['id'] === $id) {
                $mappingData = $mapping->toArray();
                $mappingData['id'] = $id;
                $mappings[$index] = $mappingData;
                break;
            }
        }

        $this->module->setProjectSetting(self::SETTING_KEY, $mappings);
    }

    /**
     * Delete a mapping by ID
     *
     * @param string $id The mapping ID to delete
     * @return void
     */
    public function delete(string $id): void
    {
        $items = $this->getAllAsArrays();

        foreach ($items as $index => $item) {
            if ($item['id'] === $id) {
                unset($items[$index]);
                break;
            }
        }

        $items = array_values($items);
        $this->module->setProjectSetting(self::SETTING_KEY, $items);
    }

    /**
     * Find a mapping by ID
     *
     * @param string $id The mapping ID
     * @param int|null $pid ProjectStructure ID (required in cron context)
     * @return Mapping The mapping
     * @throws Exception If mapping not found
     */
    public function findById(string $id, ?int $pid = null): Mapping
    {
        $data = $this->findArrayById($id, $pid);
        if (!$data) {
            throw new Exception("Mapping with ID '$id' not found");
        }
        return Mapping::fromArray($data);
    }

    /**
     * Get all mappings
     *
     * @param int|null $pid ProjectStructure ID (required in cron context)
     * @return Mapping[] Array of Mapping objects
     */
    public function findAll(?int $pid = null): array
    {
        $mappings = $this->getAllAsArrays($pid);

        return array_map(
            fn($data) => Mapping::fromArray($data),
            $mappings
        );
    }

    /**
     * Get all mappings as raw arrays
     *
     * @param int|null $pid ProjectStructure ID (required in cron context)
     * @return array Array of mapping data
     */
    private function getAllAsArrays(?int $pid = null): array
    {
        return $this->module->getProjectSetting(self::SETTING_KEY, $pid) ?? [];
    }

    /**
     * Find raw mapping data by ID
     *
     * @param string $id The mapping ID
     * @param int|null $pid ProjectStructure ID (required in cron context)
     * @return array|null The mapping data or null if not found
     */
    private function findArrayById(string $id, ?int $pid = null): ?array
    {
        $items = $this->getAllAsArrays($pid);

        return array_find($items, fn($item) => $item['id'] === $id);
    }
}
