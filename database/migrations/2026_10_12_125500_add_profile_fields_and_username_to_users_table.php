<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->unique()->after('id');
            $table->string('nama_lengkap')->nullable()->after('name');
            $table->string('nip', 50)->nullable()->after('nama_lengkap');
            $table->string('email')->nullable()->change();
        });

        // Seed / Populate username & profile awal untuk akun yang sudah ada
        $defaults = [
            'admin@mutu-rsys.id' => [
                'username'     => 'admin',
                'nama_lengkap' => 'Administrator Sistem',
                'nip'          => 'RSYS-001',
            ],
            'komite.mutu@mutu-rsys.id' => [
                'username'     => 'komite_mutu',
                'nama_lengkap' => 'dr. Hendra Setiawan, Sp.PK',
                'nip'          => '198205142008011002',
            ],
            'direktur@mutu-rsys.id' => [
                'username'     => 'direktur',
                'nama_lengkap' => 'dr. H. Rahmat Hidayat, MARS',
                'nip'          => '197508202002121001',
            ],
            'karu.igd@mutu-rsys.id' => [
                'username'     => 'karu_igd',
                'nama_lengkap' => 'Ns. Dewi Lestari, S.Kep',
                'nip'          => '198811052012012004',
            ],
            'pic.igd@mutu-rsys.id' => [
                'username'     => 'pic_igd',
                'nama_lengkap' => 'Amd. Kep. Farhan Maulana',
                'nip'          => '199203182015031003',
            ],
            'pic.ri.interna@mutu-rsys.id' => [
                'username'     => 'pic_interna',
                'nama_lengkap' => 'Ns. Budi Prakoso, S.Kep',
                'nip'          => '199009122014021002',
            ],
            'pic.irj@mutu-rsys.id' => [
                'username'     => 'pic_irj',
                'nama_lengkap' => 'Ns. Siti Nurhaliza, S.Kep',
                'nip'          => '199107252014032001',
            ],
        ];

        foreach ($defaults as $email => $data) {
            DB::table('users')->where('email', $email)->update([
                'username'     => $data['username'],
                'nama_lengkap' => $data['nama_lengkap'],
                'nip'          => $data['nip'],
            ]);
        }

        // Fallback untuk user lain jika ada yang belum memiliki username
        $otherUsers = DB::table('users')->whereNull('username')->get();
        foreach ($otherUsers as $u) {
            $baseUsername = strtolower(explode('@', $u->email)[0] ?? 'user' . $u->id);
            $cleanUsername = preg_replace('/[^a-z0-9_]/', '_', $baseUsername);
            DB::table('users')->where('id', $u->id)->update([
                'username' => $cleanUsername,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'nama_lengkap', 'nip']);
        });
    }
};
