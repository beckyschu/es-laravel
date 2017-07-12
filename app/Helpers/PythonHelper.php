<?php

namespace App\Helpers;

use UnexpectedValueException;

class PythonHelper
{
    /**
     * Check which data type the serialisation is and then run the specific
     * deserialisation method.
     *
     * @param  string $data
     * @return array|string
     */
    public function deserialise($data)
    {
        $data = $this->prepareString($data);

        if (starts_with($data, '{')) {
            return $this->deserialiseDict($data);
        }

        if (starts_with($data, '[{')) {
            return $this->deserialiseWrappedDict($data);
        }

        if (starts_with($data, '[')) {
            return $this->deserialiseArray($data);
        }

        return $data;
    }

    /**
     * Trim any wrapping quotes from data if found.
     *
     * @param  string $data
     * @return string
     */
    public function prepareString($data)
    {
        if (starts_with($data, '"')) $data = ltrim($data, '"');
        if (ends_with($data, '"'))   $data = rtrim($data, '"');

        return $data;
    }

    /**
     * Totally sucky and prone to breaking deserialisation method for string
     * representation Python dictionaries.
     *
     * This method attempts to convert into JSON and then decodes.
     *
     * @param  string $dict
     * @return array
     */
    public function deserialiseDict($data)
    {
        $data = str_replace("{u'", "{'", $data);
        $data = str_replace(": u'", ": '", $data);
        $data = str_replace(", u'", ", '", $data);
        $data = str_replace("'", '"', $data);

        return json_decode($data, true);
    }

    /**
     * Totally sucky and prone to breaking deserialisation method for string
     * representation Python dictionaries (wrapped in an array).
     *
     * This method attempts to convert into JSON and then decodes.
     *
     * @param  string $dict
     * @return array
     */
    public function deserialiseWrappedDict($data)
    {
        $data = str_replace("{u'", "{'", $data);
        $data = str_replace(": u'", ": '", $data);
        $data = str_replace(", u'", ", '", $data);
        $data = str_replace("'", '"', $data);

        return json_decode($data, true)[0];
    }

    /**
     * Totally sucky and prone to breaking deserialisation method for string
     * representation Python arrays.
     *
     * This method attempts to convert into JSON and then decodes.
     *
     * @param  string $dict
     * @return array
     */
    public function deserialiseArray($data)
    {
        $data = str_replace("[u'", "['", $data);
        $data = str_replace(", u'", ", '", $data);
        $data = str_replace("'", '"', $data);

        return json_decode($data, true);
    }
}
