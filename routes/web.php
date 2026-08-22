<?php

use App\Http\Controllers\Web\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Web\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Web\Admin\CityController as AdminCityController;
use App\Http\Controllers\Web\Admin\InvitationDesignController as AdminInvitationDesignController;
use App\Http\Controllers\Web\Admin\CommissionController as AdminCommissionController;
use App\Http\Controllers\Web\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Web\Admin\InviteeController as AdminInviteeController;
use App\Http\Controllers\Web\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Web\Admin\EventController as AdminEventController;
use App\Http\Controllers\Web\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Web\Admin\OrganizerController as AdminOrganizerController;
use App\Http\Controllers\Web\Admin\OrganizerPackageController as AdminOrganizerPackageController;
use App\Http\Controllers\Web\Admin\PayoutController as AdminPayoutController;
use App\Http\Controllers\Web\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Web\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Web\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Web\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Web\Admin\RevenueReportController as AdminRevenueReportController;
use App\Http\Controllers\Web\AuthController as CustomerAuthController;
use App\Http\Controllers\Web\CheckoutController;
use App\Http\Controllers\Web\EventController;
use App\Http\Controllers\Web\LocaleController;
use App\Http\Controllers\Web\Organizer\AuthController as OrganizerAuthController;
use App\Http\Controllers\Web\Organizer\ApplicationController as OrganizerApplicationController;
use App\Http\Controllers\Web\Organizer\DashboardController as OrganizerDashboardController;
use App\Http\Controllers\Web\Organizer\EarningsController as OrganizerEarningsController;
use App\Http\Controllers\Web\InvitationController;
use App\Http\Controllers\Web\Organizer\EventController as OrganizerEventController;
use App\Http\Controllers\Web\Organizer\EventInvitationController as OrganizerEventInvitationController;
use App\Http\Controllers\Web\Organizer\NotificationController as OrganizerNotificationController;
use App\Http\Controllers\Web\Organizer\ProfileController as OrganizerProfileController;
use App\Http\Controllers\Web\OrganizerLandingController;
use App\Http\Controllers\Web\CreateTicketLandingController;
use App\Http\Controllers\Web\AccountDeletionController;
use App\Http\Controllers\Web\LegalController;
use App\Http\Controllers\Web\OtpController as WebOtpController;
use App\Http\Controllers\Web\PrivateEventController;
use App\Http\Controllers\Web\PrivateEventInvitationController;
use App\Http\Controllers\Web\Admin\SupportConversationController as AdminSupportConversationController;
use App\Http\Controllers\Web\Admin\SupportFaqController as AdminSupportFaqController;
use App\Http\Controllers\Web\SupportController;
use App\Http\Controllers\Web\TicketController;
use Illuminate\Support\Facades\Route;

Route::get('/locale/{locale}', LocaleController::class)->name('locale.switch');

Route::get('/', [EventController::class, 'home'])->name('home');
Route::get('/account-deletion', AccountDeletionController::class)->name('account-deletion');
Route::get('/privacy', [LegalController::class, 'privacy'])->name('privacy');
Route::get('/terms', [LegalController::class, 'terms'])->name('terms');
Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/{slug}', [EventController::class, 'show'])->name('events.show');
Route::get('/organizers', OrganizerLandingController::class)->name('organizers');
Route::get('/create-ticket', CreateTicketLandingController::class)->name('create-ticket');

Route::get('/events/{slug}/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/events/{slug}/checkout', [CheckoutController::class, 'store'])->middleware('throttle:checkout')->name('checkout.store');
Route::get('/orders/{orderNumber}/confirmation', [CheckoutController::class, 'confirmation'])->name('checkout.confirmation');
Route::get('/orders/{orderNumber}/pending', [CheckoutController::class, 'pending'])->name('checkout.pending');
Route::get('/orders/{orderNumber}/failed', [CheckoutController::class, 'failed'])->name('checkout.failed');

