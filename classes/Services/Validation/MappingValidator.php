<?php

namespace Nottingham\ImportMapper\Services\Validation;

use Exception;
use Nottingham\ImportMapper\Services\ProjectService;

/**
 * Coordinates all validation checks on mapping data before save or update.
 */
final readonly class MappingValidator
{
    /**
     * @param FieldMappingStructureValidator $structureValidator
     * @param FieldMappingTransformValidator $transformValidator
     * @param ProjectService $projectService
     */
    public function __construct(
        private FieldMappingStructureValidator $structureValidator,
        private FieldMappingTransformValidator $transformValidator,
        private ProjectService                 $projectService,
    )
    {
    }

    /**
     * Perform all validation checks on mapping data.
     *
     * @param array $payload Request payload containing mapping data
     * @return array Empty array if valid, or array of error messages
     */
    public function validate(array $payload): array
    {
        $allErrors = [];

        $trimmedName = trim($payload['name'] ?? '');
        if ($trimmedName === '') {
            $allErrors[] = 'Mapping name is required';
        } elseif (strlen($trimmedName) > 255) {
            $allErrors[] = 'Mapping name must be 255 characters or less';
        }

        try {
            $projectStructure = $this->projectService->get();

            $allErrors = array_merge($allErrors, $this->structureValidator->validateFieldMappings(
                $payload['fieldMappings'] ?? [],
                $projectStructure
            ));

            $allErrors = array_merge($allErrors, $this->transformValidator->validateDateFormats(
                $payload['fieldMappings'] ?? []
            ));

            $allErrors = array_merge($allErrors, $this->transformValidator->validateValueMappings(
                $payload['fieldMappings'] ?? []
            ));

            $allErrors = array_merge($allErrors, $this->transformValidator->validateFieldCombination(
                $payload['fieldMappings'] ?? [],
                $payload['csvFields'] ?? []
            ));

            $allErrors = array_merge($allErrors, $this->transformValidator->validateFieldRegex(
                $payload['fieldMappings'] ?? []
            ));

            $allErrors = array_merge($allErrors, $this->structureValidator->validateMatchingConfig(
                $payload['matching'] ?? [],
                $payload['fieldMappings'] ?? [],
                $projectStructure
            ));
        } catch (Exception $e) {
            $allErrors[] = 'Validation failed: ' . $e->getMessage();
        }

        return $allErrors;
    }
}
