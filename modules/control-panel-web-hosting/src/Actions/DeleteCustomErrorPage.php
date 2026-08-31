<?php

declare(strict_types=1);

namespace Liberu\ControlPanel\WebHosting\Actions;

use Liberu\ControlPanel\WebHosting\Models\CustomErrorPage;

final class DeleteCustomErrorPage
{
    public function execute(CustomErrorPage $page): void
    {
        $page->delete();
    }
}
