<?php

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

namespace ZendWordpress;

class Module
{
    public function getRouteConfig()
    {
        return array(
            'factories' => array(
                'wpAdminRoute' => function ($routePluginManager, $name="", $options=[]) {
                    $locator = $routePluginManager->getServiceLocator();
                    $options = \Zend\Stdlib\ArrayUtils::merge($options, [
                        "defaults" => [
                            "plugin_prefix" => $locator->get("ApplicationConfig")["wordpress"]["plugin_prefix"]
                        ]
                    ]);
                    $route = Router\Route\WpAdminRoute::factory($options);
                    return $route;
                },
            ),
        );
    }
}
