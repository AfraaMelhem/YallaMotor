<?php

namespace App\Http\Controllers;

use App\Traits\BaseResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use BaseResponse;

    protected function prepareListData(Request $request): array
    {
        return [
            'filters' => $request->input('filters', []),
            'search' => $request->input('search'),
            'sort' => $request->input('sort', []),
        ];
    }

}
