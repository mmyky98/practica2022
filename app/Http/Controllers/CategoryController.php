<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 *
 */
class CategoryController extends ApiController
{
    /**
     * @param Request $request
     */
    public function getAll(Request $request)
    {
        return Category::all();
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function add(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|max:50',
                'parent_id' => 'nullable|exists:categories,id'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Bad request!', $validator->messages()->toArray());
            }

            $name = $request->get('name');
            $parentId = $request->get('parent_id');

            if ($parentId) {
                $parent = Category::find($parentId);

                if ($parent->parent?->parent) {
                    return $this->sendError('You can\'t add a 3rd level subcategory!');
                }
            }

            $category = new Category();
            $category->name = $name;
            $category->parent_id = $parentId;
            $category->save();

            return $this->sendResponse($category->toArray());
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!');
        }
    }

    /**
     * @param $id
     */
    public function get($id)
    {
        $category = Category::find($id);
        return $category;
    }

    /**
     * @param $id
     * @param Request $request
     */
    public function update($id, Request $request)
    {
         try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|max:50',
                'parent_id' => 'nullable|exists:categories,id'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Bad request!', $validator->messages()->toArray());
            }

            $name = $request->get('name');
            $parentId = $request->get('parent_id');

            if ($parentId) {
                $parent = Category::find($parentId);

                if ($parent->parent?->parent) {
                    return $this->sendError('You can\'t add a 3rd level subcategory!');
                }
            }

            $category = Category::find($id);
            $category->name = $name;
            $category->parent_id = $parentId;
            $category->update();

            return $this->sendResponse($category->toArray());
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!');
        }

        /*$request->validate([
            'name' => 'required',
            'parent_id' => 'nullable|exists:categories,id',
                   
        ]);

    if($category)
        {
            $category = Category::find($id);        
            $category->name = $request->name;        
            $category->parent_id = $request->parentId; 
            $category->update();

            return response()->json(['message'=>'Added Successfully'], 200);
        }
    else
        {
            return response()->json(['message'=>'The update was not performed'], 404);
        }
    */
}

    /**
     * @param $id
     */
    public function delete($id)
    {
        $category = Category::find($id)->count();
        if($category > 0) {
            Category::delete($id);
            return response()->json(['message'=>'Deleted Successfully'],200);
        } else {
            return response()->json(['message'=>'Category with that id cannot be found']);
        }
    }
}
