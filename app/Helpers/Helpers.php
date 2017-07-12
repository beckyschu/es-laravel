<?php

if (! function_exists('format_bytes')) {
    /**
     * Format a byte integer into a human readable string.
     *
     * @param  int    $bytes
     * @return string
     */
    function format_bytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $pow   = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow   = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

if (! function_exists('format_seconds')) {
    /**
     * Format seconds into a human readable string.
     *
     * @param  int    $seconds
     * @return string
     */
    function format_seconds($durationInSeconds)
    {
        $duration = '';

        $days = floor($durationInSeconds / 86400);
        $durationInSeconds -= $days * 86400;
        $hours = floor($durationInSeconds / 3600);
        $durationInSeconds -= $hours * 3600;
        $minutes = floor($durationInSeconds / 60);
        $seconds = $durationInSeconds - $minutes * 60;

        if($days > 0) {
            $duration .= $days . ' days';
        }

        if($hours > 0) {
            $duration .= ' ' . $hours . ' hours';
        }

        if($minutes > 0) {
            $duration .= ' ' . $minutes . ' minutes';
        }

        if($seconds > 0) {
            $duration .= ' ' . $seconds . ' seconds';
        }

        return trim($duration);
    }
}
