<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Faq;

class FaqController extends Controller
{
    public function index()
    {
        $faqs = Faq::active()->forLocale(app()->getLocale())->get();
        $categories = $faqs->groupBy('category');

        return view('public.pages.faq', compact('faqs', 'categories'));
    }
}
