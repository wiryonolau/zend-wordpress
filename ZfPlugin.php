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

Copyright 2015-2017 Zendmaniacs.
*/

use Zend\Mvc\MvcEvent;
use Zend\View\Model\ViewModel;

/**
 * Class of plugin with all integration methods of ZF3 applciation into WP
 */
class ZfPlugin
{

    /**
     * @var \Zend\Mvc\Application
     */
    protected static $application;

    /**
     * ZendFramework 3 Application init
     */
    public static function initApplication()
    {
        $dir = dirname(dirname(dirname(dirname(__DIR__))));
        chdir($dir);
        require 'vendor/autoload.php';
        require_once( ZF__PLUGIN_DIR . 'WpAdminRoute.php' );
        self::$application = \Zend\Mvc\Application::init(require 'config/application.config.php');
    }

    /**
     * Nothing to init
     */
    public function init()
    {
        
    }

    /**
     * Attached to activate_{ plugin_basename( __FILES__ ) } by register_activation_hook()
     * @static
     */
    public static function plugin_activation()
    {
        
    }

    /**
     * Removes all connection options
     * @static
     */
    public static function plugin_deactivation()
    {
        //tidy up
    }

    /**
     * Logging
     * @param type $debug
     */
    public static function log($debug)
    {
        //tidy up
    }

    /**
     * Call ZF application if Wordpress not in action
     * 
     * @global string $customTemplate
     * @global WP_Query $wp_query
     * @param array $query
     * @return \StdClass
     */
    public function posts($query)
    {
        global $customTemplate, $wp_query;

        if (empty($query) || $wp_query->is_404) {

            /* @var $response \Zend\Http\PhpEnvironment\Response */
            $response = self::runApplication();

            $wpResponse = new \StdClass();
            $contentType = $response->getHeaders()->get('content-type');
            if ($contentType) {
                $contentTypeValue = $contentType->getFieldValue();
                switch ($contentTypeValue) {
                    default:
                        $response->sendHeaders();
                        $response->sendContent();
                        exit;
                        break;
                }
            }
            $wpResponse->post_content = $response->getContent();
            $wpResponse->post_type = 'page';
            $wpResponse->post_title = false;
            $wpResponse->comment_status = 'closed';

            $query = [$wpResponse];

            $customTemplate = $response->getMetadata('wp-page', 'default') . '.php';
            $isDebug = self::$application->getRequest()->getQuery('debug', false);

            if (!$isDebug) {
                if($response->getStatusCode() >= 400) {
                    $customTemplate = '404.php';
                } else {
                    $wp_query->is404 = false;
                }
            } else {
                \Zend\Debug\Debug::dump($response); die;
            }
        }
        return $query;
    }

    /**
     * Execute ZF application
     * @return  Zend\Http\PhpEnvironment\Response
     */
    protected static function runApplication()
    {
        $eventManager = self::$application->getEventManager();
        $sharedEvents = self::$application->getEventManager()->getSharedManager();
        $sharedEvents->attach('Zend\Mvc\Controller\AbstractController', 'dispatch', function($e) {
            $result = $e->getResult();
            if ($result instanceof ViewModel) {
                $result->setTerminal(true);
            }
        });

        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, function(MvcEvent $e) {
            $result = $e->getResult();
            if ($result instanceof ViewModel) {
                $result->setTerminal(true);
            }
        });

        $eventManager->attach(MvcEvent::EVENT_FINISH, function(MvcEvent $event) {
            if($event->getResponse() instanceof \Zend\Http\PhpEnvironment\Response) {
                $event->stopPropagation();
            }
        });
        self::$application->getRequest()->setBaseUrl('');
        self::$application->run();
        $response = self::$application->getResponse();
        return $response;
    }

    /**
     * Setup custom template if was defined globally
     * 
     * @global string $customTemplate
     */
    public function templateRedirect()
    {
        global $customTemplate;
        $filePath = get_template_directory() . '/' . $customTemplate;
        if (!empty($customTemplate) && file_exists($filePath)) {
            include($filePath);
            exit;
        }
    }

    /**
     * Receive and setup WP widgets from ZF application
     */
    public function registerWidgets()
    {
        if (self::$application) {
            $config = self::$application->getServiceManager()->get('config');
            foreach ($config['wp_widgets'] as $widgetClass) {
                register_widget($widgetClass);
            }
        }
    }

    /**
     * Display ZF action content in admin section on WP
     */
    public static function getAdminContent()
    {
        self::$application->getRequest()->setMetadata('isWpAdmin', true);
        $response = self::runApplication();

        if ($response->isRedirect()) {
            $redirectUrl = get_option('siteurl') . $response->getHeaders()->current()->getFieldValue();
            wp_redirect($redirectUrl);
            exit();
        } else {
            require_once(ABSPATH . 'wp-admin/admin-header.php');
        }
        echo '<div class="wrap">' . $response->getContent() . '</div>';
    }

    /**
     * Register admin WP navigation
     */
    public function registerAdminNavigation()
    {
        if (self::$application) {
            $navigation = self::$application->getServiceManager()->get('Zend\Navigation\ZfToWpAdmin');
            /* @var $page \Zend\Navigation\Page\Mvc */
            foreach ($navigation as $page) {
                $params = $page->getParams();
                $params['use_just_route'] = true;
                $page->setParams($params);
                add_menu_page($page->getLabel(), $page->getLabel(), 'manage_options', $page->getHref(), array('ZfPlugin', 'getAdminContent'), $page->get('icon'));
                add_submenu_page($page->getHref(), $page->getLabel(), $page->getLabel(), 'manage_options', $page->getHref(), array('ZfPlugin', 'getAdminContent'));
                if ($page->hasPages()) {
                    foreach ($page->getPages() as $subPage) {
                        $params = $subPage->getParams();
                        $params['use_just_route'] = true;
                        $subPage->setParams($params);
                        add_submenu_page($page->getHref(), $subPage->getLabel(), $subPage->getLabel(), 'manage_options', $subPage->getHref(), array('ZfPlugin', 'getAdminContent'));
                    }
                }
            }
        }
    }

}