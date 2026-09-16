"use strict";

// Class Definition
var FormCustom = function() {

    var bacaRespons = function(data) {
        try {
            return (typeof data === 'string' ? JSON.parse(data) : data);
        } catch (err) {
            return null;
        }
    }

    var pesanGalat = function(xhr) {
        var res = xhr && xhr.responseText ? bacaRespons(xhr.responseText) : null;
        if (res && res.message) {
            return String(res.message).replace(/<[^>]*>/g, '');
        }
        if (xhr && xhr.status === 403) {
            return 'Sesi keamanan kedaluwarsa. Silakan muat ulang halaman dan coba lagi.';
        }
        return 'Terjadi gangguan. Silakan muat ulang halaman dan coba lagi.';
    }

    var handleClickDelete = function() {
        // Delegasi ke document: tabel dipaginasi DataTables sehingga baris
        // yang muncul belakangan (cari/halaman/urut) tidak ada saat binding
        // langsung. Lepas binding langsung warisan dulu agar tidak ganda
        // (form-submit-general.js global juga mengikat kelas yang sama).
        $(".ts_remove_row").off('click');
        $(document).off('click.penggunaDelete', '.ts_remove_row').on('click.penggunaDelete', '.ts_remove_row', function(e) {
            e.preventDefault();
            var href = $(this).attr('href');

            swal.fire({
                title: "Apakah Anda Yakin Akan Hapus Data?",
                text: "Data Tidak Dapat Dikembalikan!!",
                type: "warning",
                showCancelButton: !0,
                confirmButtonText: "Yes, Hapus!"
            }).then(function(e) {
                if (!e.value) {
                    return;
                }
                $.ajax(
                {
                    type: 'POST',
                    url: href,
                    success:function(data)
                    {
                        var res = bacaRespons(data);
                        if (!res) {
                            swal.fire({title: "Sesi Berakhir", text: "Sesi Anda telah berakhir. Silakan muat ulang halaman dan masuk kembali.", type: "warning"});
                            return;
                        }
                        $('#response').fadeIn('slow').html(res.response);
                        if (res.status === 'success') {
                            swal.fire({title: "Deleted!", text: res.message, type: res.status}).then(
                                function(){
                                    location.reload();
                                }
                            ) ;
                            return;
                        }
                        swal.fire({title: "Gagal!", text: res.message, type: "error"});
                    },
                    error:function(xhr)
                    {
                        swal.fire({title: "Gagal!", text: pesanGalat(xhr), type: "error"});
                    }
                });
            })
        });
    }

    var handleSubmit = function(form) {
        $('#response').html('');
        var button = $('#btn_save');
        var button_text = button.text();
        var pulihkanTombol = function() {
            button.prop("disabled", false);
            button.removeClass('disabled');
            button.text(button_text);
        };
        button.prop( "disabled", true );
        button.addClass('disabled');
        button.text('Sedang Memproses...');
        $.ajax({
            type: $(form).attr('method'),
            url: $(form).attr('action'),
            data: $(form).serialize(),
            success: function(data) {
                // Respons non-JSON (mis. halaman login 302/HTML) tidak boleh
                // meledakkan JSON.parse — tangani sebagai sesi berakhir.
                var res = bacaRespons(data);
                pulihkanTombol();
                if (!res) {
                    swal.fire({title: "Sesi Berakhir", text: "Sesi Anda telah berakhir. Silakan muat ulang halaman dan masuk kembali.", type: "warning"});
                    return;
                }
                $('#response').fadeIn('slow').html(res.response);
                swal.fire({
                    position: "top-right",
                    type: res.status,
                    title: res.message,
                    showConfirmButton: !1,
                    timer: 1500
                })
            },
            error: function(xhr) {
                pulihkanTombol();
                swal.fire({title: "Gagal!", text: pesanGalat(xhr), type: "error"});
            }
        })
    }

    var handleSubmitForm = function() {
        $("#form_custom").validate({
            rules: {
                susrNama: {
                    required: true,
                    minlength: 5 
                },
                susrSgroupNama: {
                    required: true,
                },
                susrProfil: {
                    required: true,
                },
            },
            submitHandler: function(e) {
                handleSubmit(e);
                return false
            }
        });
    }

    var handleResetPassword = function() {
        // Delegasi ke document (alasan sama dengan hapus di atas).
        $(".ts_reset_row").off('click');
        $(document).off('click.penggunaReset', '.ts_reset_row').on('click.penggunaReset', '.ts_reset_row', function(e) {
            e.preventDefault();
            var href = $(this).attr('href');
            swal.fire({
                title: "Apakah Anda Yakin Akan Reset Password?",
                text: "Data Tidak Dapat Dikembalikan!!",
                type: "warning",
                showCancelButton: !0,
                confirmButtonText: "Yes, Reset!"
            }).then(function(e) {
                if (!e.value) {
                    return;
                }
                $.ajax(
                {
                    type: 'POST',
                    url: href,
                    success:function(data)
                    {
                        var res = bacaRespons(data);
                        if (!res) {
                            swal.fire({title: "Sesi Berakhir", text: "Sesi Anda telah berakhir. Silakan muat ulang halaman dan masuk kembali.", type: "warning"});
                            return;
                        }
                        $('#response').fadeIn('slow').html(res.response);
                        swal.fire("Reset!", String(res.message).replace(/<[^>]*>/g, ''), res.status) ;
                    },
                    error:function(xhr)
                    {
                        swal.fire({title: "Gagal!", text: pesanGalat(xhr), type: "error"});
                    }
                });
            })
        })
    }

    return {
        // public functions
        init: function() {
            handleSubmitForm();
            handleClickDelete();
            handleResetPassword();
        }
    };
}();

jQuery(document).ready(function() {
    FormCustom.init()
});