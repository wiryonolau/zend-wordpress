# ZendWordpress

Library to create wordpress plugin using Zend Framework 3 MVC Application
Fork from zendmaniacs/zf-to-wp, make it availiable as composer

## Installation

Using Composer
```bash
composer require wiryonolau/zend-wordpress
```

## Usage

#### your-plugin-file.php

```php
<?php
require_once plugin_dir_path(__FILE__). '/vendor/autoload.php';

$zendWordpress = new ZendWordpress\Application();
$zendWordpress->setPluginDirectory(plugin_dir_path(__FILE__))
            ->setPluginFile(__FILE__)
            ->setRoutePrefix("my_plugin_prefix")
            ->setTablePrefix("my_db_prefix")
            ->enableSession()
            ->run();
?>
```

#### config/application.config.php
module Zend\Navigation, Zend\Router, Zend\Session already included

```php
<?php

return [
    "modules" => [
        "ZendWordpress"
    ]
];

?>
```

#### config/router.config.php
```php
<?php

return [
    "router" => [
        "routes" => [
            "plugin_admin_page" => [
                "type" => "WpAdminRoute",
                "options" => [
                    "route" => "/plugin_admin_page_without_route_prefix"
                    "defaults" => [
                        "controller" => "Your controller"
                        "action" => "action"
                    ]
                ]
            ]
        ]
    ]
]

?>
```

## Using UrlHelper on view ##

Since all url in admin are translated to a query "admin.php?page=" when assembling route,
passing "query" options to url helper will break the url due to TreeRouteStack injecting query options after assembling route.
You must pass everything as parameters, which then will be converted to query by WpAdminRoute.

You could define your own TreeRouteStack if neccessary and pass it to array("router" => array("router_class" => ""))

```php
<?php
# Do this
$this->url(
    "your page without namespace",
    array(
        "param1" => "your parameter"
        "param1" => "your parameter and so on"
    )
);

# Instead of this
$this->url(
    "your page without namespace",
    array(),
    array(
        "query" => array(
            "param1" => "your parameter"
            "param1" => "your parameter and so on"
        )
    )
);
?>
```

## Limitation ##

- wpAdminRoute does not support child_routes at the moment. If you add child_routes it will thrown an exception.
- Wordpress navigation menu only support single nested level navigation.
