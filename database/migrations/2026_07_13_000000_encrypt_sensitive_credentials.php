<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Encrypt credentials that older releases stored as plaintext.
     */
    public function up(): void
    {
        Schema::table('wordpress_applications', function (Blueprint $table) {
            $table->text('admin_password')->nullable()->change();
        });

        Schema::table('connected_accounts', function (Blueprint $table) {
            $table->text('token')->change();
            $table->text('secret')->nullable()->change();
            $table->text('refresh_token')->nullable()->change();
        });

        Schema::table('git_deployments', function (Blueprint $table) {
            $table->text('webhook_secret')->nullable()->change();
        });

        $this->encryptColumns('wordpress_applications', ['admin_password']);
        $this->encryptColumns('connected_accounts', ['token', 'secret', 'refresh_token']);
        $this->encryptColumns('git_deployments', ['deploy_key', 'webhook_secret']);
        $this->encryptColumns('domains', ['sftp_password', 'ssh_password']);
        $this->encryptColumns('databases', ['ssl_key']);
    }

    /**
     * Credential encryption is intentionally not reversed on rollback.
     */
    public function down(): void
    {
        // Keeping ciphertext prevents a rollback from silently restoring plaintext secrets.
    }

    private function encryptColumns(string $table, array $columns): void
    {
        DB::table($table)
            ->select(array_merge(['id'], $columns))
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($table, $columns): void {
                foreach ($rows as $row) {
                    $updates = [];

                    foreach ($columns as $column) {
                        $value = $row->{$column};
                        if ($value === null || $value === '') {
                            continue;
                        }

                        try {
                            Crypt::decryptString($value);
                        } catch (DecryptException) {
                            $updates[$column] = Crypt::encryptString($value);
                        }
                    }

                    if ($updates !== []) {
                        DB::table($table)->where('id', $row->id)->update($updates);
                    }
                }
            });
    }
};
