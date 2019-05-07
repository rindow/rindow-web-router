<?php
namespace Rindow\Web\Router\Parser;

use Rindow\Web\Router\Exception;
use Rindow\Web\Router\Router;

class LiteralParser
{
    public static function parse($path, $route)
    {
        // *** CAUTION ****
        // Because "substr" 's action depends on PHP version, it is necessary to check the length.
        $start = strlen($route['path']);
        if(strlen($path) <= $start)
            return array();
        // ****************

        $paramStr = substr($path,$start);
        if($paramStr===false)
            return array();
        $paramArray = explode('/',rtrim(ltrim($paramStr,'/'),'/'));
        if(count($paramArray)==0)
            return array();
        throw new Exception\PageNotFoundException('A literal route has sub-directory on route "'.$route['name'].'".',Router::STATUS_PAGE_NOT_FOUND);
    }

    public static function assemble(array $param,array $route)
    {
        if(count($param))
            throw new Exception\DomainException('a literal route can not have parameters:'.implode(',',$param));
        return $route['path'];
   }
}
