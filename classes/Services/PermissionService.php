<?php

namespace Nottingham\ImportMapper\Services;

use ExternalModules\AbstractExternalModule;
use REDCap;

/**
 * Service for checking user permissions
 */
final readonly class PermissionService
{
    /**
     * @param AbstractExternalModule $module
     */
    public function __construct(
        private AbstractExternalModule $module
    )
    {
    }

    /**
     * Check if user can modify mappings (create, edit, delete)
     * Only admins can modify mappings
     *
     * @return bool
     */
    public function canModifyMappings(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Check if user can perform imports
     * Both admins and importers can import
     *
     * @return bool
     */
    public function canImport(): bool
    {
        return $this->isAdmin() || $this->isImporter();
    }

    /**
     * Check if user can view mappings (read-only)
     * Both admins and importers can view mappings
     *
     * @return bool
     */
    public function canViewMappings(): bool
    {
        return $this->isAdmin() || $this->isImporter();
    }

    /**
     * Check if user can view logs
     * Both admins and importers can view logs
     *
     * @return bool
     */
    public function canViewLogs(): bool
    {
        return $this->isAdmin() || $this->isImporter();
    }

    /**
     * Check if current user is an admin
     * Admins can be: REDCap admins OR users with roles configured in admin-roles setting
     *
     * @return bool
     */
    private function isAdmin(): bool
    {
        // Admins always have admin access
        if ($this->isSuperUser()) {
            return true;
        }

        // Check if user has one of the configured admin roles
        $adminRoles = $this->module->getProjectSetting('admin-roles') ?? [];
        return $this->userHasAnyRole($adminRoles);
    }

    /**
     * Check if current user is an importer
     * Importers are users with roles configured in importer-roles setting
     *
     * @return bool
     */
    private function isImporter(): bool
    {
        // Check if user has one of the configured importer roles
        $importerRoles = $this->module->getProjectSetting('importer-roles') ?? [];
        return $this->userHasAnyRole($importerRoles);
    }

    /**
     * Check if current user is a REDCap admin
     *
     * @return bool
     */
    private function isSuperUser(): bool
    {
        return defined('SUPER_USER') && SUPER_USER == '1';
    }

    /**
     * Check if current user has any of the specified roles
     *
     * @param array $roleIds Array of role IDs to check
     * @return bool
     */
    private function userHasAnyRole(array $roleIds): bool
    {
        if (empty($roleIds)) {
            return false;
        }

        $username = $this->module->getUser()->getUsername();
        if (empty($username)) {
            return false;
        }

        // Get user's role for this project
        $userRights = REDCap::getUserRights($username);

        if (empty($userRights) || !isset($userRights[$username])) {
            return false;
        }

        $userRoleId = (string)($userRights[$username]['role_id'] ?? '');
        if ($userRoleId === '') {
            return false;
        }

        // Check if user's role is in the list of allowed roles
        $roleIds = array_map('strval', $roleIds);
        return in_array($userRoleId, $roleIds, true);
    }
}
