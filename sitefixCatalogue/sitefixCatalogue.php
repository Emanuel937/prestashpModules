<?php 
if (!defined("_PS_VERSION_")) {
    exit;
}
class SitefixCatalogue extends Module 
{
    public function __construct()
    {
        $this->name = "sitefixCatalogue";
        $this->tab = "front_office_features";
        $this->author = "EMANUEL ABIZIMI";
        $this->version = "0.0.1";
        $this->need_instance = 0;
        $this->selectedValues = [];
        $this->uploadDir = _PS_IMG_DIR_ . 'catalogues_sitefix/';

        $this->ps_versions_compliancy = array(
            'min' => '1.7.0.0',
            'max' => _PS_VERSION_,
        );

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('View pdf catalogues');
        $this->description = $this->l('If you have pdf catalog and you want display it on your website, this module allow to his in just 4 click');
    }

    /**
     * @return DB
     * Create database 
     * For catalogue
     */
    private function createDatabaseTable()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'sitefix_catalogue` (
                    `catalog_id` INT(11) NOT NULL AUTO_INCREMENT,
                    `catalog_name` VARCHAR(255) NOT NULL,
                    `brand` VARCHAR(255) NOT NULL,
                    `file_name` VARCHAR(255) NOT NULL,
                    `cover_img`VARCHAR(255) NOT NULL, 
                    PRIMARY KEY (`catalog_id`)
                ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        return Db::getInstance()->execute($sql);
    }

    /**
     * Select all data from the sitefix_catalogue table.
     *
     * @return array|false The result set as an array or false on failure.
    */
    private function showAllCatalog()
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'sitefix_catalogue`';
        return Db::getInstance()->executeS($sql);
    }

    private function insertIntoDatabase($catalogName, $brand, $fileName, $cover_img)

    {
        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'sitefix_catalogue` (`catalog_name`, `brand`, `file_name`, `cover_img`)
                VALUES (\'' . pSQL($catalogName) . '\', \'' . pSQL($brand) . '\', \'' . pSQL($fileName) . '\', \'' . pSQL($cover_img) . '\')';

        return Db::getInstance()->execute($sql);
    }
       public function install()
    {
        return parent::install() &&
               $this->registerHook('displayHome') &&
               $this->createDatabaseTable();
    }


    public function uninstall()
    {   
        // DELECTE CATAGLOQUE TABLE
        // DELECTED /catalogue folder 
        // THAT HOLD ALL PDF FILE 
        return parent::uninstall();
    }

    public function hookDisplayHome()
    {
        return $this->display(__FILE__, 'views/templates/hook/categories.tpl');
    }

    private function deleteDirectory($dir) {
        if (!file_exists($dir)) {
            return false;
        }
    
        if (!is_dir($dir)) {
            return unlink($dir);
        }
    
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
    
            if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
    
        return rmdir($dir);
    }

    public function getContent()
       {       
           // show all selected data : 

           $brands   = Tools::getValue("SITEFIX_SELECTED_BRANDS")?: false;
           $selected = Configuration::get("SITEFIX_SELECTED_BRANDS_VALUES");
           $selected = json_decode($selected);
         
            $selected_data = $this->getBrands();
            foreach($selected as $brand_id){
                foreach($this->getBrands() as $selected_brands){
                    if($selected_brands['id'] == $brand_id){
                        $this->selectedValues[] =  $selected_brands;
                    }
                }
            }
        
            //update selected data 
            if (Tools::isSubmit("form_1"))
            {  
                // SUBMIT THE FIRST FORM 
                // SELECT BRANDS
                if(isset($brands) && !empty($brands))
                {
                    $brands = json_encode($brands,true);
                    Configuration::updateValue("SITEFIX_SELECTED_BRANDS_VALUES", $brands);
                }
            }
            //SUBMIT THE SECOND FORM::
            //ADD CATALOG TO DATABASE 
           if(Tools::isSubmit("form_2"))
           {    $catalog_name = Tools::getValue("SITEFIX_CATALOG_NAME");
                $file_path    = $_FILES["SITEFIX_UPLOAD_CATALOG_FILE"];
                try{
                    if(isset($catalog_name)  && isset($file_path) 
                    && !empty($catalog_name) && !empty($file_path)
                        ){
                            $this->saveDataToDabase();
                        }
                }catch(Exception $e){
                    var_dump($error);
                }
           }
          
            // assign data to template
            $this->includeForms();
            return $this->context->smarty->fetch($this->local_path . 'views/templates/config/module_config.tpl');
        }
      
        /**
         * @return Array|string
         */
        private function handleFileUploads()
        {
            $uploadedFiles = array();
            
        
            if (!file_exists($this->uploadDir))
            {
                mkdir($this->uploadDir, 0755, true);
            }
            // PDF FILE
            $pdfFile           = $_FILES['SITEFIX_UPLOAD_CATALOG_FILE'];
            $validateExtension = ["pdf"];
            $pdfFile           = $this->moveUploadedFile($pdfFile , $validateExtension);
            //IMG FILE
            $coverImg          = $_FILES['SITEFIX_COVER_CATALOG'];
            $validateExtension = ["png", "jpg", "wep"];
            $coverImg          = $this->moveUploadedFile($coverImg, $validateExtension);
            
            if ($pdfFile  !== false) {
                $uploadedFiles['fileUrl'] =  $pdfFile;
            }
            if($coverImg!==false){
                $uploadedFiles["imgCover"] = $coverImg;
            }
            return $uploadedFiles;
        }


        private function moveUploadedFile($file, $validateExtension)
        {
            $uploadedFileUrl = false;
            // Valider l'extension du fichier
            $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
            if (in_array($fileExtension, $validateExtension)) {
                if (isset($file) && !empty($file['tmp_name'])) {
                    $fileName = uniqid().".".$fileExtension;
                    if (move_uploaded_file($file['tmp_name'], $this->uploadDir . $fileName)) {
                        $uploadedFileUrl = _PS_BASE_URL_ . _PS_IMG_ . 'catalogues_sitefix/' . $fileName;
                    }
                }
            } else {
                // Retourner un message d'erreur si l'extension n'est pas valide
               return $this->l("This extension is not valid, choose a PDF file");
            }
        
            return $uploadedFileUrl; // Retourne l'URL du fichier téléchargé ou false en cas d'échec
        }

    public function saveDataToDabase():void
    {     
        $catalog_name = Tools::getValue("SITEFIX_CATALOG_NAME");
        $brands_cat   = Tools::getValue("SITEIFX_ONLY_SELECTED_BRAND");
        $file_url     = $this->handleFileUploads();  // move pdf file 
        $this->insertIntoDatabase($catalog_name, $brands_cat, $file_url["fileUrl"], $file_url["imgCover"]); // insert into 
    }

  
    public function getBrands()
    {  
        $brands_array = [];
        $brands = Db::getInstance()->executeS('
            SELECT id_manufacturer AS id_brand, name
            FROM ' . _DB_PREFIX_ . 'manufacturer
            ORDER BY name ASC
        ');
        foreach($brands as $brand){
           $brands_array[] = [
            "id"       => $brand['id_brand'],
            "name"     => $brand['name'],
           ];
        }

        return $brands_array;
    }

    public function setValues()
    {
        $selected_brands = Configuration::get("SITEFIX_SELECTED_BRANDS_VALUES");
        $selected_brands = json_decode($selected_brands, true);
        return [
            "SITEFIX_RADIO"=>1,
            "SITEFIX_CATALOG_NAME"=> null,
            "SITEIFX_ONLY_SELECTED_BRAND"=>null,
            "SITEFIX_SELECTED_BRANDS[]" =>[]
        ];
    }

    public function renderForm($form, $tab_index=1, $form_action)
    {
       
        $helper = new HelperForm();
        $helper->module          = $this;
        $helper->name_controller = $this->name;
        $helper->identifier      = $this->identifier;
        $helper->token           = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex    = AdminController::$currentIndex . '&configure=' . $this->name . '&active_tab=' . $tab_index;
        $helper->show_toolbar    = false;
        $helper->title           = $this->displayName;
        $helper->submit_action   = $form_action;
        $helper->toolbar_scroll  = false;
        $helper->show_cancel_button = true;
        
        $helper->tpl_vars = array(
            'fields_value' => $this->setValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        
   
        return $helper->generateForm([$form]);
    }

    public function includeForms()
    {
        require __DIR__ . "/src/brandsForm.php";
        require __DIR__ . "/src/addCatalog.php";

        $brands  = brandsForm($this);
        $brands  = $this->renderForm($brands, 1, "form_1");
        $catalog = addCatalog($this);
        $catalog = $this->renderForm($catalog, 2, "form_2");
        $actived_tab = Tools::getValue("active_tab");
        $controller_url  = Context::getContext()->link->getModuleLink($this->name, 'deleteCat', ["ajax"=>true]);
        
        
        $this->context->smarty->assign([
            'tabs'             => $brands ?:false,
            'catalog'          => $catalog ?:false,
            'selected_values'  => json_encode($this->selectedValues, true) ?:false,
            'db_query'         => $this->showAllCatalog() ?:false,
            'all_brands'       => $this->getBrands()?:false,
            'active_tab_index' => $actived_tab ?:false,
            'controller_url'   => $controller_url ?:false,
            'img'              => $this->_path . 'views/assets/img/'
        ]);
    }
}

