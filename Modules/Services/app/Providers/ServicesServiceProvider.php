<?php

namespace Modules\Services\Providers;

use Modules\Services\Repositories\Interfaces\DisputeReasonRepositoryInterface;
use Modules\Services\Repositories\Eloquent\DisputeReasonRepository;

use Modules\Services\Repositories\Interfaces\BoostCampaignLogRepositoryInterface;
use Modules\Services\Repositories\Eloquent\BoostCampaignLogRepository;

use Modules\Services\Repositories\Interfaces\BoostCampaignRepositoryInterface;
use Modules\Services\Repositories\Eloquent\BoostCampaignRepository;

use Modules\Services\Repositories\Interfaces\BoostPlanRepositoryInterface;
use Modules\Services\Repositories\Eloquent\BoostPlanRepository;

use Modules\Services\Repositories\Interfaces\DisputeRepositoryInterface;
use Modules\Services\Repositories\Eloquent\DisputeRepository;

use Modules\Services\Repositories\Interfaces\ServiceOfferOrderExtensionReasonRepositoryInterface;
use Modules\Services\Repositories\Eloquent\ServiceOfferOrderExtensionReasonRepository;

use Modules\Services\Repositories\Interfaces\ServiceOfferOrderRejectionReasonRepositoryInterface;
use Modules\Services\Repositories\Eloquent\ServiceOfferOrderRejectionReasonRepository;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Services\Enums\OrderableType;
use Modules\Services\Listeners\MarkServiceOrderPaidOnInvoiceCompletion;
use Modules\Services\Listeners\MarkServiceOrderPhasePaidOnInvoiceCompletion;
use Modules\Services\Models\Milestone;
use Modules\Services\Models\MilestoneService;
use Modules\Services\Models\ServiceOffer;
use Modules\Services\Models\ServiceOfferReview;
use Modules\Services\Observers\MilestoneServiceObserver;
use Modules\Services\Observers\ServiceOfferObserver;
use Modules\Services\Observers\ServiceOfferReviewObserver;
use Modules\Services\Repositories\Eloquent\MilestoneRepository;
use Modules\Services\Repositories\Eloquent\MilestoneServiceRepository;
use Modules\Wallet\Events\PaymentSucceeded;
use Modules\Services\Repositories\Eloquent\CustomRequestAttachmentRepository;
use Modules\Services\Repositories\Eloquent\CustomRequestCategoryRepository;
use Modules\Services\Repositories\Eloquent\CustomRequestOrderRepository;
use Modules\Services\Repositories\Eloquent\CustomRequestQuoteRepository;
use Modules\Services\Repositories\Eloquent\CustomRequestRepository;
use Modules\Services\Repositories\Eloquent\ProviderProjectRepository;
use Modules\Services\Repositories\Eloquent\ServiceCategoryRepository;
use Modules\Services\Repositories\Eloquent\ServiceOfferOrderRepository;
use Modules\Services\Repositories\Eloquent\ServiceOfferRepository;
use Modules\Services\Repositories\Eloquent\ServiceOfferReviewRepository;
use Modules\Services\Repositories\Eloquent\ServiceProviderRepository;
use Modules\Services\Repositories\Interfaces\MilestoneRepositoryInterface;
use Modules\Services\Repositories\Interfaces\MilestoneServiceRepositoryInterface;
use Modules\Services\Repositories\Interfaces\CustomRequestAttachmentRepositoryInterface;
use Modules\Services\Repositories\Interfaces\CustomRequestCategoryRepositoryInterface;
use Modules\Services\Repositories\Interfaces\CustomRequestOrderRepositoryInterface;
use Modules\Services\Repositories\Interfaces\CustomRequestQuoteRepositoryInterface;
use Modules\Services\Repositories\Interfaces\CustomRequestRepositoryInterface;
use Modules\Services\Repositories\Interfaces\ProviderProjectRepositoryInterface;
use Modules\Services\Repositories\Interfaces\ServiceCategoryRepositoryInterface;
use Modules\Services\Repositories\Interfaces\ServiceOfferOrderRepositoryInterface;
use Modules\Services\Repositories\Interfaces\ServiceOfferRepositoryInterface;
use Modules\Services\Repositories\Interfaces\ServiceOfferReviewRepositoryInterface;
use Modules\Services\Repositories\Interfaces\ServiceProviderRepositoryInterface;
use Nwidart\Modules\Traits\PathNamespace;

class ServicesServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Services';

    protected string $nameLower = 'services';

    public function register(): void
    {




        
        $this->app->bind(DisputeReasonRepositoryInterface::class, DisputeReasonRepository::class);
        $this->app->bind(BoostCampaignLogRepositoryInterface::class, BoostCampaignLogRepository::class);
        $this->app->bind(BoostCampaignRepositoryInterface::class, BoostCampaignRepository::class);
        $this->app->bind(BoostPlanRepositoryInterface::class, BoostPlanRepository::class);
        $this->app->bind(DisputeRepositoryInterface::class, DisputeRepository::class);
        $this->app->bind(ServiceOfferOrderExtensionReasonRepositoryInterface::class, ServiceOfferOrderExtensionReasonRepository::class);
        $this->app->bind(ServiceOfferOrderRejectionReasonRepositoryInterface::class, ServiceOfferOrderRejectionReasonRepository::class);
        $this->app->bind(ServiceOfferOrderRepositoryInterface::class, ServiceOfferOrderRepository::class);
        $this->app->bind(CustomRequestCategoryRepositoryInterface::class, CustomRequestCategoryRepository::class);
        $this->app->bind(CustomRequestRepositoryInterface::class, CustomRequestRepository::class);
        $this->app->bind(CustomRequestQuoteRepositoryInterface::class, CustomRequestQuoteRepository::class);
        $this->app->bind(MilestoneRepositoryInterface::class, MilestoneRepository::class);
        $this->app->bind(MilestoneServiceRepositoryInterface::class, MilestoneServiceRepository::class);
        $this->app->bind(CustomRequestAttachmentRepositoryInterface::class, CustomRequestAttachmentRepository::class);
        $this->app->bind(CustomRequestOrderRepositoryInterface::class, CustomRequestOrderRepository::class);
        $this->app->bind(ServiceProviderRepositoryInterface::class, ServiceProviderRepository::class);
        $this->app->bind(ProviderProjectRepositoryInterface::class, ProviderProjectRepository::class);
        $this->app->bind(ServiceCategoryRepositoryInterface::class, ServiceCategoryRepository::class);
        $this->app->bind(ServiceOfferRepositoryInterface::class, ServiceOfferRepository::class);
        $this->app->bind(ServiceOfferReviewRepositoryInterface::class, ServiceOfferReviewRepository::class);
        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        Relation::morphMap([
            OrderableType::ServiceOffer->value => ServiceOffer::class,
            OrderableType::MilestoneTemplate->value => Milestone::class,
        ]);

        $this->registerTranslations();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        MilestoneService::observe(MilestoneServiceObserver::class);
        ServiceOffer::observe(ServiceOfferObserver::class);
        ServiceOfferReview::observe(ServiceOfferReviewObserver::class);

        // Wallet's PaymentSucceeded fan-out for the two referenceable shapes
        // this module owns. Each listener filters by instanceof so unrelated
        // invoices (wallet top-ups, ads, etc.) are no-ops.
        //   - Single-offer order: transition order PendingPayment → Pending
        //     and write the order-level escrow row.
        //   - Milestone phase: write the phase-level escrow row and bump
        //     parent order to InProgress on first phase payment.
        Event::listen(PaymentSucceeded::class, MarkServiceOrderPaidOnInvoiceCompletion::class);
        Event::listen(PaymentSucceeded::class, MarkServiceOrderPhasePaidOnInvoiceCompletion::class);
    }

    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/' . $this->nameLower);
        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }
}
