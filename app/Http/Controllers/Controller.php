<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

use App\Models\User;
use App\Models\Offer;
use App\Models\Company;
use App\Models\Request;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function getCount()
    {

        $dataUser = User::select(
            'id'
        )->count();

        $dataOffer = Offer::select(
            'id'
        )->count();

        $dataCompany = Company::select(
            'id'
        )->count();

        $dataRequest = Request::select(
            'id'
        )->count();

        return response()->json([
            'success' => true,
            'User' => $dataUser,
            'Offer' => $dataOffer,
            'Company' => $dataCompany,
            'Request' => $dataRequest
        ], 200); // code reponse 200 pour success
    }
}
