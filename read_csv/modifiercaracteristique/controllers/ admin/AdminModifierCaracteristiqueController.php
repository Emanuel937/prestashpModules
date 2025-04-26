<?php
class AdminModifierCaracteristiqueController extends ModuleAdminController
{
    public function __construct()
    {
        $this->table = 'product';
        $this->className = 'Product';
        parent::__construct();
    }

    public function initContent()
    {
        parent::initContent();
        $this->context->smarty->assign([
            'product_id' => Tools::getValue('product_id'),
            'attributes' => $this->module->getProductAttributes(),
        ]);
        $this->setTemplate('configure.tpl');
    }
}
