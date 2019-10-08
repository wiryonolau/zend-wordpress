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

use Zend\Mvc\MvcEvent;
use Zend\Session\SessionManager;

class Module
{
    public function onBootstrap(MvcEvent $event)
    {
        $application = $event->getApplication();
        $eventManager = $application->getEventManager();
        $sharedEvents = $application->getEventManager()->getSharedManager();
        $serviceManager = $application->getServiceManager();

        if ($serviceManager->get("ApplicationConfig")["wordpress"]["use_session"]) {
            $sessionManager = $serviceManager->get(SessionManager::class);
            $sessionManager->start();
        }

        $sharedEvents->attach(
            'Zend\Mvc\Controller\AbstractController', 'dispatch', function ($e) {
                $result = $e->getResult();
                if ($result instanceof ViewModel) {
                    $result->setTerminal(true);
                }
            }
        );

        $eventManager->attach(
            MvcEvent::EVENT_DISPATCH_ERROR, function (MvcEvent $e) {
                $result = $e->getResult();
                if ($result instanceof ViewModel) {
                    $result->setTerminal(true);
                }
            }
        );

        $eventManager->attach(
            MvcEvent::EVENT_FINISH, function (MvcEvent $event) {
                if($event->getResponse() instanceof \Zend\Http\PhpEnvironment\Response) {
                    $event->stopPropagation();
                }
            }
        );
    }

    public function getRouteConfig()
    {
        return array(
            'factories' => array(
                'wpAdminRoute' => function ($routePluginManager, $name="", $options=[]) {
                    $locator = $routePluginManager->getServiceLocator();
                    $options = \Zend\Stdlib\ArrayUtils::merge(
                        $options, [
                        "defaults" => [
                            "route_prefix" => $locator->get("ApplicationConfig")["wordpress"]["route_prefix"]
                        ]
                        ]
                    );
                    $route = Router\Http\WpAdminRoute::factory($options);
                    return $route;
                },
            ),
        );
    }
}
