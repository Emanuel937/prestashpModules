<?php
class FormRenderer
{
    protected $module;
    protected $context;

    public function __construct($module, $context)
    {
        $this->module = $module;
        $this->context = $context;
    }

    public function renderConfigForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->module->l('Settings'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->module->l('Import batch size'),
                        'name' => 'batch_size',
                        'class' => 'fixed-width-sm',
                        'desc' => $this->module->l('Number of products to process in each batch.'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->module->l('Image processing batch size'),
                        'name' => 'image_batch_size',
                        'class' => 'fixed-width-sm',
                        'desc' => $this->module->l('Number of images to process in each batch.'),
                    ],
                ],
                'submit' => [
                    'title' => $this->module->l('Save'),
                    'name' => 'submitConfig',
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this->module;
        $helper->submit_action = 'submitConfig';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->module->name . '&module_name=' . $this->module->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => [
                'batch_size' => Configuration::get('ADVANCEDIMPORT_BATCH_SIZE', 50),
                'image_batch_size' => Configuration::get('ADVANCEDIMPORT_IMAGE_BATCH_SIZE', 50),
            ],
        ];

        return $helper->generateForm([$fields_form]);
    }

    public function renderImportForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->module->l('Import'),
                    'icon' => 'icon-upload',
                ],
                'input' => [
                    [
                        'type' => 'select',
                        'label' => $this->module->l('What do you want to import?'),
                        'name' => 'entity',
                        'options' => [
                            'query' => [
                                ['value' => 0, 'name' => $this->module->l('Products')],
                            ],
                            'id' => 'value',
                            'name' => 'name',
                        ],
                        'desc' => $this->module->l('Choose the type of data you want to import.'),
                    ],
                    [
                        'type' => 'file',
                        'label' => $this->module->l('CSV File'),
                        'name' => 'file',
                        'required' => true,
                        'desc' => $this->module->l('Only UTF-8 and ISO-8859-1 encodings are allowed.'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->module->l('Field separator'),
                        'name' => 'csv_separator',
                        'class' => 'fixed-width-sm',
                        'desc' => $this->module->l('For example: 1;Ref123;Product Name;...'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->module->l('Multiple value separator'),
                        'name' => 'multiple_value_separator',
                        'class' => 'fixed-width-sm',
                        'desc' => $this->module->l('For example: Category 1,Category 2,Category 3'),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->module->l('Delete all products before import'),
                        'name' => 'truncate',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'truncate_on', 'value' => 1, 'label' => $this->module->l('Yes')],
                            ['id' => 'truncate_off', 'value' => 0, 'label' => $this->module->l('No')],
                        ],
                        'desc' => $this->module->l('Warning: This will remove all existing products.'),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->module->l('Use product reference as key'),
                        'name' => 'match_ref',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'match_ref_on', 'value' => 1, 'label' => $this->module->l('Yes')],
                            ['id' => 'match_ref_off', 'value' => 0, 'label' => $this->module->l('No')],
                        ],
                        'desc' => $this->module->l('If enabled, the product reference will be used to identify existing products.'),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->module->l('Force all ID numbers'),
                        'name' => 'force_all_id',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'force_all_id_on', 'value' => 1, 'label' => $this->module->l('Yes')],
                            ['id' => 'force_all_id_off', 'value' => 0, 'label' => $this->module->l('No')],
                        ],
                        'desc' => $this->module->l('If enabled, the ID numbers from the CSV will be used to update existing products or create new ones. Ensure IDs are unique to avoid conflicts.'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->module->l('Lines to skip'),
                        'name' => 'skip',
                        'class' => 'fixed-width-sm',
                        'desc' => $this->module->l('Number of lines to skip at the start of the file (e.g., header lines).'),
                    ],
                ],
                'submit' => [
                    'title' => $this->module->l('Next step'),
                    'name' => 'submitImportFile',
                    'icon' => 'process-icon-next',
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this->module;
        $helper->submit_action = 'submitImportFile';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->module->name . '&module_name=' . $this->module->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => [
                'entity' => 0,
                'csv_separator' => ';',
                'multiple_value_separator' => ',',
                'truncate' => 0,
                'match_ref' => 0,
                'force_all_id' => 0,
                'skip' => 1,
            ],
        ];

        return $helper->generateForm([$fields_form]);
    }

    public function renderMappingForm($headers)
    {
        $available_fields = [
            '-1' => $this->module->l('Don\'t import'),
            'id' => $this->module->l('ID (Use with "Force all ID numbers" option)'),
            'active' => $this->module->l('Active (0/1)'),
            'name' => $this->module->l('Name *'),
            'categories' => $this->module->l('Categories (x,y,z...)'),
            'price' => $this->module->l('Price tax excluded'),
            'id_tax_rules_group' => $this->module->l('Tax rule ID'), 
            'wholesale_price' => $this->module->l('Wholesale price'),
            'quantity' => $this->module->l('Quantity'),
            'description' => $this->module->l('Description'),
            'description_short' => $this->module->l('Short description'),
            'meta_title' => $this->module->l('Meta title'),
            'meta_keywords' => $this->module->l('Meta keywords'),
            'meta_description' => $this->module->l('Meta description'),
            'link_rewrite' => $this->module->l('URL rewritten'),
            'image' => $this->module->l('Image URLs (x,y,z...)'),
            'features' => $this->module->l('Feature (Name:Value:Position)'),
            'reference' => $this->module->l('Reference'),
            'ean13' => $this->module->l('EAN13'),
            'upc' => $this->module->l('UPC'),
            'manufacturer' => $this->module->l('Manufacturer'),
        ];

        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->module->l('Match your data'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'hidden',
                        'name' => 'csv_separator',
                    ],
                    [
                        'type' => 'hidden',
                        'name' => 'multiple_value_separator',
                    ],
                    [
                        'type' => 'hidden',
                        'name' => 'truncate',
                    ],
                    [
                        'type' => 'hidden',
                        'name' => 'match_ref',
                    ],
                    [
                        'type' => 'hidden',
                        'name' => 'force_all_id',
                    ],
                    [
                        'type' => 'hidden',
                        'name' => 'skip',
                    ],
                ],
                'submit' => [
                    'title' => $this->module->l('Import'),
                    'name' => 'submitImport',
                    'icon' => 'process-icon-save',
                ],
            ],
        ];

        $fields_value = [
            'csv_separator' => Tools::getValue('csv_separator', ';'),
            'multiple_value_separator' => Tools::getValue('multiple_value_separator', ','),
            'truncate' => Tools::getValue('truncate', 0),
            'match_ref' => Tools::getValue('match_ref', 0),
            'force_all_id' => Tools::getValue('force_all_id', 0),
            'skip' => Tools::getValue('skip', 1),
        ];

        foreach ($headers as $index => $header) {
            $fields_form['form']['input'][] = [
                'type' => 'select',
                'label' => sprintf($this->module->l('Column %d: %s'), $index + 1, htmlspecialchars($header)),
                'name' => 'field_mapping[' . $index . ']',
                'options' => [
                    'query' => array_map(function ($key, $value) {
                        return ['id' => $key, 'name' => $value];
                    }, array_keys($available_fields), $available_fields),
                    'id' => 'id',
                    'name' => 'name',
                ],
            ];
            $fields_value['field_mapping[' . $index . ']'] = '-1';
        }

        $helper = new HelperForm();
        $helper->module = $this->module;
        $helper->submit_action = 'submitImport';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->module->name . '&module_name=' . $this->module->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = ['fields_value' => $fields_value];

        return $helper->generateForm([$fields_form]);
    }
}