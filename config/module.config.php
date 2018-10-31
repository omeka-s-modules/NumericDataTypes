<?php
return [
    'view_manager' => [
        'template_path_stack' => [
            OMEKA_PATH . '/modules/NumericDataTypes/view',
        ],
    ],
    'entity_manager' => [
        'mapping_classes_paths' => [
            OMEKA_PATH . '/modules/NumericDataTypes/src/Entity',
        ],
        'proxy_paths' => [
            OMEKA_PATH . '/modules/NumericDataTypes/data/doctrine-proxies',
        ],
    ],
    'data_types' => [
        'invokables' => [
            'numeric:timestamp' => NumericDataTypes\DataType\Timestamp::class,
            'numeric:integer' => NumericDataTypes\DataType\Integer::class,
        ],
    ],
    'view_helpers' => [
        'factories' => [
            'numericPropertySelect' => NumericDataTypes\Service\ViewHelper\NumericPropertySelectFactory::class,
        ],
    ],
    'form_elements' => [
        'factories' => [
            'NumericDataTypes\Form\Element\NumericPropertySelect' => NumericDataTypes\Service\Form\Element\NumericPropertySelectFactory::class,
        ],
    ],
];
