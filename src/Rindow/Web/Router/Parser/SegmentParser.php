<?php
namespace Rindow\Web\Router\Parser;

use Rindow\Web\Router\Exception;

class SegmentParser
{
    public static function parse($path, $route, $doNotThrowPageNotFound=null)
    {
        $param = array();
        if(!isset($route['parameters']))
            return $param;

        // *** CAUTION ****
        // Because "substr" 's action depends on PHP version, it is necessary to check the length.
        $start = strlen($route['path']);
        if(strlen($path) <= $start)
            return $param;
        // ****************

        $paramStr = substr($path,$start);
        if($paramStr===false)
            return $param;
        $paramArray = explode('/',rtrim(ltrim($paramStr,'/'),'/'));
        $idx = 0;
        foreach($route['parameters'] as $name) {
            if(isset($paramArray[$idx]))
                $param[$name] = urldecode($paramArray[$idx]);
            $idx++;
        }
        return $param;
    }

    public static function assemble(array $param,array $route)
    {
        if(!isset($route['parameters']))
            return $route['path'];
        $path = '';
        foreach($route['parameters'] as $name) {
            if(!array_key_exists($name,$param))
                break;
            $path .= '/'.urlencode($param[$name]);
            unset($param[$name]);
        }
        if(count($param))
            throw new Exception\DomainException('unknown parameter:'.implode(',',array_keys($param)).', defined parameters are '.implode(',',$route['options']['parameters']).var_export($param));
        $path = rtrim($route['path'],'/').$path;
        if($path==='')
            return '/';
        return $path;
    }
}
