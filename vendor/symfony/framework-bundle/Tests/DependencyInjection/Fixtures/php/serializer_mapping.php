<?php

$container->loadFromExtension('framework', [
    'annotations' => ['enabled' => true],
    'serializer' => [
        'enable_annotations' => true,
        'mapping' => [
            'paths' => [
                '%kernel.project_dir%/Fixtures/TestBundle/Resources/config/serializer_mapping/files',
                '%kernel.project_dir%/Fixtures/TestBundle/Resources/config/serializer_mapping/serialization.yml',
                '%kernel.project_dir%/Fixtures/TestBundle/Resources/config/serializer_mapping/serialization.yaml',
            ],
        ],
    ],
]);
