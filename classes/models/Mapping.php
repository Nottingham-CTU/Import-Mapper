<?php

namespace Nottingham\ImportMapper\Models;

/**
 * Represents a mapping
 */
final readonly class Mapping
{
    /**
     * @param string $id
     * @param string $name
     * @param array $csvFields
     * @param array $fieldMappings
     * @param MatchingConfig $matchingConfig
     * @param string $status
     * @param string $projectStructureHash
     * @param string $createdAt
     * @param string|null $updatedAt
     */
    private function __construct(
        public string         $id,
        public string         $name,
        public array          $csvFields,
        public array          $fieldMappings,
        public MatchingConfig $matchingConfig,
        public string        $status,
        public string        $projectStructureHash,
        private string        $createdAt,
        private ?string       $updatedAt = null
    )
    {
    }

    /**
     * Create Mapping from array data
     *
     * @param array $data mapping data from project settings
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $mappingsData = $data['fieldMappings'] ?? [];
        $fieldMappings = array_map(
            fn($mapping) => FieldMapping::fromArray($mapping),
            $mappingsData
        );

        return new self(
            $data['id'] ?? '',
            $data['name'] ?? '',
            $data['csvFields'] ?? [],
            $fieldMappings,
            MatchingConfig::fromArray($data['matching'] ?? []),
            $data['status'] ?? 'draft',
            $data['projectStructureHash'] ?? '',
            $data['created_at'] ?? date('Y-m-d H:i:s'),
            $data['updated_at'] ?? null
        );
    }

    /**
     * Convert mapping to array for persistence
     *
     * @return array
     */
    public function toArray(): array
    {
        $mappingsArray = array_map(
            fn(FieldMapping $fm) => $fm->toArray(),
            $this->fieldMappings
        );

        return [
            'id' => $this->id,
            'name' => $this->name,
            'csvFields' => $this->csvFields,
            'fieldMappings' => $mappingsArray,
            'matching' => $this->matchingConfig->toArray(),
            'status' => $this->status,
            'projectStructureHash' => $this->projectStructureHash,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    /**
     * Find a FieldMapping by its ID
     *
     * @param string $fieldMappingId Field mapping ID to find
     * @return FieldMapping|null Found field mapping or null
     */
    public function findFieldMappingById(string $fieldMappingId): ?FieldMapping
    {
        return array_find($this->fieldMappings, fn($fm) => $fm->id === $fieldMappingId);
    }
}
