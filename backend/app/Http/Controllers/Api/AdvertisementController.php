<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use Illuminate\Http\Request;

class AdvertisementController extends Controller
{
    public function index(Request $request)
    {
        $placement = (string) $request->query('placement', 'home');

        if (! array_key_exists($placement, Advertisement::PLACEMENTS)) {
            $placement = 'home';
        }

        $adverts = Advertisement::live()
            ->where('placement', $placement)
            ->get(['id', 'title', 'subtitle', 'image_path', 'link_url', 'placement']);

        return response()->json($adverts);
    }
}
