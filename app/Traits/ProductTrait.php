<?php

namespace App\Traits;

use Modules\DataAcuan\Entities\Package;
use Modules\DataAcuan\Entities\Product;

/**
 *
 */
trait ProductTrait
{
    public function productPackageActiveQuantity($product_id)
    {
        $product = Product::query()
            ->with([
                "package" => function ($QQQ) {
                    return $QQQ->where("isActive", "1");
                },
            ])
            ->where("id", $product_id)
            ->first();

        if ($product) {
            if ($product->package) {
                return $product->package->quantity_per_package;
            }
        }
        return 1;
    }

    public function productPackageOnPurchaseQuantity($product_id, $package_id)
    {
        $product = Product::query()
            ->with([
                "package" => function ($QQQ) use ($package_id) {
                    return $QQQ->where("id", $package_id);
                },
            ])
            ->where("id", $product_id)
            ->first();

        if ($product) {
            if ($product->package) {
                return $product->package->quantity_per_package;
            }
        }

        return 1;
    }

    public function productPackageActive($product_id)
    {
        $product = Product::query()
            ->with([
                "package" => function ($QQQ) {
                    return $QQQ->where("isActive", "1");
                },
            ])
            ->where("id", $product_id)
            ->first();

        if ($product) {
            if ($product->package) {
                return $product->package;
            }
        }

        return 0;
    }

    public function packageAccordingRequest($product_id, $package_id)
    {
        $package = Package::query()
            ->where("product_id", $product_id)
            ->findOrFail($package_id);
        return $package;
    }
}
