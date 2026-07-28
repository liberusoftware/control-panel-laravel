<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->transformSecrets(function (string $value): string {
            try {
                Crypt::decryptString($value);

                return $value;
            } catch (Throwable) {
                return Crypt::encryptString($value);
            }
        });
    }

    public function down(): void
    {
        $this->transformSecrets(function (string $value): string {
            try {
                return Crypt::decryptString($value);
            } catch (Throwable) {
                return $value;
            }
        });
    }

    private function transformSecrets(callable $transform): void
    {
        DB::table('git_deployments')
            ->select(['id', 'deploy_key', 'webhook_secret'])
            ->orderBy('id')
            ->chunkById(100, function ($deployments) use ($transform): void {
                foreach ($deployments as $deployment) {
                    $updates = [];

                    foreach (['deploy_key', 'webhook_secret'] as $attribute) {
                        $value = $deployment->{$attribute};

                        if (is_string($value) && $value !== '') {
                            $updates[$attribute] = $transform($value);
                        }
                    }

                    if ($updates !== []) {
                        DB::table('git_deployments')
                            ->where('id', $deployment->id)
                            ->update($updates);
                    }
                }
            });
    }
};
