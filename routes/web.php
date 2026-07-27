<?php

use App\Http\Controllers\Web\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Web\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Web\Admin\CityController as AdminCityController;
use App\Http\Controllers\Web\Admin\InvitationDesignController as AdminInvitationDesignController;
use App\Http\Controllers\Web\Admin\CommissionController as AdminCommissionController;
use App\Http\Controllers\Web\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Web\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Web\Admin\EventController as AdminEventController;
use App\Http\Controllers\Web\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Web\Admin\OrganizerController as AdminOrganizerController;
use App\Http\Controllers\Web\Admin\OrganizerPackageController as AdminOrganizerPackageController;
use App\Http\Controllers\Web\Admin\PayoutController as AdminPayoutController;
use App\Http\Controllers\Web\Admin\RevenueReportController as AdminRevenueReportController;
use App\Http\Controllers\Web\AuthController as CustomerAuthController;
use App\Http\Controllers\Web\CheckoutController;
use App\Http\Controllers\Web\EventController;
use App\Http\Controllers\Web\Organizer\AuthController as OrganizerAuthController;
use App\Http\Controllers\Web\Organizer\DashboardController as OrganizerDashboardController;
use App\Http\Controllers\Web\Organizer\EarningsController as OrganizerEarningsController;
use App\Http\Controllers\Web\InvitationController;
use App\Http\Controllers\Web\Organizer\EventController as OrganizerEventController;
use App\Http\Controllers\Web\OrganizerLandingController;
use App\Http\Controllers\Web\CreateTicketLandingController;
use App\Http\Controllers\Web\OtpController as WebOtpController;
use App\Http\Controllers\Web\PrivateEventController;
use App\Http\Controllers\Web\PrivateEventInvitationController;
use App\Http\Controllers\Web\TicketController;
use Illuminate\Support\Facades\Route;

Route::get('/', [EventController::class, 'home'])->name('home');
Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/{slug}', [EventController::class, 'show'])->name('events.show');
Route::get('/organizers', OrganizerLandingController::class)->name('organizers');
Route::get('/create-ticket', CreateTicketLandingController::class)->name('create-ticket');

Route::get('/events/{slug}/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/events/{slug}/checkout', [CheckoutController::class, 'store'])->middleware('throttle:checkout')->name('checkout.store');
Route::get('/orders/{orderNumber}/confirmation', [CheckoutController::class, 'confirmation'])->name('checkout.confirmation');
Route::get('/orders/{orderNumber}/failed', [CheckoutController::class, 'failed'])->name('checkout.failed');

Route::get('/my-tickets', [TicketController::class, 'index'])->middleware('throttle:tickets')->name('tickets.index');
Route::get('/t/{code}', [TicketController::class, 'show'])->middleware('throttle:tickets')->name('tickets.show');
Route::get('/t/{code}/pdf', [TicketController::class, 'pdf'])->middleware('throttle:tickets')->name('tickets.pdf');
Route::get('/i/{token}', [InvitationController::class, 'show'])->middleware('throttle:tickets')->name('invitations.show');

Route::middleware('throttle:auth')->group(function () {
    Route::post('/otp/send', [WebOtpController::class, 'send'])->name('otp.send');
    Route::post('/otp/verify', [WebOtpController::class, 'verify'])->name('otp.verify');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [CustomerAuthController::class, 'showLogin'])->name('customer.login');
    Route::post('/login', [CustomerAuthController::class, 'login'])->middleware('throttle:auth');
    Route::get('/register', [CustomerAuthController::class, 'showRegister'])->name('customer.register');
    Route::post('/register', [CustomerAuthController::class, 'register'])->middleware('throttle:auth');
});

Route::post('/logout', [CustomerAuthController::class, 'logout'])
    ->middleware('auth')
    ->name('customer.logout');

Route::middleware(['auth'])->prefix('private-events')->name('private-events.')->group(function () {
    Route::get('/', [PrivateEventController::class, 'index'])->name('index');
    Route::get('/create', [PrivateEventController::class, 'create'])->name('create');
    Route::post('/', [PrivateEventController::class, 'store'])->name('store');
    Route::get('/{event}', [PrivateEventController::class, 'show'])->name('show');
    Route::get('/{event}/pay', [PrivateEventController::class, 'payForm'])->name('pay');
    Route::post('/{event}/pay', [PrivateEventController::class, 'pay'])->middleware('throttle:checkout')->name('pay.store');
    Route::get('/{event}/add-tickets', [PrivateEventController::class, 'addCapacityForm'])->name('capacity.create');
    Route::post('/{event}/add-tickets', [PrivateEventController::class, 'addCapacity'])->name('capacity.store');

    Route::get('/{event}/invitations', [PrivateEventInvitationController::class, 'index'])->name('invitations.index');
    Route::get('/{event}/invitations/create', [PrivateEventInvitationController::class, 'create'])->name('invitations.create');
    Route::post('/{event}/invitations', [PrivateEventInvitationController::class, 'store'])->name('invitations.store');
    Route::post('/{event}/invitations/{invitation}/resend', [PrivateEventInvitationController::class, 'resend'])->name('invitations.resend');
    Route::post('/{event}/invitations/{invitation}/phone', [PrivateEventInvitationController::class, 'updatePhone'])->name('invitations.phone');
    Route::post('/{event}/invitations/{invitation}/revoke', [PrivateEventInvitationController::class, 'revoke'])->name('invitations.revoke');
});

