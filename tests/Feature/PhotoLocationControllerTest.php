<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PhotoLocationControllerTest extends TestCase
{
    public function test_the_form_renders(): void
    {
        $response = $this->get(route('photo-location.create'));

        $response->assertOk();
    }

    public function test_a_jpeg_without_gps_metadata_reports_no_location_found(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'geoscan-test-').'.jpg';
        $image = imagecreatetruecolor(10, 10);
        imagejpeg($image, $path);
        imagedestroy($image);

        $response = $this->post(route('photo-location.store'), [
            'photo' => new UploadedFile($path, 'plain.jpg', 'image/jpeg', null, true),
        ]);

        $response->assertOk();
        $response->assertSeeText('Aucune coordonnée GPS trouvée');

        unlink($path);
    }

    public function test_a_non_image_file_is_rejected_by_validation(): void
    {
        $response = $this->post(route('photo-location.store'), [
            'photo' => UploadedFile::fake()->create('not-a-photo.txt', 10, 'text/plain'),
        ]);

        $response->assertSessionHasErrors('photo');
    }
}