Route::get('/my-tickets', [TicketController::class, 'index'])->middleware('throttle:tickets')->name('tickets.index');
Route::get('/t/{code}', [TicketController::class, 'show'])->middleware('throttle:tickets')->name('tickets.show');
Route::get('/t/{code}/qr', [TicketController::class, 'qrImage'])->middleware('throttle:tickets')->name('tickets.qr');
Route::get('/t/{code}/pdf', [TicketController::class, 'pdf'])->middleware('throttle:tickets')->name('tickets.pdf');
Route::get('/i/{token}', [InvitationController::class, 'show'])->middleware('throttle:tickets')->name('invitations.show');

Route::prefix('support')->name('support.')->middleware('throttle:support')->group(function () {
    Route::get('/faqs', [SupportController::class, 'faqs'])->name('faqs');
    Route::post('/conversation', [SupportController::class, 'conversation'])->name('conversation');
    Route::get('/conversations/{conversation}/messages', [SupportController::class, 'messages'])->name('messages');
    Route::post('/conversations/{conversation}/messages', [SupportController::class, 'storeMessage'])->name('messages.store');
});

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
    Route::post('/invitation-preview', [PrivateEventController::class, 'invitationPreview'])->name('invitation-preview');
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
        Route::get('/notifications', [OrganizerNotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/{notification}', [OrganizerNotificationController::class, 'open'])->name('notifications.open');
        Route::post('/notifications/read-all', [OrganizerNotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::get('/profile', [OrganizerProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [OrganizerProfileController::class, 'update'])->name('profile.update');
        Route::get('/earnings', OrganizerEarningsController::class)->name('earnings');
        Route::get('/application', [OrganizerApplicationController::class, 'edit'])->name('application.edit');
        Route::post('/application', [OrganizerApplicationController::class, 'update'])->name('application.update');

        Route::middleware('organizer.approved')->group(function () {
            Route::get('/events', [OrganizerEventController::class, 'index'])->name('events.index');
            Route::get('/events/create', [OrganizerEventController::class, 'create'])->name('events.create');
            Route::post('/events', [OrganizerEventController::class, 'store'])->name('events.store');
            Route::get('/events/{event}/edit', [OrganizerEventController::class, 'edit'])->name('events.edit');
            Route::put('/events/{event}', [OrganizerEventController::class, 'update'])->name('events.update');
            Route::get('/events/{event}/pay', [OrganizerEventController::class, 'payForm'])->name('events.pay');
            Route::post('/events/{event}/pay', [OrganizerEventController::class, 'pay'])->middleware('throttle:checkout')->name('events.pay.store');
            Route::get('/events/{event}/invitations', [OrganizerEventInvitationController::class, 'index'])->name('events.invitations.index');
            Route::get('/events/{event}/invitations/create', [OrganizerEventInvitationController::class, 'create'])->name('events.invitations.create');
            Route::post('/events/{event}/invitations', [OrganizerEventInvitationController::class, 'store'])->name('events.invitations.store');
            Route::post('/events/{event}/invitations/flush', [OrganizerEventInvitationController::class, 'flush'])->name('events.invitations.flush');
            Route::post('/events/{event}/invitations/{invitation}/resend', [OrganizerEventInvitationController::class, 'resend'])->name('events.invitations.resend');
            Route::post('/events/{event}/invitations/{invitation}/revoke', [OrganizerEventInvitationController::class, 'revoke'])->name('events.invitations.revoke');
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
        Route::get('/notifications', [AdminNotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/{notification}', [AdminNotificationController::class, 'open'])->name('notifications.open');
        Route::post('/notifications/read-all', [AdminNotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::get('/settings', [AdminSettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [AdminSettingController::class, 'update'])->name('settings.update');
        Route::get('/profile', [AdminProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [AdminProfileController::class, 'update'])->name('profile.update');

        Route::get('/organizers', [AdminOrganizerController::class, 'index'])->name('organizers.index');
        Route::get('/organizers/{organizer}', [AdminOrganizerController::class, 'show'])->name('organizers.show');
        Route::post('/organizers/{organizer}/approve', [AdminOrganizerController::class, 'approve'])->name('organizers.approve');
        Route::post('/organizers/{organizer}/reject', [AdminOrganizerController::class, 'reject'])->name('organizers.reject');
        Route::post('/organizers/{organizer}/package', [AdminOrganizerController::class, 'updatePackage'])->name('organizers.package');
        Route::post('/organizers/{organizer}/commission', [AdminOrganizerController::class, 'updateCommission'])->name('organizers.commission');

        Route::get('/customers', [AdminCustomerController::class, 'index'])->name('customers.index');
        Route::get('/invitees', [AdminInviteeController::class, 'index'])->name('invitees.index');

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
        Route::get('/invitation-designs/{invitationDesign}/preview', [AdminInvitationDesignController::class, 'preview'])->name('invitation-designs.preview');
        Route::put('/invitation-designs/{invitationDesign}', [AdminInvitationDesignController::class, 'update'])->name('invitation-designs.update');
        Route::post('/invitation-designs/{invitationDesign}/toggle', [AdminInvitationDesignController::class, 'toggle'])->name('invitation-designs.toggle');
        Route::delete('/invitation-designs/{invitationDesign}', [AdminInvitationDesignController::class, 'destroy'])->name('invitation-designs.destroy');
        Route::post('/invitation-designs/{invitationDesign}/fields', [AdminInvitationDesignController::class, 'storeField'])->name('invitation-designs.fields.store');
        Route::put('/invitation-designs/{invitationDesign}/fields/{field}', [AdminInvitationDesignController::class, 'updateField'])->name('invitation-designs.fields.update');
        Route::delete('/invitation-designs/{invitationDesign}/fields/{field}', [AdminInvitationDesignController::class, 'destroyField'])->name('invitation-designs.fields.destroy');

        Route::get('/packages', [AdminOrganizerPackageController::class, 'index'])->name('packages.index');
        Route::post('/packages', [AdminOrganizerPackageController::class, 'store'])->name('packages.store');
        Route::post('/packages/front-visibility', [AdminOrganizerPackageController::class, 'updateFrontVisibility'])->name('packages.front-visibility');
        Route::put('/packages/{package}', [AdminOrganizerPackageController::class, 'update'])->name('packages.update');
        Route::post('/packages/{package}/toggle', [AdminOrganizerPackageController::class, 'toggle'])->name('packages.toggle');
        Route::delete('/packages/{package}', [AdminOrganizerPackageController::class, 'destroy'])->name('packages.destroy');

        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments.index');
        Route::get('/payments/{payment}', [AdminPaymentController::class, 'show'])->name('payments.show');

        Route::get('/revenue', [AdminRevenueReportController::class, 'index'])->name('revenue.index');

        Route::get('/commission', [AdminCommissionController::class, 'edit'])->name('commission.edit');
        Route::post('/commission', [AdminCommissionController::class, 'update'])->name('commission.update');

        Route::get('/payouts', [AdminPayoutController::class, 'index'])->name('payouts.index');
        Route::post('/payouts', [AdminPayoutController::class, 'store'])->name('payouts.store');
        Route::post('/payouts/{payout}/paid', [AdminPayoutController::class, 'markPaid'])->name('payouts.paid');

        Route::get('/support/faqs', [AdminSupportFaqController::class, 'index'])->name('support.faqs.index');
        Route::post('/support/faqs', [AdminSupportFaqController::class, 'store'])->name('support.faqs.store');
        Route::put('/support/faqs/{faq}', [AdminSupportFaqController::class, 'update'])->name('support.faqs.update');
        Route::post('/support/faqs/{faq}/toggle', [AdminSupportFaqController::class, 'toggle'])->name('support.faqs.toggle');
        Route::delete('/support/faqs/{faq}', [AdminSupportFaqController::class, 'destroy'])->name('support.faqs.destroy');

        Route::get('/support/conversations', [AdminSupportConversationController::class, 'index'])->name('support.conversations.index');
        Route::get('/support/conversations/{conversation}', [AdminSupportConversationController::class, 'show'])->name('support.conversations.show');
        Route::post('/support/conversations/{conversation}/reply', [AdminSupportConversationController::class, 'reply'])->name('support.conversations.reply');
        Route::post('/support/conversations/{conversation}/close', [AdminSupportConversationController::class, 'close'])->name('support.conversations.close');
        Route::post('/support/conversations/{conversation}/reopen', [AdminSupportConversationController::class, 'reopen'])->name('support.conversations.reopen');
    });
});
