# ZendWordpress

Library to create wordpress plugin using Zend Framework 3 MVC Application
Fork from zendmaniacs/zf-to-wp, make it availiable as composer

## Installation

Using Composer
```bash
composer require wiryonolau/zend-wordpress
```

## Usage

your-plugin-file.php
```php
<?php
require_once plugin_dir_path(__FILE__). '/vendor/autoload.php';

$zendWordpress = new ZendWordpress\Application();
$zendWordpress->setPluginDirectory(plugin_dir_path(__FILE__))
            ->setPluginFile(__FILE__)
            ->setPluginPrefix("my_plugin_prefix")
            ->run();
?>
```

config/application.config.php

```php
<?php

return [
    "modules" => [
        "ZendWordpress"
    ]
];

?>
```

config/router.config.php
```php
<?php

return [
    "router" => [
        "routes" => [
            "plugin_admin_page" => [
                "type" => "WpAdminRoute",
                "options" => [
                    "route" => "/plugin_admin_page"
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
