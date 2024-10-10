<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Intervention\Image\Laravel\Facades\Image;
use App\Models\Brand; 
use App\Models\Category; 
use Carbon\Carbon;

class AdminController extends Controller
{
    public function index() {
        return view('admin.index');
    }

    public function brands() {
        $brands = Brand::orderby('id','Desc')->paginate(10);
        return view('admin.brands',compact('brands'));
    }

    public function add_brand() { 
        return view('admin.brand_add');
    }

    public function edit_brand($id) { 
        $brand = Brand::find($id);
        return view('admin.brand_edit',compact('brand'));
    }

    public function update_brand(Request $request) { 
        $request->validate([
            'name'=> 'required',
            'slug'=> 'required|unique:brands,slug,'.$request->id,
            'image'=>'mimes:png,jpg,jpeg|max:2048'
        ]);

        $brand = Brand::find($request->id);
        $brand->name = $request->name; 
        $brand->slug =Str::slug($request->name);
        if ($request->hasFile('image')) {
            if (File::exists(public_path('uploads/brands').'/'.$brand->image)) {
                File::delete(public_path('uploads/brands').'/'.$brand->image);
            }
        $image = $request->file('image');
        $file_ext = $request->file('image')->extension();
        $file_name= Carbon::now()->timestamp.'.'.$file_ext;
        $this->GenerateBrandThumbnailImage($image,$file_name);
        $brand->image = $file_name;
        }
        $brand->save();
        return redirect()->route('admin.brands')->with('status','Brand with id '.$brand->id.' has been updated successfully!!');
    }

    public function store_brand(Request $request) { 
        $request->validate([
            'name'=> 'required',
            'slug'=> 'required|unique:brands,slug',
            'image'=>'mimes:png,jpg,jpeg|max:2048'
        ]);
        $brand = new Brand();
        $brand->name = $request->name;
        $brand->slug =Str::slug($request->name);
        $image = $request->file('image');
        $file_ext = $request->file('image')->extension();
        $file_name= Carbon::now()->timestamp.'.'.$file_ext;
        $this->GenerateBrandThumbnailImage($image,$file_name);
        $brand->image = $file_name;
        $brand->save();
        return redirect()->route('admin.brands')->with('status','Brand has been created successfully!!');
    }

    public function GenerateBrandThumbnailImage($image,$imageName) {
        $destinationPath = public_path('uploads/brands');
        $img = Image::read($image->path());
        $img->cover(124,124,"top");
        $img->resize(124,124,function ($constraint) {
            $constraint->aspectRatio();
                    })->save($destinationPath.'/'.$imageName);
    }

    public function delete_brand($id) {
        $brand = Brand::find($id);
        if (File::exists(public_path('uploads/brands').'/'.$brand->image)) {
            File::delete(public_path('uploads/brands').'/'.$brand->image);
        }
        $brand->delete();
        return redirect()->route('admin.brands')->with('status','Brand with id '.$brand->id.'  has been deleted successfully');
    }

    public function categories() {
        $categories = Category::orderby('id','Desc')->paginate(10);
        return view('admin.categories',compact('categories'));
    }

    public function add_category() { 
        return view('admin.category_add');
    }

    public function edit_category($id) { 
        $category = Category::find($id);
        return view('admin.category_edit',compact('category'));
    }

    public function update_category(Request $request) { 
        $request->validate([
            'name'=> 'required',
            'description'=> 'required',
            'slug'=> 'required|unique:categories,slug,'.$request->id,
            'image'=>'mimes:png,jpg,jpeg|max:2048'
        ]);

        $category = Category::find($request->id);
        $category->name = $request->name; 
        $category->description = $request->description; 
        $category->slug =Str::slug($request->name);
        if ($request->hasFile('image')) {
            if (File::exists(public_path('uploads/categories').'/'.$category->image)) {
                File::delete(public_path('uploads/categories').'/'.$category->image);
            }
        $image = $request->file('image');
        $file_ext = $request->file('image')->extension();
        $file_name= Carbon::now()->timestamp.'.'.$file_ext;
        $this->GenerateCategoryThumbnailImage($image,$file_name);
        $category->image = $file_name;
        }
        $category->save();
        return redirect()->route('admin.categories')->with('status','Category with id '.$category->id.' has been updated successfully!!');
    }

    public function store_category(Request $request) { 
        $request->validate([
            'name'=> 'required|unique:categories',
            'description'=> 'required',
            'slug'=> 'required|unique:categories,slug',
            'image'=>'mimes:png,jpg,jpeg|max:2048'
        ]);
        $category = new Category();
        $category->name = $request->name;
        $category->description = $request->description;
        $category->slug =Str::slug($request->name);
        $image = $request->file('image');
        $file_ext = $request->file('image')->extension();
        $file_name= Carbon::now()->timestamp.'.'.$file_ext;
        $this->GenerateCategoryThumbnailImage($image,$file_name);
        $category->image = $file_name;
        $category->save();
        return redirect()->route('admin.categories')->with('status','Category '.$category->name.' has been created successfully!!');
    }

    public function GenerateCategoryThumbnailImage($image,$imageName) {
        $destinationPath = public_path('uploads/categories');
        $img = Image::read($image->path());
        $img->cover(124,124,"top");
        $img->resize(124,124,function ($constraint) {
            $constraint->aspectRatio();
                    })->save($destinationPath.'/'.$imageName);
    }

    public function delete_category($id) {
        $category = Category::find($id);
        if (File::exists(public_path('uploads/categories').'/'.$category->image)) {
            File::delete(public_path('uploads/categories').'/'.$category->image);
        }
        $category->delete();
        return redirect()->route('admin.categories')->with('status','Category with id '.$category->id.'  has been deleted successfully');
    }
}
