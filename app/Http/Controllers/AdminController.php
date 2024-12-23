<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Intervention\Image\Laravel\Facades\Image;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Order;
use App\Models\Promotion;
use App\Models\User;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function index()
    {
        $userCount = User::count();
        $brandCount = Brand::count();
        $categoryCount = Category::count();
        $productCount = Product::count();
        $orderCount = Order::count();
        $todayOrdersCount = Order::whereDate('created_at', Carbon::today())->count();
        $pendingOrdersCount = Order::where('status', 'pending')->count();
        $orders = Order::orderby('id', 'Desc')->paginate(10); 
        foreach ($orders as $order) { 
            if ($order->cart_items) { 
                $cartItems = json_decode($order->cart_items, true); 
                $order->cart_item_count = count($cartItems);  
            } else {
                $order->cart_item_count = 0;  
            }
        }  
        return view('admin.index', compact('userCount','orders', 'orderCount','todayOrdersCount','pendingOrdersCount','brandCount', 'categoryCount', 'productCount'));
    }

    public function view_order($id)
    {
        $order = Order::find($id);
        return view('admin.order_view', compact('order'));
    }
    public function users()
    {
        $users = User::paginate(10);
        return view('admin.users', compact('users'));
    }

    public function quotations()
    {
        return view('admin.quotations');
    }

    public function brands()
    {
        $brands = Brand::orderby('id', 'Desc')->paginate(10);
        return view('admin.brands', compact('brands'));
    }

    public function add_brand()
    {
        $categories = Category::all(); // Fetch all categories
        return view('admin.brand_add', compact('categories'));
    }

    public function edit_brand($id)
    {
        $brand = Brand::find($id);
        $categories = Category::all();
        return view('admin.brand_edit', compact('brand', 'categories'));
    }

    public function update_brand(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:brands,slug,' . $request->id,
            'width' => 'required|numeric|min:0.1',
            'height' => 'required|numeric|min:0.1',
            'price' => 'required|numeric|min:0.1',
            'category_id' => 'required|exists:categories,id',
            'image' => 'mimes:png,jpg,jpeg,heic,svg,svg|max:5500'
        ]);

        $width = $request->input('width');
        $height = $request->input('height');
        $price = $request->input('price');

        // Calculate price_per_m2
        $area = $width * $height;
        $price_per_m2 = $area > 0 ? $price / $area : 0;

        $brand = Brand::find($request->id);
        $brand->name = $request->name;
        $brand->width = $width;
        $brand->height = $height;
        $brand->price = $price;
        $brand->price_per_m2 = $price_per_m2;  // Use calculated value here
        $brand->slug = Str::slug($request->name);
        $brand->category_id = $request->category_id;

        if ($request->hasFile('image')) {
            if (File::exists(public_path('uploads/brands') . '/' . $brand->image)) {
                File::delete(public_path('uploads/brands') . '/' . $brand->image);
            }
            $image = $request->file('image');
            $file_ext = $request->file('image')->extension();
            $file_name = Carbon::now()->timestamp . '.' . $file_ext;
            $this->GenerateBrandThumbnailImage($image, $file_name);
            $brand->image = $file_name;
        }
        $brand->save();
        return redirect()->route('admin.brands')->with('status', 'Brand with id ' . $brand->id . ' has been updated successfully!!');
    }

    public function store_brand(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required',
            'width' => 'required|numeric|min:0',
            'height' => 'required|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'slug' => 'required|unique:brands,slug',
            'image' => 'mimes:png,jpg,jpeg,heic,svg|max:5500'
        ]);

        $width = $request->input('width');
        $height = $request->input('height');
        $price = $request->input('price');

        // Calculate price_per_m2
        $area = $width * $height;
        $price_per_m2 = $area > 0 ? $price / $area : 0;

        $brand = new Brand();
        $brand->name = $request->name;
        $brand->width = $width;
        $brand->height = $height;
        $brand->price = $price;
        $brand->price_per_m2 = $price_per_m2;  // Use calculated value here
        $brand->category_id = $request->category_id;
        $brand->slug = Str::slug($request->name);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $file_ext = $image->extension();
            $file_name = Carbon::now()->timestamp . '.' . $file_ext;
            $this->GenerateBrandThumbnailImage($image, $file_name);
            $brand->image = $file_name;
        }

        $brand->save();
        return redirect()->route('admin.brands')->with('status', 'Brand has been created successfully!!');
    }

    public function GenerateBrandThumbnailImage($image, $imageName)
    {
        $destinationPath = public_path('uploads/brands');
        $img = Image::read($image->path());
        $img->cover(124, 124, "top");
        $img->resize(124, 124, function ($constraint) {
            $constraint->aspectRatio();
        })->save($destinationPath . '/' . $imageName);
    }

    public function delete_brand($id)
    {
        $brand = Brand::find($id);
        if (File::exists(public_path('uploads/brands') . '/' . $brand->image)) {
            File::delete(public_path('uploads/brands') . '/' . $brand->image);
        }
        $brand->delete();
        return redirect()->route('admin.brands')->with('status', 'Brand with id ' . $brand->id . '  has been deleted successfully');
    }

    public function categories()
    {
        $categories = Category::orderby('id', 'Desc')->paginate(10);
        return view('admin.categories', compact('categories'));
    }

    public function add_category()
    {
        return view('admin.category_add');
    }

    public function edit_category($id)
    {
        $category = Category::find($id);
        return view('admin.category_edit', compact('category'));
    }

    public function update_category(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'required',
            'slug' => 'required|unique:categories,slug,' . $request->id,
            'image' => 'mimes:png,jpg,jpeg,svg, heic|max:5500'
        ]);

        $category = Category::find($request->id);
        $category->name = $request->name;
        $category->description = $request->description;
        $category->slug = Str::slug($request->name);
        if ($request->hasFile('image')) {
            if (File::exists(public_path('uploads/categories') . '/' . $category->image)) {
                File::delete(public_path('uploads/categories') . '/' . $category->image);
            }
            $image = $request->file('image');
            $file_ext = $request->file('image')->extension();
            $file_name = Carbon::now()->timestamp . '.' . $file_ext;
            $this->GenerateCategoryThumbnailImage($image, $file_name);
            $category->image = $file_name;
        }
        $category->save();
        return redirect()->route('admin.categories')->with('status', 'Category with id ' . $category->id . ' has been updated successfully!!');
    }

    public function store_category(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:categories',
            'description' => 'required',
            'slug' => 'required|unique:categories,slug',
            'image' => 'mimes:png,jpg,jpeg,svg,heic|max:5500'
        ]);
        $category = new Category();
        $category->name = $request->name;
        $category->description = $request->description;
        $category->slug = Str::slug($request->name);
        $image = $request->file('image');
        $file_ext = $request->file('image')->extension();
        $file_name = Carbon::now()->timestamp . '.' . $file_ext;
        $this->GenerateCategoryThumbnailImage($image, $file_name);
        $category->image = $file_name;
        $category->save();
        return redirect()->route('admin.categories')->with('status', 'Category ' . $category->name . ' has been created successfully!!');
    }

    public function GenerateCategoryThumbnailImage($image, $imageName)
    {
        $destinationPath = public_path('uploads/categories');
        $img = Image::read($image->path());
        $img->cover(124, 124, "top");
        $img->resize(124, 124, function ($constraint) {
            $constraint->aspectRatio();
        })->save($destinationPath . '/' . $imageName);
    }

    public function delete_category($id)
    {
        $category = Category::find($id);
        if (File::exists(public_path('uploads/categories') . '/' . $category->image)) {
            File::delete(public_path('uploads/categories') . '/' . $category->image);
        }
        $category->delete();
        return redirect()->route('admin.categories')->with('status', 'Category with id ' . $category->id . '  has been deleted successfully');
    }

    

    public function promotions()
    {
        $promotions = Promotion::paginate(10);
        return view('admin.promotions', compact('promotions'));
    }
    
    public function products()
    {
        $products = Product::orderby('id', 'Desc')->paginate(10);
        return view('admin.products', compact('products'));
    }
    public function add_product()
    {
        // Selecting 'id', 'name', and 'price' columns from the brands table to get both name and price of each design
        $brands = Brand::select('id', 'name', 'price')->orderby('name')->get();
        
        // Fetching only 'id' and 'name' columns from the categories table for the category dropdown in the form
        $categories = Category::select('id', 'name')->orderby('name')->get();
    
        // Passing the retrieved brands and categories to the view so they can be used in the form
        return view('admin.product_add', compact('brands', 'categories'));
    }
    

    public function edit_product($id)
    {
        $product = Product::find($id);
        $brands = Brand::select('id', 'name')->orderby('name')->get();
        $categories = Category::select('id', 'name')->orderby('name')->get();
        return view('admin.product_edit', compact('product', 'brands', 'categories'));
    }

    public function view_product($id)
    {
        $product = Product::find($id);
        return view('admin.product_view', compact('product'));
    }

    public function update_product(Request $request)
{
    // Log::info('Update product request received', $request->all());

    $request->validate([
        'name' => 'required|max:100',
        'short_description' => 'required',
      
        'regular_price' => 'required|numeric',
        'sale_price' => 'nullable|numeric',
        
        'featured' => 'required|boolean',

        'brand_id' => 'required|exists:brands,id',
        
        'slug' => 'required|unique:products,slug,' . $request->id,
        'image' => 'nullable|mimes:png,jpg,jpeg,svg,heic|max:5500',
        'images' => 'nullable|array',
        'images.*' => 'mimes:jpeg,png,jpg,svg,heic|max:5500',
    ]);

    try {
        $product = Product::findOrFail($request->id);

        $product->name = $request->name;
        $product->slug = Str::slug($request->name);
        $product->short_description = $request->short_description;

        $product->regular_price = $request->regular_price;
        $product->sale_price = $request->sale_price;

        $product->featured = $request->featured;

        $product->brand_id = $request->brand_id;


        $current_timestamp = Carbon::now()->timestamp;

        if ($request->hasFile('image')) {
            if (File::exists(public_path('uploads/products/' . $product->image))) {
                File::delete(public_path('uploads/products/' . $product->image));
            }

            $image = $request->file('image');
            $imageName = $current_timestamp . '.' . $image->extension();
            $image->move(public_path('uploads/products'), $imageName);
            $product->image = $imageName;
        }

        if ($request->hasFile('images')) {
            if ($product->images) {
                foreach (explode(',', $product->images) as $oldImage) {
                    if (File::exists(public_path('uploads/products/' . $oldImage))) {
                        File::delete(public_path('uploads/products/' . $oldImage));
                    }
                }
            }

            $gallery_arr = [];
            foreach ($request->file('images') as $key => $file) {
                $gFileName = $current_timestamp . '-' . ($key + 1) . '.' . $file->extension();
                $file->move(public_path('uploads/products'), $gFileName);
                $gallery_arr[] = $gFileName;
            }
            $product->images = implode(',', $gallery_arr);
        }

        $product->save();

        // Log::info('Product updated successfully', ['product_id' => $product->id]);

        return redirect()->route('admin.products')->with('status', 'Product with id ' . $product->id . ' has been updated successfully!!');
    } catch (\Exception $e) {
        Log::error('Failed to update product: ' . $e->getMessage());

        return redirect()->back()->with('error', 'Failed to update product. Please try again.');
    }
}



    public function store_product(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'slug' => 'required|unique:products,slug',
            'short_description' => 'required',
            'image' => 'required|mimes:png,jpg,jpeg,svg,heic|max:5500',
            'regular_price' => 'required',
            'sale_price' => 'nullable',
            'featured' => 'required',
            'brand_id' => 'required',
        ]);
        // 'category_id'=> 'required',
        $product = new Product();
        $product->name = $request->name;
        $product->slug = Str::slug($request->name);
        $product->short_description = $request->short_description;
        $product->regular_price = $request->regular_price;
        $product->sale_price = $request->sale_price;
        $product->featured = $request->featured;
        $product->brand_id = $request->brand_id;

        // $brand = Brand::find($request->brand_id);
        // if ($brand) {
        //     $product->category_id = $brand->category_id; // Set the category_id from brand
        // }

        $current_timestamp = Carbon::now()->timestamp;

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = $current_timestamp . '.' . $image->extension();
            $this->GenerateProductThumbnailImage($image, $imageName);
            $product->image = $imageName;
        }

        $gallery_arr = array();
        $gallery_images = "";
        $counter = 1;

        if ($request->hasFile('images')) {
            $allowedFileExtension = ['jpeg', 'png', 'jpg', 'heic','svg'];
            $files = $request->file('images');
            foreach ($files as $file) {
                $gExtension = $file->getClientOriginalExtension();
                $gCheck = in_array($gExtension, $allowedFileExtension);
                if ($gCheck) {
                    $gFileName = $current_timestamp . '-' . $counter . '.' . $gExtension;
                    $this->GenerateProductThumbnailImage($file, $gFileName);
                    array_push($gallery_arr, $gFileName);
                    $counter = $counter + 1;
                }
            }
            $gallery_images = implode(',', $gallery_arr);
        }

        $product->images = $gallery_images;
        $product->save();
        return redirect()->route('admin.products')->with('status', 'Product ' . $product->name . ' has been created successfully!!');
    }

    public function GenerateProductThumbnailImage($image, $imageName)
    {
        $destinationPathThumbnail = public_path('uploads/products/thumbnails');
        $destinationPath = public_path('uploads/products');
        $img = Image::read($image->path());

        $img->cover(540, 689, "top");
        $img->resize(540, 689, function ($constraint) {
            $constraint->aspectRatio();
        })->save($destinationPath . '/' . $imageName);
        $img->resize(104, 104, function ($constraint) {
            $constraint->aspectRatio();
        })->save($destinationPathThumbnail . '/' . $imageName);
    }

    public function delete_product($id)
    {
        $product = Product::find($id);
        if (File::exists(public_path('uploads/products') . '/' . $product->image)) {
            File::delete(public_path('uploads/products') . '/' . $product->image);
        }
        if (File::exists(public_path('uploads/products/thumbnails') . '/' . $product->image)) {
            File::delete(public_path('uploads/products/thumbnails') . '/' . $product->image);
        }

        foreach (explode(',', $product->images) as $oFile) {
            if (File::exists(public_path('uploads/products') . '/' . $oFile)) {
                File::delete(public_path('uploads/products') . '/' . $oFile);
            }
            if (File::exists(public_path('uploads/products/thumbnails') . '/' . $oFile)) {
                File::delete(public_path('uploads/products/thumbnails') . '/' . $oFile);
            }
        }
        $product->delete();
        return redirect()->route('admin.products')->with('status', 'Product with id ' . $product->id . '  has been deleted successfully');
    }
}
