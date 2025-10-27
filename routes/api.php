<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});



use App\Http\Controllers\MailDataController as MailData;

Route::POST('/maildata', [MailData::class, 'receive']);
Route::GET('/processdata/{module}/{status}/{encrypt}', [MailData::class, 'processData']);
Route::POST('/getaccess', [MailData::class, 'getAccess']);

use App\Http\Controllers\PurchaseSelectionController as Selection;
Route::POST('/purchase_selection', [Selection::class, 'Mail']);
Route::GET('/poselection/{status}/{encrypt}', [Selection::class, 'processData']);
Route::POST('/poselection/getaccess', [Selection::class, 'getaccess']);
Route::POST('/pos/getaccess', [Selection::class, 'getaccess']);

use App\Http\Controllers\CbPpuController as CbPPu;
Route::POST('/cbppu', [CbPPu::class, 'Mail']);
Route::GET('/cbppu/{status}/{encrypt}', [CbPPu::class, 'processData']);
Route::POST('/cbppu/getaccess', [CbPPu::class, 'getaccess']);

use App\Http\Controllers\StaffActionController as StaffAction;
Route::POST('/staffaction', [StaffAction::class, 'staffaction']);
Route::POST('/staffaction_por', [StaffAction::class, 'staffaction_por']);
Route::POST('/staffaction_pos', [StaffAction::class, 'staffaction_pos']);
Route::POST('/fileexist', [StaffAction::class, 'fileexist']);
Route::POST('/feedbackland', [StaffAction::class, 'feedback_land']);

use App\Http\Controllers\StaffFeedbackController as StaffFeedback;

Route::POST('/feedback_po', [StaffFeedback::class, 'feedback_po']);
Route::POST('/feedback_cb_fupd', [StaffFeedback::class, 'feedback_cb_fupd']);
Route::POST('/feedback_cb', [StaffFeedback::class, 'feedback_cb']);
Route::POST('/feedback_cm_progress', [StaffFeedback::class, 'feedback_cm_progress']);

use App\Http\Controllers\CbPPuVvipController as CbPPuVvip;
Route::POST('/cbppuvvip', [CbPPuVvip::class, 'Mail']);
Route::GET('/cbppuvvip/{status}/{encrypt}', [CbPPuVvip::class, 'processData']);
Route::POST('/cbppuvvip/getaccess', [CbPPuVvip::class, 'getaccess']);

use App\Http\Controllers\SelController as Select;
Route::get('/select', [Select::class, 'index']);

use App\Http\Controllers\AutoSendController as AutoSend;
Route::get('/autosend', [AutoSend::class, 'index']);

use App\Http\Controllers\AutoSendTestController as AutoSendTest;
Route::get('/autosendtest', [AutoSendTest::class, 'index']);

use App\Http\Controllers\AutoFeedbackController as AutoFeedback;
Route::get('/autofeedback', [AutoFeedback::class, 'index']);

use App\Http\Controllers\CheckFeedbackController as CheckFeedback;
Route::get('/checkfeedback', [CheckFeedback::class, 'index']);

use App\Http\Controllers\OldFeedbackController as OldFeedback;
Route::get('/oldfeedback', [OldFeedback::class, 'index']);

use App\Http\Controllers\CmDoneController as CmDone;
Route::POST('/cmdone', [CmDone::class, 'Mail']);
Route::GET('/cmdone/{status}/{encrypt}', [CmDone::class, 'processData']);
Route::POST('/cmdone/getaccess', [CmDone::class, 'update']);

use App\Http\Controllers\CmProgressController as CmProgress;
Route::POST('/cmprogress', [CmProgress::class, 'Mail']);
Route::GET('/cmprogress/{status}/{encrypt}', [CmProgress::class, 'processData']);
Route::POST('/cmprogress/getaccess', [CmProgress::class, 'update']);

use App\Http\Controllers\CmProgresswuController as CmProgresswu;
Route::POST('/cmprogresswu', [CmProgresswu::class, 'Mail']);
Route::GET('/cmprogresswu/{status}/{encrypt}', [CmProgresswu::class, 'processData']);
Route::POST('/cmprogresswu/getaccess', [CmProgresswu::class, 'update']);

use App\Http\Controllers\CmEntryController as CmEntry;
Route::POST('/cmentry', [CmEntry::class, 'Mail']);
Route::GET('/cmentry/{status}/{encrypt}', [CmEntry::class, 'processData']);
Route::POST('/cmentry/getaccess', [CmEntry::class, 'update']);

