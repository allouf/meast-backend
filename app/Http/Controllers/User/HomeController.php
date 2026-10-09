<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Modules\Store\Http\Resources\User\CategoryResource;
use Modules\Store\Http\Resources\User\ProductListResource;
use Modules\Store\Models\Category;
use Modules\Store\Models\Product;

class HomeController extends Controller
{
    /**
     * Main home structure (links only)
     */
    public function index()
    {
        $data = [
            'carousel' => route('home.carousel'),
            'featured_products' => route('home.featured'),
            'banners' => route('home.banners'),
            'top_offers' => route('home.topOffers'),
            'popular_categories' => route('home.popularCategories'),
            'best_seller_categories' => route('home.bestSellerCategories'),
            'best_seller_products' => route('home.bestSellerProducts'),
            'unique_products' => route('home.uniqueProducts'),
            'suggested_products' => route('home.suggestedProducts'),
            'sponsored_categories' => route('home.sponsoredCategories'),
            'sponsored_products' => route('home.sponsoredProducts'),
            'discounted_products' => route('home.discountedProducts'),
            'pre_order' => route('home.preOrder'),
            'introducing_products' => route('home.introducingProducts'),
            'free_shipping_products' => route('home.freeShippingProducts'),
        ];
        return $this->successResponse(__('messages.success'), $data);
    }
    public function carousel()
    {
        $data = [
            [
                'id' => 1,
                'image' => asset('storage/products/mobile.png'),
                'title' => 'Xbox Consoles',
                'subtitle' => 'Save up to 50% on select Xbox games. Get 3 months of PC Game Pass for $2 USD.Save up to 50% on select Xbox games. Get 3 months of PC Game Pass for $2 USD.',
                'price' => 799.99,
                'link' => null
            ],
            [
                'id' => 2,
                'image' => asset('storage/products/mobile.png'),
                'title' => 'PlayStation Deals',
                'subtitle' => 'Grab the hottest PlayStation games...',
                'price' => 799.99,
                'link' => null
            ],
            [
                'id' => 3,
                'image' => asset('storage/products/mobile.png'),
                'title' => 'Laptop Sale',
                'subtitle' => 'High-performance laptops at unbeatable prices.',
                'price' => 799.99,
                'link' => null
            ],
            [
                'id' => 4,
                'image' => asset('storage/products/mobile.png'),
                'title' => 'Smartphones',
                'subtitle' => 'Latest smartphones with special launch offers!',
                'price' => 799.99,
                'link' => null
            ],
        ];
        return $this->successResponse(__('messages.success'), $data);
    }
    public function featured()
    {
        $data = [
            [
                'id' => 101,
                'name' => 'New Google Pixel 6 Pro',
                'price' => 799.99,
                'discount' => 25,
                'image' => asset('storage/products/MacBook.png'),
            ],
            [
                'id' => 102,
                'name' => 'Apple MacBook Air',
                'price' => 999.99,
                'discount' => 10,
                'image' => asset('storage/products/MacBook.png'),
            ],
            [
                'id' => 103,
                'name' => 'Sony WH-1000XM5 Headphones',
                'price' => 349.99,
                'discount' => null,
                'image' => asset('storage/products/MacBook.png'),
            ],
            [
                'id' => 104,
                'name' => 'Samsung Galaxy S23 Ultra',
                'price' => 1199.99,
                'discount' => 15,
                'image' => asset('storage/products/MacBook.png'),
            ],
        ];
        return $this->successResponse(__('messages.success'), $data);
    }

    public function banners()
    {
        $data = [
            ['id' => 201, 'title' => 'Holiday Sale', 'image' => asset('storage/products/Offer.png'), 'link' => '/sale'],
            ['id' => 202, 'title' => 'New Arrivals', 'image' => asset('storage/products/Offer.png'), 'link' => '/new-arrivals'],
            ['id' => 203, 'title' => 'Limited Time Offer', 'image' => asset('storage/products/Offer.png'), 'link' => '/offers'],
        ];
        return $this->successResponse(data: $data);
    }

    public function topOffers()
    {
        $data = ProductListResource::collection(
            Product::with(['gallery', 'category', 'store'])
                ->inRandomOrder()
                ->take(4)
                ->get()
        );
        return $this->successResponse(__('messages.success'), $data);
    }
    public function popularCategories()
    {
        $data = CategoryResource::collection(
            Category::whereNull('parent_id')->get()
        );
        return $this->successResponse(__('messages.success'), $data);
    }
    public function bestSellerCategories()
    {
        $data = CategoryResource::collection(
            Category::inRandomOrder()->take(5)->get()
        );
        return $this->successResponse(__('messages.success'), $data);
    }
    public function bestSellerProducts()
    {
        $data = ProductListResource::collection(
            Product::with(['gallery', 'category', 'store'])
                ->inRandomOrder()
                ->take(4)
                ->get()
        );
        return $this->successResponse(__('messages.success'), $data);
    }
    public function uniqueProducts()
    {
        $data = [
            'featured' => new ProductListResource(Product::first()),
            'carousel' => ProductListResource::collection(
                Product::with(['gallery', 'category', 'store'])
                    ->inRandomOrder()
                    ->take(4)
                    ->get()
            )
        ];
        return $this->successResponse(__('messages.success'), $data);
    }
    public function suggestedProducts()
    {
        $data = ProductListResource::collection(
            Product::with(['gallery', 'category', 'store'])
                ->inRandomOrder()
                ->take(8)
                ->get()
        );
        return $this->successResponse(__('messages.success'), $data);
    }
    public function sponsoredCategories()
    {
        $data = CategoryResource::collection(
            Category::inRandomOrder()->take(4)->get()
        );
        return $this->successResponse(__('messages.success'), $data);
    }
    public function sponsoredProducts()
    {
        $data = ProductListResource::collection(
            Product::with(['gallery', 'category', 'store'])
                ->inRandomOrder()
                ->take(6)
                ->get()
        );
        return $this->successResponse(__('messages.success'), $data);
    }
    public function discountedProducts()
    {
        $data = ProductListResource::collection(
            Product::with(['gallery', 'category', 'store'])
                ->inRandomOrder()
                ->take(7)
                ->get()
        );
        return $this->successResponse(__('messages.success'), $data);
    }
    public function preOrder()
    {
        $data = [
            'title' => 'Be the first to own',
            'price' => 399,
            'product_name' => 'Opplo Watch Sport Series 8',
            'product_description' => 'A healthy leap ahead',
            'link' => null,
            'image' => asset('storage/products/smart.png'),
        ];
        return $this->successResponse(__('messages.success'), $data);
    }
    public function introducingProducts()
    {
        $data = [
            [
                'title' => 'New Apple Homepod Mini',
                'description' => 'Jam-packed with innovation...',
                'image' => asset('storage/products/MacBook.png')
            ],
            [
                'title' => 'Samsung Galaxy Buds Pro',
                'description' => 'Experience immersive sound...',
                'image' => asset('storage/products/mobile1.png')
            ]
        ];
        return $this->successResponse(__('messages.success'), $data);
    }
    public function freeShippingProducts()
    {
        $data = ProductListResource::collection(
            Product::with(['gallery', 'category', 'store'])
                ->inRandomOrder()
                ->take(7)
                ->get()
        );
        return $this->successResponse(__('messages.success'), $data);
    }
}
