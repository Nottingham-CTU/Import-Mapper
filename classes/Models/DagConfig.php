<?php

namespace Nottingham\ImportMapper\Models;

final readonly class DagConfig
{
    /**
     * @param bool $enabled
     * @param DagMode|null $mode
     * @param string|null $dagUniqueName
     * @param string|null $dagCsvFieldName
     */
    private function __construct(
        public bool     $enabled,
        public ?DagMode $mode,
        public ?string  $dagUniqueName,
        public ?string  $dagCsvFieldName)
    {
    }

    /**
     * @return self
     */
    public static function disabled(): self
    {
        return new self(false, null, null, null);
    }
    
    /**
     * @return self
     */
    public static function fromSelectMode(): self
    {
        return new self(true, DagMode::SELECT_DAG, null, null);
    }

    /**
     * @param string $dagUniqueName
     * @return self
     */
    public static function fromUniqueName(string $dagUniqueName): self
    {
        return new self(true, DagMode::SAME_FOR_ALL, $dagUniqueName, null);
    }

    /**
     * @param string $dagCsvFieldName
     * @return self
     */
    public static function fromCsvFieldName(string $dagCsvFieldName): self
    {
        return new self(true, DagMode::CSV_FIELD, null, $dagCsvFieldName);
    }

    /**
     * @param array $csvFields
     * @param array $projectDags
     * @return array
     */
    public function validate(array $csvFields, array $projectDags): array
    {
        $errors = [];
        if ($this->mode === DagMode::SAME_FOR_ALL) {
            if (!in_array($this->dagUniqueName, $projectDags, true)) {
                $errors[] = ImportError::mapping("DAG '$this->dagUniqueName' does not exist in this project");
            }
        } else if ($this->mode === DagMode::CSV_FIELD) {
            if (!in_array($this->dagCsvFieldName, $csvFields, true)) {
                $errors[] = ImportError::mapping("CSV column '$this->dagCsvFieldName' not found in uploaded file");
            }
        }
        // if mode is not defined, return error
        else if($this->mode !== DagMode::SELECT_DAG)
        {
            $errors[] = ImportError::mapping("Unknown DAG Mode");
        }
        return $errors;
    }
}