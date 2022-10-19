<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('var')
;

$config = new PhpCsFixer\Config();
$config->setRules([
    '@Symfony' => true,
    '@PHP70Migration' => true,
    '@PHP71Migration' => true,
    '@PHP73Migration' => true,
    '@DoctrineAnnotation' => true,
    'doctrine_annotation_array_assignment' => ['operator' => '='],
    'yoda_style' => false,
    'strict_comparison' => true,
    'strict_param' => true,
    'declare_strict_types' => true,
    'method_argument_space' => ['on_multiline' => 'ignore'],
])
    ->setRiskyAllowed(true)
    ->setFinder($finder);

return $config;
