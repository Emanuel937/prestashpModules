<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Xsa_Gmc_Export extends Module
{
    public function __construct()
    {
        $this->name = 'xsa_gmc_export';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'X-StudioApp';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('XSA Google Merchant Export');
        $this->description = $this->l('Export products to Google Merchant Center by category.');
        $this->ps_versions_compliancy = ['min' => '1.7.2', 'max' => _PS_VERSION_];
    }

    public function install(): bool
    {
        return parent::install() &&  $this->registerHook('displayBackOfficeHeader');
    }

    public function uninstall(): bool
    {
        return parent::uninstall();
    }

    public function getContent(): string
    {
        // Load categories
        $categories = Category::getSimpleCategories($this->context->language->id);
        $validCategories = array_filter($categories, fn($c) => (int)$c['id_category'] > 1);

        // Assign to Smarty
        $this->context->smarty->assign([
            'categories' => $validCategories,
            'ajax_url' => $this->context->link->getAdminLink('AdminGmcExport', true),
            'module'    => $this,
            'link' =>  $this->getPathUri() . 'exports/gmc_feed.xml' ?? null,
        ]);



        return $this->display(__FILE__, 'views/templates/admin/gmc_export.tpl');
    }


  
    public function hookDisplayBackOfficeHeader()
    {
        $this->context->controller->addJS($this->_path . 'js/admin/' . $this->name . '.js');
    }



}
