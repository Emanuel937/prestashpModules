<?php

class AdminMyModuleCsvImportController extends ModuleAdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
    }

    public function renderList()
    {
        // Afficher le formulaire d'importation CSV
        return $this->module->getContent();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitUploadCsv')) {
            $this->module->processUploadCsv();
        }
    }
}
