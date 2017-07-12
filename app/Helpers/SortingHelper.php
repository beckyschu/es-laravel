<?php

namespace App\Helpers;

use Illuminate\Http\Request;

class SortingHelper
{
    protected $request;

    protected $columns = [];

    protected $path = '/';

    protected $query = [];

    protected $column;

    protected $direction = 'ASC';

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function setColumns($columns)
    {
        $this->columns = $columns;

        return $this;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    public function setQuery($query)
    {
        $this->query = $query;
    }

    public function appendQuery($key, $value = null)
    {
        if (! $value) return;

        if (is_array($key)) {
            return $this->appendQueryArray($key);
        }

        $this->query[$key] = $value;
    }

    public function appendQueryArray($array)
    {
        foreach ($array as $key => $value) {
            $this->appendQuery($key, $value);
        }
    }

    public function getColumn()
    {
        return $this->column;
    }

    public function setColumn($column)
    {
        $this->column = $column;

        return $this;
    }

    public function getDirection()
    {
        return $this->direction;
    }

    public function setDirection($direction)
    {
        $this->direction = strtoupper($direction);

        if (! in_array($direction, ['ASC', 'DESC'])) {
            $this->direction = 'ASC';
        }

        return $this;
    }

    public function fromRequest($default_col, $default_direction)
    {
        if (! $this->request->get('sort')) {
            $this->setColumn($default_col);
            $this->setDirection($default_direction);

            return $this;
        }

        $parts = explode('.', $this->request->get('sort'));

        $this->setColumn($parts[0]);
        $this->setDirection(isset($parts[1]) ? $parts[1] : 'ASC');

        return $this;
    }

    public function getColumnsHtml()
    {
        $html = [];

        foreach ($this->getColumns() as $key => $label)
        {
            $direction = 'ASC';

            $class = 'table__sort-header';
            if ($key == $this->getColumn()) {
                $class .= ' table__sort-header--'.$this->getDirection();
                $direction = ('ASC' == $this->getDirection()) ? 'DESC' : 'ASC';
            }

            array_push($html, '<th class="'.$class.'" data-href="'.$this->getUrl($key, $direction).'">'.$label.'</th>');
        }

        return $html;
    }

    protected function getUrl($column, $direction)
    {
        $parameters = ['sort' => $column.'.'.$direction];

        if ($this->query) {
            $parameters = array_merge($this->query, $parameters);
        }

        return $this->path.'?'.http_build_query($parameters, null, '&');
    }
}
