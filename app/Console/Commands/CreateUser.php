<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use PHPUnit\Metadata\Api\Groups;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-user';

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
        $group_list = \App\Models\Group::pluck('nama', 'id')->toArray();

        $nama = $this->ask('Nama: ', 'user' . rand(1000, 9999));
        $email = $this->ask('Email: ', 'noreply' . rand(1000, 9999) . '@local.net');
        if (\App\Models\User::where('email', $email)->exists()) {
            $this->error('User dengan email ' . $email . ' sudah ada!');
            return 1;
        }
        $password = $this->ask('Password: ', 'password' . rand(1000, 9999));
        $role = $this->choice('Role: ', [1 => 'admin', 2 => 'user'], 2);
        $groups = $this->choice('Grup (pisahkan dengan koma (,) jika pilihan lebih dari 1): ', $group_list, 1, multiple: true);
        $tmp = [];
        foreach ($groups as $val) {
            $tmp[] = array_search($val, $group_list);
        }

        if (User::create([
            'name' => $nama,
            'email' => $email,
            'email_verified_at' => date('Y-m-d H:i:s'),
            'password' => bcrypt($password),
            'role' => $role,
            'managed_groups' => $tmp,
        ])) {
            $this->info('User [' . $email . '] berhasil dibuat dengan Password [' . $password . ']!');
        } else {
            $this->error('Gagal membuat user!');
        }
    }
}
