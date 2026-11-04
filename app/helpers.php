<?php

if (! function_exists('rupiah')) {
    /**
     * Format angka ke Rupiah, mis. rupiah(150000) -> "Rp 150.000"
     */
    function rupiah(int|float|null $amount): string
    {
        return 'Rp'."\u{00A0}".number_format((float) $amount, 0, ',', '.');
    }
}