use App\Http\Controllers\CmCloseController as CmClose;
Route::POST('/cmclose', [CmClose::class, 'Mail']);
Route::GET('/cmclose/{status}/{encrypt}', [CmClose::class, 'processData']);
Route::POST('/cmclose/getaccess', [CmClose::class, 'update']);

use App\Http\Controllers\VarianOrderController as VarianOrder;
Route::POST('/varianorder', [VarianOrder::class, 'Mail']);
Route::GET('/varianorder/{status}/{encrypt}', [VarianOrder::class, 'processData']);
Route::POST('/varianorder/getaccess', [VarianOrder::class, 'update']);

use App\Http\Controllers\ContractRenewController as ContractRenew;
Route::POST('/contractrenew', [ContractRenew::class, 'Mail']);
Route::GET('/contractrenew/{status}/{encrypt}', [ContractRenew::class, 'processData']);
Route::POST('/contractrenew/getaccess', [ContractRenew::class, 'update']);

use App\Http\Controllers\PLBudgetLymanController as PLBudgetLyman;
Route::POST('/budgetlyman', [PLBudgetLyman::class, 'Mail']);
Route::GET('/budgetlyman/{status}/{encrypt}', [PLBudgetLyman::class, 'processData']);
Route::POST('/budgetlyman/getaccess', [PLBudgetLyman::class, 'update']);

use App\Http\Controllers\PLBudgetRevisionController as PLBudgetRevision;
Route::POST('/budgetrevision', [PLBudgetRevision::class, 'Mail']);
Route::GET('/budgetrevision/{status}/{encrypt}', [PLBudgetRevision::class, 'processData']);
Route::POST('/budgetrevision/getaccess', [PLBudgetRevision::class, 'update']);

use App\Http\Controllers\FeedbackPLController as FeedbackPL;
Route::POST('/feedbackbudgetlyman', [FeedbackPL::class, 'feedbackbudgetlyman']);
Route::POST('/feedbackbudgetrevision', [FeedbackPL::class, 'feedbackbudgetrevision']);

use App\Http\Controllers\LandSubmissionController as LandSubmission;

Route::POST('/landsubmission', [LandSubmission::class, 'mail']);
Route::POST('/landsubmission/update', [LandSubmission::class, 'update']);
Route::GET('/landsubmission/{status}/{encrypt}', [LandSubmission::class, 'processData']);

use App\Http\Controllers\ConnectController as Connect;
Route::GET('/connect', [Connect::class, 'index']);
Route::GET('/info', [Connect::class, 'info']);

use App\Http\Controllers\LandfphController as Landfph;
Route::POST('/landfph', [Landfph::class, 'index']);
Route::GET('/landfph/{status}/{encrypt}', [Landfph::class, 'processData']);
Route::POST('/landfph/getaccess', [Landfph::class, 'getaccess']);
Route::POST('/landfph/feedback', [Landfph::class, 'feedback_fph']);

use App\Http\Controllers\LandVerificationController as LandVerification;
Route::POST('/landverification', [LandVerification::class, 'index']);
Route::GET('/landverification/{status}/{encrypt}', [LandVerification::class, 'processData']);
Route::POST('/landverification/getaccess', [LandVerification::class, 'getaccess']);
Route::POST('/landverification/feedback', [LandVerification::class, 'feedback_verification']);

use App\Http\Controllers\LandMeasuringController as LandMeasuring;
Route::POST('/landmeasuring', [LandMeasuring::class, 'index']);
Route::GET('/landmeasuring/{status}/{encrypt}', [LandMeasuring::class, 'processData']);
Route::POST('/landmeasuring/getaccess', [LandMeasuring::class, 'getaccess']);

use App\Http\Controllers\LandSphController as LandSph;
Route::POST('/landsph', [LandSph::class, 'index']);
Route::GET('/landsph/{status}/{encrypt}', [LandSph::class, 'processData']);
Route::POST('/landsph/getaccess', [LandSph::class, 'getaccess']);

use App\Http\Controllers\LandMapController as LandMap;
Route::POST('/landmap', [LandMap::class, 'index']);
Route::GET('/landmap/{status}/{encrypt}', [LandMap::class, 'processData']);
Route::POST('/landmap/getaccess', [LandMap::class, 'getaccess']);

use App\Http\Controllers\LandChangeNameController as LandChangeName;
Route::POST('/landchangename', [LandChangeName::class, 'index']);
Route::GET('/landchangename/{status}/{encrypt}', [LandChangeName::class, 'processData']);
Route::POST('/landchangename/getaccess', [LandChangeName::class, 'getaccess']);

