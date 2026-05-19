<?php

namespace Nottingham\ImportMapper\Services;

use Exception;
use Nottingham\ImportMapper\Models\DagConfig;
use Nottingham\ImportMapper\Models\DagMode;
use Nottingham\ImportMapper\Models\ImportDataRow;

/**
 * Resolver for Data Access Group (DAG) assignment
 *
 * Handles the logic for determining which DAG a record should be assigned to
 * based on the mapping configuration
 */
final class DagResolver
{
    // Default DAG name, set to the importing user's DAG (if applicable).
    private ?string $defaultDagName = null;

    /**
     * Resolve DAG value for a data row
     *
     * Determines which DAG (if any) should be assigned to a record based on:
     * - Whether DAG assignment is enabled in the mapping
     * - The DAG mode (same for all records vs. per-row from CSV column)
     * - The availability and validity of DAG values
     *
     * @param ImportDataRow $dataRow Source row from CSV
     * @return string|null DAG unique name, or null if no DAG is specified for this row
     * @throws Exception
     */
    public function resolve(ImportDataRow $dataRow, DagConfig $dagConfig, array $dagUniqueNames): ?string
    {
        if (!$dagConfig->enabled) {
            return in_array($this->defaultDagName, $dagUniqueNames) ? $this->defaultDagName : null;
        }

        $mode = $dagConfig->mode;
        if ($mode === DagMode::SAME_FOR_ALL) {
            return $dagConfig->dagUniqueName;
        } else {
            return $this->resolveFromCsvColumn($dataRow, $dagConfig, $dagUniqueNames);
        }
    }

    /**
     * Resolve DAG from CSV column value
     *
     * Extracts the DAG value from the specified CSV column and validates
     * that it exists in the project.
     *
     * @param ImportDataRow $dataRow Source row from CSV
     * @return string|null DAG unique name, or null if no DAG is specified for this row
     * @throws Exception
     */
    private function resolveFromCsvColumn(ImportDataRow $dataRow, DagConfig $dagConfig, array $dagUniqueNames): ?string
    {
        $csvFieldName = $dagConfig->dagCsvFieldName;
        $dagUniqueName = $dataRow->get($csvFieldName);

        // If the row contains no DAG value, return null
        if (empty($dagUniqueName)) {
            return null;
        }

        // Validate that the DAG exists in the project
        if (!in_array($dagUniqueName, $dagUniqueNames)) {
            throw new Exception("DAG '$dagUniqueName' does not exist in this project");
        }

        return $dagUniqueName;
    }

    /**
     *  Set the default DAG name to use if the DAG config is not enabled.
     *
     *  @param string $dagName
     */
    public function setDefaultDagName( string $dagName ): void
    {
        $this->defaultDagName = $dagName;
    }
}
