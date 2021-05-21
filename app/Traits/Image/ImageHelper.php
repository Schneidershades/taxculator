<?php

namespace App\Traits\Image;

use Auth;
use Session;
use File;
use Image;
use Storage;

class ImageHelper
{
    public static function uploadAnything($file, $name, $pathDirectory, $saveDatabaseAttribute)
    {
        $image = $file;
        $filename = $name . '.' . $image->getClientOriginalExtension();

        $directory = $pathDirectory;
        $path = $directory . $filename;

        if (!File::exists($directory)) {
            // path does not exist
            File::makeDirectory($directory, $mode = 0777, true, true);
        }

        list($width, $height, $type, $attr) = getimagesize($image);

        if ($width < $height) {
            Image::make($image)->resize(250, 400)->save($path);
        } else {
            Image::make($image)->resize(400, 250)->save($path);
        }

        return $path;
    }
}
