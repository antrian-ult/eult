<?php

if (! function_exists('eult_versi_aset')) {
    /**
     * Versi cache-buster aset berbasis waktu modifikasi file: nilai hanya
     * berubah saat filenya berubah sehingga HTTP cache tetap bekerja.
     * (Pendekatan lama date('YmdHis') berubah tiap detik dan membuat
     * browser tidak pernah menyimpan aset.)
     *
     * @param string $aset Jalur relatif terhadap public/ (FCPATH),
     *                     mis. 'assets/js/pages/eult-csrf.js'.
     */
    function eult_versi_aset(string $aset): string
    {
        $waktu = @filemtime(FCPATH . $aset);

        return $waktu !== false ? (string) $waktu : '1';
    }
}
