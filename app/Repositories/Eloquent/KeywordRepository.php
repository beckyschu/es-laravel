<?php

namespace App\Repositories\Eloquent;

use App\Models;

class KeywordRepository
{
    /**
     * Return keyword with the given ID.
     *
     * @param  int $id
     * @return Models\Keyword
     */
    public function find($id)
    {
        return Models\Keyword::findOrFail($id);
    }

    /**
     * Create a keyword with the given attributes.
     *
     * @param  array $attributes
     * @return Models\Keyword
     */
    public function create($attributes)
    {
        // Create a fill a new keyword
        $keyword = $this->fill(new Models\Keyword, $attributes);

        // Save the new keywrd
        $keyword->save();

        // Return keyword
        return $keyword;
    }

    /**
     * Update the provided keyword with the given attributes.
     *
     * @param  Models\Keyword $keyword
     * @param  array          $attributes
     * @return Models\Keyword
     */
    public function update(Models\Keyword $keyword, $attributes)
    {
        // Fill keyword with attributes
        $keyword = $this->fill($keyword, $attributes);

        // Save the updates
        $keyword->save();

        // Return keyword
        return $keyword;
    }

    /**
     * Delete the provided keyword.
     *
     * @param  Models\Keyword $keyword
     * @return Models\Keyword
     */
    public function delete(Models\Keyword $keyword)
    {
        // Delete attached parsehub settings
        $keyword->settings()->delete();

        // Delete attached schedules
        $keyword->schedules()->delete();

        // Delete the keyword
        $keyword->delete();
    }

    /**
     * Fill the provided keyword with the given attributes.
     *
     * @param  Models\Keyword $keyword
     * @param  array          $attributes
     * @return Models\Keyword
     */
    protected function fill(Models\Keyword $keyword, $attributes)
    {
        $keyword->fill($attributes);

        if ($assetId = array_get($attributes, 'asset.id')) {
            $keyword->asset_id = $assetId;
        }

        return $keyword;
    }

    /**
     * Return setting with the given ID.
     *
     * @param  int $id
     * @return Models\ParsehubSetting
     */
    public function findSetting($id)
    {
        return Models\ParsehubSetting::findOrFail($id);
    }

    /**
     * Create a parsehub setting with the given attributes.
     *
     * @param  Models\Keyword $keyword
     * @param  array          $attributes
     * @return Models\ParsehubSetting
     */
    public function createSetting(Models\Keyword $keyword, $attributes)
    {
        // Create a fill a new setting
        $setting = $this->fillSetting(new Models\ParsehubSetting, $attributes);

        // Attach the keyword
        $setting->keyword_id = $keyword->id;

        // If both settings are blank, don't create it
        if (! $setting->start_url && ! $setting->start_template) {
            return null;
        }

        // Save the new setting
        $setting->save();

        // Return setting
        return $setting;
    }

    /**
     * Update the provided setting with the given attributes.
     *
     * @param  Models\ParsehubSetting $setting
     * @param  array                  $attributes
     * @return Models\ParsehubSetting|null
     */
    public function updateSetting(Models\ParsehubSetting $setting, $attributes)
    {
        // Fill setting with attributes
        $setting = $this->fillSetting($setting, $attributes);

        // If both settings are blank, delete it
        if (! $setting->start_url && ! $setting->start_template) {
            $setting->delete();
            return null;
        }

        // Save the updates
        $setting->save();

        // Return setting
        return $setting;
    }

    /**
     * Fill the provided setting with the given attributes.
     *
     * @param  Models\ParsehubSetting $setting
     * @param  array                  $attributes
     * @return Models\ParsehubSetting
     */
    protected function fillSetting(Models\ParsehubSetting $setting, $attributes)
    {
        $setting->fill($attributes);

        if ($crawlerId = array_get($attributes, 'crawler.id')) {
            $setting->crawler_id = $crawlerId;
        }

        return $setting;
    }

    /**
     * Return schedule with the given ID.
     *
     * @param  int $id
     * @return Models\Schedule
     */
    public function findSchedule($id)
    {
        return Models\Schedule::findOrFail($id);
    }

    /**
     * Create a schedule with the given attributes.
     *
     * @param  Models\Keyword $keyword
     * @param  array          $attributes
     * @return Models\Schedule
     */
    public function createSchedule(Models\Keyword $keyword, $attributes)
    {
        // Create and fill a new schedule
        $schedule = $this->fillSchedule(new Models\Schedule, $attributes);

        // Attach the keyword
        $schedule->keyword_id = $keyword->id;

        // If schedule is blank, don't create it
        if (! $schedule->schedule) {
            return null;
        }

        // Save the new schedule
        $schedule->save();

        // Return schedule
        return $schedule;
    }

    /**
     * Update the provided schedule with the given attributes.
     *
     * @param  Models\Schedule $schedule
     * @param  array           $attributes
     * @return Models\Schedule|null
     */
    public function updateSchedule(Models\Schedule $schedule, $attributes)
    {
        // Fill schedule with attributes
        $schedule = $this->fillSchedule($schedule, $attributes);

        // If no schedule provided, delete it
        if (! $schedule->schedule) {
            $schedule->delete();
            return null;
        }

        // Save the updates
        $schedule->save();

        // Return schedule
        return $schedule;
    }

    /**
     * Fill the provided schedule with the given attributes.
     *
     * @param  Models\Schedule $schedule
     * @param  array           $attributes
     * @return Models\Schedule
     */
    protected function fillSchedule(Models\Schedule $schedule, $attributes)
    {
        $schedule->fill($attributes);

        if ($crawlerId = array_get($attributes, 'crawler.id')) {
            $schedule->crawler_id = $crawlerId;
        }

        return $schedule;
    }
}
