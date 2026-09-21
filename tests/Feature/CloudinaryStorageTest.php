<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('configures the cloudinary disk in filesystems', function () {
    $diskConfig = config('filesystems.disks.cloudinary');

    expect($diskConfig)->not->toBeNull();
    expect($diskConfig['driver'])->toBe('cloudinary');
    expect(config('cloudinary'))->toBeArray();
});

it('can store and retrieve images and pdf documents on the cloudinary disk', function () {
    Storage::fake('cloudinary');

    $disk = Storage::disk('cloudinary');

    // 1. Image storage (courses / user photos)
    $imageFile = UploadedFile::fake()->image('course-cover.jpg', 600, 400);
    $imagePath = $disk->putFile('courses', $imageFile);

    expect($imagePath)->not->toBeFalse();
    $disk->assertExists($imagePath);

    // 2. PDF document storage (certificates)
    $pdfFile = UploadedFile::fake()->create('certificate-sample.pdf', 150, 'application/pdf');
    $pdfPath = $disk->putFileAs('certificates', $pdfFile, 'CERT-2026-TEST.pdf');

    expect($pdfPath)->toBe('certificates/CERT-2026-TEST.pdf');
    $disk->assertExists('certificates/CERT-2026-TEST.pdf');
});
