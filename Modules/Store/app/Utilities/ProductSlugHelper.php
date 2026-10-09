<?php

namespace Modules\Store\Utilities;

use Modules\Store\Models\Product;
use Modules\Store\Models\ProductVariant;

class ProductSlugHelper
{
   
    public static function generateVariantSku(string $productName, array $optionValues = [], int $variantId = null): string
    {
        
        $englishName = self::transliterateArabic($productName);
        $cleanName = self::cleanString($englishName);
        $prefix = strtoupper(substr($cleanName, 0, 4));
        if (strlen($prefix) < 3) {
            $prefix = 'PROD';
        }
        
        $optionSuffix = '';
        if (!empty($optionValues)) {
            
            $cleanOptionValues = [];
            foreach ($optionValues as $value) {
                $cleanValue = self::cleanString(self::transliterateArabic($value));
                $cleanValue = strtoupper(preg_replace('/[^A-Z0-9]/', '', $cleanValue));
                if (!empty($cleanValue)) {
                    $cleanOptionValues[] = substr($cleanValue, 0, 3); 
                }
            }
            if (!empty($cleanOptionValues)) {
                $optionSuffix = '-' . implode('', $cleanOptionValues);
                
                if (strlen($optionSuffix) > 10) {
                    $optionSuffix = '-' . substr(implode('', $cleanOptionValues), 0, 8);
                }
            }
        }
        
        $variantSuffix = str_pad($variantId ?? 0, 3, '0', STR_PAD_LEFT);
        $baseSku = $prefix . $optionSuffix . '-' . $variantSuffix;
        return self::ensureUniqueVariantSku($baseSku, $variantId);
    }
  
    public static function generateSlug(string $productName, int $productId = null): string
    {
        $englishName = self::transliterateArabic($productName);
        $cleanName = self::cleanString($englishName);
        $slug = strtolower($cleanName);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        if (empty($slug)) {
            $slug = 'product-' . $productId;
        }
        return self::ensureUniqueSlug($slug, $productId);
    }
   
    private static function transliterateArabic(string $text): string
    {
        $arabicToEnglish = [
            'ا' => 'a',
            'أ' => 'a',
            'إ' => 'i',
            'آ' => 'aa',
            'ب' => 'b',
            'ت' => 't',
            'ث' => 'th',
            'ج' => 'j',
            'ح' => 'h',
            'خ' => 'kh',
            'د' => 'd',
            'ذ' => 'dh',
            'ر' => 'r',
            'ز' => 'z',
            'س' => 's',
            'ش' => 'sh',
            'ص' => 's',
            'ض' => 'd',
            'ط' => 't',
            'ظ' => 'z',
            'ع' => 'a',
            'غ' => 'gh',
            'ف' => 'f',
            'ق' => 'q',
            'ك' => 'k',
            'ل' => 'l',
            'م' => 'm',
            'ن' => 'n',
            'ه' => 'h',
            'و' => 'w',
            'ي' => 'y',
            'ى' => 'a',
            'ة' => 'h',
            'ء' => 'a',
            'ؤ' => 'w',
            'ئ' => 'y',
            'لا' => 'la',
            'ال' => 'al',
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9'
        ];
        $transliterated = strtr($text, $arabicToEnglish);
        return $transliterated;
    }
 
    private static function cleanString(string $text): string
    {
        $cleaned = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        $cleaned = trim($cleaned);
        return $cleaned;
    }
  
    private static function ensureUniqueVariantSku(string $baseSku, int $variantId = null): string
    {
        $sku = $baseSku;
        $counter = 1;
        while (self::variantSkuExists($sku, $variantId)) {
            $sku = $baseSku . '-' . $counter;
            $counter++;
        }
        return $sku;
    }
  
    private static function ensureUniqueSlug(string $baseSlug, int $productId = null): string
    {
        $slug = $baseSlug;
        $counter = 1;
        while (self::slugExists($slug, $productId)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        return $slug;
    }
  
    private static function variantSkuExists(string $sku, int $variantId = null): bool
    {
        if (!app()->bound('db')) {
            return false;
        }
        return ProductVariant::where('sku', $sku)
            ->when($variantId, function ($query) use ($variantId) {
                return $query->where('id', '!=', $variantId);
            })
            ->exists();
    }
   
    private static function slugExists(string $slug, int $productId = null): bool
    {
        if (!app()->bound('db')) {
            return false;
        }
        return Product::where('slug', $slug)
            ->when($productId, function ($query) use ($productId) {
                return $query->where('id', '!=', $productId);
            })
            ->exists();
    }
}
