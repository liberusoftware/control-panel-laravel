<?php
declare(strict_types=1);
namespace Liberu\ControlPanel\ControlCore\Actions;
use Liberu\ControlPanel\ControlCore\Enums\CredentialStatus; use Liberu\ControlPanel\ControlCore\Models\NodeCredential;
final class RevokeNodeCredential { public function execute(NodeCredential $credential):NodeCredential { $credential->update(['status'=>CredentialStatus::Revoked]); return $credential->refresh(); } }
