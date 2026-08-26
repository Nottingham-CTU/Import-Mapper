<?php

require_once APP_PATH_DOCROOT . 'ProjectGeneral/header.php';
$module->initializeJavascriptModuleObject();
$manifestPath = dirname(__FILE__, 2) . '/dist/.vite/manifest.json';
$buildManifest = json_decode(file_get_contents($manifestPath), true);
$mainJs = $buildManifest['src/App.jsx']['file'];
?>

    <script>
        window.IMPORT_WRANGLER = {
            moduleObj: <?= $module->getJavascriptModuleObjectName() ?>,
            csrfToken: "<?= $module->getCSRFToken() ?>",
            canModifyMappings: <?= json_encode($module->permissionService->canModifyMappings()) ?>
        };
    </script>
    <div id="import-wrangler"></div>
    <script type="module" src="<?= $module->getUrl('dist/' . $mainJs) ?>"></script>
    <?php
    if ( $module->getUser()->isSuperUser() )
    {
    ?>
        <p>&nbsp;</p>
        <div>
        <hr style="max-width:300px;margin-left:0px">
        <p><b>Administrative Options</b></p>
        <ul>
         <li>
          <a href="<?php echo $module->getUrl( 'pages/export_mappings.php' ) ?>">Export mapping definitions</a>
         </li>
         <li>
          <a href="<?php echo $module->getUrl( 'pages/import_mappings.php' ) ?>">Import mapping definitions</a>
         </li>
        </ul>
        </div>
    <?php
    }
    ?>
<?php require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php'; ?>