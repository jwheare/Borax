<?php

namespace Core;

class Dump {
    protected static $output = '';
    public static function var_dump ($var, $highlight = false) {
        $output = '';
        if ($highlight) {
            $output .= '<div style="font-weight: bold;">';
        }
        $xdebug = ini_get('xdebug.overload_var_dump');
        if (!$xdebug) {
            $output .= "<pre style='overflow: auto; background: #fff; color: #222; border: 1px dotted #ddd; padding: 3px;'>";
        }
        // Dump into a buffer
        ob_start();
        var_dump($var);
        $dump = ob_get_contents();
        ob_end_clean();
        if ($xdebug) {
            $output .= $dump;
        } else {
            $output .= safe($dump);
            $output .= "</pre>";
        }
        if ($highlight) {
            $output .= '</div>';
        }
        self::$output .= $output;
        if (!headers_sent()) {
            self::flush();
        }
    }
    public static function light ($var) {
        // Dump into a buffer
        ob_start();
        print_r($var);
        print_r("\n");
        $output = ob_get_contents();
        ob_end_clean();
        if (headers_sent()) {
            echo $output;
        } else {
            self::$output .= $output;
        }
    }
    public static function flush () {
        echo ob_get_clean();
        echo self::$output;
        self::$output = '';
    }
}