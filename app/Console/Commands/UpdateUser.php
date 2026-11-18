<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-user
                            {email : Email user yang akan diupdate}
                            {field : Nama|Email|Password|Role|Grup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = \App\Models\User::where('email', $this->argument('email'))->first();
        if (!$user) {
            $this->error('User dengan email ' . $this->argument('email') . ' tidak ditemukan!');
            return 1;
        }

        $field = strtolower($this->argument('field'));
        if ($field == 'role') {
            $new_value = $this->choice('Pilih Role baru: ', [1 => 'admin', 2 => 'user'], 2);
            $user->role = $new_value;
        } elseif ($field == 'grup') {
            $group_list = \App\Models\Group::pluck('nama', 'id')->toArray();
            $new_value = $this->choice('Pilih Grup baru (pisahkan dengan koma (,) jika pilihan lebih dari 1): ', $group_list, 1, multiple: true);
            $tmp = [];
            foreach ($new_value as $val) {
                $tmp[] = array_search($val, $group_list);
            }
            $user->managed_groups = $tmp;
        } else {
            $new_value = $this->ask('Masukkan nilai baru untuk ' . ucfirst($field) . ': ');
            switch ($field) {
                case 'nama':
                    $user->name = $new_value;
                    break;
                case 'email':
                    $user->email = $new_value;
                    break;
                case 'password':
                    $user->password = bcrypt($new_value);
                    break;
                default:
                    $this->error('Field ' . $field . ' tidak dikenali!');
                    return 1;
            }
        }
        if ($user->save()) {
            $this->info('User [' . $user->email . '] berhasil diupdate!');
        } else {
            $this->error('Gagal mengupdate user!');
        }
    }
}
