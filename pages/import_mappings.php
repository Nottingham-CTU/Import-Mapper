<?php

use Nottingham\ImportMapper\Repositories\MappingRepository;
use Nottingham\ImportMapper\Services\ProjectService;
use Nottingham\ImportMapper\Models\Mapping;
use Nottingham\ImportMapper\Services\Validation\MappingValidator;
use Nottingham\ImportMapper\Services\Validation\FieldMappingStructureValidator;
use Nottingham\ImportMapper\Services\Validation\FieldMappingTransformValidator;
/*
 * 	Imports the Import Mappings configuration from a JSON document.
 */


if (!$module->framework->getUser()->isSuperUser()) {
    exit;
}


function findMappingInImport($id, $listImported)
{
    foreach ($listImported as $index => $mapping) {
            if($id === $mapping['id'])
            {
                return $mapping;
            }
    }
    return null;
}

function displayConfigValues($label, $value, $newValue)
{
    if(!is_array($value))
    {
        if ($value == $newValue) {
            echo "<li><b>" . $label . ":</b>" . htmlspecialchars($newValue) . "</li>";
        } else {
            echo '<li style="color:#c00;text-decoration:line-through"><b> ' . $label . ': </b> ' . htmlspecialchars($value). '</li>';
            echo '<li style="color:#060"><b>' . $label . ': </b> ' . htmlspecialchars($newValue) . '</li>';
        }
    }
    else 
    {
        if($value == $newValue)
        {
            echo "<li><b>" . $label . ":</b> Definition is the same</li>";
        }
        else
        {
            echo "<li><b>" . $label . ": </b>";
            echo '<span style="color:#060">Definition is updated</span></li>';
        }
    }
}

