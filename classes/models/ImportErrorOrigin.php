<?php

namespace Nottingham\ImportMapper\Models;

enum ImportErrorOrigin: string
{
    case MAPPING = 'MAPPING';
    case CSV_STRUCTURE = 'CSV_STRUCTURE';
    case CSV_DATA = 'CSV_DATA';
    case TRANSFORMATION = 'TRANSFORMATION';
    case REDCAP_SAVE = 'REDCAP_SAVE';
}
