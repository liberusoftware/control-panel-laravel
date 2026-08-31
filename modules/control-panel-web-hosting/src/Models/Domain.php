<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Liberu\ControlPanel\WebHosting\Enums\DomainStatus;

final class Domain extends Model
{
    use HasUuids;

    protected $table = 'control_panel_domains';

    protected $fillable = ['team_id', 'account_id', 'hostname', 'status', 'metadata'];

    protected function casts(): array
    {
        return ['status' => DomainStatus::class, 'metadata' => 'array'];
    }

    public function virtualHosts(): HasMany
    {
        return $this->hasMany(VirtualHost::class);
    }

    public function gitDeployments(): HasMany
    {
        return $this->hasMany(GitDeployment::class);
    }

    public function phpConfiguration(): HasOne
    {
        return $this->hasOne(PhpConfiguration::class);
    }

    public function resourceUsage(): HasMany
    {
        return $this->hasMany(ResourceUsage::class);
    }

    public function hotlinkProtection(): HasOne
    {
        return $this->hasOne(HotlinkProtection::class);
    }

    public function directoryProtections(): HasMany
    {
        return $this->hasMany(DirectoryProtection::class);
    }

    public function customErrorPages(): HasMany
    {
        return $this->hasMany(CustomErrorPage::class);
    }

    public function mimeTypes(): HasMany
    {
        return $this->hasMany(MimeType::class);
    }

    public function cronJobs(): HasMany
    {
        return $this->hasMany(CronJob::class);
    }

    public function subdomains(): HasMany
    {
        return $this->hasMany(Subdomain::class);
    }
}
