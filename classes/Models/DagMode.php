<?php

namespace Nottingham\ImportMapper\Models;

/**
 * Enum for DAG assignment modes
 */
enum DagMode: string
{
    case SAME_FOR_ALL = 'same_for_all';
    case CSV_FIELD = 'csv_field';
    case SELECT_DAG = 'select_dag';
}
