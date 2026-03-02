<?php

namespace Nottingham\ImportMapper\Controllers;

use Exception;
use Nottingham\ImportMapper\Repositories\MappingRepository;
use Nottingham\ImportMapper\Services\ImportJobManager;
use Nottingham\ImportMapper\Services\ProjectService;
use Nottingham\ImportMapper\Models\Mapping;
use Nottingham\ImportMapper\Services\PermissionService;
use Nottingham\ImportMapper\Services\Validation\MappingValidator;

/**
 * Mapping data AJAX actions
 */
final readonly class MappingController
{
    /**
     * Constructor
     *
     * @param MappingRepository $mappingRepository Mapping repo
     * @param PermissionService $permissionService Permission checking service
     * @param ProjectService $projectService ProjectStructure service
     * @param MappingValidator $mappingValidator Mapping validation service
     * @param ImportJobManager $jobManager Job manager for lock checks
     */
    public function __construct(
        private MappingRepository $mappingRepository,
        private PermissionService $permissionService,
        private ProjectService    $projectService,
        private MappingValidator  $mappingValidator,
        private ImportJobManager  $jobManager
    )
    {

    }

    /**
     * Save new mapping
     *
     * @param array $payload Request payload containing mapping data
     * @return array Response with success status and mapping ID
     */
    public function save(array $payload): array
    {
        if (!$this->permissionService->canModifyMappings()) {
            return [
                'success' => false,
                'errors' => ['You do not have permission to create mappings']
            ];
        }

        // Validate all mapping data
        $validationErrors = $this->mappingValidator->validate($payload);
        if (!empty($validationErrors)) {
            return [
                'success' => false,
                'errors' => $validationErrors
            ];
        }

        $mappingData = [
            'name' => trim($payload['name']),
            'csvFields' => $payload['csvFields'],
            'fieldMappings' => $payload['fieldMappings'],
            'matching' => $payload['matching'],
            'status' => $payload['status'],
            'projectStructureHash' => $this->projectService->getProjectStructureHash(),
            'created_at' => date('c')
        ];

        $mapping = Mapping::fromArray($mappingData);
        $id = $this->mappingRepository->save($mapping);

        return [
            'success' => true,
            'id' => $id
        ];
    }

    /**
     * Update existing mapping
     *
     * @param array $payload Request payload containing mapping ID and updated data
     * @return array Response with success status and mapping ID
     */
    public function update(array $payload): array
    {
        if (!$this->permissionService->canModifyMappings()) {
            return [
                'success' => false,
                'errors' => ['You do not have permission to update mappings']
            ];
        }

        $lockedIds = $this->jobManager->getLockedMappingIds($this->jobManager->getProjectId());
        if (in_array($payload['id'] ?? '', $lockedIds, true)) {
            return [
                'success' => false,
                'errors' => ['This mapping cannot be edited while an import is in progress.'],
            ];
        }

        try {
            // Validate all mapping data
            $validationErrors = $this->mappingValidator->validate($payload);
            if (!empty($validationErrors)) {
                return [
                    'success' => false,
                    'errors' => $validationErrors
                ];
            }

            $id = $payload['id'];
            $existingMapping = $this->mappingRepository->findById($id);
            $existingData = $existingMapping->toArray();

            $mappingData = [
                'name' => trim($payload['name']),
                'csvFields' => $payload['csvFields'],
                'fieldMappings' => $payload['fieldMappings'],
                'matching' => $payload['matching'],
                'status' => $payload['status'],
                'projectStructureHash' => $this->projectService->getProjectStructureHash(),
                'created_at' => $existingData['created_at'],
                'updated_at' => date('c')
            ];

            $mapping = Mapping::fromArray($mappingData);
            $this->mappingRepository->update($id, $mapping);

            return [
                'success' => true,
                'id' => $id
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * Update mapping status only, without full validation
     * Used when marking a mapping as draft due to project structure changes
     *
     * @param array $payload Request payload containing mapping ID and status
     * @return array Response with success status and mapping ID
     */
    public function updateStatus(array $payload): array
    {
        if (!$this->permissionService->canModifyMappings()) {
            return [
                'success' => false,
                'errors' => ['You do not have permission to update mappings']
            ];
        }

        $lockedIds = $this->jobManager->getLockedMappingIds($this->jobManager->getProjectId());
        if (in_array($payload['id'] ?? '', $lockedIds, true)) {
            return [
                'success' => false,
                'errors' => ['This mapping cannot be edited while an import is in progress.'],
            ];
        }

        try {
            $id = $payload['id'] ?? '';
            $status = $payload['status'] ?? '';

            if (!in_array($status, ['draft', 'final'], true)) {
                return [
                    'success' => false,
                    'errors' => ['Invalid status value']
                ];
            }

            $existingMapping = $this->mappingRepository->findById($id);
            $existingData = $existingMapping->toArray();
            $mappingData = array_merge($existingData, [
                'status' => $status,
                'updated_at' => date('c'),
            ]);

            $mapping = Mapping::fromArray($mappingData);
            $this->mappingRepository->update($id, $mapping);

            return [
                'success' => true,
                'id' => $id,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * Copy mapping
     *
     * @param array $payload Request payload containing mapping ID to copy
     * @return array Response with success status and new mapping data
     */
    public function copy(array $payload): array
    {
        if (!$this->permissionService->canModifyMappings()) {
            return [
                'success' => false,
                'errors' => ['You do not have permission to copy mappings']
            ];
        }

        try {
            // Get source mapping
            $sourceMapping = $this->mappingRepository->findById($payload['id']);
            $copyData = $sourceMapping->toArray();

            // Remove fields that shouldn't be copied
            unset($copyData['id']);
            unset($copyData['created_at']);
            unset($copyData['updated_at']);
            unset($copyData['projectStructureHash']);

            // Update copy metadata
            $copyData['name'] = $copyData['name'] . ' (Copy)';

            // Calculate current hash
            $copyData['projectStructureHash'] = $this->projectService->getProjectStructureHash();

            // Save the copy
            $newMapping = Mapping::fromArray($copyData);
            $newId = $this->mappingRepository->save($newMapping);
            $savedMapping = $this->mappingRepository->findById($newId);

            return [
                'success' => true,
                'mapping' => $savedMapping->toArray()
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * Delete mapping
     *
     * @param array $payload Request payload containing mapping ID to delete
     * @return array Response with success status
     */
    public function delete(array $payload): array
    {
        if (!$this->permissionService->canModifyMappings()) {
            return [
                'success' => false,
                'errors' => ['You do not have permission to delete mappings']
            ];
        }

        $this->mappingRepository->delete($payload['id']);

        return [
            'success' => true
        ];

    }

    /**
     * Get single mapping
     *
     * @param array $payload Request payload containing mapping ID
     * @return array Response with success status and mapping data
     */
    public function get(array $payload): array
    {
        if (!$this->permissionService->canViewMappings()) {
            return [
                'success' => false,
                'errors' => ['You do not have permission to view mappings']
            ];
        }

        try {
            $mapping = $this->mappingRepository->findById($payload['id']);
            $mappingData = $mapping->toArray();

            // Check if project structure has changed since mapping was saved
            $currentHash = $this->projectService->getProjectStructureHash();
            $savedHash = $mappingData['projectStructureHash'] ?? '';
            $structureChanged = !empty($savedHash) && $savedHash !== $currentHash;

            $lockedIds = $this->jobManager->getLockedMappingIds($this->jobManager->getProjectId());

            return [
                'success' => true,
                'mapping' => $mappingData,
                'structureChanged' => $structureChanged,
                'lockedByImport' => in_array($mappingData['id'], $lockedIds, true),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * Get all mappings
     *
     * @return array Response with success status and array of all mappings
     */
    public function getAll(): array
    {
        if (!$this->permissionService->canViewMappings()) {
            return [
                'success' => false,
                'errors' => ['You do not have permission to view mappings']
            ];
        }

        $mappings = $this->mappingRepository->findAll();
        $currentHash = $this->projectService->getProjectStructureHash();
        $lockedIds = $this->jobManager->getLockedMappingIds($this->jobManager->getProjectId());

        $mappingsArray = array_map(function ($mapping) use ($currentHash, $lockedIds) {
            $mappingData = $mapping->toArray();
            $savedHash = $mappingData['projectStructureHash'] ?? '';
            $mappingData['structureChanged'] = !empty($savedHash) && $savedHash !== $currentHash;
            $mappingData['lockedByImport'] = in_array($mappingData['id'], $lockedIds, true);
            return $mappingData;
        }, $mappings);

        return [
            'success' => true,
            'mappings' => $mappingsArray
        ];
    }

}
