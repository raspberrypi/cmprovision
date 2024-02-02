<?php

namespace App\Models;

class Firmware
{
    public $name, $channel, $path;

    public static function allOfChannel($channel)
    {
        $entries = [];
        $dir = storage_path("app/firmware/$channel");

        if (@is_readable($dir))
        {
            $files = scandir($dir, SCANDIR_SORT_DESCENDING);
            foreach ($files as $f)
            {
                if (preg_match('/^pieeprom-.+\\.bin$/', $f))
                {
                    $entry = new Firmware;
                    $entry->name = $f;
                    $entry->channel = $channel;
                    $entry->path = $channel.'/'.$f;
                    $entries[] = $entry;
                }
            }
        }

        return $entries;
    }

    public static function all()
    {
        $entries = [];
        $channels = ["latest", "feature-specific"];

        foreach ($channels as $channel)
        {
            $entries = array_merge($entries, self::allOfChannel($channel));
        }

        return $entries;
    }

    public static function basedir()
    {
        return storage_path("app/firmware");
    }
}
