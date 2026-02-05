<?php
namespace App\Http\Controllers;

class ApplicationController extends Controller
{
    /**
     * Application main view
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function __invoke()
    {
        return view('app');
    }
}