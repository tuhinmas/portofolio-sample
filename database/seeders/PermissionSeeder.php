<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Ramsey\Uuid\Uuid;
use DateTimeInterface;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
       $arrayOfPermissionNames=[ 
                        '(M) Direct Sales',
                        '(S) Konfirmasi Order',
                        '(B) Tambah Order',
                        '(B) Konfirmasi Order',
                        '(P) Konfirmasi Order Detail',
                        '(S) Riwayat Direct Sales',
                        '(F) Pembayaran Direct Sales',
                        '(S) Lap. Penitipan Pembayaran',
                        '(M) Indirect Sales',
                        '(S) Proofing Indirect Sales',
                        '(B) Proofing Indirect Sales',
                        '(S) Konfirmasi Indirect Sales',
                        '(B) Konfirmasi Indirect Sales',
                        '(S) Penyesuaian Indirect Sales',
                        '(S) Riwayat Indirect Sales',
                        '(M) Dealer',
                        '(S) Konfirmasi Dealer',
                        '(S) Persetujuan Dealer',
                        '(S) Daftar Dealer',
                        '(B) Custom Kredit Limit',
                        '(C) Custom Term Pembayaran',
                        '(D) Ubah Grade',
                        '(B) Ubah Dealer',
                        '(B) Blokir / Buka Blokir',
                        '(D) Ubah Tingkat Keagenan',
                        '(B) Kontrak Distributor',
                        '(T) Risalah Telepon',
                        '(F) Input Risalah Telepon',
                        '(T) Catatan Dealer',
                        '(F) Input Catatan Dealer',
                        '(S) Dealer Tidak Aktif',
                        '(B) Follow Up',
                        '(S) Daftar Distributor',
                        '(B) Lihat Kontrak Distributor',
                        '(B) Catat Kontrak Distributor',
                        '(M) Sub Dealer',
                        '(S) Konfirmasi Sub-Dealer',
                        '(S) Daftar Sub-Dealer',
                        '(B) Ubah Sub-Dealer',
                        '(B) Jadikan Dealer',
                        '(B) Tutup Sub-Dealer',
                        '(T) Catatan Sub-Dealer',
                        '(F) Input Catatan Sub-Dealer',
                        '(M) Kios',
                        '(S) Konfirmasi Kios',
                        '(S) Daftar Kios',
                        '(B) Ubah Kios',
                        '(B) Hapus Kios',
                        '(M) Forecast & Pencapaian',
                        '(S) Forecast',
                        '(B) Kunci Target',
                        '(S) Pencapaian',
                        '(M) Area Marketing',
                        '(S) Daftar Area',
                        '(B) Tambah Region',
                        '(B) Tambah Sub Region',
                        '(B) Atur Area',
                        '(B) Atur Wilayah',
                        '(B) Atur Marketing',
                        '(S) Target Marketing',
                        '(B) Ubah Target Marketing',
                        '(M) Data Marketing',
                        '(S) List Marketing',
                        '(S) Status Marketing',
                        '(B) Ubah Jabatan',
                        '(B) Serah terima',
                        '(T) Catatan Marketing',
                        '(F) Input Catatan Marketing',
                        '(S) Perolehan Fee & Poin',
                        '(S) Daftar Agenda Marketing ',
                        '(M) Kalender Tanaman',
                        '(M) Data Acuan',
                        '(S) Harga Produk',
                        '(B) Manage Pricelist',
                        '(S) Produk',
                        '(B) Tambah Produk',
                        '(B) Ubah Produk',
                        '(B) Hapus Produk',
                        '(B) Tambah Packaging',
                        '(B) Ubah Packaging',
                        '(B) Hapus Packaging',
                        '(S) Tanaman',
                        '(F) Tambah Tanaman',
                        '(B) Ubah Tanaman',
                        '(B) Hapus Tanaman',
                        '(B) Tambah Kategori Tanaman',
                        '(B) Ubah Kategori Tanaman',
                        '(B) Hapus Kategori Tanaman',
                        '(S) Acuan Poin',
                        '(B) Tambah Acuan Poin',
                        '(B) Ubah Acuan Poin',
                        '(B) Hapus Acuan Poin',
                        '(S) Fee Produk',
                        '(B) Tambah Acuan Fee',
                        '(B) Ubah Acuan Fee',
                        '(B) Hapus Acuan Fee',
                        '(S) Acuan Fee Marketing',
                        '(B) Atur Fee Cash dan Jabatan',
                        '(B) Atur Status Limpahan',
                        '(B) Tambah Aturan Fee Follow Up',
                        '(B) Atur Fee Follow Up',
                        '(S) Metode Pembayaran',
                        '(F) Input Metode Pembayaran',
                        '(B) Hapus Metode Pembayaran',
                        '(B) Seeder Kios'
                    ];

        $permissions = collect($arrayOfPermissionNames)->map(function ($permission) {
                return ['id'=>Uuid::uuid4()->toString(),'name' => $permission, 'guard_name' => 'api','created_at'=>now()->format('Y-m-d H:i:s'),'updated_at'=>now()->format('Y-m-d H:i:s')];
            })->toArray();
        Permission::insert($permissions);
    }
}
