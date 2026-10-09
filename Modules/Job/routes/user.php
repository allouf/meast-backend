<?php

use Illuminate\Support\Facades\Route;
use Modules\Job\Http\Controllers\User\JobApplicationController;
use Modules\Job\Http\Controllers\User\JobCategoryController;
use Modules\Job\Http\Controllers\User\JobController;
use Modules\Job\Http\Controllers\User\JobTemplateController;
use Modules\Job\Http\Controllers\User\ProfileSectionController;
use Modules\Job\Http\Controllers\User\UserCertificationController;
use Modules\Job\Http\Controllers\User\UserEducationController;
use Modules\Job\Http\Controllers\User\UserLanguageController;
use Modules\Job\Http\Controllers\User\UserLinkController;
use Modules\Job\Http\Controllers\User\UserProjectController;
use Modules\Job\Http\Controllers\User\UserSkillController;
use Modules\Job\Http\Controllers\User\CompanyProfileController;
use Modules\Job\Http\Controllers\User\JobProfileController;
use Modules\Job\Http\Controllers\User\UserWorkExperienceController;

// Public job postings (user)
Route::controller(JobController::class)->prefix('job-postings')->group(function ($route) {
    $route->get('', 'index');
    $route->get('details/{id}', 'show');
});

// Profile sections + job categories: both company (employer) and consumer (candidate)
Route::middleware(['auth:api', 'validate.device', 'profile.complete', 'role:company|consumer'])->group(function ($route) {
    Route::controller(ProfileSectionController::class)->prefix('job-postings')->group(function ($route) {
        $route->get('profile-sections', 'index');
    });
    Route::controller(JobCategoryController::class)->prefix('job-categories')->group(function ($route) {
        $route->get('', 'index');
    });
});

// Authenticated – employer: templates, postings, applications
Route::middleware(['auth:api', 'validate.device', 'profile.complete','role:company'])->group(function ($route) {
    Route::controller(JobTemplateController::class)->prefix('job-templates')->group(function ($route) {
        $route->get('my-templates', 'myTemplates');
        $route->get('system-templates', 'systemTemplates');
        $route->get('by-category/{categoryId}', 'availableByCategory');
        $route->get('show/{id}', 'show');
        $route->post('', 'store');
        $route->post('update/{id}', 'update');
        $route->delete('delete/{id}', 'destroy');
        $route->post('clone-from-system/{id}', 'cloneFromSystem');
    });
    Route::controller(JobController::class)->prefix('job-postings')->group(function ($route) {
        $route->get('my-jobs', 'myJobs')->middleware('job.profile.company');
        $route->get('candidates/kanban/{id}', 'getCandidatesKanban');
        $route->get('candidates/{id}', 'getCandidates');
        $route->get('candidate-statistics/{id}', 'getCandidateStatistics');
        $route->get('statistics', 'statistics');
        $route->get('{id}', 'show');
        $route->post('', 'store')->middleware('job.profile.company');
        $route->post('update/{id}', 'update')->middleware('job.profile.company');
        $route->delete('delete/{id}', 'destroy');
        $route->post('update-status/{id}', 'updateStatus');

    });
    Route::controller(JobApplicationController::class)->prefix('job-applications')->group(function ($route) {

        $route->get('show/{id}', 'showApplication');
        $route->post('move-stage', 'moveStageBulk');
        $route->get('employer-tags', 'getEmployerTags');
        $route->post('employer-tags', 'createEmployerTag');
        $route->get('attachments/{attachmentId}/download', 'downloadAttachment');
        $route->delete('attachments/{attachmentId}', 'deleteAttachment');
        $route->patch('move-stage', 'moveStage');
        $route->patch('rating', 'setRating');
        $route->post('add-note', 'addNote');
        $route->post('attach-tag', 'attachTag');
        $route->post('update-employer-tags/{id}', 'updateEmployerTag');
        $route->delete('detach-tag', 'detachTag');
        $route->post('upload-attachment', 'uploadAttachment');
        $route->delete('delete/{id}', 'deleteApplication');
    });
});
Route::controller(CompanyProfileController::class)->prefix('job-postings/companies')->group(function ($r) {
    $r->get('profile/{id}', 'viewCompanyByUserId');
    $r->get('profile/{id}/jobs', 'jobsByCompany');
});

// Job candidate profile (role:consumer) – work experiences, educations, etc.
Route::middleware(['auth:api', 'validate.device', 'profile.complete', 'role:consumer'])->group(function ($r) {
    Route::controller(JobApplicationController::class)->prefix('job-applications')->group(function ($r) {
        $r->post('apply', 'store')->middleware('job.profile.candidate');
        $r->get('get-by-candidate', 'myApplications');
        $r->get('get-by-candidate/show/{id}', 'showMyApplication');
    });
    Route::controller(UserWorkExperienceController::class)->prefix('work-experiences')->group(function ($r) {
        $r->get('', 'getMyExperiences');
        $r->post('add', 'addExperience');
        $r->put('update/{id}', 'updateExperience');
        $r->delete('remove/{id}', 'removeExperience');
    });
    Route::controller(UserEducationController::class)->prefix('educations')->group(function ($r) {
        $r->get('', 'getMyEducations');
        $r->post('add', 'addEducation');
        $r->put('update/{id}', 'updateEducation');
        $r->delete('remove/{id}', 'removeEducation');
    });
    Route::controller(UserLanguageController::class)->prefix('languages')->group(function ($r) {
        $r->get('', 'getMyLanguages');
        $r->post('add', 'addLanguage');
        $r->put('update/{id}', 'updateLanguage');
        $r->delete('remove/{id}', 'removeLanguage');
    });
    Route::controller(UserSkillController::class)->prefix('skills')->group(function ($r) {
        $r->get('', 'getMySkills');
        $r->get('available', 'getAvailableSkills');
        $r->post('add', 'addSkill');
        $r->put('update', 'updateSkill');
        $r->delete('remove/{id}', 'removeSkill');
    });

    Route::controller(UserCertificationController::class)->prefix('certifications')->group(function ($r) {
        $r->get('', 'getMyCertifications');
        $r->post('add', 'addCertification');
        $r->put('update/{id}', 'updateCertification');
        $r->delete('remove/{id}', 'removeCertification');
    });
    Route::controller(UserProjectController::class)->prefix('projects')->group(function ($r) {
        $r->get('', 'getMyProjects');
        $r->post('add', 'addProject');
        $r->put('update/{id}', 'updateProject');
        $r->delete('remove/{id}', 'removeProject');
    });
    Route::controller(UserLinkController::class)->prefix('links')->group(function ($r) {
        $r->get('', 'getMyLinks');
        $r->post('add', 'addLink');
        $r->put('update/{id}', 'updateLink');
        $r->delete('remove/{id}', 'removeLink');
    });
    Route::controller(JobProfileController::class)->prefix('job-profile')->group(function ($r) {
        $r->get('show', 'show');
        $r->post('update', 'update');
    });
});