Route::prefix('organizer')->name('organizer.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [OrganizerAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [OrganizerAuthController::class, 'login'])->middleware('throttle:auth');
        Route::get('/register', [OrganizerAuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [OrganizerAuthController::class, 'register'])->middleware('throttle:auth');
    });

    Route::middleware(['auth', 'role:organizer,admin'])->group(function () {
        Route::post('/logout', [OrganizerAuthController::class, 'logout'])->name('logout');
        Route::get('/', OrganizerDashboardController::class)->name('dashboard');
        Route::get('/earnings', OrganizerEarningsController::class)->name('earnings');

        Route::middleware('organizer.approved')->group(function () {
            Route::get('/events', [OrganizerEventController::class, 'index'])->name('events.index');
            Route::get('/events/create', [OrganizerEventController::class, 'create'])->name('events.create');
            Route::post('/events', [OrganizerEventController::class, 'store'])->name('events.store');
            Route::get('/events/{event}/edit', [OrganizerEventController::class, 'edit'])->name('events.edit');
            Route::put('/events/{event}', [OrganizerEventController::class, 'update'])->name('events.update');
            Route::delete('/events/{event}', [OrganizerEventController::class, 'destroy'])->name('events.destroy');
        });
    });
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AdminAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:auth');
    });

    Route::middleware(['auth', 'role:admin'])->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        Route::get('/organizers', [AdminOrganizerController::class, 'index'])->name('organizers.index');
        Route::get('/organizers/{organizer}', [AdminOrganizerController::class, 'show'])->name('organizers.show');
        Route::post('/organizers/{organizer}/approve', [AdminOrganizerController::class, 'approve'])->name('organizers.approve');
        Route::post('/organizers/{organizer}/reject', [AdminOrganizerController::class, 'reject'])->name('organizers.reject');
        Route::post('/organizers/{organizer}/package', [AdminOrganizerController::class, 'updatePackage'])->name('organizers.package');
        Route::post('/organizers/{organizer}/commission', [AdminOrganizerController::class, 'updateCommission'])->name('organizers.commission');

        Route::get('/customers', [AdminCustomerController::class, 'index'])->name('customers.index');

        Route::get('/events', [AdminEventController::class, 'index'])->name('events.index');
        Route::post('/events/{event}/approve', [AdminEventController::class, 'approve'])->name('events.approve');
        Route::post('/events/{event}/reject', [AdminEventController::class, 'reject'])->name('events.reject');
        Route::post('/events/{event}/feature', [AdminEventController::class, 'toggleFeatured'])->name('events.feature');
        Route::post('/events/{event}/status', [AdminEventController::class, 'updateStatus'])->name('events.status');

        Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
        Route::post('/categories/{category}/toggle', [AdminCategoryController::class, 'toggle'])->name('categories.toggle');
        Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/cities', [AdminCityController::class, 'index'])->name('cities.index');
        Route::post('/cities', [AdminCityController::class, 'store'])->name('cities.store');
        Route::put('/cities/{city}', [AdminCityController::class, 'update'])->name('cities.update');
        Route::post('/cities/{city}/toggle', [AdminCityController::class, 'toggle'])->name('cities.toggle');
        Route::delete('/cities/{city}', [AdminCityController::class, 'destroy'])->name('cities.destroy');

        Route::any('/private-event-categories/{any?}', fn () => redirect()->route('admin.categories.index'))
            ->where('any', '.*')
            ->name('private-event-categories.index');
        Route::get('/invitation-designs', [AdminInvitationDesignController::class, 'index'])->name('invitation-designs.index');
        Route::get('/invitation-designs/create', [AdminInvitationDesignController::class, 'create'])->name('invitation-designs.create');
        Route::post('/invitation-designs', [AdminInvitationDesignController::class, 'store'])->name('invitation-designs.store');
        Route::get('/invitation-designs/{invitationDesign}/edit', [AdminInvitationDesignController::class, 'edit'])->name('invitation-designs.edit');
        Route::put('/invitation-designs/{invitationDesign}', [AdminInvitationDesignController::class, 'update'])->name('invitation-designs.update');
        Route::post('/invitation-designs/{invitationDesign}/toggle', [AdminInvitationDesignController::class, 'toggle'])->name('invitation-designs.toggle');
        Route::delete('/invitation-designs/{invitationDesign}', [AdminInvitationDesignController::class, 'destroy'])->name('invitation-designs.destroy');
        Route::post('/invitation-designs/{invitationDesign}/fields', [AdminInvitationDesignController::class, 'storeField'])->name('invitation-designs.fields.store');
        Route::put('/invitation-designs/{invitationDesign}/fields/{field}', [AdminInvitationDesignController::class, 'updateField'])->name('invitation-designs.fields.update');
        Route::delete('/invitation-designs/{invitationDesign}/fields/{field}', [AdminInvitationDesignController::class, 'destroyField'])->name('invitation-designs.fields.destroy');

        Route::get('/packages', [AdminOrganizerPackageController::class, 'index'])->name('packages.index');
        Route::post('/packages', [AdminOrganizerPackageController::class, 'store'])->name('packages.store');
        Route::put('/packages/{package}', [AdminOrganizerPackageController::class, 'update'])->name('packages.update');
        Route::post('/packages/{package}/toggle', [AdminOrganizerPackageController::class, 'toggle'])->name('packages.toggle');
        Route::delete('/packages/{package}', [AdminOrganizerPackageController::class, 'destroy'])->name('packages.destroy');

        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');

        Route::get('/revenue', [AdminRevenueReportController::class, 'index'])->name('revenue.index');

        Route::get('/commission', [AdminCommissionController::class, 'edit'])->name('commission.edit');
        Route::post('/commission', [AdminCommissionController::class, 'update'])->name('commission.update');

        Route::get('/payouts', [AdminPayoutController::class, 'index'])->name('payouts.index');
        Route::post('/payouts', [AdminPayoutController::class, 'store'])->name('payouts.store');
        Route::post('/payouts/{payout}/paid', [AdminPayoutController::class, 'markPaid'])->name('payouts.paid');
    });
});
