<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\Image;
use App\Models\Shop;

class ImageController extends Controller
{

    public function __construct()
    {
        $this->middleware('upload.auth');
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'image' => 'bail|required|image|mimes:jpeg,png,jpg,gif,bmp|max:300000',
        ]);

        if ($validator->fails()) {
            return response([
                'messages' => $validator->errors()
            ], 422);
        }

        $shop = $request->get('shop');

        try {

            $path = Storage::disk('s3')->put($request->shop, $request->image, 'public');

            $image = new Image([
                'path' => $path
            ]);

            $shop->images()->save($image);

            return response()->json($image);

        } catch (\Exception $e) {
            return response()->json([ 'error' => $e->getMessage() ], 401);
        }
    }

}
