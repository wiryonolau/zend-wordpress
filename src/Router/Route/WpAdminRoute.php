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


namespace ZendWordpress\Router\Route;

use Zend\Router\Http\RouteInterface;
use Zend\Router\Http\RouteMatch;
use Zend\Stdlib\RequestInterface as Request;
use Zend\Stdlib\ArrayUtils;
use Zend\Router\Exception\InvalidArgumentException;

class WpAdminRoute implements RouteInterface
{

    protected $defaults = array();
    protected $params = array();
    protected $path = '/wp-admin/admin.php';
    protected $route;

    /**
     * Create a new page route.
     */
    public function __construct(array $defaults = array(), $route = '')
    {
        $this->defaults = $defaults;
        $this->route = $route;
    }

    /**
     * Create a new route with given options.
     */
    public static function factory($options = array())
    {
        if ($options instanceof \Traversable) {
            $options = ArrayUtils::iteratorToArray($options);
        } elseif (!is_array($options)) {
            throw new InvalidArgumentException(__METHOD__ . ' expects an array or Traversable set of options');
        }

        if (!isset($options['defaults'])) {
            $options['defaults'] = array();
        }

        if (!isset($options['route'])) {
            $options['route'] = '';
        }

        return new static($options['defaults'], $options['route']);
    }

    /**
     * @param \Zend\Http\PhpEnvironment\Request $request
     * @param null $pathOffset
     * @return null
     */
    public function match(Request $request, $pathOffset = null)
    {
        $params = array();
        $path = $request->getUri()->getPath();
        $query = $request->getUri()->getQuery();

        parse_str($query, $params);

        if ($path !== $this->path) {
            return null;
        }

        if (!empty($params['page'])) {
            return null;
        }

        if (preg_replace(sprintf('/^%s/', $this->defaults["plugin_prefix"]), '', $params['page']) !== $this->route) {
            return null;
        }

        try {
            unset($params['page']);
            $params = array_merge($this->defaults, $params);
            $this->params = $params;
            $routeMatch = new RouteMatch($params, 10);
            return $routeMatch;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Assemble the route.
     */
    public function assemble(array $params = array(), array $options = array())
    {
        if (empty($params['use_just_route'])) {
            $params['page'] = $this->route;
            $query = http_build_query($params);
            return $this->path.'?'.$query;
        } else {
            return $this->route;
        }
    }

    /**
     * Get a list of parameters used while assembling.
     */
    public function getAssembledParams()
    {
        return array();
    }

}
