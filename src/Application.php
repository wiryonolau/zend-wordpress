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
/**
 * Class of plugin with all integration methods of ZF3 applciation into WP
 */
class Application
{

    /**
     * @var \Zend\Mvc\Application
     */
    protected static $application;

    protected $plugin_dir = "";
    protected $plugin_file = "";
    protected $prefix = "";
    protected $scripts = array();

    public function __construct( array $options = array() )
    {
        $defaults = array(
            "plugin_directory" => "",
            "plugin_file" => "",
            "plugin_prefix" => ""
        );

        $defaults = array_merge($defaults, array_intersect_key($options, $defaults));

        $this->setPluginDirectory($defaults["plugin_directory"]);
        $this->setPluginFile($defaults["plugin_file"]);
        $this->setPluginPrefix($defaults["plugin_prefix"]);
    }

    public function setPluginDirectory($plugin_dir)
    {
        if (is_dir($plugin_dir)) {
            $this->plugin_dir = $plugin_dir;
        }
        return $this;
    }

    public function setPluginFile($plugin_file)
    {
        if (file_exists($plugin_file)) {
            $this->plugin_file = $plugin_file;
        }
        return $this;
    }

    public function setPluginPrefix($plugin_prefix)
    {
        if (preg_match('/[a-zA-Z0-9_-]+/', $plugin_prefix)) {
            $this->plugin_prefix = $plugin_prefix;
        }
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

    public function registerScript($scope)
    {
        foreach($this->scripts as $script) {
            if (!in_array($script["scope"], ["*", "all"]) and $script["scope"] !== $scope ) {
                continue;
            }

            extract($script);
            switch($type) {
            case "style":
                wp_enqueue_style($handler, $source, $dependency, $version, $media);
                break;
            case "script":
                wp_enqueue_script($handler, $source, $dependency, $version, $in_footer);
                break;
            default:
            }
        };
    }

    public function run()
    {
        if (empty($this->plugin_dir) or empty($this->plugin_file)) {
            throw new \Exception("Must specify plugin directory path and plugin file path");
        }

        register_activation_hook($this->plugin_file, array( $this, 'plugin_activation' ));
        register_deactivation_hook($this->plugin_file, array( $this, 'plugin_deactivation' ));

        add_action('init', array( $this, 'init' ));
        add_filter('posts_results', array( $this, 'posts' ));
        add_action('template_redirect', array( $this,'templateRedirect'));
        add_action('widgets_init', array( $this,'registerWidgets'));
        add_action('admin_menu', array( $this,'registerAdminNavigation'));
        add_action(
            'wp_enqueue_scripts', function () {
                call_user_func(array($this, 'registerScript'), "frontend");
            }
        );
        add_action(
            'admin_enqueue_scripts', function () {
                call_user_func(array($this, 'registerScript'), "admin");
            }
        );

        self::initApplication($this->plugin_dir, $this->plugin_prefix);
    }


    /**
     * ZendFramework 3 Application init
     */
    public static function initApplication( $plugin_dir, $plugin_prefix = "")
    {
        /**
         * Inject wordpress config
         * Can be access from $container->get("ApplicationConfig") or $application->getServiceManager->get("ApplicationConfig")
         */
        $config = array(
            "wordpress" => array(
                "plugin_dir" => $plugin_dir,
                "plugin_prefix" => $plugin_prefix
            )
        );

        if (file_exists($plugin_dir. '/config/application.config.php')) {
            $config = \Zend\Stdlib\ArrayUtils::merge($config, include $plugin_dir. '/config/application.config.php');
        }

        self::$application = MvcApplication::init($config);
    }

    /**
     * Nothing to init
     */
    public function init()
    {

    }

    /**
     * Attached to activate_{ plugin_basename( __FILES__ ) } by register_activation_hook()
     *
     * @static
     */
    public function plugin_activation()
    {

    }

    /**
     * Removes all connection options
     *
     * @static
     */
    public function plugin_deactivation()
    {
        //tidy up
    }

    /**
     * Logging
     *
     * @param type $debug
     */
    public function log($debug)
    {
        //tidy up
    }

    /**
     * Call ZF application if Wordpress not in action
     *
     * @global string $customTemplate
     * @global WP_Query $wp_query
     * @param  array $query
     * @return \StdClass
     */
    public function posts($query)
    {
        global $customTemplate, $wp_query;

        if (empty($query) || !$this->isVisiblePost($query) || $wp_query->is_404) {

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
     * Check if post is HTML type
     *
     * @param  array $query
     * @return boolean
     */
    protected function isVisiblePost($query)
    {
        foreach($query as $post) {
            switch($post->post_type) {
            case 'post':
            case 'page':
                return true;
            }
        }
        return false;
    }

    /**
     * Execute ZF application
     *
     * @return Zend\Http\PhpEnvironment\Response
     */
    protected static function runApplication()
    {
        self::$application->getRequest()->setBaseUrl('');
        self::$application->run();
        return self::$application->getResponse();
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
            include $filePath;
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

            if (empty($config["wp_widgets"])) {
                return false;
            }

            foreach ($config['wp_widgets'] as $widgetClass) {
                register_widget($widgetClass);
            }
        }
    }

    /**
     * Display ZF action content in admin section on WP
     */
    public function getAdminContent()
    {
        self::$application->getRequest()->setMetadata('isWpAdmin', true);
        $response = self::runApplication();

        if ($response->isRedirect()) {
            $redirectUrl = get_option('siteurl') . $response->getHeaders()->current()->getFieldValue();
            wp_redirect($redirectUrl);
            exit();
        } else {
            include_once ABSPATH . 'wp-admin/admin-header.php';
        }
        echo '<div class="wrap">' . $response->getContent() . '</div>';
    }

    /**
     * Register admin WP navigation
     */
    public function registerAdminNavigation()
    {
        if (self::$application) {
            $navigation = self::$application->getServiceManager()->get('Zend\Navigation\Navigation');
            $this->addPage($navigation);
        }
    }

    private function addPage($pages, $parent_target = null)
    {
        foreach ($pages as $page) {
            if ($parent_target === null) {
                $target = $this->getHref($page);
                add_menu_page($page->getLabel(), $page->getLabel(), 'manage_options', $target, array($this, 'getAdminContent'), $page->get('icon'));
                if(!empty($params["parent_as_child"]) and $params["parent_as_child"] == true) {
                    add_submenu_page($target, $page->getLabel(), $page->getLabel(), 'manage_options', $target, array($this, 'getAdminContent'));
                }
            } else {
                $target = $this->getHref($page);
                add_submenu_page($parent_target, $page->getLabel(), $page->getLabel(), 'manage_options', $target, array($this, 'getAdminContent'));
            }

            if ($page->hasPages()) {
                $this->addPage($page->getPages(), $target);
            }
        }
    }

    private function getHref($page)
    {
        $params = $page->getParams();
        $params['use_just_route'] = true;
        $page->setParams($params);

        $href = array_values(array_filter(explode("/", $page->getHref())));
        $href[0] = sprintf("%s%s", $this->plugin_prefix, $href[0]);
        return implode("/", $href);
    }

}
