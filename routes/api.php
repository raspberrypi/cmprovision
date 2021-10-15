<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Cm;
use App\Models\Project;
use App\Models\Firmware;
use App\Models\Image;
use App\Models\Script;
use App\Models\Label;
use App\Http\Controllers\AddImageController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/* Routes to list all information of a certain group */

Route::middleware('auth:sanctum')->get('/cms', function (Request $request) {
    return Cm::orderBy('id')->get();
});

Route::middleware('auth:sanctum')->get('/projects/{projectId}/cms', function (Request $request, $projectId) {
    return Cm::where('project_id', $projectId)->orderBy('id')->get()->toJson();
});

Route::middleware('auth:sanctum')->get('/projects', function (Request $request) {
    return Project::orderBy('name')->get();
});

Route::middleware('auth:sanctum')->get('/images', function (Request $request) {
    return Image::orderBy('filename')->orderBy('id')->get();
});

Route::middleware('auth:sanctum')->get('/firmware', function (Request $request) {
    return Firmware::all();
});

Route::middleware('auth:sanctum')->get('/scripts', function (Request $request) {
    return Script::orderBy('name')->get();
});

Route::middleware('auth:sanctum')->get('/labels', function (Request $request) {
    return Label::orderBy('name')->get();
});

/* Routes to add or update individual objects */

Route::middleware('auth:sanctum')->post('/images', function (Request $request) {
    if ($request->user()->tokenCan('create'))
    {
        $c = new AddImageController;
        return $c->store($request);
    }
    else
    {
        App::abort(403, "API user lacks 'create' permission");
    }
});

Route::middleware('auth:sanctum')->get('/images/{imageId}', function (Request $request, $imageId) {
    return Image::findOrFail($imageId);
});

Route::middleware('auth:sanctum')->delete('/images/{imageId}', function (Request $request, $imageId) {
    if ($request->user()->tokenCan('delete'))
    {
        Image::findOrFail($imageId)->delete();
    }
    else
    {
        App::abort(403, "API user lacks 'delete' permission");
    }
});

Route::middleware('auth:sanctum')->get('/projects/{projectId}', function (Request $request, $projectId) {
    return Project::findOrFail($projectId);
});

Route::middleware('auth:sanctum')->patch('/projects/{projectId}', function (Request $request, $projectId) {
    if ($request->user()->tokenCan('update'))
    {
        $project = Project::findOrFail($projectId);
        $project->update($request->all());
        return $project;
    }
    else
    {
        App::abort(403, "API user lacks 'update' permission");        
    }
});
