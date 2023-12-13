<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\Image;
use App\Models\Shop;
use Maestroerror\HeicToJpg;
use Illuminate\Support\Str;

class ImageController extends Controller
{

    public function __construct()
    {
        $this->middleware('upload.auth');
    }

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'image' => 'bail|required|file|mimeTypes:image/jpeg,image/png,image/gif,image/bmp,image/heic|max:300000',
        ]);

        if ($validator->fails()) {
            return response([
                'errors' => $validator->errors()
            ], 422);
        }

        $image_file = $request->image;

        $shop = $request->get('shop');

        if (HeicToJpg::isHeic($image_file)) {
            $converted_image = HeicToJpg::convert($image_file)->get();

            $filepath = "{$request->shop}/" . Str::uuid() . '.jpg';

            try {

                $success = Storage::disk('s3')->put($filepath, $converted_image, 'public');

                if (!$success) {
                    return response()->json("Image failed to upload", 500);
                }

                $image = new Image([
                    'path' => $filepath
                ]);

                $shop->images()->save($image);

                return response()->json([ 'image' => $image ]);

            } catch (\Exception $e) {
                return response()->json([ 'error' => $e->getMessage() ], 401);
            }

            return;
        }


        try {

            $filepath = Storage::disk('s3')->put($request->shop, $image_file, 'public');

            $image = new Image([
                'path' => $filepath
            ]);

            $shop->images()->save($image);

            return response()->json([ 'image' => $image ]);

        } catch (\Exception $e) {
            return response()->json([ 'error' => $e->getMessage() ], 401);
        }
    }

    public function storeMany(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'images' => 'array|bail|required',
            'images.*' => 'file|mimeTypes:image/jpeg,image/png,image/gif,image/bmp,image/heif,image/heic|max:300000'
        ]);

        if ($validator->fails()) {
            return response([
                'errors' => $validator->errors()
            ], 422);
        }

        $shop = $request->get('shop');

        try {

            $images = collect([]);

            foreach($request->images as $index => $image_file) {

                if ($index > 3) break;


                if (HeicToJpg::isHeic($image_file)) {
                    $image_file = HeicToJpg::convert($image_file)->get();

                    $filepath = "{$request->shop}/" . Str::uuid() . '.jpg';

                    $success = Storage::disk('s3')->put($filepath, $image_file, 'public');

                    if (!$success) continue;

                    $image = new Image([
                        'path' => $filepath
                    ]);

                    $images->push($image);

                    continue;
                }

                $filepath = Storage::disk('s3')->put($request->shop, $image_file, 'public');

                $image = new Image([
                    'path' => $filepath
                ]);

                $images->push($image);
            }

            $shop->images()->saveMany($images);

            return response()->json(['images' => $images]);

        } catch (\Exception $e) {
            return response()->json([ 'error' => $e->getMessage() ], 401);
        }
    }


    public function delete(Request $request)
    {
        $shop = $request->get('shop');

        $validator = Validator::make($request->all(), [
            'path' => ['required', 'string', 'exists:images'],
        ]);

        if ($validator->fails()) {
            return response([
                'errors' => $validator->errors()
            ], 422);
        }

        $image =  $shop->images()->where('path', $request->path)->first();

        if (!$image) {
            return response([
                'error' => "Image #{$id} not found"
            ], 404);
        }

        Storage::disk('s3')->delete($image->path);

        $deleted = $image->delete();

        return response()->json(['removed' => $deleted]);
    }

}
