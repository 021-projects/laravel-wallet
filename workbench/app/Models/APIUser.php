<?php

namespace Workbench\App\Models;

class APIUser extends User
{
    public function toApi(): array
    {
        return [
            'gg' => true,
            'name' => $this->name,
        ];
    }
}
