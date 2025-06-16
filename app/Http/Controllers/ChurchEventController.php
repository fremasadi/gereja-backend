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

     // Ambil hanya field images dari semua event
     public function images()
     {
         $images = ChurchEvent::select('images')->get();
 
         return response()->json([
             'status' => true,
             'data' => $images,
         ]);
     }

}
