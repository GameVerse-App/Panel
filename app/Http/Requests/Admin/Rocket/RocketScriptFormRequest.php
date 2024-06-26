<?php

namespace Kubectyl\Http\Requests\Admin\Rocket;

use Kubectyl\Http\Requests\Admin\AdminFormRequest;

class RocketScriptFormRequest extends AdminFormRequest
{
    /**
     * Return the rules to be used when validating the data sent in the request.
     */
    public function rules(): array
    {
        return [
            'script_install' => 'sometimes|nullable|string',
            'script_is_privileged' => 'sometimes|required|boolean',
            'script_entry' => 'sometimes|required|string',
            'script_container' => 'sometimes|required|string',
            'copy_script_from' => 'sometimes|nullable|numeric',
        ];
    }
}
