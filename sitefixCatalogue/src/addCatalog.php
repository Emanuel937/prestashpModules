<?php
function addCatalog($class) {
    $form = [
        'form' => [
            'legend' => [
                'title' => $class->l('Add catalog to brands'),
                'icon' => 'icon-cogs',
            ],
          'input' => [
                [
                    "type"        =>"text",
                    "name"        => $class->l('SITEFIX_CATALOG_NAME'),
                    "label"       => $class->l('Catalog name'),
                    "required"    => true,
                    "placeholder" => $class->l("The name for  your catalog"),
                    "desc"        => $class->l('*** Name of your catalog')
                    
                 ],
                 [
                    "name"=>"SITEFIX_UPLOAD_CATALOG_FILE",
                    "type"=>"file",
                    "label" => "File",
                    "required"=>true,
                    "desc" =>$class->l("upload your pdf file")
                 ],
                 [
                    "type"=>"file",
                     "name"=>"SITEFIX_COVER_CATALOG",
                     "label"=> $class->l("Catalog cover"),
                     "required"=>false,
                     "placeholder"=>$class->l("Upload imag cover"),
                     "desc"=>$class->l("Upload an image to be the cover of catalog")


                
                 ],
                [
                    'type' => 'select', // Utiliser un type 'select' pour afficher les marques
                    'label' => $class->l('The brands for catalog'),
                    'name' => 'SITEIFX_ONLY_SELECTED_BRAND',
                    'options' => [
                        'query' => $class->selectedValues, // Fonction pour obtenir les marques
                        'id' => 'id', // Clé de l'identifiant des marques
                        'name' => 'name' // Clé du nom des marques
                    ],
                    'required' => true,
                    "desc"     => $class->l('Match your catalog with his brands')
                    // Permettre la sélection multiple
                ],
               
            ],
            'submit' => [
                'title' => $class->l('Save'),
                'class' => 'btn btn-default pull-right'
            ]
        ]
    ];
    return $form;
}
