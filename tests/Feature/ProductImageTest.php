<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_upload_view_replace_and_remove_image(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $user->id, 'name' => 'Shop']);
        $product = Product::create(['workspace_id' => $workspace->id, 'name' => 'Card', 'type' => 'single']);

        $this->actingAs($user)->post("/catalog/{$product->id}/image", [
            'image' => UploadedFile::fake()->image('front.jpg'),
        ])->assertRedirect(route('catalog.edit', $product));

        $firstPath = $product->fresh()->image_path;
        Storage::disk('local')->assertExists($firstPath);
        $this->get("/catalog/{$product->id}/image")->assertOk();

        $this->post("/catalog/{$product->id}/image", [
            'image' => UploadedFile::fake()->image('new.png'),
        ])->assertRedirect('/catalog');

        Storage::disk('local')->assertMissing($firstPath);
        Storage::disk('local')->assertExists($product->fresh()->image_path);

        $this->delete("/catalog/{$product->id}/image")->assertRedirect('/catalog');
        $this->assertNull($product->fresh()->image_path);
    }

    public function test_other_owner_cannot_view_or_modify_image(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Private']);
        $product = Product::create(['workspace_id' => $workspace->id, 'name' => 'Private', 'type' => 'single']);

        $this->actingAs($other)->get("/catalog/{$product->id}/image")->assertNotFound();
        $this->post("/catalog/{$product->id}/image", [
            'image' => UploadedFile::fake()->image('test.jpg'),
        ])->assertNotFound();
        $this->delete("/catalog/{$product->id}/image")->assertNotFound();
    }

    public function test_non_image_upload_is_rejected_and_demo_cannot_mutate(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $workspace = Workspace::create(['owner_user_id' => $owner->id, 'name' => 'Shop']);
        $product = Product::create(['workspace_id' => $workspace->id, 'name' => 'Card', 'type' => 'single']);

        $this->actingAs($owner)->post("/catalog/{$product->id}/image", [
            'image' => UploadedFile::fake()->create('text.txt', 2, 'text/plain'),
        ])->assertSessionHasErrors('image');

        $workspace->update(['is_demo' => true]);
        $this->post("/catalog/{$product->id}/image", [
            'image' => UploadedFile::fake()->image('front.jpg'),
        ])->assertForbidden();
    }
}
