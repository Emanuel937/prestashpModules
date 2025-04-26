<?php 

if (!defined('_PS_VERSION_')) {
    exit;
}

class Mastermodifier extends Module
{
    public function __construct()
    {
        $this->name = 'mastermodifier';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Emanuel ABIZIMI';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('🎓 Master modifier |  X-studioApp');
        $this->description = $this->l('it allows update, add new information and delete all the information about product');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall ?', [], 'Modules.Mymodule.Admin');
        
        if(!Configuration::get('MYMODULE_NAME')){
            $this->warning = $this->trans('No name provided', [], 'Modules.Mymodule.Admin');
        }
    }

    public function install()
    {
        return parent::install()
        && $this->registerHook('displayBackOfficeHeader');
     
    }


    public function uninstall(){
        return parent::uninstall();
    }


    //render form 
    public function getContent(){
        // send caracterist data to template 
        $this->context->smarty->assign([
            'features'=>$this->getFeatures(),
            'featureControllerURl' => $this->getFeatureController()
        ]);

        return $this->displayForm();
    }

    public function getFeatures(){

        // this function will ne remove on the other version like 1.0.0.2
        $id_lang = Context::getContext()->language->id;
        return Feature::getFeatures($id_lang);

    }


    public function displayForm()
    {
        return $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');
    }

    public function getFeatureController()
    
    {
        $url  = $link = $this->context->link->getAdminLink('AdminFeatureMastermodifier');
     
        return $url;
    }


}