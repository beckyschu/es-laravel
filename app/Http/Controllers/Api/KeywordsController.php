<?php

namespace App\Http\Controllers\Api;

use App\Repositories;
use App\Transformers;
use Illuminate\Http\Request;
use League\Fractal\Resource\Item;
use App\Http\Controllers\Controller;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class KeywordsController extends Controller
{
    protected $fractal;

    protected $keywordRepo;

    public function __construct(
        \League\Fractal\Manager $fractal,
        Repositories\Eloquent\KeywordRepository $keywordRepo
    ) {
        $this->fractal     = $fractal->setSerializer(new \League\Fractal\Serializer\ArraySerializer);
        $this->keywordRepo = $keywordRepo;
    }

    public function show($id)
    {
        // Fetch keyword
        $keyword = $this->keywordRepo->find($id);

        // Enable includes
        $this->fractal->parseIncludes('all_settings,all_settings.crawler,all_schedules,all_schedules.crawler');

        // Build fractal item
        $item = new Item($keyword, new Transformers\KeywordTransformer);

        // Build response
        $response = $this->fractal->createData($item)->toArray();

        // Return success response
        return response()->json($response);
    }

    public function create(Request $request)
    {
        // No post data found
        if (! $attributes = $request->input()) {
            throw new BadRequestHttpException('You must provide attributes.');
        }

        // Run the create
        $keyword = $this->keywordRepo->create($attributes);

        // Return the show response
        return $this->show($keyword->id);
    }

    public function update($id, Request $request)
    {
        // No post data found
        if (! $attributes = $request->input()) {
            throw new BadRequestHttpException('You must provide attributes.');
        }

        // Fetch keyword
        $keyword = $this->keywordRepo->find($id);

        // Update the keyword
        $keyword = $this->keywordRepo->update($keyword, $attributes);

        // Return the show response
        return $this->show($keyword->id);
    }

    public function delete($id)
    {
        // Fetch keyword
        $keyword = $this->keywordRepo->find($id);

        // Delete the keyword
        $this->keywordRepo->delete($keyword);

        // Return empty response
        return response()->make();
    }

    public function createSetting($keywordId, Request $request)
    {
        // No post data found
        if (! $attributes = $request->input()) {
            throw new BadRequestHttpException('You must provide attributes.');
        }

        // Fetch keyword
        $keyword = $this->keywordRepo->find($keywordId);

        // Create the setting
        $setting = $this->keywordRepo->createSetting($keyword, $attributes);

        // Setting was not created
        if (! $setting) return response()->make();

        // Enable includes
        $this->fractal->parseIncludes('crawler');

        // Build fractal item
        $item = new Item($setting, new Transformers\ParsehubSettingTransformer);

        // Build response
        $response = $this->fractal->createData($item)->toArray();

        // Return success response
        return response()->json($response);
    }

    public function updateSetting($keywordId, $id, Request $request)
    {
        // No post data found
        if (! $attributes = $request->input()) {
            throw new BadRequestHttpException('You must provide attributes.');
        }

        // Fetch setting
        $setting = $this->keywordRepo->findSetting($id);

        // Update the setting
        $setting = $this->keywordRepo->updateSetting($setting, $attributes);

        // Setting has been destroyed
        if (! $setting) return response()->make();

        // Enable includes
        $this->fractal->parseIncludes('crawler');

        // Build fractal item
        $item = new Item($setting, new Transformers\ParsehubSettingTransformer);

        // Build response
        $response = $this->fractal->createData($item)->toArray();

        // Return success response
        return response()->json($response);
    }

    public function createSchedule($keywordId, Request $request)
    {
        // No post data found
        if (! $attributes = $request->input()) {
            throw new BadRequestHttpException('You must provide attributes.');
        }

        // Fetch keyword
        $keyword = $this->keywordRepo->find($keywordId);

        // Create the schedule
        $schedule = $this->keywordRepo->createSchedule($keyword, $attributes);

        // Schedule was not created
        if (! $schedule) return response()->make();

        // Enable includes
        $this->fractal->parseIncludes('crawler');

        // Build fractal item
        $item = new Item($schedule, new Transformers\ScheduleTransformer);

        // Build response
        $response = $this->fractal->createData($item)->toArray();

        // Return success response
        return response()->json($response);
    }

    public function updateSchedule($keywordId, $id, Request $request)
    {
        // No post data found
        if (! $attributes = $request->input()) {
            throw new BadRequestHttpException('You must provide attributes.');
        }

        // Fetch schedule
        $schedule = $this->keywordRepo->findSchedule($id);

        // Update the schedule
        $schedule = $this->keywordRepo->updateSchedule($schedule, $attributes);

        // Schedule has been destroyed
        if (! $schedule) return response()->make();

        // Enable includes
        $this->fractal->parseIncludes('crawler');

        // Build fractal item
        $item = new Item($schedule, new Transformers\ScheduleTransformer);

        // Build response
        $response = $this->fractal->createData($item)->toArray();

        // Return success response
        return response()->json($response);
    }
}
