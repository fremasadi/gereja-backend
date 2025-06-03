<?php

namespace App\Http\Controllers;

use App\Models\ChurchEvent;
use Illuminate\Http\Request;

class ChurchEventController extends Controller
{
    // Get all church events
    public function index()
    {
        $events = ChurchEvent::all();
        return response()->json($events);
    }

}