$mode = 'upload';
if (!empty($_FILES)) { // file is uploaded
    $mode = 'verify';
    // Check that a file has been uploaded and it is valid.
    if (!is_uploaded_file($_FILES['import_file']['tmp_name'])) {
        $mode = 'error';
        $error = 'No file uploaded.';
    }
    if ($mode == 'verify') { // no error
        $fileData = file_get_contents($_FILES['import_file']['tmp_name']);
        $data = json_decode($fileData, true);
        if ($data == null || !is_array($data) ) {
            $mode = 'error';
            $error = 'The uploaded file is not a valid mappings export.';
        }
    }
    $pid = $module->getProjectID();
    $projectService = new projectService($module);
    $projectService->get($pid);

    $mappingValidator = new MappingValidator(
                    new FieldMappingStructureValidator(),
                    new FieldMappingTransformValidator(),
                    $projectService);
    if ($mode == 'verify') { // no error
        
        $error = 'The uploaded file is not a valid mapping.<br><br>';
        foreach ($data as $index => $item) {
            if (!isset($item['id'])) {
                $mode = 'error';
                $error = "Id missing for Mapping";
                break;
            }
            else
            {

                // Validate each mapping data
               $validationErrors = [];
               $validationErrors = $mappingValidator->validate($item);

               if (!empty($validationErrors)) {
                  $mode = 'error';
                  $error .= '<span style="color:black;"><b>'.$item['name'].'</b> ('.$item['id'].')<br>Errors:<br> ';
                  $error .= implode('<br>', $validationErrors);
                  $error .= '</span><br><br>';
               }

            }
        }
    }

     
    // Parse the uploaded file for differences between the existing mappings and those contained
    // within the file. The user will be asked to confirm the changes.
     if ($mode == 'verify') { // no error
        $pid = $module->getProjectID();
        $mappingRepository = new MappingRepository($module);
        $listCurrent = $module->getProjectSetting('mappings');
        $listImported = $data;
        $listNew = [];
        $listDeleted = [];
        $listIdentical = [];
        $listChanged = [];
        
        foreach ($listCurrent as $index => $existingMapping) {
            
            if(findMappingInImport($existingMapping['id'], $listImported) === null)
            {
                $listDeleted[] = $existingMapping;
            }
            
        }
        
        foreach ($listImported as $index => $newMapping) {
            $id = $newMapping['id'];
            try
            {
                $mapping = $mappingRepository->findById($id);
            }
            catch (Exception $e)
            {
                $listNew[] = $newMapping;
                continue;
            }
            
            $existingMapping = $mapping->toArray();
            unset($newMapping['projectStructureHash'], $newMapping['created_at'], $newMapping['updated_at']);
            $exisitngProjHash = $existingMapping['projectSructureHash'];
            $exisitngCreate = $existingMapping['created_at'];
            unset($existingMapping['projectStructureHash'], $existingMapping['created_at'], $existingMapping['updated_at']);
            $identicalMapping = ( $existingMapping == $newMapping );
            if ($identicalMapping) {
                $listIdentical[] = $newMapping;
            } else {
                $existingMapping['projectStructureHash'] = $exisitngProjHash;
                $existingMapping['created_at'] = $exisitngCreate;
                $listChanged[] = ['id' => $id, 'oldmapping' => $existingMapping, 'newmapping' => $newMapping];
            }
            
        }
    }
} elseif (!empty($_POST)) { // normal POST request (confirming import)
    $mode = 'complete';
    // The contents of the file are passed across from the verify stage. If this is valid, the
    // selected changes are applied.
    $fileData = $_POST['import_data'];
    $data = json_decode($fileData, true);
     if ($data == null || !is_array($data) ) {
        $mode = 'error';
        $error = 'The uploaded file data is not valid.';
    }
    
    
     if ($mode == 'complete') { // no error
        $mappingRepository = new MappingRepository($module); 
        foreach ($_POST as $key => $val) {
            if (substr($key, 0, 12) == 'mapping-add-') {
                // Add new mapping into project from file.
                $id = substr($key, 12);
                foreach ($data as $index => $newMapping) {
                    if($id === $newMapping['id'])
                    {
                        $pid = $module->getProjectID();
                        $projectService = new projectService($module);
                        $projectService->get($pid);
                        $newMapping['projectStructureHash'] = $projectService->getProjectStructureHash();
                        $newMapping['created_at'] = date('c');
                        $newMapping['updated_at'] = date('c');
                        $newMapping = Mapping::fromArray($newMapping);
                        $mappingRepository->save($newMapping, $id);
                        break;
                    }
                    
                }
            } elseif (substr($key, 0, 16) == 'mapping-changed-') {
                // Update mapping configuration
                $id = substr($key, 16);
                foreach ($data as $index => $newMapping) {
                    if($id === $newMapping['id'])
                    {
                        try
                        {
                            $mapping = $mappingRepository->findById($id);
                            $mapping = $mapping->toArray();
                            $newMapping['projectStructureHash'] = $mapping['projectStructureHash'];
                            $newMapping['created_at'] = $mapping['created_at'];;
                            $newMapping['updated_at'] = date('c');
                            $newMapping = Mapping::fromArray($newMapping);
                            $mappingRepository->update($id, $newMapping);
                        }
                        catch (Exception $e)
                        {
                            
                        }
                        break;
                    }
                }
                        
                        
          
            } elseif (substr($key, 0, 15) == 'mapping-delete-') {
                // Remove mapping from project.
                $id = substr($key, 15); 
                $mappingRepository->delete($id);
            }
        }
    }
}


// Display the project header
require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
?>
<div class="projhdr">Import Mapping Definationas</div>
<p style="font-size:11px">
    <a href="<?php echo $module->getUrl('pages/dashboard.php') ?>"><i class="fas fa-arrow-circle-left fs11"></i> Back to Mappings</a>