use App\Http\Controllers\LandVerificationPaymentController as LandVerificationPayment;
Route::POST('/landverificationpayment', [LandVerificationPayment::class, 'index']);
Route::GET('/landverificationpayment/{status}/{encrypt}', [LandVerificationPayment::class, 'processData']);
Route::POST('/landverificationpayment/getaccess', [LandVerificationPayment::class, 'getaccess']);

use App\Http\Controllers\LandMeasuringSftController as LandMeasuringSft;
Route::POST('/landmeasuringsft', [LandMeasuringSft::class, 'index']);
Route::GET('/landmeasuringsft/{status}/{encrypt}', [LandMeasuringSft::class, 'processData']);
Route::POST('/landmeasuringsft/getaccess', [LandMeasuringSft::class, 'getaccess']);

use App\Http\Controllers\LandBoundaryController as LandBoundary;
Route::POST('/landboundary', [LandBoundary::class, 'index']);
Route::GET('/landboundary/{status}/{encrypt}', [LandBoundary::class, 'processData']);
Route::POST('/landboundary/getaccess', [LandBoundary::class, 'getaccess']);

use App\Http\Controllers\LandSftProposeController as LandSftPropose;
Route::POST('/landsftpropose', [LandSftPropose::class, 'index']);
Route::GET('/landsftpropose/{status}/{encrypt}', [LandSftPropose::class, 'processData']);
Route::POST('/landsftpropose/getaccess', [LandSftPropose::class, 'getaccess']);

use App\Http\Controllers\LandSftBphtbController as LandSftBphtb;
Route::POST('/landsftbphtb', [LandSftBphtb::class, 'index']);
Route::GET('/landsftbphtb/{status}/{encrypt}', [LandSftBphtb::class, 'processData']);
Route::POST('/landsftbphtb/getaccess', [LandSftBphtb::class, 'getaccess']);

use App\Http\Controllers\LandSftShgbController as LandSftShgb;
Route::POST('/landsftshgb', [LandSftShgb::class, 'index']);
Route::GET('/landsftshgb/{status}/{encrypt}', [LandSftShgb::class, 'processData']);
Route::POST('/landsftshgb/getaccess', [LandSftShgb::class, 'getaccess']);

use App\Http\Controllers\LandHandoverShgbController as LandHandoverShgb;
Route::POST('/landhandovershgb', [LandHandoverShgb::class, 'index']);
Route::GET('/landhandovershgb/{status}/{encrypt}', [LandHandoverShgb::class, 'processData']);
Route::POST('/landhandovershgb/getaccess', [LandHandoverShgb::class, 'getaccess']);

use App\Http\Controllers\LandCancelNopController as LandCancelNop;
Route::POST('/landcancelnop', [LandCancelNop::class, 'index']);
Route::GET('/landcancelnop/{status}/{encrypt}', [LandCancelNop::class, 'processData']);
Route::POST('/landcancelnop/getaccess', [LandCancelNop::class, 'getaccess']);

use App\Http\Controllers\LandHandoverLegalController as LandHandoverLegal;
Route::POST('/landhandoverlegal', [LandHandoverLegal::class, 'index']);
Route::GET('/landhandoverlegal/{status}/{encrypt}', [LandHandoverLegal::class, 'processData']);
Route::POST('/landhandoverlegal/getaccess', [LandHandoverLegal::class, 'getaccess']);

use App\Http\Controllers\LandSplitShgbController as LandSplitShgb;
Route::POST('/landsplitshgb', [LandSplitShgb::class, 'index']);
Route::GET('/landsplitshgb/{status}/{encrypt}', [LandSplitShgb::class, 'processData']);
Route::POST('/landsplitshgb/getaccess', [LandSplitShgb::class, 'getaccess']);

use App\Http\Controllers\LandMergeShgbController as LandMergeShgb;
Route::POST('/landmergeshgb', [LandMergeShgb::class, 'index']);
Route::GET('/landmergeshgb/{status}/{encrypt}', [LandMergeShgb::class, 'processData']);
Route::POST('/landmergeshgb/getaccess', [LandMergeShgb::class, 'getaccess']);

use App\Http\Controllers\LandExtensionShgbController as LandExtensionShgb;
Route::POST('/landextensionshgb', [LandExtensionShgb::class, 'index']);
Route::GET('/landextensionshgb/{status}/{encrypt}', [LandExtensionShgb::class, 'processData']);
Route::POST('/landextensionshgb/getaccess', [LandExtensionShgb::class, 'getaccess']);
