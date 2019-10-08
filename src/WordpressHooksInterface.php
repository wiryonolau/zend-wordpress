<?php

namespace ZendWordpress;

use Zend\Debug\Debug;

interface WordpressHooksInterface
{
    public function setApplication($application);
    public function setRoutePrefix($route_prefix = "");
    public function init();
    public function plugin_activation();
    public function plugin_deactivation();
    public function log($debug);
    public function posts($query);
    public function templateRedirect();
    public function registerScript($scope, array $scripts = array());
    public function registerWidgets();
    public function registerAdminNavigation($navigation);
    public function getAdminContent();
}
