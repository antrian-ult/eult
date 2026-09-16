<?php

/**
 * Helper upload EULT.
 * Porting dari CI3 application/helpers/uploadfile_helper.php dan
 * uploadcustom_helper.php. CI3 memakai library upload internal;
 * di CI4 dipakai $request->getFile() + validasi manual agar
 * perilaku (tipe, ukuran, nama file) tetap sama.
 */

if (! function_exists('eult_upload_ticket')) {
    /**
     * Mengunggah lampiran tiket (field 'ticketArchiveId') lalu
     * mencatatnya ke d_archive via ModelMaster.
     *
     * @param array{url:string,type:string,size:int,namafile:string} $konfig
     */
    function eult_upload_ticket(array $konfig, array $paramArsip): bool
    {
        $request = service('request');
        $berkas  = $request->getFile('ticketArchiveId');

        if ($berkas === null || $berkas->getError() === UPLOAD_ERR_NO_FILE) {
            return false;
        }

        if (! $berkas->isValid()) {
            eult_message_kirim(strip_tags($berkas->getErrorString()), 'error');
        }

        $tujuan = rtrim($konfig['url'], '/') . '/';
        if (! is_dir($tujuan)) {
            mkdir($tujuan, 0755, true);
        }

        // Validasi ekstensi sesuai allowed_types CI3 (mis. 'pdf' atau 'pdf|jpg|png')
        $diizinkan = explode('|', strtolower($konfig['type']));
        $ekstensi  = strtolower($berkas->getExtension());
        if (! in_array($ekstensi, $diizinkan, true)) {
            eult_message_kirim('Tipe file tidak diizinkan. Hanya: ' . $konfig['type'], 'error');
        }

        // Ekstensi bisa dipalsukan dengan rename — cocokkan isi berkas
        // (magic byte) dengan tipe yang diklaim.
        if (! eult_upload_konten_valid($berkas, $ekstensi)) {
            eult_message_kirim('Isi file tidak cocok dengan tipe ' . strtoupper($ekstensi) . '.', 'error');
        }

        // Validasi ukuran (CI3 max_size dalam KB)
        if ($berkas->getSizeByUnit('kb') > $konfig['size']) {
            eult_message_kirim('Ukuran file melebihi batas ' . round($konfig['size'] / 1024) . ' MB.', 'error');
        }

        $namaBaru                    = $konfig['namafile'] . '.' . $ekstensi;
        $berkas->move($tujuan, $namaBaru, true);
        $paramArsip['archiveFile'] = $namaBaru;

        $model = new \App\Models\ModelMaster();

        return $model->tambah('d_archive', $paramArsip);
    }
}

if (! function_exists('eult_upload_konten_valid')) {
    /**
     * Validasi isi berkas (magic byte) sesuai ekstensi yang diklaim:
     * PDF wajib berawalan %PDF-, gambar wajib terbaca getimagesize().
     * Ekstensi lain diperbolehkan (tidak ada magic byte yang dikenal).
     */
    function eult_upload_konten_valid(\CodeIgniter\HTTP\Files\UploadedFile $berkas, string $ekstensi): bool
    {
        if ($ekstensi === 'pdf') {
            $fp = @fopen($berkas->getTempName(), 'rb');

            if ($fp === false) {
                return false;
            }

            $magic = (string) fread($fp, 5);
            fclose($fp);

            return $magic === '%PDF-';
        }

        if (in_array($ekstensi, ['jpg', 'jpeg', 'png', 'gif'], true)) {
            return @getimagesize($berkas->getTempName()) !== false;
        }

        return true;
    }
}

if (! function_exists('eult_upload_custom')) {
    /**
     * Unggah generik untuk field arbitrer (porting uploadcustom CI3).
     * Mengembalikan instance File pada lokasi tujuan, sehingga
     * getFilename() berisi nama berkas sebenarnya (bukan nama
     * temporer upload PHP seperti /tmp/phpXXXX).
     *
     * @param array{url:string,type:string,size:int,namafile:string} $konfig
     */
    function eult_upload_custom(array $konfig, string $namaField): \CodeIgniter\Files\File
    {
        $request = service('request');
        $berkas  = $request->getFile($namaField);

        if ($berkas === null || ! $berkas->isValid()) {
            $pesan = $berkas instanceof \CodeIgniter\HTTP\Files\UploadedFile
                ? $berkas->getErrorString()
                : 'File tidak ditemukan.';
            eult_message_kirim(strip_tags($pesan), 'error');
        }

        $tujuan = rtrim($konfig['url'], '/') . '/';
        if (! is_dir($tujuan)) {
            mkdir($tujuan, 0755, true);
        }

        $diizinkan = explode('|', strtolower($konfig['type']));
        $ekstensi  = strtolower($berkas->getExtension());
        if (! in_array($ekstensi, $diizinkan, true)) {
            eult_message_kirim('Tipe file tidak diizinkan. Hanya: ' . $konfig['type'], 'error');
        }

        // Ekstensi bisa dipalsukan dengan rename — cocokkan isi berkas
        // (magic byte) dengan tipe yang diklaim.
        if (! eult_upload_konten_valid($berkas, $ekstensi)) {
            eult_message_kirim('Isi file tidak cocok dengan tipe ' . strtoupper($ekstensi) . '.', 'error');
        }

        if ($berkas->getSizeByUnit('kb') > $konfig['size']) {
            eult_message_kirim('Ukuran file melebihi batas ' . round($konfig['size'] / 1024) . ' MB.', 'error');
        }

        $namaBaru = $konfig['namafile'] . '.' . $ekstensi;
        $berkas->move($tujuan, $namaBaru, true);

        return new \CodeIgniter\Files\File($tujuan . $namaBaru, true);
    }
}
