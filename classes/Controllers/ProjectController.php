<?php

namespace Nottingham\ImportMapper\Controllers;

use Nottingham\ImportMapper\Services\PermissionService;
use Nottingham\ImportMapper\Services\ProjectService;

/**
 * REDCap project data AJAX actions
 */
final readonly class ProjectController
{
    /**
     * Constructor
     *
     * @param ProjectService $projectService Project service
     * @param PermissionService $permissionService Permission checking service
     */
    public function __construct(
        private ProjectService    $projectService,
        private PermissionService $permissionService
    )
    {
    }

    /**
     * Get project structure
     *
     * @return array Response with project structure
     */
    public function get(): array
    {
        if (!$this->permissionService->canViewMappings()) {
            return [
                'success' => false,
                'errors' => ['You do not have permission to view project structure']
            ];
        }

        $projectStructure = $this->projectService->get();

        return [
            'success' => true,
            'projectStructure' => $projectStructure->toArray(),
        ];
    }

    /**
     * Get current project structure hash
     *
     * @return array Response with success status and hash
     */
    public function getProjectStructureHash(): array
    {
        if (!$this->permissionService->canViewMappings()) {
            return [
                'success' => false,
                'errors' => ['You do not have permission to view project structure']
            ];
        }

        return [
            'success' => true,
            'hash' => $this->projectService->getProjectStructureHash()
        ];
    }
}
