<?php

namespace Kubectyl\Http\Requests\Api\Client\Servers\Network;

use Kubectyl\Models\Permission;
use Kubectyl\Http\Requests\Api\Client\ClientApiRequest;

class NewAllocationRequest extends ClientApiRequest
{
    public function permission(): string
    {
        return Permission::ACTION_ALLOCATION_CREATE;
    }
}
