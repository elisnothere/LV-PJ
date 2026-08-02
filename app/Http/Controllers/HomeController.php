<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
    public function __invoke()
    {
        if (! auth()->check()) {
            return view('home');
        }

        return auth()->user()->role === 'admin'
            ? redirect()->route('dashboard')
            : redirect()->route('catalog.index');
    }
}
