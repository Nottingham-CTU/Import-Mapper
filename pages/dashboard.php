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

<?php require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php'; ?>