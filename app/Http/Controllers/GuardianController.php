<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class GuardianController extends Controller
{
    public function index($category){
        $response = $this->getGuardianCategory($category);
    }

    public function getGuardianCategory($category){
        $response = Http::withHeaders([
            'api-key' => '61bd1f09-3ef5-40d6-b1dc-0d2ab0305ff4',
        ])->get('https://content.guardianapis.com/search', [
            'section' => $category,
        ]);
        return $response;
    }
}
