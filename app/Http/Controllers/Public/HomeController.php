<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Faq;
use App\Models\Location;

class HomeController extends Controller
{
    public function index()
    {
        $locale = app()->getLocale();

        $locations = Location::active()->orderBy('sort_order')->get();
        $faqs = Faq::active()->forLocale($locale)->limit(6)->get();
        $blogPosts = BlogPost::published()->forLocale($locale)->limit(3)->get();

        return view('public.home', compact('locations', 'faqs', 'blogPosts'));
    }
}
