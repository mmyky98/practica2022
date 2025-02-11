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
    // /**
    //  * @param Request $request
    //  * @Mihaela
    //  */
    // public function getAll(Request $request)
    // {
    //     return Category::all();
    // }


    public function getAll(Request $request): JsonResponse
    {
        try {
            $categories = Category::query();

            $perPage = $request->get('perPage', 20);
            $search = $request->get('search', '');

            if ($search && $search !== '') {
                $categories = $categories->where('name', 'LIKE', '%' . $search . '%');
            }

            $categories = $categories->paginate($perPage);

            $results = [
                'data' => $categories->items(),
                'currentPage' => $categories->currentPage(),
                'perPage' => $categories->perPage(),
                'total' => $categories->total(),
                'hasMorePages' => $categories->hasMorePages()
            ];

            return $this->sendResponse($results);
        } catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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

    // /**
    //  * @param $id
    //  * @Mihaela
    //  */
    // public function get($id)
    // {
    //     $category = Category::find($id);
    //     return $category;
    // }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function get($id): JsonResponse
    {
        try {
            $category = Category::find($id);

            if (!$category) {
                return $this->sendError('Category not found!', [], Response::HTTP_NOT_FOUND);
            }

            return $this->sendResponse($category->toArray());
        } catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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

    // /**
    //  * @param $id
    //  * @Mihaela
    //  */
    // public function delete($id)
    // {
    //     $category = Category::find($id)->count();
    //     if($category > 0) {
    //         Category::destroy($id);
    //         return response()->json(['message'=>'Deleted Successfully'],200);
    //     } else {
    //         return response()->json(['message'=>'Category with that id cannot be found']);
    //     }
    // }


    /**
     * @param $id
     * @return JsonResponse
     */
    public function delete($id): JsonResponse
    {
        try {
            $category = Category::find($id);

            if (!$category) {
                return $this->sendError('Category not found!', [], Response::HTTP_NOT_FOUND);
            }

            DB::beginTransaction();

            $category->delete();

            DB::commit();

            return $this->sendResponse([], Response::HTTP_NO_CONTENT);
        } catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @return JsonResponse
     */
    public function tree(): JsonResponse
    {
        try {
            $tree = [];
            $subcategoryParents = [];

            $categories = Category::all();

            foreach ($categories as $category) {
                if (!$category->parent_id) {
                    $tree[$category->id] = [
                        'category' => $category,
                        'childs' => []
                    ];
                } else {
                    if (isset($tree[$category->parent_id])) {
                        $tree[$category->parent_id]['childs'][$category->id] = [
                            'category' => $category,
                            'childs' => []
                        ];

                        $subcategoryParents[$category->id] = $category->parent_id;
                    } else {
                        $topParent = $subcategoryParents[$category->parent_id];

                        $tree[$topParent]['childs'][$category->parent_id]['childs'][$category->id] = [
                            'category' => $category,
                            'childs' => []
                        ];
                    }
                }
            }

            return $this->sendResponse($tree);
        } catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}




//     /**
//      * @param Request $request
//      * @return JsonResponse
//      */
//     public function add(Request $request): JsonResponse
//     {
//         try {
//             $validator = Validator::make($request->all(), [
//                 'name' => 'required|max:50',
//                 'parent_id' => 'nullable|exists:categories,id'
//             ]);

//             if ($validator->fails()) {
//                 return $this->sendError('Bad request!', $validator->messages()->toArray());
//             }

//             $name = $request->get('name');
//             $parentId = $request->get('parent_id');

//             if ($parentId) {
//                 $parent = Category::find($parentId);

//                 if ($parent->parent?->parent) {
//                     return $this->sendError('You can\'t add a 3rd level subcategory!');
//                 }
//             }

//             $category = new Category();
//             $category->name = $name;
//             $category->parent_id = $parentId;
//             $category->save();

//             return $this->sendResponse([], Response::HTTP_CREATED);
//         } catch (Exception $exception) {
//             Log::error($exception);

//             return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
//         }
//     }



//     /**
//      * @param $id
//      * @param Request $request
//      * @return JsonResponse
//      */
//     public function update($id, Request $request): JsonResponse
//     {
//         try {
//             $category = Category::find($id);

//             if (!$category) {
//                 return $this->sendError('Category not found!', [], Response::HTTP_NOT_FOUND);
//             }

//             $validator = Validator::make($request->all(), [
//                 'name' => 'required|max:50',
//                 'parent_id' => 'nullable|exists:categories,id'
//             ]);

//             if ($validator->fails()) {
//                 return $this->sendError('Bad request!', $validator->messages()->toArray());
//             }

//             $name = $request->get('name');
//             $parentId = $request->get('parent_id');

//             if ($parentId) {
//                 $parent = Category::find($parentId);

//                 if ($parent->parent?->parent) {
//                     return $this->sendError('You can\'t add a 3rd level subcategory!');
//                 }

//                 if ($parentId === $category->id) {
//                     return $this->sendError('You can\'t add same category as parent!');
//                 }
//             }

//             $category->name = $name;
//             $category->parent_id = $parentId;
//             $category->save();

//             return $this->sendResponse($category->toArray());
//         } catch (Exception $exception) {
//             Log::error($exception);

//             return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
//         }
//     }

