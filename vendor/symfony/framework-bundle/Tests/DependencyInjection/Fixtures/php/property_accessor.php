<?php

$container->loadFromExtension('framework', [
    'property_access' => [
        'magic_call' => true,
        'throw_exception_on_invalid_index' => true,
    ],
]);
