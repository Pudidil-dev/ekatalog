<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pesan validasi bahasa Indonesia
    |--------------------------------------------------------------------------
    |
    | Aplikasi memakai locale "id". Tanpa berkas ini Laravel menampilkan kunci
    | mentah seperti "validation.required" kepada admin.
    |
    */

    'accepted' => ':Attribute harus disetujui.',
    'accepted_if' => ':Attribute harus disetujui jika :other bernilai :value.',
    'active_url' => ':Attribute harus berupa URL yang valid.',
    'after' => ':Attribute harus berupa tanggal setelah :date.',
    'after_or_equal' => ':Attribute harus berupa tanggal setelah atau sama dengan :date.',
    'alpha' => ':Attribute hanya boleh berisi huruf.',
    'alpha_dash' => ':Attribute hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
    'alpha_num' => ':Attribute hanya boleh berisi huruf dan angka.',
    'any_of' => ':Attribute tidak valid.',
    'array' => ':Attribute harus berupa daftar.',
    'ascii' => ':Attribute hanya boleh berisi karakter alfanumerik dan simbol satu bita.',
    'before' => ':Attribute harus berupa tanggal sebelum :date.',
    'before_or_equal' => ':Attribute harus berupa tanggal sebelum atau sama dengan :date.',
    'between' => [
        'array' => ':Attribute harus berisi :min sampai :max item.',
        'file' => 'Ukuran :attribute harus :min sampai :max KB.',
        'numeric' => ':Attribute harus bernilai :min sampai :max.',
        'string' => ':Attribute harus berisi :min sampai :max karakter.',
    ],
    'boolean' => ':Attribute harus bernilai benar atau salah.',
    'can' => ':Attribute berisi nilai yang tidak diizinkan.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'contains' => ':Attribute belum memuat nilai yang diwajibkan.',
    'current_password' => 'Password saat ini tidak sesuai.',
    'date' => ':Attribute harus berupa tanggal yang valid.',
    'date_equals' => ':Attribute harus berupa tanggal yang sama dengan :date.',
    'date_format' => ':Attribute harus sesuai format :format.',
    'decimal' => ':Attribute harus memiliki :decimal angka desimal.',
    'declined' => ':Attribute harus ditolak.',
    'declined_if' => ':Attribute harus ditolak jika :other bernilai :value.',
    'different' => ':Attribute dan :other harus berbeda.',
    'digits' => ':Attribute harus terdiri atas :digits digit.',
    'digits_between' => ':Attribute harus terdiri atas :min sampai :max digit.',
    'dimensions' => 'Dimensi :attribute tidak valid.',
    'distinct' => ':Attribute memiliki nilai yang duplikat.',
    'doesnt_contain' => ':Attribute tidak boleh memuat salah satu nilai berikut: :values.',
    'doesnt_end_with' => ':Attribute tidak boleh diakhiri salah satu nilai berikut: :values.',
    'doesnt_start_with' => ':Attribute tidak boleh diawali salah satu nilai berikut: :values.',
    'email' => ':Attribute harus berupa alamat email yang valid.',
    'encoding' => ':Attribute harus memakai pengodean :encoding.',
    'ends_with' => ':Attribute harus diakhiri salah satu nilai berikut: :values.',
    'enum' => ':Attribute yang dipilih tidak valid.',
    'exists' => ':Attribute yang dipilih tidak valid.',
    'extensions' => ':Attribute harus berekstensi salah satu dari: :values.',
    'file' => ':Attribute harus berupa berkas.',
    'filled' => ':Attribute wajib diisi.',
    'gt' => [
        'array' => ':Attribute harus berisi lebih dari :value item.',
        'file' => 'Ukuran :attribute harus lebih dari :value KB.',
        'numeric' => ':Attribute harus lebih dari :value.',
        'string' => ':Attribute harus lebih dari :value karakter.',
    ],
    'gte' => [
        'array' => ':Attribute harus berisi :value item atau lebih.',
        'file' => 'Ukuran :attribute minimal :value KB.',
        'numeric' => ':Attribute minimal :value.',
        'string' => ':Attribute minimal :value karakter.',
    ],
    'hex_color' => ':Attribute harus berupa warna heksadesimal yang valid.',
    'image' => ':Attribute harus berupa gambar.',
    'in' => ':Attribute yang dipilih tidak valid.',
    'in_array' => ':Attribute harus terdapat di :other.',
    'in_array_keys' => ':Attribute harus memuat setidaknya salah satu kunci berikut: :values.',
    'integer' => ':Attribute harus berupa bilangan bulat.',
    'ip' => ':Attribute harus berupa alamat IP yang valid.',
    'ipv4' => ':Attribute harus berupa alamat IPv4 yang valid.',
    'ipv6' => ':Attribute harus berupa alamat IPv6 yang valid.',
    'json' => ':Attribute harus berupa teks JSON yang valid.',
    'list' => ':Attribute harus berupa daftar.',
    'lowercase' => ':Attribute harus berupa huruf kecil.',
    'lt' => [
        'array' => ':Attribute harus berisi kurang dari :value item.',
        'file' => 'Ukuran :attribute harus kurang dari :value KB.',
        'numeric' => ':Attribute harus kurang dari :value.',
        'string' => ':Attribute harus kurang dari :value karakter.',
    ],
    'lte' => [
        'array' => ':Attribute tidak boleh berisi lebih dari :value item.',
        'file' => 'Ukuran :attribute maksimal :value KB.',
        'numeric' => ':Attribute maksimal :value.',
        'string' => ':Attribute maksimal :value karakter.',
    ],
    'mac_address' => ':Attribute harus berupa alamat MAC yang valid.',
    'max' => [
        'array' => ':Attribute tidak boleh berisi lebih dari :max item.',
        'file' => 'Ukuran :attribute maksimal :max KB.',
        'numeric' => ':Attribute maksimal :max.',
        'string' => ':Attribute maksimal :max karakter.',
    ],
    'max_digits' => ':Attribute tidak boleh lebih dari :max digit.',
    'mimes' => ':Attribute harus berformat :values.',
    'mimetypes' => ':Attribute harus berformat :values.',
    'min' => [
        'array' => ':Attribute harus berisi minimal :min item.',
        'file' => 'Ukuran :attribute minimal :min KB.',
        'numeric' => ':Attribute minimal :min.',
        'string' => ':Attribute minimal :min karakter.',
    ],
    'min_digits' => ':Attribute minimal :min digit.',
    'missing' => ':Attribute tidak boleh dikirim.',
    'missing_if' => ':Attribute tidak boleh dikirim jika :other bernilai :value.',
    'missing_unless' => ':Attribute tidak boleh dikirim kecuali :other bernilai :value.',
    'missing_with' => ':Attribute tidak boleh dikirim jika :values ada.',
    'missing_with_all' => ':Attribute tidak boleh dikirim jika :values ada.',
    'multiple_of' => ':Attribute harus kelipatan :value.',
    'not_in' => ':Attribute yang dipilih tidak valid.',
    'not_regex' => 'Format :attribute tidak valid.',
    'numeric' => ':Attribute harus berupa angka.',
    'password' => [
        'letters' => ':Attribute harus memuat setidaknya satu huruf.',
        'mixed' => ':Attribute harus memuat setidaknya satu huruf kapital dan satu huruf kecil.',
        'numbers' => ':Attribute harus memuat setidaknya satu angka.',
        'symbols' => ':Attribute harus memuat setidaknya satu simbol.',
        'uncompromised' => ':Attribute ini pernah muncul dalam kebocoran data. Gunakan :attribute lain.',
    ],
    'present' => ':Attribute harus ada.',
    'present_if' => ':Attribute harus ada jika :other bernilai :value.',
    'present_unless' => ':Attribute harus ada kecuali :other bernilai :value.',
    'present_with' => ':Attribute harus ada jika :values ada.',
    'present_with_all' => ':Attribute harus ada jika :values ada.',
    'prohibited' => ':Attribute tidak boleh diisi.',
    'prohibited_if' => ':Attribute tidak boleh diisi jika :other bernilai :value.',
    'prohibited_if_accepted' => ':Attribute tidak boleh diisi jika :other disetujui.',
    'prohibited_if_declined' => ':Attribute tidak boleh diisi jika :other ditolak.',
    'prohibited_unless' => ':Attribute tidak boleh diisi kecuali :other bernilai :values.',
    'prohibits' => ':Attribute membuat :other tidak boleh diisi.',
    'regex' => 'Format :attribute tidak valid.',
    'required' => ':Attribute wajib diisi.',
    'required_array_keys' => ':Attribute harus memuat isian untuk: :values.',
    'required_if' => ':Attribute wajib diisi jika :other bernilai :value.',
    'required_if_accepted' => ':Attribute wajib diisi jika :other disetujui.',
    'required_if_declined' => ':Attribute wajib diisi jika :other ditolak.',
    'required_unless' => ':Attribute wajib diisi kecuali :other bernilai :values.',
    'required_with' => ':Attribute wajib diisi jika :values ada.',
    'required_with_all' => ':Attribute wajib diisi jika :values ada.',
    'required_without' => ':Attribute wajib diisi jika :values tidak ada.',
    'required_without_all' => ':Attribute wajib diisi jika :values tidak ada.',
    'same' => ':Attribute harus sama dengan :other.',
    'size' => [
        'array' => ':Attribute harus berisi :size item.',
        'file' => 'Ukuran :attribute harus :size KB.',
        'numeric' => ':Attribute harus bernilai :size.',
        'string' => ':Attribute harus berisi :size karakter.',
    ],
    'starts_with' => ':Attribute harus diawali salah satu nilai berikut: :values.',
    'string' => ':Attribute harus berupa teks.',
    'timezone' => ':Attribute harus berupa zona waktu yang valid.',
    'unique' => ':Attribute sudah digunakan.',
    'uploaded' => ':Attribute gagal diunggah.',
    'uppercase' => ':Attribute harus berupa huruf kapital.',
    'url' => ':Attribute harus berupa URL yang valid.',
    'ulid' => ':Attribute harus berupa ULID yang valid.',
    'uuid' => ':Attribute harus berupa UUID yang valid.',

    /*
    |--------------------------------------------------------------------------
    | Pesan khusus per isian
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'category_id' => [
            'integer' => 'Kategori yang dipilih tidak valid.',
        ],
        'sku' => [
            'regex' => 'SKU hanya boleh berisi huruf, angka, dan tanda hubung, diawali huruf atau angka, dengan panjang 2–80 karakter.',
        ],
        'whatsapp_number' => [
            'regex' => 'Nomor WhatsApp harus 8–15 digit, diawali kode negara tanpa tanda + atau angka 0, misalnya 6281234567890.',
        ],
        'instagram_url' => [
            'url' => 'URL Instagram harus berupa alamat https:// yang valid.',
        ],
        'facebook_url' => [
            'url' => 'URL Facebook harus berupa alamat https:// yang valid.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Nama isian yang ditampilkan kepada admin
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'email' => 'email',
        'password' => 'password',
        'current_password' => 'password saat ini',
        'sku' => 'SKU',
        'category_id' => 'kategori',
        'description' => 'deskripsi',
        'price' => 'harga',
        'specifications' => 'spesifikasi',
        'version' => 'versi data',
        'initial_stock' => 'stok awal',
        'initial_reason' => 'alasan stok awal',
        'image' => 'gambar',
        'quantity' => 'jumlah stok',
        'reason' => 'alasan perubahan',
        'request_key' => 'kunci permintaan',
        'store_name' => 'nama toko',
        'short_description' => 'deskripsi singkat',
        'whatsapp_number' => 'nomor WhatsApp',
        'address' => 'alamat',
        'instagram_url' => 'URL Instagram',
        'facebook_url' => 'URL Facebook',
        'q' => 'kata kunci',
        'category' => 'kategori',
    ],

];
