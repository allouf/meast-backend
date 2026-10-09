<?php

use App\Http\Controllers\MigrateDataController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    return view('blank');
});
Route::get('optimize-app', function () {
    Artisan::call('optimize:clear');
    return 'app optimized successfult !';
});
Route::get('storage-link', function () {
    Artisan::call('storage:link');
    return 'Storage link created successfully!';
});

Route::get('migrate-countries', [MigrateDataController::class, 'migrateCountries']);


Route::get('store/invitation/accept/{token}', [\Modules\Store\Http\Controllers\Seller\InvitationController::class, 'acceptInvitationWeb'])->name('store.invitation.accept');


Route::get('account/delete', function () {
    return view('account.delete', [
        'frontendUrl' => config('app.frontend_url', 'https://uat.middleeastcommerce.net')
    ]);
})->name('account.delete.page');

Route::post('account/delete', [\Modules\Core\Http\Controllers\User\Account\UserController::class, 'deleteAccountWeb'])->name('account.delete');


if (config('app.debug')) {
    Route::get('email-preview', [\App\Http\Controllers\EmailPreviewController::class, 'index'])->name('email.preview.index');
    Route::get('email-preview/{template}', [\App\Http\Controllers\EmailPreviewController::class, 'preview'])->name('email.preview.show');
    Route::post('email-preview/{template}/send', [\App\Http\Controllers\EmailPreviewController::class, 'sendTest'])->name('email.preview.send');
}

Route::get('/test-elastic', function () {
    $client = app('Elasticsearch');

    $info = $client->info();
    return $info;
});

Route::get('/elastic-ping', function (\App\Library\Elasticsearch $es) {
    return ['ping' => $es->ping()];
});


if (config('app.debug')) {
    Route::get('/elastic-recreate-products-index', function () {
        $productES = new \Modules\Store\Library\Elasticsearch\ProductElasticsearch();
        $result = $productES->recreateProductIndex();
        $products = \Modules\Store\Models\Product::all();
        foreach ($products as $product) {
            $productES->indexProduct($product);
        }
        return [
            'success' => true,
            'message' => 'Products index recreated and re-indexed successfully',
            'products_count' => $products->count()
        ];
    });

    Route::get('/elastic-recreate-stores-index', function () {
        $storeES = new \Modules\Store\Library\Elasticsearch\StoreElasticsearch();
        $result = $storeES->recreateStoreIndex();
        $stores = \Modules\Store\Models\Store::all();
        foreach ($stores as $store) {
            $storeES->indexStore($store);
        }
        return [
            'success' => true,
            'message' => 'Stores index recreated and re-indexed successfully',
            'stores_count' => $stores->count()
        ];
    });

    Route::get('/elastic-recreate-advertisements-index', function () {
        $advertisementES = new \Modules\Advertisement\Library\AdvertisementElasticsearch();
        $advertisementES->recreateAdvertisementIndex();

        $advertisements = \Modules\Advertisement\Models\Advertisement::all();
        foreach ($advertisements as $advertisement) {
            $advertisementES->indexAdvertisement($advertisement);
        }

        return [
            'success' => true,
            'message' => 'Advertisements index recreated and re-indexed successfully',
            'advertisements_count' => $advertisements->count()
        ];
    });

    Route::get('/elastic-recreate-jobs-index', function () {
        $jobES = new \Modules\Job\Library\JobElasticsearch();
        $jobES->recreateJobIndex();

        $jobs = \Modules\Job\Models\Job::all();
        foreach ($jobs as $job) {
            $jobES->indexJob($job);
        }

        return [
            'success' => true,
            'message' => 'Job postings index recreated and re-indexed successfully',
            'jobs_count' => $jobs->count()
        ];
    });
}
