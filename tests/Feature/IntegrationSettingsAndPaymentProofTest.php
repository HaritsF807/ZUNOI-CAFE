<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Setting;
use App\Models\Table;
use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IntegrationSettingsAndPaymentProofTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that integration setup page requires authentication.
     */
    public function test_integration_setup_page_requires_auth(): void
    {
        $response = $this->get(route('integration.setup'));
        $response->assertRedirect(route('login'));
    }

    /**
     * Test that owner can view integration settings.
     */
    public function test_owner_can_view_integration_settings(): void
    {
        $owner = User::create([
            'name' => 'Owner',
            'email' => 'owner@zunoi.id',
            'password' => bcrypt('password'),
            'role' => 'owner',
        ]);

        Setting::create(['key' => 'qris_manual_url', 'value' => 'http://example.com/qris.png']);
        Setting::create(['key' => 'fonnte_token', 'value' => 'custom-token']);
        Setting::create(['key' => 'tokopay_merchant_id', 'value' => 'custom-merchant']);
        Setting::create(['key' => 'tokopay_secret', 'value' => 'custom-secret']);

        $response = $this->actingAs($owner)->get(route('integration.setup'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('IntegrationSetup')
            ->has('settings.qris_manual_url')
            ->where('settings.fonnte_token', 'custom-token')
        );
    }

    /**
     * Test that owner can update settings.
     */
    public function test_owner_can_update_settings(): void
    {
        $owner = User::create([
            'name' => 'Owner',
            'email' => 'owner@zunoi.id',
            'password' => bcrypt('password'),
            'role' => 'owner',
        ]);

        $response = $this->actingAs($owner)->post(route('settings.update'), [
            'qris_manual_url' => 'https://example.com/new-qris.png',
            'fonnte_token' => 'new-token',
            'tokopay_merchant_id' => 'new-merchant',
            'tokopay_secret' => 'new-secret',
        ]);

        $response->assertRedirect();
        
        $this->assertEquals('https://example.com/new-qris.png', Setting::getValue('qris_manual_url'));
        $this->assertEquals('new-token', Setting::getValue('fonnte_token'));
    }

    /**
     * Test that ordering with cashier or tokopay works without payment proof.
     */
    public function test_order_without_proof_for_cashier_payment(): void
    {
        $table = Table::create([
            'table_name' => 'Meja 1',
            'secure_token' => 'testtoken'
        ]);

        $category = Category::create(['name' => 'Kopi', 'slug' => 'kopi']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Kopi Latte',
            'price' => 15000,
            'image' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=400&q=80',
            'is_available' => true
        ]);

        $response = $this->withSession(['active_table_id' => $table->id])
            ->post(route('order.store'), [
                'customer_name' => 'Budi',
                'customer_phone' => '0812345678',
                'order_type' => 'dine_in',
                'payment_method' => 'cashier',
                'cart_items' => [
                    ['id' => $product->id, 'name' => 'Kopi', 'price' => 15000, 'quantity' => 1]
                ]
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Budi',
            'payment_method' => 'cashier',
            'payment_proof' => null
        ]);
    }

    /**
     * Test that manual QRIS ordering requires payment proof upload.
     */
    public function test_manual_qris_requires_payment_proof(): void
    {
        $table = Table::create([
            'table_name' => 'Meja 1',
            'secure_token' => 'testtoken'
        ]);

        $category = Category::create(['name' => 'Kopi', 'slug' => 'kopi']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Kopi Latte',
            'price' => 15000,
            'image' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=400&q=80',
            'is_available' => true
        ]);

        $response = $this->withSession(['active_table_id' => $table->id])
            ->post(route('order.store'), [
                'customer_name' => 'Budi',
                'customer_phone' => '0812345678',
                'order_type' => 'dine_in',
                'payment_method' => 'qris_manual',
                'cart_items' => [
                    ['id' => $product->id, 'name' => 'Kopi', 'price' => 15000, 'quantity' => 1]
                ]
            ]);

        $response->assertSessionHasErrors('payment_proof');
    }

    /**
     * Test manual QRIS upload and store success.
     */
    public function test_manual_qris_ordering_success_with_proof_upload(): void
    {
        $table = Table::create([
            'table_name' => 'Meja 1',
            'secure_token' => 'testtoken'
        ]);

        $category = Category::create(['name' => 'Kopi', 'slug' => 'kopi']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Kopi Latte',
            'price' => 15000,
            'image' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=400&q=80',
            'is_available' => true
        ]);

        Storage::fake('public');
        $dummyImage = UploadedFile::fake()->image('receipt.jpg');

        $response = $this->withSession(['active_table_id' => $table->id])
            ->post(route('order.store'), [
                'customer_name' => 'Budi',
                'customer_phone' => '0812345678',
                'order_type' => 'dine_in',
                'payment_method' => 'qris_manual',
                'payment_proof' => $dummyImage,
                'cart_items' => [
                    ['id' => $product->id, 'name' => 'Kopi', 'price' => 15000, 'quantity' => 1]
                ]
            ]);

        $response->assertRedirect();
        
        $order = Order::first();
        $this->assertNotNull($order->payment_proof);
        $this->assertStringContainsString('/uploads/proofs/', $order->payment_proof);
    }
}
