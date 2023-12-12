<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Shop;
use Illuminate\Support\Str;

class CreateShop extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shop:create {shop}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install a new shop that is allowed to use the image upload API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $shop_arg = $this->argument('shop');

        if (!Str::endsWith($shop_arg, '.myshopify.com')) {
            $this->error("Invalid \"shop\" parameter. The string must end with .myshopify.com");
            return;
        }

        $shop = Shop::where('shop', $shop_arg)->first();

        if ($shop) {
            $this->error("A record already exists for \"{$shop_arg}\"");
            $this->table(['Shop', 'Secret'], [[$shop->shop, $shop->secret]]);
            return;
        }

        $random_bytes = Str::random(10);
        $timestamp = time();
        $new_secret = md5("{$shop_arg}:{$random_bytes}:{$timestamp}");

        $shop = new Shop([
            'shop' => $shop_arg,
            'secret' => $new_secret
        ]);

        $shop->save();

        $this->info("Shop created successfully!");

        $this->table(['Shop', 'Secret'], [[$shop->shop, $shop->secret]]);

    }
}
