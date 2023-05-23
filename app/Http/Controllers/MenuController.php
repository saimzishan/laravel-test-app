<?php

namespace App\Http\Controllers;

use App\Http\Libraries\HelperLibrary;
use App\Http\Resources\MenuListResource;
use App\Http\Resources\MenuResource;
use App\Http\Resources\MenusResource;
use App\Menu;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index()
    {
        $data = Menu::where("is_delete", 0)->paginate(HelperLibrary::perPage());
        return MenusResource::collection($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function create()
    {
        $data = Menu::where("is_delete", 0)->where("parent_id", 0)->get();
        return MenuListResource::collection($data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $row = new Menu;
        $row->parent_id = $request->parent;
        $row->name = $request->name;
        $row->slug = $request->slug;
        $row->icon = $request->icon;
        $row->order = $row->getNewOrder($request->parent);
        $row->created_by = HelperLibrary::getLoggedInUser()->id;
        $row->updated_by = HelperLibrary::getLoggedInUser()->id;
        if ($row->save()) {
            $success = true;
        } else {
            $success = false;
        }
        return response()->json([
            "success" => $success
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $row = Menu::find($id);
        $row->is_active = $request->action == "active" ? 1 : 0;
        $row->updated_by = HelperLibrary::getLoggedInUser()->id;
        if ($row->save()) {
            $success = true;
        } else {
            $success = false;
        }
        return response()->json([
            "success" => $success
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return MenuResource
     */
    public function edit($id)
    {
        $data = Menu::where("is_delete", 0)->where("id", $id)->first();
        return new MenuResource($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $row = Menu::find($id);
        $row->parent_id = $request->parent;
        $row->name = $request->name;
        $row->slug = $request->slug;
        $row->icon = $request->icon;
        $row->updated_by = HelperLibrary::getLoggedInUser()->id;
        if ($row->save()) {
            $success = true;
        } else {
            $success = false;
        }
        return response()->json([
            "success" => $success
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $row = Menu::find($id);
        $row->is_delete = 1;
        $row->updated_by = HelperLibrary::getLoggedInUser()->id;
        if ($row->save()) {
            $success = true;
        } else {
            $success = false;
        }
        return response()->json([
            "success" => $success
        ]);
    }
    
    /**
     * generate Menu For the User
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function generatemenu()
    {
        $ret = [];
        $rows = Menu::where("is_delete", 0)->where("is_active", 1)->where("parent_id", 0)->orderBy("order", "asc")->get();
        foreach ($rows as $row) {
            $childerns = [];
            foreach ($row->children as $child) {
                $childerns[] = [
                    "name" => $child->name,
                    "slug" => $child->slug,
                    "icon" => $child->icon,
                ];    
            }
            $ret[] = [
                "name" => $row->name,
                "slug" => $row->slug,
                "icon" => $row->icon,
                "childerns" => $childerns,
            ];
        }
        return response()->json($ret);
    }
}
