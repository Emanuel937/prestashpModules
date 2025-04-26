<?php
function brandsForm($class=null) {
    $form = [
        'form' => [
            'legend' => [
                'title' => $class->l('Selected brands'),
                'icon' => 'icon-cogs'
            ],
            'input' => [
                [
                    'type' => 'select',
                    'label' => $class->l('Select Brands'),
                    'name' => "SITEFIX_SELECTED_BRANDS",
                    'options' => [
                        'query' => $class->getBrands(),
                        'id' => 'id', // Clé de l'identifiant des marques
                        'name'=>'name',
                        'selected' => 'selected'
                    ],
                    'required' => true,
                    'multiple' => true, // Permettre la sélection multiple
                    'desc'     => $class->l("Only the selected brand you will be able to add catalog ...")
                ],
                [
                    "type"=>"radio",
                    "name"=>"SITEFIX_RADIO",
                    "id"  =>"SITEFIX_RADIO",
                    "label" => "Select all brands",
                    "desc"=>$class->l("You can select all brands at all "),
                    'values'    => array(
                    array(
                        'id'    => 'active_off',
                        'value' => 0,
                        'label' => $class->l('no')
                    ),                                 // $values contains the data itself.
                    array(
                      'id'    => 'active_on',                           // The content of the 'id' attribute of the <input> tag, and of the 'for' attribute for the <label> tag.
                      'value' => 1,                                     // The content of the 'value' attribute of the <input> tag.   
                      'label' => $class->l('yes')                    // The <label> for this radio button.
                    ),
                 
                  ),
                
                ]
            ],
            'submit' => [
                'title' => $class->l('Save'),
                'class' => 'btn btn-default pull-right'
            ]
        ]
    ];
    return $form;
}
