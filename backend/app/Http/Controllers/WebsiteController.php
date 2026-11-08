<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use App\Models\BusStop;
use App\Models\Route;
use App\Support\Website;

class WebsiteController extends Controller
{
    public function index()
    {
        $content = Website::content();

        $adverts = Advertisement::live()
            ->where('placement', 'website')
            ->get();

        $routes = $content['show_routes']
            ? Route::with(['origin', 'destination'])->latest()->take(6)->get()
            : collect();

        $stops = BusStop::whereNotNull('image_path')->latest()->take(6)->get();

        return view('website.home', compact('content', 'adverts', 'routes', 'stops'));
    }
}
