<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHostingApi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Liberu\ControlPanel\WebHosting\Actions\AddDirectoryProtectionUser;
use Liberu\ControlPanel\WebHosting\Actions\ConfigureHotlinkProtection;
use Liberu\ControlPanel\WebHosting\Actions\CreateDirectoryProtection;
use Liberu\ControlPanel\WebHosting\Actions\DeleteCustomErrorPage;
use Liberu\ControlPanel\WebHosting\Actions\DeleteDirectoryProtection;
use Liberu\ControlPanel\WebHosting\Actions\RemoveDirectoryProtectionUser;
use Liberu\ControlPanel\WebHosting\Actions\SaveCustomErrorPage;
use Liberu\ControlPanel\WebHosting\Models\CustomErrorPage;
use Liberu\ControlPanel\WebHosting\Models\DirectoryProtection;
use Liberu\ControlPanel\WebHosting\Models\DirectoryProtectionUser;
use Liberu\ControlPanel\WebHosting\Models\Domain;
use Liberu\ControlPanel\WebHosting\Models\HotlinkProtection;

final class WebProtectionController
{
    public function hotlink(Request $request, string $domain, ConfigureHotlinkProtection $configure): JsonResponse
    {
        $item = $this->domain($request, $domain);
        $data = $request->validate(['enabled' => ['sometimes', 'boolean'], 'allowed_domains' => ['nullable', 'array'], 'allowed_domains.*' => ['string', 'max:255'], 'protected_extensions' => ['nullable', 'array'], 'protected_extensions.*' => ['string', 'max:32'], 'redirect_url' => ['nullable', 'url', 'max:255'], 'allow_blank_referrer' => ['sometimes', 'boolean']]);

        return response()->json(['data' => self::hotlinkResource($configure->execute($item, $data))]);
    }

    public function directory(Request $request, string $domain, CreateDirectoryProtection $create): JsonResponse
    {
        $item = $this->domain($request, $domain);
        $data = $request->validate(['directory_path' => ['required', 'string', 'starts_with:/', 'max:2048'], 'auth_name' => ['nullable', 'string', 'max:255'], 'htpasswd_file_path' => ['nullable', 'string', 'starts_with:/', 'max:2048'], 'active' => ['sometimes', 'boolean']]);

        return response()->json(['data' => self::directoryResource($create->execute($item, $data))], 201);
    }

    public function deleteDirectory(Request $request, string $protection, DeleteDirectoryProtection $delete): JsonResponse
    {
        $item = $this->directoryProtection($request, $protection);
        $delete->execute($item);

        return response()->json(status: 204);
    }

    public function directoryUser(Request $request, string $protection, AddDirectoryProtectionUser $add): JsonResponse
    {
        $item = $this->directoryProtection($request, $protection);
        $data = $request->validate(['username' => ['required', 'string', 'regex:/^[A-Za-z0-9._-]{1,120}$/'], 'password' => ['required', 'string', 'min:8']]);

        return response()->json(['data' => self::userResource($add->execute($item, $data['username'], $data['password']))], 201);
    }

    public function deleteDirectoryUser(Request $request, string $user, RemoveDirectoryProtectionUser $remove): JsonResponse
    {
        $item = DirectoryProtectionUser::query()->whereKey($user)->where('team_id', $this->teamId($request))->firstOrFail();
        $remove->execute($item);

        return response()->json(status: 204);
    }

    public function errorPage(Request $request, string $domain, SaveCustomErrorPage $save): JsonResponse
    {
        $item = $this->domain($request, $domain);
        $data = $request->validate(['error_code' => ['required', 'integer', 'between:100,599'], 'custom_content' => ['nullable', 'string'], 'custom_file_path' => ['nullable', 'string', 'starts_with:/', 'max:2048'], 'active' => ['sometimes', 'boolean']]);

        return response()->json(['data' => self::errorResource($save->execute($item, $data))], 201);
    }

    public function deleteErrorPage(Request $request, string $page, DeleteCustomErrorPage $delete): JsonResponse
    {
        $item = CustomErrorPage::query()->whereKey($page)->where('team_id', $this->teamId($request))->firstOrFail();
        $delete->execute($item);

        return response()->json(status: 204);
    }

    private function domain(Request $request, string $id): Domain
    {
        return Domain::query()->whereKey($id)->where('team_id', $this->teamId($request))->firstOrFail();
    }

    private function directoryProtection(Request $request, string $id): DirectoryProtection
    {
        return DirectoryProtection::query()->whereKey($id)->where('team_id', $this->teamId($request))->firstOrFail();
    }

    private function teamId(Request $request): string
    {
        $teamId = $request->user()?->current_team_id;
        abort_if($teamId === null, 403, 'A current team is required.');

        return (string) $teamId;
    }

    /** @return array<string, mixed> */
    private static function hotlinkResource(HotlinkProtection $item): array
    {
        return ['id' => $item->getKey(), 'type' => 'control-panel-hotlink-protection', 'attributes' => $item->only(['domain_id', 'enabled', 'allowed_domains', 'protected_extensions', 'redirect_url', 'allow_blank_referrer'])];
    }

    /** @return array<string, mixed> */
    private static function directoryResource(DirectoryProtection $item): array
    {
        return ['id' => $item->getKey(), 'type' => 'control-panel-directory-protection', 'attributes' => $item->only(['domain_id', 'directory_path', 'auth_name', 'htpasswd_file_path', 'active'])];
    }

    /** @return array<string, mixed> */
    private static function userResource(DirectoryProtectionUser $item): array
    {
        return ['id' => $item->getKey(), 'type' => 'control-panel-directory-protection-user', 'attributes' => $item->only(['directory_protection_id', 'username'])];
    }

    /** @return array<string, mixed> */
    private static function errorResource(CustomErrorPage $item): array
    {
        return ['id' => $item->getKey(), 'type' => 'control-panel-custom-error-page', 'attributes' => $item->only(['domain_id', 'error_code', 'custom_content', 'custom_file_path', 'active'])];
    }
}
