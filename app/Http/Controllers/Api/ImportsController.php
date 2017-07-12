<?php

namespace App\Http\Controllers\Api;

use Response;
use App\Http\Controllers\Controller;

class ImportsController extends Controller
{
    public function import()
    {
        // Collect request params
        //$assetId  = request()->get('asset');
        $platform = request()->get('platform');
        //$keyword = request()->get('assetkeyword');
        $file     = request()->file('file');
	$action = request()->get('action');

        // Run an import on the given asset/file
	$ret = null;

	if($action == 'validate'){
	        $ret = app('App\Contracts\ImportRepositoryInterface')->validate_file($file);
	}
	else if($action == 'import'){
	        app('App\Contracts\ImportRepositoryInterface')->import($platform, /*$assetId, $keyword,*/ $file);
	}
	else{
		$ret = "Invalid action";
	}

        // Return success response
        return Response::make($ret, 201);
    }
}
