<form action="{$link->getAdminLink('AdminMyModuleCsvImport')}" method="post" enctype="multipart/form-data">
    <label>{$module->l('Upload your CSV file')}</label>
    <input type="file" name="csv_file" required>
    <button type="submit" name="submitUploadCsv">{$module->l('Import CSV')}</button>
</form>
