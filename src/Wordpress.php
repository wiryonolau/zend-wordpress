<?php

namespace ZendWordpress;

use Zend\Debug\Debug;
use ZendWordpress\WordpressHooksInterface;

class Wordpress implements WordpressHooksInterface
{
    protected $application = null;
    protected $routePrefix = null;

    public function setApplication($application) {
        $this->application = $application;
        return $this;
    }

    public function setRoutePrefix($route_prefix = "") {
        $this->routePrefix = $route_prefix;
        return $this;
    }

    public function init()
    {
    }

    public function plugin_activation()
    {
    }

    public function plugin_deactivation()
    {
    }

    public function log($debug)
    {
    }

    public function posts($query)
    {
        global $customTemplate, $wp_query;

        if (empty($query) || !$this->isVisiblePost($query) || $wp_query->is_404) {

            /* @var $response \Zend\Http\PhpEnvironment\Response */
            $response = $this->runApplication();

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
            $isDebug = $this->application->getRequest()->getQuery('debug', false);

            if (!$isDebug) {
                if($response->getStatusCode() >= 400) {
                    $customTemplate = '404.php';
                } else {
                    $wp_query->is404 = false;
                }
            } else {
                Debug::dump($response); die;
            }
        }
        return $query;
    }

    public function templateRedirect()
    {
        global $customTemplate;
        $filePath = get_template_directory() . '/' . $customTemplate;
        if (!empty($customTemplate) && file_exists($filePath)) {
            include $filePath;
            exit;
        }
    }

    public function registerScript($scope, array $scripts = array())
    {
        foreach($scripts as $script) {
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

    public function registerWidgets()
    {
        if ($this->$application) {
            $config = $this->$application->getServiceManager()->get('config');

            if (empty($config["wp_widgets"])) {
                return false;
            }

            foreach ($config['wp_widgets'] as $widgetClass) {
                register_widget($widgetClass);
            }
        }
    }

    public function registerAdminNavigation($navigation)
    {
        if ($this->application) {
            $navigation = $this->application->getServiceManager()->get('Zend\Navigation\Navigation');
            $this->addPage($navigation);
        }
    }

    public function getAdminContent()
    {
        $this->application->getRequest()->setMetadata('isWpAdmin', true);
        $response = $this->runApplication();

        if ($response->isRedirect()) {
            $redirectUrl = get_option('siteurl') . $response->getHeaders()->current()->getFieldValue();
            wp_redirect($redirectUrl);
            exit();
        } else {
            include_once ABSPATH . 'wp-admin/admin-header.php';
        }
        echo '<div class="wrap">' . $response->getContent() . '</div>';
    }


    protected function addPage($pages, $parent_target = null)
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

    protected function getHref($page)
    {
        $params = $page->getParams();
        $params['use_just_route'] = true;
        $page->setParams($params);

        $href = array_values(array_filter(explode("/", $page->getHref())));
        $href[0] = sprintf("%s%s", $this->routePrefix, $href[0]);
        return implode("/", $href);
    }

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

    protected function runApplication()
    {
        $this->application->getRequest()->setBaseUrl('');
        $this->application->run();
        return $this->application->getResponse();
    }
}
