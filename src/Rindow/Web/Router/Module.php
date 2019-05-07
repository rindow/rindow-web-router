<?php
namespace Rindow\Web\Router;

class Module
{
    public function getConfig()
    {
        return array(
            'annotation' => array(
                'aliases' => array(
                    'Interop\\Lenient\\Web\\Router\\Annotation\\Controller' =>
                        'Rindow\\Web\\Router\\Annotation\\Controller',
                    'Interop\\Lenient\\Web\\Router\\Annotation\\RequestMapping' =>
                        'Rindow\\Web\\Router\\Annotation\\RequestMapping',
                ),
            ),
            'container' => array(
                'aliases' => array(
                    'Rindow\\Web\Mvc\\DefaultRouter'        => 'Rindow\\Web\\Router\\DefaultRouter',
                ),
                'components' => array(
                    'Rindow\\Web\\Router\\DefaultRouter' => array(
                        'class' => 'Rindow\\Web\\Router\\Router',
                        'properties' => array(
                            'configCacheFactory' => array('ref' => 'ConfigCacheFactory'),
                            'serviceLocator' => array('ref' => 'ServiceLocator'),
                            'config' => array('config' => 'web::router'),
                            'doNotThrowPageNotFound' => array('value'=>true),
                        ),
                    ),
                    'Rindow\\Web\\Router\\RoutingTableBuilder\\DefaultAnnotation' => array(
                        'class' => 'Rindow\\Web\\Router\\RoutingTableBuilder\\Annotation',
                        'properties' => array(
                            'serviceLocator' => array('ref' => 'ServiceLocator'),
                            'annotationReader' => array('ref' => 'AnnotationReader'),
                        ),
                        'scope'=>'prototype',
                    ),
                    'Rindow\\Web\\Router\\RoutingTableBuilder\\DefaultFile' => array(
                        'class' => 'Rindow\\Web\\Router\\RoutingTableBuilder\\File',
                        'scope'=>'prototype',
                    ),
                ),
            ),
            'web'=> array(
                'router' => array(
                    'builder_aliases' => array(
                        'annotation' => 'Rindow\\Web\\Router\\RoutingTableBuilder\\DefaultAnnotation',
                        'file'       => 'Rindow\\Web\\Router\\RoutingTableBuilder\\DefaultFile',
                    ),
                    /*
                     *  Inject your configurations for the router
                     *
                    'builders' => array(
                        'annotation' => array(
                            'controller_paths' => array(
                                'your_controllers_file_path1' => true,
                                'your_controllers_file_path2' => true,
                            ),
                        ),
                        'file' => array(
                            'config_files' => array(
                                'php' => array(
                                    'Namespace1' => 'your_routes_file1.php',
                                    'Namespace2' => 'your_routes_file2.php',
                                ),
                            ),

                        ),
                    ),
                    'routes' => array(
                        'your\\namespace\\some_route_name' => array(
                            'path' => '/some/request/path',
                            'type' => 'literal or segment',
                            'conditions' => array(
                                'method' => 'GET or POST or someting',
                                'headers' => array(....)
                            ),
                            .......
                        ),
                        'your\\namespace\\other_route_name' => array(
                            'path' => '/other/request/path',
                            'conditions' => array(
                                'method' => 'GET',
                            ),
                            .......
                        ),
                    ),
                    */
                ),
            ),
        );
    }
}
