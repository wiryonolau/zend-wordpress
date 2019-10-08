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
use Zend\View\Model\ViewModel;
use Zend\Mvc\Application as MvcApplication;
use ZendWordpress\Wordpress;
use ZendWordpress\WordpressHooksInterface;

class Application
{
    protected $wordpressHooks = null;

    protected $scripts = array();

    protected $options = array(
        "plugin_directory" => "",
        "plugin_file" => "",
        "route_prefix" => "",
        "table_prefix" => "",
        "use_session" => false
    );

    public function __construct( array $options = array() )
    {
        $this->options = \Zend\Stdlib\ArrayUtils::merge($this->options, $options);
    }

    public function setWordpressHooks(WordpressHooksInterface $hooks) {
        $this->wordpressHooks = $hooks;
    }

    public function setPluginDirectory($plugin_dir)
    {
        if (is_dir($plugin_dir)) {
            $this->options["plugin_directory"] = $plugin_dir;
        }
        return $this;
    }

    public function setPluginFile($plugin_file)
    {
        if (file_exists($plugin_file)) {
            $this->options["plugin_file"] = $plugin_file;
        }
        return $this;
    }

    public function setRoutePrefix($route_prefix)
    {
        if (preg_match('/[a-zA-Z0-9_-]+/', $route_prefix)) {
            $this->options["route_prefix"] = $route_prefix;
        }
        return $this;
    }

    public function setTablePrefix($table_prefix = "")
    {
        if (preg_match('/[a-zA-Z0-9_-]+/', $table_prefix)) {
            $this->options["table_prefix"] = $table_prefix;
        }
        return $this;
    }

    public function enableSession() {
        $this->options["use_session"] = true;
        return $this;
    }

    public function addStyle($scope, $handler, $source="", $dependency=array(), $version=false, $media = "all")
    {
        array_push(
            $this->scripts, array(
                "type" => "style",
                "scope" => $scope,
                "handler" => $handler,
                "source" => $source,
                "dependency" => $dependency,
                "version" => $version,
                "media" => $media
            )
        );

        return $this;
    }

    public function addScript($scope, $handler, $source="", $dependency=array(), $version=false, $in_footer = false)
    {
        array_push(
            $this->scripts, array(
                "type" => "script",
                "scope" => $scope,
                "handler" => $handler,
                "source" => $source,
                "dependency" => $dependency,
                "version" => $version,
                "in_footer" => $in_footer
            )
        );

        return $this;
    }

    public function run()
    {
        if (is_null($this->wordpressHooks)) {
            $this->wordpressHooks = new Wordpress();
        }

        if (empty($this->options["plugin_directory"]) or empty($this->options["plugin_file"])) {
            throw new \Exception("Must specify plugin directory path and plugin file path");
        }

        register_activation_hook($this->options["plugin_file"], array( $this->wordpressHooks, 'pluginActivation' ));
        register_deactivation_hook($this->options["plugin_file"], array( $this->wordpressHooks, 'pluginDeactivation' ));

        add_action('init', array( $this->wordpressHooks, 'init' ));
        add_filter('posts_results', array( $this->wordpressHooks, 'posts' ));
        add_action('template_redirect', array( $this->wordpressHooks,'templateRedirect'));
        add_action('widgets_init', array( $this->wordpressHooks,'registerWidgets'));
        add_action('admin_menu', array( $this->wordpressHooks,'registerAdminNavigation'));
        add_action(
            'wp_enqueue_scripts', function () {
                call_user_func(array($this->wordpressHooks, 'registerScript'), "frontend", $this->scripts);
            }
        );
        add_action(
            'admin_enqueue_scripts', function () {
                call_user_func(array($this->wordpressHooks, 'registerScript'), "admin", $this->scripts);
            }
        );

        $this->initApplication();
    }

    protected function initApplication()
    {
        /**
         * Inject wordpress config
         * Can be access from $container->get("ApplicationConfig") or $application->getServiceManager->get("ApplicationConfig")
         */
        $config = array(
            "modules" => array(
                "Zend\Router",
                "Zend\Session",
                "Zend\Navigation"
            ),
            "wordpress" => $this->options
        );

        if (file_exists($this->options["plugin_directory"]. '/config/application.config.php')) {
            $config = \Zend\Stdlib\ArrayUtils::merge($config, include $this->options["plugin_directory"]. '/config/application.config.php');
        }

        $this->wordpressHooks->setRoutePrefix($this->options["route_prefix"])
                        ->setApplication(MvcApplication::init($config));
    }
}
