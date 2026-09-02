<?php

namespace App\Http\Controllers;

use App\Support\ExifGpsReader;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Reads GPS coordinates straight out of a photo's own EXIF metadata — no
 * relation to Shodan or IP scraping. The upload is never persisted to
 * disk: only its temporary path (cleaned up by PHP after the request) is
 * read.
 */
class PhotoLocationController extends Controller
{
    public function create(): View
    {
        return view('photo-location.create', ['checked' => false]);
    }

    public function store(Request $request): View
    {
        $request->validate([
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,tiff,tif', 'max:20480'],
        ]);

        $coordinates = ExifGpsReader::fromPath($request->file('photo')->getRealPath());

        return view('photo-location.create', [
            'checked' => true,
            'coordinates' => $coordinates,
        ]);
    }
}
