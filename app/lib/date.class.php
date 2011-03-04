<?php

namespace App\Lib;
use \DateTime;

class Date {
    static function getTimeYearsAgo($yearsAgo = 1, $time = null) {
        if (!$time) {
            $time = time();
        }
        $timeYearsAgo = strtotime("-$yearsAgo year", $time);
        if (!$timeYearsAgo) {
            error_log("Invalid time string in AppDate::getTimeYearsAgo: [-$yearsAgo year] [$time]");
        }
        return $timeYearsAgo;
    } 
    // Format an interval with the two largest parts.
    static function relative ($interval) {
        $formatParts = array();
        if ($interval->y) {
            $formatParts[] = "%y " . plur($interval->y, "year");
        }
        if ($interval->m) {
            $formatParts[] = "%m " . plur($interval->m, "month");
        }
        if ($interval->d) {
            $formatParts[] = "%d " . plur($interval->d, "day");
        }
        if ($interval->h) {
            $formatParts[] = "%h " . plur($interval->h, "hour");
        }
        if ($interval->i) {
            $formatParts[] = "%i " . plur($interval->i, "minute");
        }
        if ($interval->s) {
            if (!count($formatParts)) {
                return "less than a minute";
            }
        }
        
        // We use the two biggest parts
        $format = $formatParts[0];
        if (count($formatParts) > 1) {
            $format .= ', ' . $formatParts[1];
        }
        
        // Prepend 'since ' or whatever you like
        return $interval->format($format);
    }
}
