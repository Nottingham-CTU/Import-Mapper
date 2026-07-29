<?php
/*
 *	Exports the Import Mappings configuration as a JSON document.
 */


if ( ! $module->getUser()->isSuperUser() )
{
	exit;
}

header( 'Content-Type: application/json' );
header( 'Content-Disposition: attachment; filename=' .
        trim( preg_replace( '/[^A-Za-z0-9-]+/', '_', \REDCap::getProjectTitle() ), '_-' ) .
        '_mappings_' . gmdate( 'Ymd-His' ) . '.json' );

$pid = $module->getProjectID();
$mappings = $module->getProjectSetting('mappings');

foreach ($mappings as $index => $item) {      
    unset( $item['projectStructureHash'], $item['created_at'], $item['updated_at']);
    $exportmappings[] = $item;
}

echo json_encode($exportmappings);