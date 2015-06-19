<?php

namespace tourze\Base;

use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

/**
 * 调试类的实现
 *
 * @package tourze\Base
 */
class Debug
{

    /**
     * @var Run
     */
    protected static $_debugger = null;

    /**
     * @var bool 是否激活了内置的调试和错误处理方法
     */
    public static $enabled = false;

    /**
     * @var  array  需要显示出来的错误信息级别
     */
    public static $shutdownErrors = [
        E_PARSE,
        E_ERROR,
        E_USER_ERROR
    ];

    /**
     * @return \Whoops\Run
     */
    public static function debugger()
    {
        return self::$_debugger;
    }

    /**
     * 激活调试器
     */
    public static function enable()
    {
        if (self::$enabled)
        {
            return;
        }

        $whoops = new Run;
        $whoops->pushHandler(new PrettyPageHandler);
        $whoops->register();

        self::$enabled = true;
    }

    /**
     * 返回变量的打印html
     *
     *     // 可以打印多个变量
     *     echo self::vars($foo, $bar, $baz);
     *
     * @return string
     */
    public static function vars()
    {
        if (func_num_args() === 0)
        {
            return null;
        }

        $variables = func_get_args();
        $output = [];
        foreach ($variables as $var)
        {
            $output[] = self::_dump($var, 1024);
        }

        return '<pre class="debug">' . implode("\n", $output) . '</pre>';
    }

    /**
     * 返回单个变量的HTML格式
     *
     * @param   mixed   $value          variable to dump
     * @param   integer $length         maximum length of strings
     * @param   integer $levelRecursion recursion limit
     *
     * @return  string
     */
    public static function dump($value, $length = 128, $levelRecursion = 10)
    {
        return self::_dump($value, $length, $levelRecursion);
    }

    /**
     * Helper for self::dump(), handles recursion in arrays and objects.
     *
     * @param   mixed   $var    variable to dump
     * @param   integer $length maximum length of strings
     * @param   integer $limit  recursion limit
     * @param   integer $level  current recursion level (internal usage only!)
     *
     * @return  string
     */
    protected static function _dump(& $var, $length = 128, $limit = 10, $level = 0)
    {
        if (null === $var)
        {
            return '<small>null</small>';
        }
        elseif (is_bool($var))
        {
            return '<small>bool</small> ' . ($var ? 'true' : 'false');
        }
        elseif (is_float($var))
        {
            return '<small>float</small> ' . $var;
        }
        elseif (is_resource($var))
        {
            if (($type = get_resource_type($var)) === 'stream' && $meta = stream_get_meta_data($var))
            {
                $meta = stream_get_meta_data($var);

                if (isset($meta['uri']))
                {
                    $file = $meta['uri'];

                    return '<small>resource</small><span>(' . $type . ')</span> ' . htmlspecialchars($file, ENT_NOQUOTES, Base::$charset);
                }
            }
            else
            {
                return '<small>resource</small><span>(' . $type . ')</span>';
            }
        }
        elseif (is_string($var))
        {
            if (strlen($var) > $length)
            {
                // Encode the truncated string
                $str = htmlspecialchars(substr($var, 0, $length), ENT_NOQUOTES, Base::$charset) . '&nbsp;&hellip;';
            }
            else
            {
                // Encode the string
                $str = htmlspecialchars($var, ENT_NOQUOTES, Base::$charset);
            }

            return '<small>string</small><span>(' . strlen($var) . ')</span> "' . $str . '"';
        }
        elseif (is_array($var))
        {
            $output = [];

            // Indentation for this variable
            $space = str_repeat($s = '    ', $level);

            static $marker;

            if (null === $marker)
            {
                // Make a unique marker
                $marker = uniqid("\x00");
            }

            if (empty($var))
            {
                // Do nothing
            }
            elseif (isset($var[$marker]))
            {
                $output[] = "(\n$space$s*RECURSION*\n$space)";
            }
            elseif ($level < $limit)
            {
                $output[] = "<span>(";

                $var[$marker] = true;
                foreach ($var as $key => & $val)
                {
                    if ($key === $marker)
                    {
                        continue;
                    }
                    if ( ! is_int($key))
                    {
                        $key = '"' . htmlspecialchars($key, ENT_NOQUOTES, Base::$charset) . '"';
                    }

                    $output[] = "$space$s$key => " . self::_dump($val, $length, $limit, $level + 1);
                }
                unset($var[$marker]);

                $output[] = "$space)</span>";
            }
            else
            {
                // Depth too great
                $output[] = "(\n$space$s...\n$space)";
            }

            return '<small>array</small><span>(' . count($var) . ')</span> ' . implode("\n", $output);
        }
        elseif (is_object($var))
        {
            // Copy the object as an array
            $array = (array) $var;

            $output = [];

            // Indentation for this variable
            $space = str_repeat($s = '    ', $level);

            $hash = spl_object_hash($var);

            // Objects that are being dumped
            static $objects = [];

            if (empty($var))
            {
                // Do nothing
            }
            elseif (isset($objects[$hash]))
            {
                $output[] = "{\n$space$s*RECURSION*\n$space}";
            }
            elseif ($level < $limit)
            {
                $output[] = "<code>{";

                $objects[$hash] = true;
                foreach ($array as $key => & $val)
                {
                    if ($key[0] === "\x00")
                    {
                        // Determine if the access is protected or protected
                        $access = '<small>' . (($key[1] === '*') ? 'protected' : 'private') . '</small>';

                        // Remove the access level from the variable name
                        $key = substr($key, strrpos($key, "\x00") + 1);
                    }
                    else
                    {
                        $access = '<small>public</small>';
                    }

                    $output[] = "$space$s$access $key => " . self::_dump($val, $length, $limit, $level + 1);
                }
                unset($objects[$hash]);

                $output[] = "$space}</code>";
            }
            else
            {
                // Depth too great
                $output[] = "{\n$space$s...\n$space}";
            }

            return '<small>object</small> <span>' . get_class($var) . '(' . count($array) . ')</span> ' . implode("\n", $output);
        }

        return '<small>' . gettype($var) . '</small> ' . htmlspecialchars(print_r($var, true), ENT_NOQUOTES, Base::$charset);
    }
}
