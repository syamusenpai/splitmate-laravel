<?php

if (! function_exists('rm')) {
    /** Format an amount in sen as "RM1,234.50". */
    function rm(int $sen): string
    {
        return 'RM'.number_format(abs($sen) / 100, 2);
    }
}
