<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\Certificates\Actions;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Liberu\ControlPanel\Certificates\Models\AcmeAccount;
final class RegisterAcmeAccount
{
    public function execute(array $attributes): AcmeAccount
    {
        $email = trim((string)($attributes['email'] ?? ''));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) throw ValidationException::withMessages(['email' => 'A valid ACME email is required.']);
        return AcmeAccount::query()->create(['id'=>(string)Str::uuid(),'team_id'=>$attributes['team_id']??null,'email'=>$email,'directory'=>$attributes['directory']??'https://acme-v02.api.letsencrypt.org/directory','credentials'=>$attributes['credentials']??[],'active'=>true]);
    }
}
