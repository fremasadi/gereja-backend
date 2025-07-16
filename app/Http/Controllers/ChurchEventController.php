<?php

namespace App\Http\Controllers;

use App\Models\ChurchEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ChurchEventController extends Controller
{

public function index()
{
    $today = Carbon::today();

    $events = ChurchEvent::whereDate('date', '>=', $today)->get();

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
