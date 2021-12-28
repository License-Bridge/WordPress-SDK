<?php

if (!function_exists('lb_array_empty')) {
    function lb_array_empty($mixed)
    {
        if (is_array($mixed)) {
            foreach ($mixed as $value) {
                if (!lb_array_empty($value)) {
                    return false;
                }
            }
        } elseif (!empty($mixed)) {
            return false;
        }

        return true;
    }
}
