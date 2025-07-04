<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Jika pakai auth, sesuaikan
    }

    public function rules(): array
    {
        return [
            // Murid
            'no_induk' => 'required|string|unique:students,no_induk',
            'nisn' => 'nullable|regex:/^[0-9]{5,9}$/',
            'name' => 'required|string',
            'jenis_kelamin' => 'required|in:L,P',
            'tempat_lahir' => 'required|string',
            'ttl' => 'nullable|date',
            'usia' => 'nullable|string|max:100',
            'nik' => 'nullable|digits:16',
            'no_akte' => 'nullable|string',
            'no_kk' => 'nullable|digits:16',
            'alamat_kk' => 'nullable|string',
            'alamat_tinggal' => 'nullable|string',
            'pas_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'akte' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'kk' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'edu_class_id' => 'required|exists:edu_classes,id',
            'rfid_uid' => 'required|unique:students,rfid_uid',
            'total_biaya' => 'required|numeric',

            // Wali Murid (Ayah & Ibu)
            'wali_nama' => 'required|array|size:2',
            'wali_nama.*' => 'required|string',

            'wali_jenis_kelamin' => 'required|array|size:2',
            'wali_jenis_kelamin.*' => 'required|in:L,P',

            'wali_hubungan' => 'required|array|size:2',
            'wali_hubungan.*' => 'required|in:Ayah,Ibu',

            'wali_nik' => 'nullable|array',
            'wali_nik.*' => 'nullable|digits:16',

            'wali_no_hp' => 'required|array|size:2',
            'wali_no_hp.*' => 'required|string',

            'wali_email' => 'required|array|size:2',
            'wali_email.*' => 'required|email|distinct|unique:wali_murids,email',

            'wali_pendidikan_terakhir' => 'nullable|array',
            'wali_pekerjaan' => 'nullable|array',
            'wali_alamat' => 'nullable|array',

            'wali_foto_ktp' => 'nullable|array',
            'wali_foto_ktp.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',

            // Biaya
            'akun_keuangan_id' => 'required|array|min:1',
            'akun_keuangan_id.*' => 'required|distinct|exists:akun_keuangans,id',
            'jumlah' => 'required|array|min:1',
            'jumlah.*' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            // Contoh custom message
            'no_induk.required' => 'No. Induk wajib diisi.',
            'nisn.regex' => 'NISN harus berupa 5–9 digit angka.',
            'wali_email.*.unique' => 'Email wali sudah digunakan.',
            'wali_hubungan.*.in' => 'Hubungan wali hanya boleh Ayah atau Ibu.',
            'wali_nama.*.required' => 'Nama wali wajib diisi.',
            'wali_foto_ktp.*.image' => 'Foto KTP harus berupa gambar.',
        ];
    }
}

