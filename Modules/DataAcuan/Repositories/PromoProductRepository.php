<?php

namespace Modules\DataAcuan\Repositories;

use Modules\DataAcuan\Entities\Product;
use Modules\DataAcuan\Entities\Promo;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrderV2\Entities\SalesOrderDetailV2;
use Modules\SalesOrderV2\Entities\SalesOrderV2;

class PromoProductRepository {

    public function showPromo($promoId)
    {
        return Promo::find($promoId);
    }

    public function storeOrUpdate($data, $promoId)
    {
        $promo = Promo::find($promoId);
        $promo->update([
            'attributes' => json_encode($data)
        ]);

        return $promo->refresh();
    }

    public function listProductByPromo($params = [], $promoId)
    {
        $promo = Promo::find($promoId);
        $decodeData =  json_decode($promo->attributes, true);
        $productKeyKist = array_keys($decodeData ?? []);
        $productList = Product::with('package')->whereIn('id', $productKeyKist)
        ->when(isset($params['name']), function($q) use($params){
            $q->where('name','like',"%$params[name]%");
        })
        ->get()
        ->toArray();
        $data = [];

        foreach (($decodeData ?? []) as $key => $row) {
            foreach ($row as $value) {
               foreach (($productList ?? []) as $product) {
                    if($key == $product['id']){
                        $idProduct = $product['id'];
                        $productName = $product['name'].' '.$product['size'];
                        $data[] = array_merge([
                            'product_id' => $idProduct,
                            'product_name' => $productName,
                            'product_unit' => $product['unit'],
                            'product_size' => $product['size'],
                            "product_quantity_per_package" => count($product['package'] ?? []) > 0 ? $product['package']['quantity_per_package'] : null,
                            'have_package' => count($product['package'] ?? []) > 0 ? true : false,
                            'package_unit' => count($product['package'] ?? []) > 0 ? $product['package']['packaging'] : null,
                        ], $value);
                    }
                }
            }
        }

        return $data;
    }

    public function delete($promoId, $promoProductId)
    {
        $promo = Promo::find($promoId);
        if ($promo) {
            $data = json_decode($promo->attributes, true);
            foreach ($data as $productId => $entries) {
                foreach ($entries as $key => $entry) {
                    if ($entry['id'] == $promoProductId) {
                        unset($data[$productId][$key]); // Remove the entry from the array
                        if (empty($data[$productId])) {
                            unset($data[$productId]); // If the parent array is empty, remove it as well
                        }
                    }
                }
            }

            $promo->update(['attributes' => json_encode($data)]);
        }
    }

    public function listPromoByProduct($params = [])
    {
        if (empty($params['product_id'])) {
            return [];
        }

        return Promo::where(function($q) use($params){
            foreach ($params['product_id'] as $key => $value) {
                $q->orWhere('attributes', 'like','%'.$value.'%');
            }
        })->when(isset($params['name']), function($q) use($params){
            return $q->where('name', $params['name']);
        })->get()->map(function($q){
            return [
                'id' => $q->id,
                'name' => $q->name,
                'is_active' => date('Y-m-d') >= $q->date_start && date('Y-m-d') <= $q->date_end ? 1 : 0
            ];
        });
    }

    public function saveAttributeProduct($data = [], $promoId)
    {
        $promo = Promo::findOrFail($promoId);
        $promo->update([
            'attributes' => json_encode($data['attributes'])
        ]);
        
        return $promo->refresh();
    }

    public function stoppedPromo($promoId)
    {
        $promo = Promo::findOrFail($promoId);
        $promo->update([
            'date_end' => date('Y-m-d')
        ]);
        
        return $promo->refresh();
    }

    public function listPromoByOrder($params = [], $salesOrderId)
    {
        $salesOrder = SalesOrderDetailV2::where('sales_order_id', $salesOrderId)->groupBy('product_id')->get()->pluck('product_id')->toArray();

        return Promo::where(function($q) use($salesOrder){
            foreach ($salesOrder as $key => $value) {
                $q->orWhere('attributes', 'like','%'.$value.'%');
            }
        })->when(isset($params['name']), function($q) use($params){
            return $q->where('name', $params['name']);
        })->get()->map(function($q) use($salesOrder){
            $attributes = json_decode($q->attributes, true);
            foreach ($attributes as $key => $value) {
                if (!in_array($key, $salesOrder)) {
                    unset($attributes[$key]);
                }
            }
            return [
                'id' => $q->id,
                'name' => $q->name,
                'is_active' => date('Y-m-d') >= $q->date_start && date('Y-m-d') <= $q->date_end ? 1 : 0,
                "date_start" => $q->date_start,
                "date_end" => $q->date_end,
                'attributes' => $attributes
            ];
        });
    }

    public function listSimplePromo($params = [])
    {
        return Promo::orderBy('date_start', 'desc')
        ->whereNotNull('attributes')
        ->when(isset($params['name']), function($q) use($params){
            return $q->where('name', $params['name']);
        })->when(isset($params['promo_date']), function($q) use($params){
            return $q->where('date_end','>=', date('Y-m-d', strtotime($params['promo_date'])))
            ->where('date_start','<=', date('Y-m-d', strtotime($params['promo_date'])));
        })->when(isset($params['promo_status']) && !isset($params['promo_date']), function($q) use($params){
            switch ($params['promo_status']) {
                case 0:
                    $q->where('date_end','>=', date('Y-m-d'))->where('date_start','<=', date('Y-m-d'));
                    break;

                case 1:
                    $q->where('date_end','<=', date('Y-m-d'))->where('date_start','>=', date('Y-m-d'));
                        break;
                
                default:
                    break;
            }
        })->get()->map(function($q){
            $attributes = json_decode($q->attributes, true);
            return [
                'id' => $q->id,
                'name' => $q->name,
                "date_start" => $q->date_start,
                "date_end" => $q->date_end,
            ];
        });
    }
}