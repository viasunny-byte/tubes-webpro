<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Image;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;

class AdminController extends Controller
{
    /* ============================
       DASHBOARD
    ============================ */
    public function index()
    {
        return view('admin.index');
    }

    /* ============================
       BRANDS
    ============================ */
    public function brands()
    {
        $brands = Brand::orderBy('id', 'DESC')->paginate(10);
        return view('admin.brands', compact('brands'));
    }

    public function add_brand()
    {
        return view('admin.brand-add');
    }

    public function brand_store(Request $request)
    {
        $request->validate([
            'name'  => 'required',
            'slug'  => 'required|unique:brands,slug',
            'image' => 'mimes:png,jpg,jpeg|max:2048'
        ]);

        $brand = new Brand();
        $brand->name = $request->name;
        $brand->slug = Str::slug($request->name);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $fileName = time() . '.' . $file->extension();
            $this->GenerateBrandThumbnail($file, $fileName);
            $brand->image = $fileName;
        }

        $brand->save();
        return redirect()->route('admin.brands')->with('status', 'Brand added successfully!');
    }

    public function brand_edit($id)
    {
        $brand = Brand::findOrFail($id);
        return view('admin.brand-edit', compact('brand'));
    }

    public function brand_update(Request $request)
    {
        $request->validate([
            'name'  => 'required',
            'slug'  => 'required|unique:brands,slug,' . $request->id,
            'image' => 'mimes:png,jpg,jpeg|max:2048'
        ]);

        $brand = Brand::findOrFail($request->id);
        $brand->name = $request->name;
        $brand->slug = Str::slug($request->name);

        if ($request->hasFile('image')) {

            if ($brand->image && File::exists(public_path('uploads/brands/' . $brand->image))) {
                File::delete(public_path('uploads/brands/' . $brand->image));
            }

            $file = $request->file('image');
            $fileName = time() . '.' . $file->extension();
            $this->GenerateBrandThumbnail($file, $fileName);

            $brand->image = $fileName;
        }

        $brand->save();
        return redirect()->route('admin.brands')->with('status', 'Brand updated successfully!');
    }

    public function brand_delete($id)
    {
        $brand = Brand::findOrFail($id);

        if ($brand->image && File::exists(public_path('uploads/brands/' . $brand->image))) {
            File::delete(public_path('uploads/brands/' . $brand->image));
        }

        $brand->delete();
        return redirect()->route('admin.brands')->with('status', 'Brand deleted successfully!');
    }

    private function GenerateBrandThumbnail($image, $name)
    {
        $path = public_path('uploads/brands');

        if (!File::exists($path)) {
            File::makeDirectory($path, 0777, true);
        }

        Image::read($image->path())
            ->resize(124, 124)
            ->save($path . '/' . $name);
    }

    /* ============================
       CATEGORIES
    ============================ */
    public function categories()
    {
        $categories = Category::orderBy('id', 'DESC')->paginate(10);
        return view('admin.categories', compact('categories'));
    }

    public function category_add()
    {
        return view('admin.category-add');
    }

    public function category_store(Request $request)
    {
        $request->validate([
            'name'  => 'required',
            'slug'  => 'required|unique:categories,slug',
            'image' => 'mimes:png,jpg,jpeg|max:2048'
        ]);

        $category = new Category();
        $category->name = $request->name;
        $category->slug = Str::slug($request->name);

        if ($request->hasFile('image')) {
            $fileName = time() . '.' . $request->file('image')->extension();
            $this->GenerateCategoryThumbnail($request->file('image'), $fileName);
            $category->image = $fileName;
        }

        $category->save();
        return redirect()->route('admin.categories')->with('status', 'Category added successfully!');
    }

    private function GenerateCategoryThumbnail($image, $name)
    {
        $path = public_path('uploads/categories');

        if (!File::exists($path)) {
            File::makeDirectory($path, 0777, true);
        }

        Image::read($image->path())
            ->resize(124, 124)
            ->save($path . '/' . $name);
    }

    public function category_edit($id)
    {
        $category = Category::findOrFail($id);
        return view('admin.category-edit', compact('category'));
    }

    public function category_update(Request $request)
    {
        $request->validate([
            'name'  => 'required',
            'slug'  => 'required|unique:categories,slug,' . $request->id,
            'image' => 'mimes:png,jpg,jpeg|max:2048'
        ]);

        $category = Category::findOrFail($request->id);
        $category->name = $request->name;
        $category->slug = Str::slug($request->name);

        if ($request->hasFile('image')) {

            if ($category->image && File::exists(public_path('uploads/categories/' . $category->image))) {
                File::delete(public_path('uploads/categories/' . $category->image));
            }

            $fileName = time() . '.' . $request->file('image')->extension();
            $this->GenerateCategoryThumbnail($request->file('image'), $fileName);

            $category->image = $fileName;
        }

        $category->save();
        return redirect()->route('admin.categories')->with('status', 'Category updated successfully!');
    }

    public function category_delete($id)
    {
        $category = Category::findOrFail($id);

        if ($category->image && File::exists(public_path('uploads/categories/' . $category->image))) {
            File::delete(public_path('uploads/categories/' . $category->image));
        }

        $category->delete();
        return redirect()->route('admin.categories')->with('status', 'Category deleted successfully!');
    }

    /* ============================
       PRODUCTS
    ============================ */
    public function products()
    {
        $products = Product::orderBy('created_at', 'DESC')->paginate(10);
        return view('admin.products', compact('products'));
    }

    public function product_add()
    {
        $categories = Category::select('id', 'name')->orderBy('name')->get();
        $brands     = Brand::select('id', 'name')->orderBy('name')->get();

        return view('admin.product-add', compact('categories', 'brands'));
    }

    public function product_store(Request $request)
    {
        $request->validate([
            'name'              => 'required',
            'slug'              => 'required|unique:products,slug',
            'short_description' => 'required',
            'description'       => 'required',
            'regular_price'     => 'required',
            'sale_price'        => 'required',
            'SKU'               => 'required',
            'stock_status'      => 'required',
            'featured'          => 'required',
            'quantity'          => 'required',
            'image'             => 'required|mimes:png,jpg,jpeg|max:2048',
            'category_id'       => 'required',
            'brand_id'          => 'required',
        ]);

        $product = new Product();
        $product->name              = $request->name;
        $product->slug              = Str::slug($request->name);
        $product->short_description = $request->short_description;
        $product->description       = $request->description;
        $product->regular_price     = $request->regular_price;
        $product->sale_price        = $request->sale_price;
        $product->SKU               = $request->SKU;
        $product->stock_status      = $request->stock_status;
        $product->featured          = $request->featured;
        $product->quantity          = $request->quantity;
        $product->category_id       = $request->category_id;
        $product->brand_id          = $request->brand_id;

        /* ---- Main Image ---- */
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $imageName = time() . '.' . $file->extension();
            $this->GenerateProductThumbnail($file, $imageName);
            $product->image = $imageName;
        }

        /* ---- Gallery ---- */
        $gallery = [];

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $ext = $file->getClientOriginalExtension();
                if (!in_array($ext, ['jpg', 'jpeg', 'png'])) continue;

                $fileName = uniqid() . '.' . $ext;

                $this->GenerateProductThumbnail($file, $fileName);
                $file->move(public_path('uploads/products/gallery'), $fileName);

                $gallery[] = $fileName;
            }
        }

        $product->images = json_encode($gallery);
        $product->save();

        return redirect()->route('admin.products')->with('status', 'Product added successfully!');
    }

    private function GenerateProductThumbnail($image, $name)
    {
        $main = public_path('uploads/products');
        $thumb = public_path('uploads/products/thumbnails');

        if (!File::exists($main))  File::makeDirectory($main, 0777, true);
        if (!File::exists($thumb)) File::makeDirectory($thumb, 0777, true);

        $img = Image::read($image->path());

        // Main
        $img->cover(540, 689, 'top')
            ->resize(540, 689)
            ->save($main . '/' . $name);

        // Thumbnail
        $img->resize(104, 104)
            ->save($thumb . '/' . $name);
    }

    public function product_edit($id)
    {
        $product    = Product::findOrFail($id);
        $categories = Category::orderBy('name')->get();
        $brands     = Brand::orderBy('name')->get();

        return view('admin.product-edit', compact('product', 'categories', 'brands'));
    }

    public function product_update(Request $request)
    {
        $request->validate([
            'name'              => 'required',
            'slug'              => 'required|unique:products,slug,' . $request->id,
            'short_description' => 'required',
            'description'       => 'required',
            'regular_price'     => 'required',
            'sale_price'        => 'required',
            'SKU'               => 'required',
            'stock_status'      => 'required',
            'featured'          => 'required',
            'quantity'          => 'required',
            'image'             => 'nullable|mimes:png,jpg,jpeg|max:2048',
            'category_id'       => 'required',
            'brand_id'          => 'required'
        ]);

        $product = Product::findOrFail($request->id);

        /* ---- Update basic fields ---- */
        $product->update([
            'name'              => $request->name,
            'slug'              => Str::slug($request->name),
            'short_description' => $request->short_description,
            'description'       => $request->description,
            'regular_price'     => $request->regular_price,
            'sale_price'        => $request->sale_price,
            'SKU'               => $request->SKU,
            'stock_status'      => $request->stock_status,
            'featured'          => $request->featured,
            'quantity'          => $request->quantity,
            'category_id'       => $request->category_id,
            'brand_id'          => $request->brand_id,
        ]);

        /* ---- Main image update ---- */
        if ($request->hasFile('image')) {

            if ($product->image) {
                File::delete(public_path('uploads/products/' . $product->image));
                File::delete(public_path('uploads/products/thumbnails/' . $product->image));
            }

            $file = $request->file('image');
            $imageName = time() . '.' . $file->extension();

            $this->GenerateProductThumbnail($file, $imageName);

            $file->move(public_path('uploads/products'), $imageName);

            $product->image = $imageName;
        }

        /* ---- Update gallery ---- */
        if ($request->hasFile('images')) {

            $oldGallery = json_decode($product->images, true) ?? [];

            foreach ($oldGallery as $old) {
                File::delete(public_path('uploads/products/gallery/' . $old));
                File::delete(public_path('uploads/products/thumbnails/' . $old));
            }

            $newGallery = [];

            foreach ($request->file('images') as $file) {
                $ext = $file->getClientOriginalExtension();
                if (!in_array($ext, ['jpg', 'jpeg', 'png'])) continue;

                $fileName = uniqid() . '.' . $ext;

                $this->GenerateProductThumbnail($file, $fileName);

                $file->move(public_path('uploads/products/gallery'), $fileName);

                copy(
                    public_path('uploads/products/gallery/' . $fileName),
                    public_path('uploads/products/thumbnails/' . $fileName)
                );

                $newGallery[] = $fileName;
            }

            $product->images = json_encode($newGallery);
        }

        $product->save();

        return redirect()->route('admin.products')->with('status', 'Product updated successfully!');
    }

    public function product_delete($id)
    {
        $product = Product::find($id);

        if (File::exists(public_path('uploads/products/').'/'. $product->image)) {
            File::delete(public_path('uploads/products/').'/'. $product->image);
        }
        if (File::exists(public_path('uploads/products/thumbnails/').'/'. $product->image)) {
            File::delete(public_path('uploads/products/thumbnails/').'/'. $product->image);
        }

        foreach (explode(',', $product->images) as $file) {
            if (File::exists(public_path('uploads/products/').'/'. $file)) {
                File::delete(public_path('uploads/products/').'/'. $file);
            }
            if (File::exists(public_path('uploads/products/thumbnails/').'/'. $file)) {
                File::delete(public_path('uploads/products/thumbnails/').'/'. $file);
            }
        }

        $product->delete();
        return redirect()->route('admin.products')->with('status', 'Product has been deleted successfully!');
    }
}