</p>
<?php
// Display the file upload form.
if ($mode == 'upload') {
?>
    <form method="post" enctype="multipart/form-data">
        <table class="mod-mappings-formtable">
            <tr>
                <td>Import file</td>
                <td>
                    <input type="file" name="import_file">
                </td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <input type="submit" value="Import">
                </td>
            </tr>
        </table>
    </form>
<?php
}
// Display the options to confirm the changes to the maaping definitions introduced by the file.
elseif ($mode == 'verify') {
?>
   <form method="post">
        <table class="mod-mappings-formtable" cellpadding="2">    
            <?php
            if (count($listIdentical) > 0) {
            ?>
                <tr>
                    <th padding="5" colspan="2">Identical mappings</th>
                    <tr>
                    <td colspan="2" style="width:40%; text-align:left;">
                        <ul>
                            <?php
                            foreach ($listIdentical as $mapping) {
                                echo '<li>Mapping ID=' . htmlspecialchars($mapping['id']) . ($mapping['name'] === "" ? '' : '&nbsp;<i>(' . $mapping['name'] . ')</i></li>');
                            }
                            ?>
                        </ul>
                    </td>
                    </tr>
                </tr>
                
            <?php
            }
            if (count($listNew) > 0) {
            ?>
                <tr>
                    <th colspan="2">New Mappings</th>
                </tr>
                <?php
                foreach ($listNew as $mapping) {
  
                ?>
                    <tr>
                        <td td colspan="2" style="text-align:left"><?php echo 'Mapping ID=' . htmlspecialchars($mapping['id']) . ($mapping['name'] === "" ? '' : '&nbsp;<i>(' . $mapping['name'] . ')</i>'); ?>  </td>
                        <td>
                            <input type="checkbox" name="mapping-add-<?php echo htmlspecialchars($mapping['id']); ?>" value="1" checked>
                            Add this mapping
                    </td>
                </tr>  
            <?php
                }
            }
            if (count($listChanged) > 0) {
            ?>
                <tr>
                    <th colspan="2">Changed Mappings</th>
                </tr> 
                <?php
                foreach ($listChanged as $mapping) {
                    
                $oldmapping = $mapping['oldmapping'];
                $newmapping = $mapping['newmapping'];
                ?>
                    <tr>
                        <td td colspan="2" style="text-align:left"><?php echo 'Mapping ID=' . htmlspecialchars($mapping['id']) . ($newmapping['name'] === "" ? '' : '&nbsp;<i>(' . $newmapping['name'] . ')</i>'); ?>
                        </td>
                        <td>
                            <input type="checkbox" name="mapping-changed-<?php echo htmlspecialchars($mapping['id']); ?>" value="1" checked> 
                            Update mappings (changes highlighted below)
                            <br>
                 
                            <ul>
                                <?php
   
                                foreach ($oldmapping as $label => $value) {
                                   
                                    $newValue = $newmapping[$label];
                                    
                                    if($label === 'projectStructureHash' || $label === 'created_at' || $label === 'updated_at')
                                            continue;
                                    
                                    if($label === 'matching')
                                    {
                                        foreach($value as $matchLabel => $matchValue)
                                        { 
                                            $value = $matchValue;
                                            $newValue = $newmapping[$label][$matchLabel];
                                            if($matchLabel === 'dag' || $matchLabel === 'record')
                                            {
                                                $value = implode(', ', $value);
                                                $newValue = implode(', ', $newValue);
                                            }
                                            $newlabel = $label.' - '.$matchLabel;
                                            
                                            displayConfigValues($newlabel, $value, $newValue);
                                           
                                        }
                                        continue;
                                    }
                                    
                                    if($label === 'csvFields' )
                                    {
                                        $value = implode(', ', $value);
                                        $newValue = implode(', ', $newValue);
                                    }
                                    
                                    displayConfigValues($label, $value, $newValue);
                                }
                                ?>
                            </ul>
                        </td>
                    </tr>
            <?php
                }
            }



            if (count($listDeleted) > 0) {
            ?>
                <tr>
                    <th colspan="2">Mappings Not In Import File</th>
                </tr>   
                <?php
                   foreach ($listDeleted as $mapping) {
                 ?> 
                    <tr>
                        <td colspan="2" style="text-align:left">
                        <?php echo 'Mapping ID=' . htmlspecialchars($mapping['id']) . ($mapping['name'] === "" ? '' : '&nbsp;<i>(' . $mapping['name'] . ')</i>') ?>
                        </td>
                        <td>
                            <input type="checkbox" name="mapping-delete-<?php echo htmlspecialchars($mapping['id']); ?>" value="1">
                            Delete this mapping
                        </td>
                    </tr> 
                <?php
                }
            }
            ?>
            <tr>
                <td>
                    <?php 
                    if (($listDeleted !== null && count($listDeleted) > 0) ||  ($listChanged != null && count($listChanged) > 0) || ($listNew !== null && count($listNew) > 0) )  
                    { 
                    ?>
                         <br>
                         <input type="submit" value="Update Selected Mappings">
                         <input type="hidden" name="import_data" value="<?php echo htmlspecialchars($fileData); ?>">
                    <?php
                    }
                    ?>
                </td>
            </tr>
        </table>
    </form> 
<?php
}
// Display error message.
elseif ($mode == 'error') {
    echo '<p style="font-size:14px;color:#f00">'.$error.'</p>';
}
// Display success message.
elseif ($mode == 'complete') {
    echo '<p style="font-size:14px">Import complete</p>';
}

// Display the project footer
require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';

