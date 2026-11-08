<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use App\Models\BusStop;
use App\Models\Route;
use App\Support\Website;
use Illuminate\Support\Facades\Cache;

class WebsiteController extends Controller
{
    public function index()
    {
        $cacheKey = Website::homepageCacheKey();

        $html = Cache::remember($cacheKey, now()->addMinutes(5), function () {
            $content = Website::content();

            $adverts = Advertisement::live()
                ->where('placement', 'website')
                ->get();

            $routes = $content['show_routes']
                ? Route::with(['origin', 'destination'])->latest()->take(6)->get()
                : collect();

            $stops = BusStop::whereNotNull('image_path')->latest()->take(6)->get();

            return view('website.home', compact('content', 'adverts', 'routes', 'stops'))->render();
        });

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'public, max-age=60, s-maxage=300, stale-while-revalidate=600',
        ]);
    }

    public function sitemap()
    {
        $pages = [
            ['loc' => url('/'), 'changefreq' => 'weekly', 'priority' => '1.0'],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($pages as $page) {
            $xml .= '<url><loc>'.e($page['loc']).'</loc>'
                .'<changefreq>'.$page['changefreq'].'</changefreq>'
                .'<priority>'.$page['priority'].'</priority></url>';
        }
        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
